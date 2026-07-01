<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Pivot gói ↔ tính năng (bật/tắt + hạn mức). */
class PlanFeature extends Model
{
    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean', 'limits_json' => 'array'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
