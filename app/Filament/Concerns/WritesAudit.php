<?php

namespace App\Filament\Concerns;

use App\Models\AuditLog;

/**
 * Small helper so bespoke /admin pages can write an audit_logs row for the
 * write-actions they expose (create/edit/toggle). Mirrors the inline audit()
 * used by StatementApprovalQueue.
 */
trait WritesAudit
{
    protected function audit(string $action, string $description, ?string $subjectType = null, ?int $subjectId = null): void
    {
        $user = auth()->user();

        AuditLog::create([
            'tenant_id' => $user?->tenant_id,
            'building_id' => $user?->building_id,
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
        ]);
    }
}
