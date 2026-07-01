<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 4 — Gói dịch vụ SaaS (platform-global). */
class SaasPlan extends Model
{
    protected $guarded = [];

    protected $casts = ['price_monthly' => 'decimal:2', 'price_yearly' => 'decimal:2'];

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }
}
