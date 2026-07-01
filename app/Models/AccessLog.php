<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 2 — Nhật ký ra/vào (cổng, thẻ, QR, khuôn mặt). */
class AccessLog extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'event_at' => 'datetime',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function accessCard(): BelongsTo
    {
        return $this->belongsTo(AccessCard::class);
    }
}
