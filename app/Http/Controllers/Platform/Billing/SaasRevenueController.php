<?php

namespace App\Http\Controllers\Platform\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\TenantSubscription;
use App\Models\UsageRecord;
use Illuminate\Http\JsonResponse;

/** Batch 07 — API tong quan doanh thu SaaS. */
class SaasRevenueController extends Controller
{
    public function index(): JsonResponse
    {
        $active = ['active', 'trial', 'pending_renewal'];
        $mrr = (float) TenantSubscription::whereIn('status', $active)->sum('mrr');

        return response()->json([
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'active_subscriptions' => TenantSubscription::where('status', 'active')->count(),
            'trial' => TenantSubscription::where('status', 'trial')->count(),
            'churn' => TenantSubscription::whereIn('status', ['suspended', 'cancelled'])->count(),
            'overage_revenue' => (float) UsageRecord::sum('overage_amount'),
            'overdue_invoices' => BillingInvoice::where('status', 'overdue')
                ->orWhere(fn ($q) => $q->whereIn('status', ['issued', 'sent', 'partially_paid'])->whereDate('due_date', '<', now()))->count(),
            'mrr_by_plan' => TenantSubscription::whereIn('status', $active)->selectRaw('plan_id, sum(mrr) mrr')->groupBy('plan_id')->get(),
            'top_tenants' => TenantSubscription::with('tenant')->whereIn('status', $active)->orderByDesc('mrr')->limit(5)->get(),
        ]);
    }
}
