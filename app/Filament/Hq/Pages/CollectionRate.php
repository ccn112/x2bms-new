<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-05 — Tỷ lệ thu theo kỳ phí. */
class CollectionRate extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Tỷ lệ thu';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Tỷ lệ thu theo kỳ phí';

    protected static ?string $slug = 'collection-rate';

    protected string $view = 'filament.hq.pages.collection-rate';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $trend = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'collection_rate')->orderBy('period')->get()
            ->map(fn ($m) => ['period' => $m->dimension['period'] ?? $m->period, 'value' => (float) $m->value]);
        $byProject = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'collection_by_project')->get()
            ->sortBy(fn ($m) => $m->dimension['sort'] ?? 0)
            ->map(fn ($m) => ['project' => $m->dimension['project'], 'value' => (float) $m->value])->values();

        return ['trend' => $trend, 'byProject' => $byProject, 'avg' => round($trend->avg('value'), 1)];
    }
}
