<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-02 — Công nợ theo dự án. */
class DebtByProject extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Công nợ theo dự án';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Công nợ theo dự án';

    protected static ?string $slug = 'debts/by-project';

    protected string $view = 'filament.hq.pages.debt-by-project';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $projects = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'project_aging')->orderByDesc('value')->get()
            ->map(fn ($m) => $m->dimension + ['total' => (float) $m->value]);

        return [
            'projects' => $projects,
            'total' => (float) $projects->sum('total'),
            'units' => (int) $projects->sum('units'),
            'badAvg' => round($projects->avg('bad_pct'), 1),
        ];
    }
}
