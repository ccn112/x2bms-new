<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * HQ-02-03 — Ví công ty / số dư / hạn mức.
 * Số dư + hạn mức tín dụng + top-up tháng + auto-topup + biểu đồ số dư + phân bổ theo dự án.
 */
class CompanyWallet extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';

    protected static ?string $navigationLabel = 'Ví công ty';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Ví công ty';

    protected static ?string $slug = 'billing/wallet';

    protected string $view = 'filament.hq.pages.company-wallet';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $wallet = Wallet::where('tenant_id', $tid)->first();

        $tx = WalletTransaction::where('tenant_id', $tid)->orderBy('posted_at')->get();
        $monthStart = Carbon::parse('2026-07-01');
        $topupMonth = $tx->where('type', 'top_up')->filter(fn ($t) => $t->posted_at && $t->posted_at->gte($monthStart));

        $chart = $tx->whereNotNull('balance_after')->take(-10)->map(fn ($t) => [
            'date' => optional($t->posted_at)->format('d/m'), 'value' => (float) $t->balance_after,
        ])->values();

        $allocations = $tx->where('type', 'allocation')->map(fn ($t) => [
            'name' => $t->project?->name ?? 'Ngân sách dự phòng HQ', 'value' => (float) $t->amount,
        ])->values();
        $allocTotal = $allocations->sum('value');

        $balance = (float) ($wallet?->balance ?? 0);
        $limit = (float) ($wallet?->credit_limit ?? 0);

        return [
            'wallet' => $wallet,
            'balance' => $balance, 'limit' => $limit,
            'usedPct' => $limit ? round($balance / $limit * 100, 2) : 0,
            'remaining' => $limit - $balance,
            'topupMonth' => (float) $topupMonth->sum('amount'),
            'topupCount' => $topupMonth->count(),
            'chart' => $chart,
            'allocations' => $allocations,
            'allocTotal' => $allocTotal,
        ];
    }
}
