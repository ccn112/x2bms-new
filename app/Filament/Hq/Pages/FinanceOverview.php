<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-01 — Tổng quan tài chính đa dự án. */
class FinanceOverview extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Tổng quan tài chính';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Tổng quan tài chính đa dự án';

    protected static ?string $slug = 'finance/overview';

    protected string $view = 'filament.hq.pages.finance-overview';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'finance_kpi')->get()
            ->mapWithKeys(fn ($m) => [$m->dimension['metric'] => (float) $m->value]);
        $aging = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'aging_bucket')->get()
            ->sortBy(fn ($m) => $m->dimension['sort'] ?? 0)
            ->map(fn ($m) => ['label' => $m->dimension['label'], 'value' => (float) $m->value, 'pct' => $m->dimension['pct']]);
        $trend = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'collection_rate')->orderBy('period')->get()
            ->map(fn ($m) => ['period' => $m->dimension['period'] ?? $m->period, 'value' => (float) $m->value]);
        $topProjects = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'project_aging')->orderByDesc('value')->take(5)->get()
            ->map(fn ($m) => ['project' => $m->dimension['project'], 'total' => (float) $m->value, 'share' => $m->dimension['share']]);

        return ['kpi' => $kpi, 'aging' => $aging->values(), 'trend' => $trend, 'topProjects' => $topProjects];
    }
}
