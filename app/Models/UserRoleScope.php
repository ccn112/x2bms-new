<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

/**
 * A grant of one role to a user within a scope (platform|tenant|project|building).
 *
 * Source of truth for the 3-tier RBAC model: Platform → Tenant (công ty vận hành)
 * → Project (ban quản lý dự án). Building is an optional finer scope, not a tier.
 *
 * NOT tenant-scoped (no BelongsToTenant): it is read while resolving the user's own
 * access, before any tenant context exists — global-scoping it would recurse.
 */
class UserRoleScope extends Model
{
    protected $guarded = [];

    public const SCOPE_PLATFORM = 'platform';
    public const SCOPE_TENANT = 'tenant';
    public const SCOPE_PROJECT = 'project';
    public const SCOPE_BUILDING = 'building';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
