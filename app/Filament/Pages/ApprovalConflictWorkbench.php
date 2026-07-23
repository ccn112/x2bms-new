<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\GlobalUserAccount;
use App\Models\ResidentUnitBinding;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * BQL-02-09 — Workbench xung đột (cách A: thuần tính toán, không bảng trạng thái).
 * Phát hiện live 2 loại xung đột từ dữ liệu hiện có: (1) trùng danh tính (nhiều tài khoản
 * cùng duplicate_group_id), (2) căn tranh chấp (>1 chủ sở hữu đang hoạt động). "Ghi nhận
 * xử lý" ghi AuditLog; xung đột tự biến mất khi dữ liệu gốc được sửa (ở màn tương ứng).
 */
class ApprovalConflictWorkbench extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Workbench xung đột';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'Workbench xung đột duyệt';

    protected static ?string $slug = 'approval-conflicts';

    protected string $view = 'filament.pages.approval-conflict-workbench';

    /** @return array<int> */
    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    public function acknowledge(string $type, string $key): void
    {
        $this->audit('conflict.acknowledge', "Ghi nhận xử lý xung đột [{$type}] {$key}");
        Notification::make()->title('Đã ghi nhận (kiểm tra AuditLog)')
            ->body('Xung đột sẽ mất khi dữ liệu gốc được sửa ở màn tương ứng.')->success()->send();
    }

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id, 'building_id' => $user->building_id,
            'user_id' => $user->id, 'actor_name' => $user->name,
            'action' => $action, 'description' => $description,
        ]);
    }

    protected function getViewData(): array
    {
        $bids = $this->buildingIds();
        $conflicts = [];

        // (1) Trùng danh tính: tài khoản gắn căn trong tòa, cùng duplicate_group_id (>1 thành viên).
        $accountIds = ResidentUnitBinding::query()->whereIn('building_id', $bids)->pluck('user_account_id')->unique();
        $dupGroups = GlobalUserAccount::query()
            ->whereIn('id', $accountIds)
            ->whereNotNull('duplicate_group_id')
            ->get()
            ->groupBy('duplicate_group_id')
            ->filter(fn ($g) => $g->count() > 1);

        foreach ($dupGroups as $groupId => $accounts) {
            $conflicts[] = [
                'type' => 'duplicate_account',
                'type_label' => 'Trùng danh tính',
                'tone' => 'red',
                'key' => (string) $groupId,
                'title' => 'Nhóm trùng #'.$groupId.' — '.$accounts->count().' tài khoản',
                'parties' => $accounts->map(fn (GlobalUserAccount $a) => [
                    'name' => $a->full_name ?: ($a->phone ?: '#'.$a->id),
                    'sub' => trim(($a->phone ?? '').' '.($a->email ?? '')),
                    'url' => url('/admin/resident-accounts/'.$a->id.'/detail'),
                ])->all(),
                'action_hint' => 'Mở chi tiết từng tài khoản để gộp/xác minh.',
            ];
        }

        // (2) Căn tranh chấp: >1 chủ sở hữu (owner) đang hoạt động trên cùng căn.
        $ownerRows = ResidentUnitBinding::query()
            ->whereIn('building_id', $bids)
            ->where('role', 'owner')->where('status', 'active')
            ->with(['apartment', 'account'])
            ->get()
            ->groupBy('apartment_id')
            ->filter(fn ($g) => $g->count() > 1);

        foreach ($ownerRows as $apartmentId => $rows) {
            $apt = $rows->first()->apartment;
            $conflicts[] = [
                'type' => 'contested_unit',
                'type_label' => 'Căn tranh chấp chủ sở hữu',
                'tone' => 'red',
                'key' => 'apt:'.$apartmentId,
                'title' => 'Căn '.($apt?->code ?? '#'.$apartmentId).' — '.$rows->count().' chủ sở hữu đang hoạt động',
                'parties' => $rows->map(fn (ResidentUnitBinding $b) => [
                    'name' => $b->account?->full_name ?: ('TK #'.$b->user_account_id),
                    'sub' => 'từ '.($b->starts_at?->format('d/m/Y') ?? '—'),
                    'url' => url('/admin/resident-accounts/'.$b->user_account_id.'/detail'),
                ])->all(),
                'action_hint' => 'Xác minh chuyển nhượng; thu hồi liên kết sai ở màn Gắn căn.',
            ];
        }

        return [
            'conflicts' => $conflicts,
            'kpis' => [
                ['label' => 'Tổng xung đột', 'value' => count($conflicts), 'accent' => count($conflicts) ? 'red' : 'green'],
                ['label' => 'Trùng danh tính', 'value' => $dupGroups->count(), 'accent' => 'amber'],
                ['label' => 'Căn tranh chấp', 'value' => $ownerRows->count(), 'accent' => 'amber'],
            ],
        ];
    }
}
