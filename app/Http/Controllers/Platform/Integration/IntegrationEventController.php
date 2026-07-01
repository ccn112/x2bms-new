<?php

namespace App\Http\Controllers\Platform\Integration;

use App\Filament\Concerns\WritesIntegrationAudit;
use App\Http\Controllers\Controller;
use App\Models\IntegrationEvent;
use App\Models\IntegrationRetryJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 08 — Event log API (list/detail/replay idempotent). */
class IntegrationEventController extends Controller
{
    use WritesIntegrationAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(IntegrationEvent::with('tenant')
            ->when($request->source, fn ($q, $s) => $q->where('source', $s))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->event_type, fn ($q, $t) => $q->where('event_type', $t))
            ->latest('created_at')->paginate((int) $request->get('per_page', 25)));
    }

    public function show(IntegrationEvent $event): JsonResponse
    {
        return response()->json($event->load('tenant'));
    }

    public function replay(IntegrationEvent $event): JsonResponse
    {
        // Idempotent: do not enqueue a duplicate replay for the same event.
        $existing = IntegrationRetryJob::where('event_id', $event->event_id)
            ->whereIn('status', ['pending', 'retrying'])->first();
        if ($existing) {
            return response()->json(['message' => 'Replay already queued (idempotent)', 'retry_job_id' => $existing->id], 200);
        }
        $job = IntegrationRetryJob::create([
            'event_id' => $event->event_id, 'source' => $event->source, 'reason' => 'manual_replay',
            'status' => 'pending', 'attempt_no' => 0, 'max_attempts' => 5, 'next_retry_at' => now(),
        ]);
        $event->increment('retry_count');
        $this->integrationAudit('event.replayed', null, after: ['event_id' => $event->event_id]);

        return response()->json(['message' => 'queued', 'retry_job_id' => $job->id], 201);
    }
}
