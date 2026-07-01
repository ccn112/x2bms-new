<?php

namespace App\Http\Controllers\Platform\Billing;

use App\Filament\Concerns\WritesBillingAudit;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SubscriptionAddon;
use App\Models\SubscriptionContract;
use App\Models\SubscriptionItem;
use App\Models\TenantSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 07 — API thuê bao SaaS (CRUD + lifecycle). */
class TenantSubscriptionController extends Controller
{
    use WritesBillingAudit;

    public function index(Request $request): JsonResponse
    {
        $q = TenantSubscription::with(['tenant', 'plan'])
            ->when($request->status, fn ($qq, $s) => $qq->where('status', $s))
            ->when($request->tenant_id, fn ($qq, $t) => $qq->where('tenant_id', $t));

        return response()->json($q->orderByDesc('mrr')->paginate((int) $request->get('per_page', 20)));
    }

    public function show(TenantSubscription $subscription): JsonResponse
    {
        return response()->json($subscription->load(['tenant', 'plan', 'contract', 'items', 'addons', 'invoices']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'mode' => 'nullable|in:active,trial',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'auto_renew' => 'nullable|boolean',
        ]);
        $plan = Plan::find($data['plan_id']);
        $mode = $data['mode'] ?? 'active';
        $mrr = $mode === 'trial' ? 0 : (float) $plan->monthly_base_price;

        $contract = SubscriptionContract::create([
            'tenant_id' => $data['tenant_id'], 'contract_no' => 'HDTB-'.now()->format('Ymd').'-'.$data['tenant_id'],
            'contract_type' => $mode === 'trial' ? 'trial' : 'standard', 'status' => 'active',
            'start_date' => $data['start_date'] ?? now(), 'end_date' => $data['end_date'] ?? now()->addYear(), 'annual_value' => $mrr * 12,
        ]);
        $sub = TenantSubscription::create([
            'tenant_id' => $data['tenant_id'], 'plan_id' => $data['plan_id'], 'status' => $mode,
            'billing_cycle' => $data['billing_cycle'], 'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? now()->addYear(), 'auto_renew' => $data['auto_renew'] ?? true,
            'mrr' => $mrr, 'arr' => $mrr * 12, 'currency' => 'VND', 'contract_id' => $contract->id,
        ]);
        SubscriptionItem::create([
            'subscription_id' => $sub->id, 'item_type' => 'plan', 'name' => 'Gói '.$plan->name,
            'quantity' => 1, 'unit_price' => $mrr, 'amount' => $mrr,
        ]);
        $this->billingAudit('subscription.create', $sub, null, ['plan_id' => $data['plan_id'], 'mode' => $mode]);

        return response()->json($sub->load('items'), 201);
    }

    public function upgrade(Request $request, TenantSubscription $subscription): JsonResponse
    {
        return $this->changePlan($request, $subscription, 'upgrade');
    }

    public function downgrade(Request $request, TenantSubscription $subscription): JsonResponse
    {
        return $this->changePlan($request, $subscription, 'downgrade');
    }

    private function changePlan(Request $request, TenantSubscription $sub, string $dir): JsonResponse
    {
        $data = $request->validate(['plan_id' => 'required|exists:plans,id']);
        $before = ['plan_id' => $sub->plan_id, 'mrr' => $sub->mrr];
        $mrr = (float) Plan::find($data['plan_id'])->monthly_base_price;
        $sub->update(['plan_id' => $data['plan_id'], 'mrr' => $mrr, 'arr' => $mrr * 12]);
        $this->billingAudit('subscription.'.$dir, $sub, $before, ['plan_id' => $data['plan_id'], 'mrr' => $mrr]);

        return response()->json($sub->fresh());
    }

    public function addAddon(Request $request, TenantSubscription $subscription): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string', 'mrr' => 'required|numeric', 'wallet_type' => 'nullable|string', 'addon_code' => 'nullable|string']);
        $addon = SubscriptionAddon::create([
            'subscription_id' => $subscription->id, 'addon_code' => $data['addon_code'] ?? ('ADD-'.strtoupper(substr(md5($data['name']), 0, 6))),
            'name' => $data['name'], 'quantity' => 1, 'unit_price' => $data['mrr'], 'mrr' => $data['mrr'],
            'wallet_type' => $data['wallet_type'] ?? null, 'status' => 'active', 'start_date' => now(),
        ]);
        $before = ['mrr' => $subscription->mrr];
        $subscription->increment('mrr', (float) $data['mrr']);
        $subscription->update(['arr' => $subscription->mrr * 12]);
        $this->billingAudit('subscription.add_addon', $subscription, $before, ['mrr' => $subscription->mrr, 'addon' => $addon->name]);

        return response()->json($addon, 201);
    }

    public function removeAddon(TenantSubscription $subscription, SubscriptionAddon $addon): JsonResponse
    {
        $addon->update(['status' => 'cancelled']);
        $before = ['mrr' => $subscription->mrr];
        $subscription->decrement('mrr', (float) $addon->mrr);
        $subscription->update(['arr' => $subscription->mrr * 12]);
        $this->billingAudit('subscription.remove_addon', $subscription, $before, ['mrr' => $subscription->mrr]);

        return response()->json(['ok' => true]);
    }

    public function pause(TenantSubscription $subscription): JsonResponse
    {
        return $this->setStatus($subscription, 'suspended', 'subscription.pause');
    }

    public function resume(TenantSubscription $subscription): JsonResponse
    {
        return $this->setStatus($subscription, 'active', 'subscription.resume');
    }

    public function suspend(TenantSubscription $subscription): JsonResponse
    {
        return $this->setStatus($subscription, 'suspended', 'subscription.suspend');
    }

    public function renew(TenantSubscription $subscription): JsonResponse
    {
        $before = ['end_date' => (string) $subscription->end_date, 'status' => $subscription->status];
        $months = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12][$subscription->billing_cycle] ?? 1;
        $base = $subscription->end_date && $subscription->end_date->isFuture() ? $subscription->end_date : now();
        $subscription->update(['end_date' => $base->copy()->addMonths($months), 'status' => 'active']);
        $this->billingAudit('subscription.renew', $subscription, $before, ['end_date' => (string) $subscription->end_date]);

        return response()->json($subscription->fresh());
    }

    private function setStatus(TenantSubscription $sub, string $status, string $action): JsonResponse
    {
        $before = ['status' => $sub->status];
        $sub->update(['status' => $status]);
        $this->billingAudit($action, $sub, $before, ['status' => $status]);

        return response()->json($sub->fresh());
    }
}
