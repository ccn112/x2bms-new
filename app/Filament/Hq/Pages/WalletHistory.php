<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\WalletTransaction;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-02-04 — Lịch sử nạp ví & thanh toán platform. */
class WalletHistory extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';

    protected static ?string $navigationLabel = 'Lịch sử nạp & thanh toán';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Lịch sử nạp ví & thanh toán platform';

    protected static ?string $slug = 'billing/wallet-history';

    protected string $view = 'filament.hq.pages.wallet-history';

    public string $filter = 'all';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $tx = WalletTransaction::where('tenant_id', $tid)->with('project')->orderByDesc('posted_at')->get();

        $rows = $tx->when($this->filter !== 'all', fn ($c) => $c->where('type', $this->filter))
            ->map(fn ($t) => [
                'ref' => $t->reference_no, 'type' => $t->type, 'amount' => (float) $t->amount,
                'balance' => (float) $t->balance_after, 'desc' => $t->description,
                'project' => $t->project?->name, 'at' => optional($t->posted_at)->format('d/m/Y H:i'), 'status' => $t->status,
            ])->values();

        return [
            'rows' => $rows,
            'kpi' => [
                'topup' => (float) $tx->where('type', 'top_up')->sum('amount'),
                'deduct' => (float) $tx->where('type', 'deduct')->sum('amount'),
                'allocation' => (float) $tx->where('type', 'allocation')->sum('amount'),
                'count' => $tx->count(),
            ],
        ];
    }
}
