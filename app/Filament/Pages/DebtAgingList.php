<?php

namespace App\Filament\Pages;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Debt;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * BQL-03-05 — Danh sách công nợ & tuổi nợ (Debt & aging summary).
 * Aging KPIs + a per-apartment debt ledger with 0-30 / 31-60 / 61-90 / >90 buckets,
 * risk level, assignee and recovery status. Scoped to the project's primary building
 * (the topbar building), reading the seeded debt ledger — no hardcoded totals.
 */
class DebtAgingList extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính – Phí';

    protected static ?string $navigationLabel = 'Công nợ';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Danh sách công nợ & tuổi nợ';

    protected static ?string $slug = 'debts';

    protected string $view = 'filament.pages.debt-aging-list';

    public const RISK = [
        'low' => ['Thấp', 'green'],
        'medium' => ['Trung bình', 'amber'],
        'high' => ['Cao', 'amber'],
        'critical' => ['Rất cao', 'red'],
    ];

    public const RECOVERY = [
        'new' => ['Mới tạo', 'slate'],
        'in_progress' => ['Đang thu hồi', 'blue'],
        'overdue_handling' => ['Quá hạn xử lý', 'red'],
    ];

    /** Finance screens scope to the topbar building = the project's primary building. */
    protected function financeBuildingId(): int
    {
        $pid = app(CurrentContext::class)->projectId();

        return (int) (Building::where('project_id', $pid)->orderBy('id')->value('id') ?? 0);
    }

    public static function money(float $v): string
    {
        return number_format($v, 0, ',', '.');
    }

    /** Compact VND: "2,18 tỷ" / "650 triệu" / "12.450.000". */
    public static function compact(float $v): string
    {
        if ($v >= 1_000_000_000) {
            return rtrim(rtrim(number_format($v / 1_000_000_000, 2, ',', '.'), '0'), ',').' tỷ';
        }
        if ($v >= 1_000_000) {
            return number_format($v / 1_000_000, 0, ',', '.').' triệu';
        }

        return number_format($v, 0, ',', '.');
    }

    protected function getViewData(): array
    {
        $bid = $this->financeBuildingId();
        $base = fn () => Debt::query()->where('building_id', $bid);

        $apts = Apartment::whereIn('id', (clone $base())->pluck('apartment_id'))->pluck('code', 'id');

        $rows = (clone $base())->orderByDesc('amount')->get()->map(function (Debt $d) use ($apts) {
            $risk = self::RISK[$d->risk_level] ?? ['—', 'slate'];
            $rec = self::RECOVERY[$d->recovery_status] ?? ['—', 'slate'];

            return [
                'id' => $d->id,
                'code' => $d->code ?? '—',
                'apartment' => $apts[$d->apartment_id] ?? '—',
                'resident' => $d->resident_name ?? '—',
                'last_period' => $d->last_period_code ?? '—',
                'amount' => self::money((float) $d->amount),
                'b0_30' => (float) $d->bucket_0_30 ? self::money((float) $d->bucket_0_30) : '0',
                'b31_60' => (float) $d->bucket_31_60 ? self::money((float) $d->bucket_31_60) : '0',
                'b61_90' => (float) $d->bucket_61_90 ? self::money((float) $d->bucket_61_90) : '0',
                'bover90' => (float) $d->bucket_over_90 ? self::money((float) $d->bucket_over_90) : '0',
                'risk_label' => $risk[0], 'risk_tone' => $risk[1],
                'rec_label' => $rec[0], 'rec_tone' => $rec[1],
                'assignee' => $d->assignee_name ?? '—',
            ];
        })->all();

        return [
            'kpis' => [
                ['label' => 'Tổng còn nợ', 'value' => self::compact((float) (clone $base())->sum('amount')), 'accent' => 'blue'],
                ['label' => '0 - 30 ngày', 'value' => self::compact((float) (clone $base())->sum('bucket_0_30')), 'accent' => 'green'],
                ['label' => '31 - 60 ngày', 'value' => self::compact((float) (clone $base())->sum('bucket_31_60')), 'accent' => 'amber'],
                ['label' => '61 - 90 ngày', 'value' => self::compact((float) (clone $base())->sum('bucket_61_90')), 'accent' => 'amber'],
                ['label' => '> 90 ngày', 'value' => self::compact((float) (clone $base())->sum('bucket_over_90')), 'accent' => 'red'],
            ],
            'rows' => $rows,
        ];
    }
}
