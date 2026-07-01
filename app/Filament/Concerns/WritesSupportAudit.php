<?php

namespace App\Filament\Concerns;

use App\Models\SupportAuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Batch 10 — every sensitive Support Center action MUST write support_audit_logs
 * (data fix, approval/rejection, rollback, ticket closure, escalation, sign-off).
 */
trait WritesSupportAudit
{
    protected function supportAudit(
        string $action,
        ?Model $entity = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?int $tenantId = null,
    ): void {
        SupportAuditLog::create([
            'actor_id' => auth()->id(),
            'tenant_id' => $tenantId ?? ($entity->tenant_id ?? null),
            'entity_type' => $entity ? class_basename($entity) : 'Support',
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
