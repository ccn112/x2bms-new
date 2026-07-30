<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Tier 2 — Lượt đặt tiện ích của cư dân. */
class AmenityBooking extends Model
{
    use BelongsToTenant, HasAttachments, HasComments, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'booking_date' => 'date',
        'price' => 'decimal:2',
        'approved_at' => 'datetime',
        // Mốc cư dân TỰ huỷ — tách khỏi approved_at vì đó là quyết định của
        // BQL, hai chủ thể khác nhau không nên chung một cột thời gian.
        'cancelled_at' => 'datetime',
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
