<?php

namespace App\Http\Controllers\Platform\Integration;

use App\Filament\Concerns\WritesIntegrationAudit;
use App\Http\Controllers\Controller;
use App\Models\IntegrationApiKey;
use App\Models\IntegrationConnection;
use App\Models\IntegrationSecurityPolicy;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Batch 08 — Security settings + audit log API. */
class IntegrationSecurityController extends Controller
{
    use WritesIntegrationAudit;

    public function show(): JsonResponse
    {
        return response()->json(IntegrationSecurityPolicy::orderBy('policy_key')->get());
    }

    public function update(Request $request): JsonResponse
    {
        foreach ((array) $request->input('policies', []) as $key => $value) {
            IntegrationSecurityPolicy::updateOrCreate(['policy_key' => $key],
                ['policy_value_json' => $value, 'is_enabled' => true, 'updated_by' => auth()->id()]);
        }
        $this->integrationAudit('security.settings_updated', null, after: $request->input('policies', []));

        return response()->json(IntegrationSecurityPolicy::orderBy('policy_key')->get());
    }

    public function enforceHmac(): JsonResponse
    {
        IntegrationSecurityPolicy::updateOrCreate(['policy_key' => 'hmac_signature_enforcement'],
            ['policy_value_json' => ['enforced' => true], 'is_enabled' => true, 'updated_by' => auth()->id()]);
        $flagged = WebhookEndpoint::where('signature_type', 'none')->count();
        $this->integrationAudit('security.enforce_hmac', null, after: ['flagged_unsigned' => $flagged]);

        return response()->json(['enforced' => true, 'flagged_unsigned' => $flagged]);
    }

    public function emergencyDisable(Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);
        if (! auth()->user()?->isPlatformAdmin()) {
            return response()->json(['message' => 'Requires platform admin'], 403);
        }
        IntegrationConnection::whereNot('status', 'disabled')->update(['status' => 'disabled']);
        IntegrationApiKey::where('status', 'active')->update(['status' => 'suspended']);
        WebhookEndpoint::whereNot('status', 'disabled')->update(['status' => 'disabled']);
        IntegrationSecurityPolicy::updateOrCreate(['policy_key' => 'emergency_disable_switch'],
            ['policy_value_json' => ['enabled' => true, 'at' => now()->toDateTimeString()], 'is_enabled' => true, 'updated_by' => auth()->id()]);
        $this->integrationAudit('security.emergency_disable', null, reason: $request->reason);

        return response()->json(['message' => 'All integrations disabled']);
    }
}
