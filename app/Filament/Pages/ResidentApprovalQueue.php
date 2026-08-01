<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\ResidentApprovalRequest;
use App\Services\Community\MembershipService;
use App\Support\Rules\ApprovalRiskRules;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * WEB-02-04 — Duyệt cư dân. Approval queue with working decisions.
 * Approve creates a Resident + apartment relation and writes an audit log.
 *
 * Giai đoạn 3 Community Domain (2026-08-01): đây là điểm DUY NHẤT hiện có
 * trong repo tạo mới `ResidentApartmentRelation` cho một cư dân được kích
 * hoạt lần đầu (khác `ResidentImportProfile`, vốn là nhập hàng loạt từ Excel
 * — cân nhắc wiring riêng nếu cần sau). Sau khi tạo quan hệ, cấp grant vào
 * nhóm cư dân chính thức (`official_resident_group`) của dự án chứa căn hộ đó
 * — nếu dự án chưa có nhóm chính thức nào thì `grantResidentRelation()` no-op
 * (không tạo nhóm mới, ngoài phạm vi service này).
 *
 * **Khoảng trống đã biết**: repo hiện KHÔNG có luồng "kết thúc quan hệ căn hộ"
 * nào (`resident_apartment_relations` không có `end_date`/`status`, không nơi
 * nào xoá hay archive quan hệ) — nên `MembershipService::revokeResidentRelation()`
 * viết sẵn, có test cách ly, nhưng CHƯA có call site sản xuất nào gọi tới. Khi
 * tính năng "chấm dứt hợp đồng/bán nhà" được xây (ngoài phạm vi Giai đoạn 3),
 * đó là nơi phải gọi `revokeResidentRelation()`.
 */
class ResidentApprovalQueue extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Duyệt cư dân';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Duyệt cư dân';

    protected static ?string $slug = 'resident-approvals';

    protected string $view = 'filament.pages.resident-approval-queue';

    public static function getNavigationBadge(): ?string
    {
        $count = ResidentApprovalRequest::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function approve(int $id): void
    {
        $req = ResidentApprovalRequest::findOrFail($id);

        // Rule gate: policy_block chặn duyệt nhanh — chuyển sang Chi tiết để override (bắt buộc lý do).
        if (ApprovalRiskRules::forRequest($req)->isBlocked()) {
            Notification::make()
                ->title('Hồ sơ vi phạm chính sách — không thể duyệt nhanh')
                ->body('Mở Chi tiết để xem cảnh báo; chỉ HQ/SuperAdmin mới override được (kèm lý do).')
                ->danger()->send();

            return;
        }

        $user = auth()->user();

        $resident = Resident::create([
            'building_id' => $user->building_id,
            'code' => 'CD-'.str_pad((string) (Resident::max('id') + 1), 4, '0', STR_PAD_LEFT),
            'full_name' => $req->full_name,
            'phone' => $req->phone,
            'email' => $req->email,
            'status' => 'active',
        ]);

        if ($req->apartment_id) {
            $relation = ResidentApartmentRelation::create([
                'resident_id' => $resident->id,
                'apartment_id' => $req->apartment_id,
                'role' => $req->requested_role,
                'is_primary' => $req->requested_role === 'owner',
                'start_date' => now(),
            ]);

            app(MembershipService::class)->grantResidentRelation($relation);
        }

        $req->update(['status' => 'approved']);
        $this->audit('resident.approve', "Duyệt hồ sơ cư dân: {$req->full_name}");
    }

    public function reject(int $id): void
    {
        ResidentApprovalRequest::whereKey($id)->update(['status' => 'rejected']);
        $this->audit('resident.reject', 'Từ chối hồ sơ cư dân #'.$id);
    }

    public function needMore(int $id): void
    {
        ResidentApprovalRequest::whereKey($id)->update(['status' => 'need_more']);
        $this->audit('resident.need_more', 'Yêu cầu bổ sung hồ sơ #'.$id);
    }

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'action' => $action,
            'description' => $description,
        ]);
    }

    protected function getViewData(): array
    {
        $pending = ResidentApprovalRequest::where('status', 'pending');

        $kpis = [
            ['label' => 'Chờ duyệt', 'value' => (clone $pending)->count(), 'accent' => 'amber'],
            ['label' => 'Độ khớp TB', 'value' => (int) round((clone $pending)->avg('match_score') ?? 0).'%', 'accent' => 'blue'],
            ['label' => 'Đã duyệt', 'value' => ResidentApprovalRequest::where('status', 'approved')->count(), 'accent' => 'green'],
            ['label' => 'Cần bổ sung', 'value' => ResidentApprovalRequest::where('status', 'need_more')->count(), 'accent' => 'red'],
        ];

        $requests = (clone $pending)->with('apartment')->orderByDesc('match_score')->get();

        // Rule-based risk per request (Module 0) — chip + gate ở view.
        $riskById = $requests->mapWithKeys(function (ResidentApprovalRequest $r): array {
            $report = ApprovalRiskRules::forRequest($r);

            return [$r->id => [
                'tone' => $report->tone(),
                'count' => count($report->all()),
                'blocked' => $report->isBlocked(),
                'top' => $report->highestLevel(),
            ]];
        })->all();

        return [
            'kpis' => $kpis,
            'requests' => $requests,
            'riskById' => $riskById,
        ];
    }
}
