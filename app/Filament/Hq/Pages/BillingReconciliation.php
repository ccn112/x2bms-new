<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\BillingAdjustment;
use App\Models\BillingReconciliation as BillingReconciliationModel;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-02-08 — Đối soát ví, hóa đơn, usage. */
class BillingReconciliation extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';

    protected static ?string $navigationLabel = 'Đối soát';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Đối soát ví, hóa đơn, usage';

    protected static ?string $slug = 'billing/reconciliation';

    protected string $view = 'filament.hq.pages.billing-reconciliation';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $recs = BillingReconciliationModel::where('tenant_id', $tid)->with('invoice')->latest('id')->get();
        $adj = BillingAdjustment::where('tenant_id', $tid)->with('invoice')->latest('id')->get();

        return [
            'recs' => $recs->map(fn ($r) => [
                'ref' => $r->bank_transaction_ref, 'invoice' => $r->invoice?->invoice_no ?? '—',
                'status' => $r->status, 'diff' => (float) $r->difference_amount,
                'confirmed' => optional($r->confirmed_at)->format('d/m/Y'),
            ]),
            'adjustments' => $adj->map(fn ($a) => [
                'case' => $a->case_id, 'invoice' => $a->invoice?->invoice_no ?? '—',
                'type' => $a->adjustment_type, 'amount' => (float) $a->amount, 'status' => $a->status, 'reason' => $a->reason,
            ]),
            'kpi' => [
                'matched' => $recs->where('status', 'matched')->count(),
                'mismatch' => $recs->where('status', 'mismatch')->count(),
                'diffTotal' => (float) $recs->sum('difference_amount'),
                'adjPending' => $adj->where('status', 'pending_approval')->count(),
            ],
        ];
    }
}
