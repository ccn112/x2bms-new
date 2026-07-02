<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-03 — Báo cáo tuổi nợ (Aging Report). */
class DebtAging extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Tuổi nợ (Aging)';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Báo cáo tuổi nợ (Aging Report)';

    protected static ?string $slug = 'debts/aging';

    protected string $view = 'filament.hq.pages.debt-aging';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $buckets = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'aging_bucket')->get()
            ->sortBy(fn ($m) => $m->dimension['sort'] ?? 0)
            ->map(fn ($m) => ['label' => $m->dimension['label'], 'value' => (float) $m->value, 'pct' => $m->dimension['pct']])->values();
        $projects = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'project_aging')->orderByDesc('value')->get()
            ->map(fn ($m) => $m->dimension + ['total' => (float) $m->value]);

        $total = $buckets->sum('value');
        $badDebt = $buckets->firstWhere('label', 'Trên 90 ngày')['value'] ?? 0;

        return [
            'buckets' => $buckets,
            'projects' => $projects,
            'total' => $total,
            'badDebt' => $badDebt,
            'badPct' => $total ? round($badDebt / $total * 100, 1) : 0,
            'units' => (int) $projects->sum('units'),
        ];
    }
}
