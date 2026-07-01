<?php

namespace App\Filament\Concerns;

use App\Models\IntegrationAuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Batch 08 — every state-changing Integration Center action MUST write
 * integration_audit_logs (actor/entity/action/before/after/reason/ip/ua).
 * Used by all 10 bespoke pages and the API controllers.
 */
trait WritesIntegrationAudit
{
    protected function integrationAudit(
        string $action,
        ?Model $entity = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?int $connectionId = null,
        ?int $tenantId = null,
    ): void {
        IntegrationAuditLog::create([
            'actor_id' => auth()->id(),
            'tenant_id' => $tenantId,
            'connection_id' => $connectionId ?? ($entity instanceof \App\Models\IntegrationConnection ? $entity->getKey() : null),
            'entity_type' => $entity ? class_basename($entity) : 'Integration',
            'entity_id' => $entity?->getKey(),
            'action' => $action,
            'before_json' => $before,
            'after_json' => $after,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'created_at' => now(),
        ]);
    }
}
