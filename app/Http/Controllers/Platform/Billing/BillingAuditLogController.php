<?php

namespace App\Http\Controllers\Platform\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 07 — API nhat ky billing. */
class BillingAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(BillingAuditLog::with('actor')
            ->when($request->entity_type, fn ($q, $e) => $q->where('entity_type', $e))
            ->when($request->tenant_id, fn ($q, $t) => $q->where('tenant_id', $t))
            ->latest('created_at')->paginate((int) $request->get('per_page', 50)));
    }
}
