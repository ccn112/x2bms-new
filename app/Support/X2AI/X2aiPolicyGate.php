<?php

namespace App\Support\X2AI;

use App\Models\AiPolicy;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * WEB-UX-09 governance gate. Decides — from RBAC permissions and the active
 * ai_policies rows (not hardcoded) — whether a user may use the copilot, which
 * mode they get, the risk level of a turn, and whether human approval is required.
 *
 * Permissions: `ai.use` (use copilot at all), `ai.data_lookup` (Mode 2 DB lookup).
 * super_admin bypasses everything via Gate::before.
 */
class X2aiPolicyGate
{
    /** May the user use the copilot at all? Defaults open if the permission is unseeded. */
    public function canUse(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        try {
            return $user->can('ai.use');
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * May the user run Mode 2 (real DB lookup tool)? Needs the ai.data_lookup
     * permission AND a configured lookup API — until X2AI_DATA_API_URL is set the
     * tool would only hit a stub, so we stay in context mode. Defaults closed.
     */
    public function dataLookupAllowed(?User $user): bool
    {
        if (! $user || empty(config('services.x2ai.data_api.url'))) {
            return false;
        }

        try {
            return $user->can('ai.data_lookup');
        } catch (\Throwable) {
            return false;
        }
    }

    /** Mode is now permission-driven (no user toggle): data if allowed, else context. */
    public function effectiveMode(?User $user): string
    {
        return $this->dataLookupAllowed($user) ? 'data' : 'context';
    }

    /** @return Collection<int, AiPolicy> tenant-scoped active policies */
    public function activePolicies(): Collection
    {
        return AiPolicy::where('status', 'active')->get();
    }

    /** Base risk for a turn. Read-only chat = low; DB lookup = medium. */
    public function riskFor(string $mode): string
    {
        return $mode === 'data' ? 'medium' : 'low';
    }

    /**
     * A high-risk turn needs human approval when an active "risk/high" policy
     * (e.g. "Hành động rủi ro cao cần người duyệt") is in force.
     */
    public function requiresApproval(string $risk): bool
    {
        if ($risk !== 'high') {
            return false;
        }

        return $this->activePolicies()
            ->contains(fn (AiPolicy $p) => $p->category === 'risk' && $p->risk_level === 'high');
    }

    /** Active policies as constraint lines injected into the system prompt. */
    public function guidelines(): array
    {
        return $this->activePolicies()
            ->map(fn (AiPolicy $p) => trim($p->name.($p->description ? ' — '.$p->description : '')))
            ->all();
    }
}
