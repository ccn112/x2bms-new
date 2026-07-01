<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 07 — Bản ghi usage theo tenant/meter/kỳ (+overage). */
class UsageRecord extends Model
{
    protected $guarded = [];

    protected $casts = ['metadata_json' => 'array'];

    public function period(): BelongsTo
    {
        return $this->belongsTo(UsagePeriod::class, 'usage_period_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
