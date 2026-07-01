<?php

namespace App\Http\Controllers\Platform\Support;

use App\Filament\Concerns\WritesSupportAudit;
use App\Http\Controllers\Controller;
use App\Models\SupportSlaPolicy;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\SupportTicketStatusLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Batch 10 — Support ticket API (queue/detail/create/assign/escalate/close/reopen/message). */
class SupportTicketController extends Controller
{
    use WritesSupportAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(SupportTicket::with(['tenant', 'owner', 'team'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($request->sla_state, fn ($q, $s) => $q->where('sla_state', $s))
            ->latest()->paginate((int) $request->get('per_page', 25)));
    }

    public function show(SupportTicket $ticket): JsonResponse
    {
        return response()->json($ticket->load(['tenant', 'owner', 'team', 'messages', 'statusLogs', 'escalations', 'dataCorrectionRequests']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255', 'description' => 'nullable|string',
            'tenant_id' => 'nullable|exists:tenants,id', 'module' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical', 'environment' => 'nullable|string',
        ]);
        $sla = SupportSlaPolicy::where('priority', $data['priority'])->first();
        $t = SupportTicket::create($data + [
            'ticket_no' => 'TKT-'.now()->format('Y').'-'.strtoupper(Str::random(5)),
            'status' => 'new', 'sla_state' => 'within_sla', 'sla_policy_id' => $sla?->id,
            'sla_due_at' => $sla ? now()->addMinutes($sla->resolution_minutes) : now()->addDay(), 'owner_id' => auth()->id(),
        ]);
        SupportTicketStatusLog::create(['support_ticket_id' => $t->id, 'to_status' => 'new', 'changed_by' => auth()->id(), 'created_at' => now()]);
        $this->supportAudit('ticket.created', $t, after: ['ticket_no' => $t->ticket_no]);

        return response()->json($t, 201);
    }

    public function assign(Request $request, SupportTicket $ticket): JsonResponse
    {
        $ticket->update(['team_id' => $request->integer('team_id'), 'owner_id' => $request->integer('owner_id') ?: $ticket->owner_id]);
        $this->supportAudit('ticket.assigned', $ticket, after: $request->only('team_id', 'owner_id'));

        return response()->json($ticket->fresh());
    }

    public function escalate(Request $request, SupportTicket $ticket): JsonResponse
    {
        $data = $request->validate(['to_level' => 'required|string', 'reason' => 'required|string']);
        $ticket->escalations()->create(['from_level' => 'L1', 'to_level' => $data['to_level'], 'reason' => $data['reason'], 'status' => 'active', 'escalated_by' => auth()->id()]);
        $ticket->update(['status' => 'escalated']);
        $this->supportAudit('ticket.escalated', $ticket, after: $data);

        return response()->json($ticket->fresh('escalations'));
    }

    public function close(Request $request, SupportTicket $ticket): JsonResponse
    {
        $ticket->update(['status' => 'closed', 'sla_state' => 'resolved', 'closed_at' => now(), 'resolved_at' => $ticket->resolved_at ?? now(), 'resolution_summary' => $request->input('resolution_summary'), 'csat_score' => $request->input('csat_score')]);
        $this->supportAudit('ticket.closed', $ticket, reason: 'resolved');

        return response()->json($ticket->fresh());
    }

    public function reopen(SupportTicket $ticket): JsonResponse
    {
        $ticket->update(['status' => 'reopened', 'closed_at' => null, 'reopen_count' => $ticket->reopen_count + 1]);
        $this->supportAudit('ticket.reopened', $ticket);

        return response()->json($ticket->fresh());
    }

    public function addMessage(Request $request, SupportTicket $ticket): JsonResponse
    {
        $data = $request->validate(['type' => 'required|in:internal,customer,system', 'body' => 'required|string']);
        $msg = SupportTicketMessage::create(['support_ticket_id' => $ticket->id, 'author_id' => auth()->id(), 'author_name' => auth()->user()?->name, 'type' => $data['type'], 'body' => $data['body']]);
        $this->supportAudit('ticket.message_added', $ticket, after: ['type' => $data['type']]);

        return response()->json($msg, 201);
    }
}
