<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\FinanceScope;
use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * BQL-03-02 — Chu kỳ phí & đợt thu (Fee cycle list) + "Thiết lập kỳ phí" drawer.
 * Lists one fee cycle per fee type per month (CP-* codes) with KPI counters and a
 * 5-step setup drawer. Reached from the "Khoản thu" area; not a separate nav item.
 */
class FeeCycleList extends Page
{
    use FinanceScope;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Chu kỳ phí & đợt thu';

    protected static ?string $slug = 'fees/cycles';

    protected string $view = 'filament.pages.fee-cycle-list';

    public const STATUS = [
        'open' => ['Đang mở', 'green'],
        'pending_close' => ['Chờ chốt', 'amber'],
        'published' => ['Đã phát hành', 'blue'],
        'locked' => ['Đã khóa', 'slate'],
    ];

    /** Create a draft cycle from the setup drawer's "Tạo kỳ phí" button. */
    public function createDraftCycle(): void
    {
        $bid = $this->financeBuildingId();
        $tid = app(CurrentContext::class)->tenantId();
        $seq = BillingPeriod::where('building_id', $bid)->where('code', 'like', 'CP-2026-07-%')->count() + 1;

        BillingPeriod::create([
            'tenant_id' => $tid,
            'building_id' => $bid,
            'code' => 'CP-2026-07-DV'.($seq > 1 ? '-'.$seq : ''),
            'label' => 'Kỳ 07/2026',
            'name' => 'Phí quản lý tháng 07/2026',
            'fee_category' => 'Phí quản lý',
            'scope_label' => 'Sunshine Garden',
            'period_month' => '2026-07-01',
            'expected_units' => Apartment::where('building_id', $bid)->count(),
            'expected_amount' => 4_216_800_000,
            'status' => 'open',
            'is_current' => false,
        ]);

        Notification::make()->title('Đã tạo kỳ phí nháp')->success()->send();
    }

    protected function getViewData(): array
    {
        $bid = $this->financeBuildingId();
        $base = fn () => BillingPeriod::query()->where('building_id', $bid)->where('code', 'like', 'CP-%');

        $rows = (clone $base())
            ->orderByDesc('period_month')->orderBy('code')
            ->get()
            ->map(function (BillingPeriod $c) {
                $badge = self::STATUS[$c->status] ?? ['—', 'slate'];

                return [
                    'code' => $c->code,
                    'name' => $c->name ?? $c->label,
                    'fee_category' => $c->fee_category ?? '—',
                    'scope' => $c->scope_label ?? '—',
                    'period' => $c->period_month?->format('m/Y') ?? '—',
                    'status_label' => $badge[0], 'status_tone' => $badge[1],
                ];
            })->all();

        $count = fn (string $s) => (clone $base())->where('status', $s)->count();

        return [
            'kpis' => [
                ['label' => 'Kỳ phí đang mở', 'value' => $count('open'), 'accent' => 'blue'],
                ['label' => 'Chờ chốt', 'value' => $count('pending_close'), 'accent' => 'amber'],
                ['label' => 'Đã phát hành', 'value' => $count('published'), 'accent' => 'green'],
                ['label' => 'Tổng kỳ phí', 'value' => (clone $base())->count(), 'accent' => 'teal'],
            ],
            'rows' => $rows,
            // Prefilled template for the "Thiết lập kỳ phí" drawer (matches the design).
            'draft' => [
                'code' => 'CP-2026-07-DV',
                'name' => 'Phí quản lý tháng 07/2026',
                'period' => '07/2026',
                'fee_type' => 'Phí quản lý',
                'units' => number_format(Apartment::where('building_id', $bid)->count(), 0, ',', '.'),
                'expected' => '4.216.800.000',
                'creator' => auth()->user()?->name ?? 'Nguyễn Minh Anh',
            ],
        ];
    }
}
