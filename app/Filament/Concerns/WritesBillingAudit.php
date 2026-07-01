<?php

namespace App\Filament\Concerns;

use App\Models\BillingAuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Batch 07 — mọi hành động thay đổi trạng thái billing PHẢI ghi billing_audit_logs
 * (actor/tenant/entity/action/before/after/reason). Dùng cho 9 màn billing.
 */
trait WritesBillingAudit
{
    protected function billingAudit(string $action, Model $entity, ?array $before = null, ?array $after = null, ?string $reason = null): void
    {
        $user = auth()->user();

        BillingAuditLog::create([
            'actor_id' => $user?->id,
            'tenant_id' => $entity->tenant_id ?? ($entity->tenant_id ?? null),
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'action' => $action,
            'before_json' => $before,
            'after_json' => $after,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
