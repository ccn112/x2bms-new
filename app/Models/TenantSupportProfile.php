<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 10 — Tenant support profile (plan, health, csat, VIP notes). */
class TenantSupportProfile extends Model
{
    protected $guarded = [];

    protected $casts = ['health_score' => 'decimal:2', 'csat' => 'decimal:2'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(TenantSupportContact::class, 'tenant_id', 'tenant_id');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(SupportEntitlement::class, 'tenant_id', 'tenant_id');
    }
}
