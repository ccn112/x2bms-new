<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Row-level multi-tenancy (single DB). Adds a global scope on `tenant_id`
 * resolved from the authenticated user, and auto-fills tenant_id on create.
 *
 * No-op in console (migrations/seeders) and when no tenant context exists,
 * so seeding stays unscoped and login/landing pages work before auth.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::currentTenantId();
            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id) && ($tenantId = static::currentTenantId()) !== null) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    protected static function currentTenantId(): ?int
    {
        // Seeders/migrations/queue run in console → leave unscoped.
        if (app()->runningInConsole()) {
            return null;
        }

        return auth()->user()?->tenant_id;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
