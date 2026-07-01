<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Tier 2 — Lượt đặt tiện ích của cư dân. */
class AmenityBooking extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'booking_date' => 'date',
        'price' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function amenity(): BelongsTo
    {
        return $this->belongsTo(Amenity::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AmenitySlot::class, 'amenity_slot_id');
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function qrPass(): HasOne
    {
        return $this->hasOne(BookingQrPass::class);
    }
}
