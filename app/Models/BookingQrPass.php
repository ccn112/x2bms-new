<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 2 — Mã QR/pass phát cho lượt đặt tiện ích. */
class BookingQrPass extends Model
{
    protected $guarded = [];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(AmenityBooking::class, 'amenity_booking_id');
    }
}
