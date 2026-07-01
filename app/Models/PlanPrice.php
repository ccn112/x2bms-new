<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 07 — Bảng giá theo chu kỳ cho từng plan. */
class PlanPrice extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
