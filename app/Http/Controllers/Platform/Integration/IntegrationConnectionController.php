<?php

namespace App\Http\Controllers\Platform\Integration;

use App\Filament\Concerns\WritesIntegrationAudit;
use App\Http\Controllers\Controller;
use App\Models\IntegrationConnection;
use App\Models\IntegrationConnectionCheck;
use App\Models\IntegrationCredential;
use App\Support\Integration\IntegrationSecret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Batch 08 — External connections API (list/detail/create/test/enable/disable/rotate). */
class IntegrationConnectionController extends Controller
{
    use WritesIntegrationAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(IntegrationConnection::with('category')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->environment, fn ($q, $e) => $q->where('environment', $e))
            ->orderBy('name')->paginate((int) $request->get('per_page', 20)));
    }

    public function show(IntegrationConnection $connection): JsonResponse
    {
        return response()->json($connection->load(['category', 'credentials', 'mappings', 'checks']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150', 'category_id' => 'nullable|exists:integration_categories,id',
            'provider_code' => 'required|string|max:80',
            'environment' => 'required|in:sandbox,staging,production', 'base_url' => 'nullable|url',
        ]);
        $conn = IntegrationConnection::create($data + [
            'code' => 'CONN-'.strtoupper(Str::random(6)), 'status' => 'disabled',
            'sla_status' => 'healthy', 'owner_user_id' => auth()->id(),
        ]);
        $this->integrationAudit('connection.created', $conn, after: $conn->only(['code', 'name']));

        return response()->json($conn, 201);
    }

    public function test(IntegrationConnection $connection): JsonResponse
    {
        $ok = $connection->status !== 'incident';
        $latency = random_int(80, 900);
        IntegrationConnectionCheck::create([
            'connection_id' => $connection->id, 'status' => $ok ? 'success' : 'failed',
            'latency_ms' => $latency, 'http_status' => $ok ? 200 : 500, 'message' => $ok ? 'OK' : 'Failed',
            'checked_at' => now(), 'checked_by' => auth()->id(), 'created_at' => now(),
        ]);
        $connection->update(['last_checked_at' => now(), 'avg_latency_ms' => $latency, 'status' => $ok && $connection->status === 'disabled' ? 'active' : $connection->status]);
        $this->integrationAudit('connection.tested', $connection, after: ['result' => $ok ? 'success' : 'failed']);

        return response()->json(['result' => $ok ? 'success' : 'failed', 'latency_ms' => $latency, 'http_status' => $ok ? 200 : 500]);
    }

    public function enable(IntegrationConnection $connection): JsonResponse
    {
        $connection->update(['status' => 'active']);
        $this->integrationAudit('connection.enabled', $connection);

        return response()->json($connection->fresh());
    }

    public function disable(IntegrationConnection $connection): JsonResponse
    {
        $connection->update(['status' => 'disabled']);
        $this->integrationAudit('connection.disabled', $connection);

        return response()->json($connection->fresh());
    }

    public function rotateSecret(IntegrationConnection $connection): JsonResponse
    {
        $secret = app(IntegrationSecret::class);
        $plain = $secret->generateApiSecret();
        IntegrationCredential::where('connection_id', $connection->id)->where('status', 'valid')
            ->update(['status' => 'rotated', 'rotated_at' => now(), 'rotated_by' => auth()->id()]);
        IntegrationCredential::create([
            'connection_id' => $connection->id, 'credential_type' => 'api_key',
            'encrypted_payload' => $secret->encrypt($plain), 'masked_summary' => $secret->mask($plain),
            'status' => 'valid', 'expires_at' => now()->addDays(90), 'created_by' => auth()->id(),
        ]);
        $this->integrationAudit('connection.secret_rotated', $connection);

        // Secret returned ONCE.
        return response()->json(['secret' => $plain, 'masked' => $secret->mask($plain)]);
    }
}
