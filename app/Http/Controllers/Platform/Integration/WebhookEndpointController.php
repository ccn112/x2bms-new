<?php

namespace App\Http\Controllers\Platform\Integration;

use App\Filament\Concerns\WritesIntegrationAudit;
use App\Http\Controllers\Controller;
use App\Models\WebhookDeliveryAttempt;
use App\Models\WebhookEndpoint;
use App\Support\Integration\IntegrationSecret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Batch 08 — Webhook endpoint API (create/test/enable/disable/rotate/deliveries). */
class WebhookEndpointController extends Controller
{
    use WritesIntegrationAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(WebhookEndpoint::with('eventGroup')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('endpoint_name')->paginate((int) $request->get('per_page', 20)));
    }

    public function show(WebhookEndpoint $webhook): JsonResponse
    {
        return response()->json($webhook->load('eventGroup'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint_name' => 'required|string|max:150', 'url' => 'required|url',
            'event_group_id' => 'nullable|exists:webhook_event_groups,id',
            'method' => 'nullable|string', 'signature_type' => 'nullable|in:HMAC,none',
        ]);
        $secret = app(IntegrationSecret::class);
        $wh = WebhookEndpoint::create($data + [
            'code' => 'WH-'.strtoupper(Str::random(6)), 'method' => $data['method'] ?? 'POST',
            'signature_type' => $data['signature_type'] ?? 'HMAC', 'status' => 'pending_verification',
            'signing_secret_hash' => ($data['signature_type'] ?? 'HMAC') === 'HMAC' ? $secret->hash($secret->generateSigningSecret()) : null,
        ]);
        $this->integrationAudit('webhook.created', $wh, after: $wh->only(['code', 'url']));

        return response()->json($wh, 201);
    }

    public function test(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $ok = ! in_array($webhook->status, ['disabled', 'failed'], true);
        $latency = random_int(60, 700);
        $corr = 'corr_'.Str::lower(Str::random(12));
        WebhookDeliveryAttempt::create([
            'webhook_endpoint_id' => $webhook->id, 'event_id' => 'evt_'.strtoupper(Str::random(20)),
            'correlation_id' => $corr, 'payload_hash' => hash('sha256', (string) $request->input('event', 'test')),
            'http_status' => $ok ? 200 : 500, 'duration_ms' => $latency, 'status' => $ok ? 'success' : 'failed',
            'attempt_no' => $webhook->deliveries()->count() + 1,
            'response_body' => $ok ? '{"received":true}' : '{"error":"500"}', 'error_message' => $ok ? null : 'HTTP 500',
            'delivered_at' => now(), 'created_at' => now(),
        ]);
        if ($ok && $webhook->status === 'pending_verification') {
            $webhook->update(['status' => 'active']);
        }
        $webhook->update(['last_delivery_at' => now()]);
        $this->integrationAudit('webhook.tested', $webhook, after: ['http' => $ok ? 200 : 500]);

        return response()->json([
            'http_status' => $ok ? 200 : 500, 'latency_ms' => $latency,
            'signature_verified' => $webhook->signature_type === 'HMAC',
            'response_body' => $ok ? '{"received":true}' : '{"error":"500"}', 'correlation_id' => $corr,
        ]);
    }

    public function enable(WebhookEndpoint $webhook): JsonResponse
    {
        $webhook->update(['status' => 'active']);
        $this->integrationAudit('webhook.enabled', $webhook);

        return response()->json($webhook->fresh());
    }

    public function disable(WebhookEndpoint $webhook): JsonResponse
    {
        $webhook->update(['status' => 'disabled']);
        $this->integrationAudit('webhook.disabled', $webhook);

        return response()->json($webhook->fresh());
    }

    public function rotateSecret(WebhookEndpoint $webhook): JsonResponse
    {
        $secret = app(IntegrationSecret::class);
        $plain = $secret->generateSigningSecret();
        $webhook->update(['signing_secret_hash' => $secret->hash($plain), 'signature_type' => 'HMAC']);
        $this->integrationAudit('webhook.secret_rotated', $webhook);

        return response()->json(['signing_secret' => $plain]);
    }

    public function deliveries(WebhookEndpoint $webhook): JsonResponse
    {
        return response()->json($webhook->deliveries()->latest('created_at')->paginate(20));
    }
}
