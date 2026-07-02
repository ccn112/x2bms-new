<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * BQL-00-10 — Cấu hình dự án kế thừa (Inherited Project Settings Preview).
 * Read-only view of the configuration the project inherits from HQ / SuperAdmin,
 * grouped into 6 config domains with inheritance source + override status. Summary,
 * source breakdown and recent updaters are computed from the group set / audit log.
 * Editing lives upstream (HQ/SA); here BQL only previews + can request an override.
 */
class ProjectSettingsPreview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $navigationLabel = 'Cấu hình dự án';

    protected static ?int $navigationSort = 80;

    protected static ?string $title = 'Cấu hình dự án kế thừa';

    protected static ?string $slug = 'project-settings';

    protected string $view = 'filament.pages.project-settings-preview';

    protected function getViewData(): array
    {
        $ctx = app(CurrentContext::class);

        // The 6 inherited configuration domains (source of truth = HQ/SuperAdmin).
        $groups = [
            [
                'icon' => 'squares', 'title' => '1. Module đang bật', 'desc' => 'Các module & tính năng được kích hoạt',
                'source' => 'HQ', 'override' => 'allowed',
                'fields' => [['Cư dân & Căn hộ', 'Bật'], ['Dịch vụ', 'Bật'], ['Phê duyệt', 'Bật'], ['Tài chính', 'Bật'], ['An ninh & Kỹ thuật', 'Bật']],
                'extra' => '+3 module',
            ],
            [
                'icon' => 'cash', 'title' => '2. Chính sách biểu phí', 'desc' => 'Quy định về tính phí & chu kỳ thanh toán',
                'source' => 'SuperAdmin', 'override' => 'allowed',
                'fields' => [['Chu kỳ biểu phí', 'Hàng tháng'], ['Ngày chốt công nợ', 'Cuối tháng'], ['Phương thức thu', 'Tự động & Thủ công'], ['Phí trễ hạn', '2%/tháng'], ['Miễn phí dịch vụ', 'Không']],
            ],
            [
                'icon' => 'doc', 'title' => '3. Biểu mẫu & workflow', 'desc' => 'Mẫu biểu, quy trình phê duyệt & luồng xử lý',
                'source' => 'HQ', 'override' => 'allowed',
                'fields' => [['Quy trình duyệt mặc định', '2 cấp: BQL → Ban Giám đốc'], ['Mẫu biểu', '18 mẫu'], ['Luồng công việc', '5 luồng'], ['Thời hạn xử lý', '2 ngày làm việc'], ['Tự động chuyển bước', 'Bật']],
            ],
            [
                'icon' => 'bell', 'title' => '4. Thông báo & thương hiệu', 'desc' => 'Kênh thông báo & nhận diện thương hiệu',
                'source' => 'SuperAdmin', 'override' => 'overriding',
                'fields' => [['Kênh cho phép', 'App, Email, SMS'], ['Email gửi từ', 'no-reply@x2bms.vn'], ['Mẫu thông báo', 'Mặc định'], ['Logo dự án', 'Kế thừa từ HQ'], ['Màu chủ đạo', '#1E88E5']],
            ],
            [
                'icon' => 'puzzle', 'title' => '5. Tích hợp', 'desc' => 'Các hệ thống & dịch vụ tích hợp',
                'source' => 'HQ', 'override' => 'allowed',
                'fields' => [['Kế toán', 'MISA SME'], ['Hóa đơn điện tử', 'VNPT Invoice'], ['SMS Gateway', 'Viettel SMS'], ['Email Gateway', 'SendGrid'], ['SSO (SSO/AD)', 'Không kết nối']],
            ],
            [
                'icon' => 'shield', 'title' => '6. Bảo mật & phân quyền', 'desc' => 'Chính sách mật khẩu, 2FA & phân quyền mặc định',
                'source' => 'SuperAdmin', 'override' => 'allowed',
                'fields' => [['Xác thực 2 lớp (2FA)', 'Bật'], ['Chính sách mật khẩu', 'Mạnh'], ['Phiên đăng nhập', '30 phút'], ['Phân quyền theo vai trò', 'Kế thừa từ HQ'], ['Nhật ký hoạt động', 'Bật']],
            ],
        ];

        // Right-column summary — computed from the group set (real counts, no hardcode).
        $sourceCounts = collect($groups)->countBy('source');
        $overriding = collect($groups)->where('override', 'overriding')->count();
        $noOverride = collect($groups)->where('override', 'none')->count();
        $total = count($groups);

        $recent = AuditLog::query()
            ->when($ctx->tenantId(), fn ($q, $t) => $q->where('tenant_id', $t))
            ->latest()->limit(3)->get()
            ->map(fn (AuditLog $a) => [
                'actor' => $a->actor_name ?: 'Hệ thống',
                'desc' => $a->description ?: $a->action,
                'at' => $a->created_at?->format('d/m/Y H:i'),
            ])->all();

        return [
            'project' => $ctx->project(),
            'groups' => $groups,
            'summary' => [
                'primary_source' => 'SuperAdmin',
                'scope' => 'Toàn hệ thống',
                'total' => $total,
                'inherited' => $total - $overriding - $noOverride,
                'overriding' => $overriding,
                'no_override' => $total - collect($groups)->where('override', 'allowed')->count() - $overriding,
            ],
            'sourceBreakdown' => [
                ['label' => 'Kế thừa từ HQ', 'count' => $sourceCounts['HQ'] ?? 0, 'color' => '#6366f1'],
                ['label' => 'Kế thừa từ SuperAdmin', 'count' => $sourceCounts['SuperAdmin'] ?? 0, 'color' => '#8b5cf6'],
                ['label' => 'Đang override', 'count' => $overriding, 'color' => '#f59e0b'],
            ],
            'total' => $total,
            'recent' => $recent,
        ];
    }
}
