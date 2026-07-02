<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\Project;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * HQ Portal — Tổng quan HQ (landing).
 *
 * Trang chủ của Cổng Công ty: KPI nhanh trên phạm vi đa dự án đang chọn.
 * Mọi số liệu tính từ DB theo tenant + tập project của CurrentContext (không hardcode).
 */
class HqOverview extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Tổng quan';

    protected static ?string $navigationLabel = 'Tổng quan HQ';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Tổng quan HQ';

    protected static ?string $slug = 'overview';

    protected string $view = 'filament.hq.pages.hq-overview';

    protected function getViewData(): array
    {
        $ctx = app(CurrentContext::class);
        $tenantId = $ctx->tenantId();
        $projectIds = $ctx->hqProjectIds();

        $projects = Project::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('id', $projectIds ?: [0])
            ->get();

        $activeCount = $projects->where('status', 'active')->count();

        return [
            'tenant' => $ctx->tenant(),
            'scopeLabel' => $ctx->hqAllProjectsSelected() ? 'Tất cả dự án' : count($projectIds).' dự án',
            'totalProjects' => $projects->count(),
            'activeProjects' => $activeCount,
            'totalApartments' => (int) $projects->sum('apartment_count'),
            'totalBuildings' => (int) $projects->sum('building_count'),
        ];
    }
}
