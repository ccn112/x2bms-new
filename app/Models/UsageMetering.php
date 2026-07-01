<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Đo lường sử dụng để tính phí SaaS (alias billing_usage). */
class UsageMetering extends Model
{
    use BelongsToTenant;

    protected $table = 'usage_metering';

    protected $guarded = [];

    protected $casts = ['quantity' => 'decimal:2', 'recorded_at' => 'datetime'];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
