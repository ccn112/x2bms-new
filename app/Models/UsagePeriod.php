<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 07 — Kỳ đo usage (open→calculating→locked). */
class UsagePeriod extends Model
{
    protected $guarded = [];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'locked_at' => 'datetime'];

    public function records(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }
}
