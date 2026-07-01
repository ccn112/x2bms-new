<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Sự kiện cảm biến nhà thông minh. */
class SensorEvent extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['event_at' => 'datetime'];

    public function device(): BelongsTo
    {
        return $this->belongsTo(SmartDevice::class, 'smart_device_id');
    }
}
