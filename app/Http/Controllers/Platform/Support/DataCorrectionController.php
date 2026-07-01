<?php

namespace App\Http\Controllers\Platform\Support;

use App\Filament\Concerns\WritesSupportAudit;
use App\Http\Controllers\Controller;
use App\Models\DataCorrectionRequest;
use App\Models\DataFixExecution;
use App\Models\DataFixRollback;
use App\Models\DataFixSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Batch 10 — Data correction + controlled data fix wizard API. */
class DataCorrectionController extends Controller
{
    use WritesSupportAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(DataCorrectionRequest::with(['tenant', 'requestedBy'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->risk, fn ($q, $r) => $q->where('risk', $r))
            ->latest()->paginate((int) $request->get('per_page', 20)));
    }

    public function show(DataCorrectionRequest $request): JsonResponse
    {
        return response()->json($request->load(['affectedRecords', 'diffItems', 'approvals', 'snapshots', 'executions', 'rollbacks']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'data_type' => 'required|string', 'tenant_id' => 'nullable|exists:tenants,id',
            'support_ticket_id' => 'nullable|exists:support_tickets,id', 'target_entity' => 'nullable|string',
            'affected_records' => 'integer|min:0', 'risk' => 'required|in:low,medium,high,critical', 'reason' => 'nullable|string',
        ]);
        $dcr = DataCorrectionRequest::create($data + ['code' => 'DCR-'.now()->format('Y').'-'.strtoupper(Str::random(4)), 'status' => 'pending_approval', 'requested_by' => auth()->id()]);
        $this->supportAudit('data_correction.created', $dcr, after: ['code' => $dcr->code]);

        return response()->json($dcr, 201);
    }

    public function approve(Request $request, DataCorrectionRequest $dcr): JsonResponse
    {
        $dcr->approvals()->create(['approver_id' => auth()->id(), 'decision' => 'approved', 'reason' => $request->input('reason'), 'approved_at' => now()]);
        $needed = in_array($dcr->risk, ['high', 'critical'], true) ? 2 : 1;
        if ($dcr->approvals()->where('decision', 'approved')->count() >= $needed) {
            $dcr->update(['status' => 'approved', 'approver_id' => auth()->id(), 'approved_at' => now()]);
        }
        $this->supportAudit('data_correction.approved', $dcr, reason: $request->input('reason'));

        return response()->json(['status' => $dcr->fresh()->status, 'approvals' => $dcr->approvals()->count(), 'needed' => $needed]);
    }

    public function reject(Request $request, DataCorrectionRequest $dcr): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);
        $dcr->update(['status' => 'rejected']);
        $dcr->approvals()->create(['approver_id' => auth()->id(), 'decision' => 'rejected', 'reason' => $request->reason, 'approved_at' => now()]);
        $this->supportAudit('data_correction.rejected', $dcr, reason: $request->reason);

        return response()->json($dcr->fresh());
    }

    public function snapshot(DataCorrectionRequest $dcr): JsonResponse
    {
        $snap = DataFixSnapshot::create(['data_correction_request_id' => $dcr->id, 'snapshot_json' => ['records' => $dcr->affected_records], 'record_count' => $dcr->affected_records, 'created_by' => auth()->id(), 'created_at' => now()]);
        $this->supportAudit('data_fix.snapshot_created', $dcr);

        return response()->json($snap, 201);
    }

    public function execute(Request $request, DataCorrectionRequest $dcr): JsonResponse
    {
        if ($dcr->snapshots()->count() === 0) {
            return response()->json(['message' => 'Backup snapshot required before execution'], 422);
        }
        $request->validate(['reason' => 'required|string']);
        $dcr->update(['status' => 'executed']);
        DataFixExecution::create(['data_correction_request_id' => $dcr->id, 'executed_by' => auth()->id(), 'status' => 'executed', 'affected_count' => $dcr->affected_records, 'executed_at' => now(), 'log' => 'Executed with row-level lock']);
        $this->supportAudit('data_fix.executed', $dcr, reason: $request->reason, after: ['affected' => $dcr->affected_records]);

        return response()->json($dcr->fresh());
    }

    public function rollback(Request $request, DataCorrectionRequest $dcr): JsonResponse
    {
        $dcr->update(['status' => 'rolled_back']);
        DataFixRollback::create(['data_correction_request_id' => $dcr->id, 'requested_by' => auth()->id(), 'approved_by' => auth()->id(), 'status' => 'rolled_back', 'restored_count' => $dcr->affected_records, 'rolled_back_at' => now()]);
        $this->supportAudit('data_fix.rolled_back', $dcr, reason: $request->input('reason'));

        return response()->json($dcr->fresh());
    }
}
