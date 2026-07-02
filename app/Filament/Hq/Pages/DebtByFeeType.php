<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-06 — Công nợ theo loại phí. */
class DebtByFeeType extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Công nợ theo loại phí';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Công nợ theo loại phí';

    protected static ?string $slug = 'finance/debt-by-fee';

    protected string $view = 'filament.hq.pages.debt-by-fee-type';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $rows = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'debt_by_fee')->get()
            ->sortBy(fn ($m) => $m->dimension['sort'] ?? 0)
            ->map(fn ($m) => ['fee' => $m->dimension['fee_type'], 'value' => (float) $m->value])->values();

        return ['rows' => $rows, 'total' => (float) $rows->sum('value')];
    }
}
