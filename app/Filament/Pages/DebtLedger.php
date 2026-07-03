<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\FinanceScope;
use App\Models\Apartment;
use App\Models\Debt;
use App\Models\Statement;
use BackedEnum;
use Filament\Pages\Page;

/**
 * BQL-03-06 — Sổ công nợ cư dân (Debt detail per apartment).
 * Resident header + KPIs, the per-period statement ledger, and the aging chart
 * (from the debt buckets). Reached from the debt list; not in nav.
 */
class DebtLedger extends Page
{
    use FinanceScope;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'debts/{record}';

    protected string $view = 'filament.pages.debt-ledger';

    public Debt $record;

    public function mount(Debt $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Sổ công nợ cư dân';
    }

    protected function getViewData(): array
    {
        $d = $this->record;
        $apt = Apartment::with(['building', 'floor'])->find($d->apartment_id);

        $statements = Statement::with('billingPeriod')
            ->where('apartment_id', $d->apartment_id)
            ->get()
            ->sortByDesc(fn (Statement $s) => optional($s->billingPeriod)->period_month)
            ->values();

        $rows = $statements->map(function (Statement $s) {
            $remaining = (float) $s->total_amount - (float) $s->paid_amount;

            return [
                'period' => optional($s->billingPeriod)->label ?? '—',
                'accrued' => self::money((float) $s->total_amount),
                'collected' => self::money((float) $s->paid_amount),
                'remaining' => self::money($remaining),
                'remaining_raw' => $remaining,
                'status_label' => $remaining <= 0 ? 'Đã thanh toán' : 'Còn nợ',
                'status_tone' => $remaining <= 0 ? 'green' : 'amber',
            ];
        })->all();

        $buckets = [
            ['label' => '0 - 30 ngày', 'value' => (float) $d->bucket_0_30],
            ['label' => '31 - 60 ngày', 'value' => (float) $d->bucket_31_60],
            ['label' => '61 - 90 ngày', 'value' => (float) $d->bucket_61_90],
            ['label' => '> 90 ngày', 'value' => (float) $d->bucket_over_90],
        ];
        $agingTotal = max(1, array_sum(array_column($buckets, 'value')));
        foreach ($buckets as &$b) {
            $b['pct'] = round($b['value'] / $agingTotal * 100, 1);
            $b['money'] = self::money($b['value']);
        }
        unset($b);

        $overdue = (float) $d->bucket_31_60 + (float) $d->bucket_61_90 + (float) $d->bucket_over_90;
        $paidThisYear = (float) $statements->sum('paid_amount');
        $openPeriods = collect($rows)->where('remaining_raw', '>', 0)->count();

        return [
            'debt' => $d,
            'apt' => $apt,
            'resident' => $d->resident_name ?? '—',
            'kpis' => [
                ['label' => 'Nợ hiện tại', 'value' => self::money((float) $d->bucket_0_30).' đ', 'sub' => 'Chưa quá hạn', 'accent' => 'blue'],
                ['label' => 'Nợ quá hạn', 'value' => self::money($overdue).' đ', 'sub' => 'Quá hạn', 'accent' => 'red'],
                ['label' => 'Số kỳ còn nợ', 'value' => max($openPeriods, collect($buckets)->where('value', '>', 0)->count()), 'sub' => 'kỳ', 'accent' => 'amber'],
                ['label' => 'Tổng đã thanh toán năm nay', 'value' => self::money($paidThisYear).' đ', 'accent' => 'green'],
            ],
            'rows' => $rows,
            'buckets' => $buckets,
            'totalDebt' => self::money((float) $d->amount),
        ];
    }
}
