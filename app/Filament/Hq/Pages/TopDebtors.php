<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-04 — Top căn hộ / cư dân nợ cao. */
class TopDebtors extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Top nợ cao';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Top căn hộ / cư dân nợ cao';

    protected static ?string $slug = 'debts/top-debtors';

    protected string $view = 'filament.hq.pages.top-debtors';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'debt_kpi')->get()
            ->mapWithKeys(fn ($m) => [$m->dimension['metric'] => (float) $m->value]);
        $rows = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'top_debtor')->get()
            ->sortBy(fn ($m) => $m->dimension['sort'] ?? 0)
            ->map(fn ($m) => $m->dimension + ['amount' => (float) $m->value])->values();

        return ['kpi' => $kpi, 'rows' => $rows];
    }
}
