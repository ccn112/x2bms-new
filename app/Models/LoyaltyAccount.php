<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Tài khoản điểm thưởng cư dân. */
class LoyaltyAccount extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }
}
