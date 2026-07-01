<?php

namespace App\Http\Controllers\Platform\Billing;

use App\Filament\Concerns\WritesBillingAudit;
use App\Http\Controllers\Controller;
use App\Models\QuotaAlert;
use App\Models\UsagePeriod;
use App\Models\UsageRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 07 — API usage & metering (lock ky + sinh canh bao). */
class UsageMeteringController extends Controller
{
    use WritesBillingAudit;

    public function index(Request $request): JsonResponse
    {
        $period = $request->period_id ? UsagePeriod::find($request->period_id) : UsagePeriod::latest('period_start')->first();
        $records = $period ? UsageRecord::with('tenant')->where('usage_period_id', $period->id)->get() : collect();

        return response()->json(['period' => $period, 'records' => $records]);
    }

    public function recalculate(UsagePeriod $period): JsonResponse
    {
        foreach (UsageRecord::where('usage_period_id', $period->id)->get() as $r) {
            $r->update(['overage_value' => max(0, (float) $r->usage_value - (float) $r->included_limit), 'status' => 'calculated']);
        }
        $period->update(['status' => 'calculating']);
        $this->billingAudit('usage.recalculate', $period, null, ['status' => 'calculating']);

        return response()->json($period->fresh());
    }

    public function lock(UsagePeriod $period): JsonResponse
    {
        $before = ['status' => $period->status];
        $period->update(['status' => 'locked', 'locked_at' => now(), 'locked_by' => auth()->user()->name]);
        UsageRecord::where('usage_period_id', $period->id)->update(['status' => 'locked']);
        $this->billingAudit('usage.lock', $period, $before, ['status' => 'locked']);

        return response()->json($period->fresh());
    }

    public function unlock(UsagePeriod $period): JsonResponse
    {
        $before = ['status' => $period->status];
        $period->update(['status' => 'open', 'locked_at' => null, 'locked_by' => null]);
        $this->billingAudit('usage.unlock', $period, $before, ['status' => 'open']);

        return response()->json($period->fresh());
    }

    public function generateAlerts(UsagePeriod $period): JsonResponse
    {
        $created = 0;
        foreach (UsageRecord::where('usage_period_id', $period->id)->where('overage_value', '>', 0)->get() as $r) {
            $pct = $r->included_limit > 0 ? round(($r->overage_value / $r->included_limit) * 100, 2) : 100;
            $alert = QuotaAlert::firstOrCreate(
                ['tenant_id' => $r->tenant_id, 'usage_period_id' => $period->id, 'meter_type' => $r->meter_type],
                ['code' => 'QA-'.$period->id.'-'.$r->tenant_id.'-'.$r->meter_type, 'usage_value' => $r->usage_value,
                    'included_limit' => $r->included_limit, 'over_percent' => $pct, 'estimated_fee' => $r->overage_amount,
                    'recommendation' => 'Mua add-on hoac nang goi', 'status' => 'open']
            );
            if ($alert->wasRecentlyCreated) {
                $created++;
            }
        }
        $this->billingAudit('usage.generate_alerts', $period, null, ['created' => $created]);

        return response()->json(['created' => $created]);
    }
}
