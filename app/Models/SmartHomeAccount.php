<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Tài khoản nhà thông minh của cư dân. */
class SmartHomeAccount extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['linked_at' => 'datetime'];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(SmartDevice::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(SmartScene::class);
    }
}
