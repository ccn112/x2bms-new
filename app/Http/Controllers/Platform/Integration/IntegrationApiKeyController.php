<?php

namespace App\Http\Controllers\Platform\Integration;

use App\Filament\Concerns\WritesIntegrationAudit;
use App\Http\Controllers\Controller;
use App\Models\IntegrationApiKey;
use App\Models\IntegrationApiKeyRotation;
use App\Models\IntegrationApiKeyScope;
use App\Support\Integration\IntegrationSecret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 08 — API key API (create with scopes, rotate/revoke/suspend/resume). */
class IntegrationApiKeyController extends Controller
{
    use WritesIntegrationAudit;

    public function index(Request $request): JsonResponse
    {
        return response()->json(IntegrationApiKey::withCount('scopes')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate((int) $request->get('per_page', 20)));
    }

    public function show(IntegrationApiKey $apiKey): JsonResponse
    {
        return response()->json($apiKey->load('scopes', 'rotations'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150', 'environment' => 'required|in:sandbox,staging,production',
            'scopes' => 'array', 'scopes.*' => 'string', 'rate_limit_per_minute' => 'integer|min:1',
            'require_hmac' => 'boolean', 'require_ip_allowlist' => 'boolean', 'allowed_ips' => 'array',
        ]);
        $secret = app(IntegrationSecret::class);
        $plain = $secret->generateApiSecret();
        $key = IntegrationApiKey::create([
            'name' => $data['name'], 'client_id' => $secret->generateClientId(), 'secret_hash' => $secret->hash($plain),
            'environment' => $data['environment'], 'status' => 'active', 'owner_user_id' => auth()->id(),
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? 600,
            'require_hmac' => $data['require_hmac'] ?? false, 'require_ip_allowlist' => $data['require_ip_allowlist'] ?? false,
            'allowed_ips_json' => $data['allowed_ips'] ?? null, 'metadata_json' => ['secret_masked' => $secret->mask($plain)],
        ]);
        foreach (($data['scopes'] ?? []) as $sc) {
            [$res, $lvl] = array_pad(explode(':', $sc), 2, 'read');
            IntegrationApiKeyScope::create(['api_key_id' => $key->id, 'scope_code' => $sc, 'scope_name' => $sc, 'permission_level' => $lvl]);
        }
        $this->integrationAudit('api_key.created', $key, after: ['client_id' => $key->client_id]);

        // client secret returned ONCE.
        return response()->json(['api_key' => $key->fresh('scopes'), 'client_id' => $key->client_id, 'secret' => $plain], 201);
    }

    public function rotate(IntegrationApiKey $apiKey): JsonResponse
    {
        $secret = app(IntegrationSecret::class);
        $plain = $secret->generateApiSecret();
        $old = $apiKey->secret_hash;
        $apiKey->update(['secret_hash' => $secret->hash($plain), 'metadata_json' => array_merge($apiKey->metadata_json ?? [], ['secret_masked' => $secret->mask($plain)])]);
        IntegrationApiKeyRotation::create([
            'api_key_id' => $apiKey->id, 'old_secret_hash' => $old, 'new_secret_hash' => $apiKey->secret_hash,
            'rotated_by' => auth()->id(), 'rotated_at' => now(), 'reason' => request('reason', 'api rotation'), 'created_at' => now(),
        ]);
        $this->integrationAudit('api_key.rotated', $apiKey);

        return response()->json(['secret' => $plain]);
    }

    public function revoke(Request $request, IntegrationApiKey $apiKey): JsonResponse
    {
        $apiKey->update(['status' => 'revoked']);
        $this->integrationAudit('api_key.revoked', $apiKey, reason: $request->reason);

        return response()->json($apiKey->fresh());
    }

    public function suspend(IntegrationApiKey $apiKey): JsonResponse
    {
        $apiKey->update(['status' => 'suspended']);
        $this->integrationAudit('api_key.suspended', $apiKey);

        return response()->json($apiKey->fresh());
    }

    public function resume(IntegrationApiKey $apiKey): JsonResponse
    {
        $apiKey->update(['status' => 'active']);
        $this->integrationAudit('api_key.resumed', $apiKey);

        return response()->json($apiKey->fresh());
    }
}
