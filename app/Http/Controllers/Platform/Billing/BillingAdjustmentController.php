<?php

namespace App\Http\Controllers\Platform\Billing;

use App\Filament\Concerns\WritesBillingAudit;
use App\Http\Controllers\Controller;
use App\Models\BillingAdjustment;
use App\Models\CreditNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 07 — API dieu chinh billing + credit note. */
class BillingAdjustmentController extends Controller
{
    use WritesBillingAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(BillingAdjustment::with(['tenant', 'invoice'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate((int) $request->get('per_page', 20)));
    }

    public function approve(BillingAdjustment $adjustment): JsonResponse
    {
        $before = ['status' => $adjustment->status];
        $adjustment->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->billingAudit('adjustment.approve', $adjustment, $before, ['status' => 'approved']);

        return response()->json($adjustment->fresh());
    }

    public function reject(Request $request, BillingAdjustment $adjustment): JsonResponse
    {
        $before = ['status' => $adjustment->status];
        $adjustment->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->billingAudit('adjustment.reject', $adjustment, $before, ['status' => 'rejected'], $request->reason);

        return response()->json($adjustment->fresh());
    }

    public function issueCreditNote(BillingAdjustment $adjustment): JsonResponse
    {
        $cn = CreditNote::create([
            'credit_note_no' => 'CN-'.$adjustment->case_id, 'tenant_id' => $adjustment->tenant_id, 'invoice_id' => $adjustment->invoice_id,
            'adjustment_id' => $adjustment->id, 'amount' => abs((float) $adjustment->amount), 'reason' => $adjustment->reason,
            'status' => 'issued', 'issued_at' => now(),
        ]);
        if ($adjustment->invoice) {
            $inv = $adjustment->invoice;
            $newRemaining = max(0, (float) $inv->remaining_amount - abs((float) $adjustment->amount));
            $inv->update(['remaining_amount' => $newRemaining, 'status' => $newRemaining <= 0 ? 'credited' : $inv->status]);
            $cn->update(['status' => 'applied', 'applied_at' => now()]);
        }
        $this->billingAudit('adjustment.credit_note', $adjustment, null, ['credit_note' => $cn->credit_note_no]);

        return response()->json($cn, 201);
    }
}
