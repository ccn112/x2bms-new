<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use BackedEnum;
use Filament\Pages\Page;

/**
 * Design System · DS-04 — Trang danh sách chuẩn (List Page Standard). Chuẩn hoá
 * giải phẫu 1 trang /admin list dùng chung mọi module: title + breadcrumb, action
 * bar (Chính→Phụ→Hỗ trợ→Khác), KPI theo BỘ LỌC, filter bar + chip + drawer nâng cao
 * + ẩn/hiện cột, bảng (hyperlink, FREEZE cột chọn/Mã/thao tác), row action + More,
 * bulk bar, phân trang (mặc định 10 + bộ đếm + số trang). Hiện thực mẫu: ResidentDirectory
 * + ApartmentDirectory. Bám kèm skill `.claude/skills/x2bms-admin-listing-page`.
 * Chỉ ở /sa; chuẩn áp chung /sa /hq /admin.
 */
class DesignSystemSet4 extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static string|\UnitEnum|null $navigationGroup = 'Design System';

    protected static ?string $navigationLabel = 'DS-04 · Trang danh sách chuẩn';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Design System · DS-04 Trang danh sách chuẩn (List Page)';

    protected static ?string $slug = 'design-system/ds04';

    protected string $view = 'filament.sa.ds.set4';
}
