<?php

namespace App\Http\Controllers\Platform\Billing;

use App\Filament\Concerns\WritesBillingAudit;
use App\Http\Controllers\Controller;
use App\Models\QuotaAlert;
use App\Models\SubscriptionAddon;
use App\Models\TenantSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 07 — API canh bao vuot han. */
class QuotaAlertController extends Controller
{
    use WritesBillingAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(QuotaAlert::with('tenant')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('estimated_fee')->paginate((int) $request->get('per_page', 20)));
    }

    public function resolve(QuotaAlert $alert): JsonResponse
    {
        $before = ['status' => $alert->status];
        $alert->update(['status' => 'resolved', 'resolved_at' => now()]);
        $this->billingAudit('quota.resolve', $alert, $before, ['status' => 'resolved']);

        return response()->json($alert->fresh());
    }

    public function convertToAddon(QuotaAlert $alert): JsonResponse
    {
        $sub = TenantSubscription::where('tenant_id', $alert->tenant_id)->whereIn('status', ['active', 'trial', 'pending_renewal'])->first();
        if ($sub) {
            SubscriptionAddon::create([
                'subscription_id' => $sub->id, 'addon_code' => 'ADD-'.strtoupper($alert->meter_type), 'name' => 'Add-on '.$alert->meter_type,
                'quantity' => 1, 'unit_price' => $alert->estimated_fee, 'mrr' => $alert->estimated_fee, 'status' => 'active', 'start_date' => now(),
            ]);
            $sub->increment('mrr', (float) $alert->estimated_fee);
            $sub->update(['arr' => $sub->mrr * 12]);
        }
        $before = ['status' => $alert->status];
        $alert->update(['status' => 'converted_to_addon', 'resolved_at' => now()]);
        $this->billingAudit('quota.convert_addon', $alert, $before, ['status' => 'converted_to_addon']);

        return response()->json($alert->fresh());
    }

    public function convertToUpgrade(QuotaAlert $alert): JsonResponse
    {
        $before = ['status' => $alert->status];
        $alert->update(['status' => 'converted_to_upgrade', 'resolved_at' => now()]);
        $this->billingAudit('quota.convert_upgrade', $alert, $before, ['status' => 'converted_to_upgrade']);

        return response()->json($alert->fresh());
    }
}
