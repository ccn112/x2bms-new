<?php

namespace App\Http\Controllers\Platform\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationAuditLog;
use App\Models\IntegrationConnection;
use App\Models\IntegrationEvent;
use App\Models\IntegrationIncident;
use App\Models\IntegrationRetryJob;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 08 — Overview dashboard + audit log API. */
class IntegrationOverviewController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'connections' => [
                'total' => IntegrationConnection::count(),
                'active' => IntegrationConnection::where('status', 'active')->count(),
                'warning' => IntegrationConnection::where('status', 'warning')->count(),
                'incident' => IntegrationConnection::where('status', 'incident')->count(),
            ],
            'avg_success_rate' => round((float) IntegrationConnection::whereNotNull('success_rate_24h')->avg('success_rate_24h'), 2),
            'webhook_success_rate' => round((float) WebhookEndpoint::whereNotNull('success_rate')->avg('success_rate'), 2),
            'events_by_status' => IntegrationEvent::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'retry_queue' => IntegrationRetryJob::whereIn('status', ['pending', 'retrying'])->count(),
            'open_incidents' => IntegrationIncident::whereIn('status', ['open', 'investigating'])->count(),
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        return response()->json(IntegrationAuditLog::with('actor')
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->when($request->entity_type, fn ($q, $e) => $q->where('entity_type', $e))
            ->latest('created_at')->paginate((int) $request->get('per_page', 25)));
    }
}
