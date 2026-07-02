<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-02-09 — Dự báo chi phí tháng tới. */
class CostForecast extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';

    protected static ?string $navigationLabel = 'Dự báo chi phí';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Dự báo chi phí tháng tới';

    protected static ?string $slug = 'billing/forecast';

    protected string $view = 'filament.hq.pages.cost-forecast';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $trend = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'monthly_cost')->orderBy('period')->get()
            ->map(fn ($m) => ['period' => $m->period, 'value' => (float) $m->value, 'forecast' => false]);
        $forecast = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'forecast')->latest('id')->first();

        $current = (float) ($trend->last()['value'] ?? 0);
        $projected = (float) ($forecast?->value ?? $current);

        $series = $trend->push([
            'period' => $forecast?->period ?? '2026-08', 'value' => $projected, 'forecast' => true,
        ]);

        return [
            'series' => $series,
            'current' => $current,
            'projected' => $projected,
            'growth' => (float) ($forecast?->dimension['growth_percent'] ?? 0),
            'confidence' => $forecast?->dimension['confidence'] ?? 'medium',
        ];
    }
}
