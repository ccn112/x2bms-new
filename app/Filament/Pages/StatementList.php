<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\FinanceScope;
use App\Models\Statement;
use BackedEnum;
use Filament\Pages\Page;
use Livewire\WithPagination;

/**
 * BQL-03-04 — Bảng kê phí cư dân (Statement list / approval / publish).
 * KPI row (chờ phát hành / đã phát hành / đã xem / quá hạn / tổng phải thu) + a
 * paginated statement table for the current period, scoped to the topbar building.
 */
class StatementList extends Page
{
    use FinanceScope;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính – Phí';

    protected static ?string $navigationLabel = 'Hóa đơn & thanh toán';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Bảng kê phí cư dân';

    protected static ?string $slug = 'statements';

    protected string $view = 'filament.pages.statement-list';

    public const STATUS = [
        'paid' => ['Đã thanh toán', 'green'],
        'published' => ['Đã phát hành', 'blue'],
        'pending' => ['Chờ phát hành', 'amber'],
        'overdue' => ['Quá hạn', 'red'],
    ];

    protected function getViewData(): array
    {
        $bid = $this->financeBuildingId();
        $period = $this->currentPeriod();
        $pid = $period?->id ?? 0;
        $today = '2026-07-02';

        $base = fn () => Statement::query()->where('building_id', $bid)->where('billing_period_id', $pid);

        $overdue = fn () => (clone $base())->where('approval_status', 'published')
            ->whereDate('due_date', '<', $today)->whereColumn('paid_amount', '<', 'total_amount');

        $paginator = (clone $base())
            ->with(['apartment.residents' => fn ($q) => $q->wherePivot('is_primary', true)])
            ->withCount('lines')
            // Stable hash shuffle so each page shows a realistic mix of statuses
            // (published / paid / pending / overdue) like the design.
            ->orderByRaw('(id * 2654435761) % 100003')
            ->paginate(10);

        $paginator->getCollection()->transform(function (Statement $s) use ($today) {
            $remaining = (float) $s->total_amount - (float) $s->paid_amount;
            $isOverdue = $s->approval_status === 'published' && $s->due_date
                && $s->due_date->format('Y-m-d') < $today && $remaining > 0;
            $key = $isOverdue ? 'overdue'
                : ($s->status === 'paid' ? 'paid'
                : ($s->approval_status === 'published' ? 'published' : 'pending'));
            $badge = self::STATUS[$key] ?? ['—', 'slate'];

            return [
                'id' => $s->id,
                'code' => $s->code ?? ('BK-'.$s->id),
                'apartment' => $s->apartment?->code ?? '—',
                'resident' => $s->apartment?->residents->first()?->full_name ?? '—',
                'period' => optional($s->billingPeriod)->label ?? '07/2026',
                'lines' => $s->lines_count,
                'total' => self::money((float) $s->total_amount),
                'paid' => self::money((float) $s->paid_amount),
                'remaining' => self::money($remaining),
                'remaining_raw' => $remaining,
                'issued_at' => $s->published_at?->format('d/m/Y') ?? '—',
                'due_at' => $s->due_date?->format('d/m/Y') ?? '—',
                'status_label' => $badge[0], 'status_tone' => $badge[1],
            ];
        });

        return [
            'kpis' => [
                ['label' => 'Chờ phát hành', 'value' => (clone $base())->where('approval_status', 'pending')->count(), 'accent' => 'amber'],
                ['label' => 'Đã phát hành', 'value' => number_format((clone $base())->where('approval_status', 'published')->count(), 0, ',', '.'), 'accent' => 'green'],
                ['label' => 'Đã xem', 'value' => (clone $base())->whereNotNull('viewed_at')->count(), 'accent' => 'blue'],
                ['label' => 'Quá hạn', 'value' => $overdue()->count(), 'accent' => 'red'],
                ['label' => 'Tổng phải thu', 'value' => self::moneyCompact((float) (clone $base())->sum('total_amount')), 'accent' => 'teal'],
            ],
            'statements' => $paginator,
        ];
    }
}
