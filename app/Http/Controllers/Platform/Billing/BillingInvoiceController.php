<?php

namespace App\Http\Controllers\Platform\Billing;

use App\Filament\Concerns\WritesBillingAudit;
use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\BillingInvoiceLine;
use App\Models\BillingPayment;
use App\Models\BillingReconciliation;
use App\Models\SubscriptionAddon;
use App\Models\TenantSubscription;
use App\Models\UsagePeriod;
use App\Models\UsageRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Batch 07 — API hoa don SaaS (generate/approve/send/payment/reconcile/void). */
class BillingInvoiceController extends Controller
{
    use WritesBillingAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(BillingInvoice::with('tenant')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->tenant_id, fn ($q, $t) => $q->where('tenant_id', $t))
            ->latest('issue_date')->paginate((int) $request->get('per_page', 20)));
    }

    public function show(BillingInvoice $invoice): JsonResponse
    {
        return response()->json($invoice->load(['tenant', 'lines', 'payments']));
    }

    public function generate(Request $request): JsonResponse
    {
        $period = $request->period_id ? UsagePeriod::find($request->period_id) : UsagePeriod::latest('period_start')->first();
        if (! $period || $period->status !== 'locked') {
            return response()->json(['message' => 'Ky usage chua khoa'], 422);
        }
        $periodKey = $period->period_end?->format('Y-m');
        $created = 0;

        foreach (TenantSubscription::whereIn('status', ['active', 'pending_renewal', 'past_due'])->get() as $s) {
            if (BillingInvoice::where('subscription_id', $s->id)->where('period', $periodKey)->exists()) {
                continue;
            }
            DB::transaction(function () use ($s, $period, $periodKey, &$created): void {
                $base = (float) $s->mrr;
                $addons = SubscriptionAddon::where('subscription_id', $s->id)->where('status', 'active')->get();
                $overage = (float) UsageRecord::where('usage_period_id', $period->id)->where('tenant_id', $s->tenant_id)->sum('overage_amount');
                $subtotal = $base + (float) $addons->sum('mrr') + $overage;
                $tax = round($subtotal * 0.1);
                $total = $subtotal + $tax;
                $inv = BillingInvoice::create([
                    'invoice_no' => 'INV-'.$periodKey.'-'.$s->tenant_id.str_pad((string) $s->id, 3, '0', STR_PAD_LEFT),
                    'tenant_id' => $s->tenant_id, 'subscription_id' => $s->id, 'period' => $periodKey, 'status' => 'draft',
                    'issue_date' => now(), 'due_date' => now()->addDays(15), 'subtotal' => $subtotal, 'discount_total' => 0,
                    'tax_total' => $tax, 'total_amount' => $total, 'paid_amount' => 0, 'remaining_amount' => $total, 'currency' => 'VND',
                ]);
                BillingInvoiceLine::create(['invoice_id' => $inv->id, 'line_type' => 'subscription', 'description' => 'Thue bao', 'quantity' => 1, 'unit_price' => $base, 'amount' => $base]);
                foreach ($addons as $a) {
                    BillingInvoiceLine::create(['invoice_id' => $inv->id, 'line_type' => 'addon', 'description' => $a->name, 'quantity' => 1, 'unit_price' => $a->mrr, 'amount' => $a->mrr]);
                }
                if ($overage > 0) {
                    BillingInvoiceLine::create(['invoice_id' => $inv->id, 'line_type' => 'usage_overage', 'description' => 'Overage', 'quantity' => 1, 'unit_price' => $overage, 'amount' => $overage]);
                }
                BillingInvoiceLine::create(['invoice_id' => $inv->id, 'line_type' => 'tax', 'description' => 'VAT 10%', 'quantity' => 1, 'unit_price' => $tax, 'amount' => $tax, 'tax_rate' => 10]);
                $this->billingAudit('invoice.generate', $inv, null, ['total' => $total]);
                $created++;
            });
        }

        return response()->json(['created' => $created]);
    }

    public function approve(BillingInvoice $invoice): JsonResponse
    {
        $before = ['status' => $invoice->status];
        $invoice->update(['status' => 'issued', 'issue_date' => $invoice->issue_date ?? now()]);
        $this->billingAudit('invoice.approve', $invoice, $before, ['status' => 'issued']);

        return response()->json($invoice->fresh());
    }

    public function send(BillingInvoice $invoice): JsonResponse
    {
        $invoice->update(['status' => 'sent']);
        $this->billingAudit('invoice.send', $invoice, null, ['status' => 'sent']);

        return response()->json($invoice->fresh());
    }

    public function void(Request $request, BillingInvoice $invoice): JsonResponse
    {
        $before = ['status' => $invoice->status];
        $invoice->update(['status' => 'voided']);
        $this->billingAudit('invoice.void', $invoice, $before, ['status' => 'voided'], $request->reason);

        return response()->json($invoice->fresh());
    }

    public function recordPayment(Request $request, BillingInvoice $invoice): JsonResponse
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0', 'payment_method' => 'nullable|string', 'transaction_ref' => 'nullable|string']);
        $amount = (float) $data['amount'];
        BillingPayment::create([
            'invoice_id' => $invoice->id, 'tenant_id' => $invoice->tenant_id, 'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'amount' => $amount, 'paid_at' => now(), 'transaction_ref' => $data['transaction_ref'] ?? null, 'status' => 'confirmed',
        ]);
        $before = ['paid' => $invoice->paid_amount, 'status' => $invoice->status];
        $paid = (float) $invoice->paid_amount + $amount;
        $remaining = max(0, (float) $invoice->total_amount - $paid);
        $status = $remaining <= 0 ? 'paid' : 'partially_paid';
        $invoice->update(['paid_amount' => $paid, 'remaining_amount' => $remaining, 'status' => $status]);
        $this->billingAudit('invoice.payment', $invoice, $before, ['paid' => $paid, 'status' => $status]);

        return response()->json($invoice->fresh());
    }

    public function reconcile(BillingInvoice $invoice): JsonResponse
    {
        $payment = $invoice->payments()->latest('paid_at')->first();
        $diff = (float) $invoice->total_amount - (float) $invoice->paid_amount;
        $rec = BillingReconciliation::create([
            'tenant_id' => $invoice->tenant_id, 'invoice_id' => $invoice->id, 'payment_id' => $payment?->id,
            'bank_transaction_ref' => $payment?->transaction_ref, 'status' => abs($diff) < 1 ? 'matched' : 'mismatch',
            'difference_amount' => $diff, 'confirmed_by' => auth()->id(), 'confirmed_at' => now(),
        ]);
        $this->billingAudit('invoice.reconcile', $invoice, null, ['status' => $rec->status]);

        return response()->json($rec);
    }
}
