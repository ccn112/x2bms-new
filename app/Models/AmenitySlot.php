<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 2 — Khung giờ của tiện ích. */
class AmenitySlot extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function amenity(): BelongsTo
    {
        return $this->belongsTo(Amenity::class);
    }
}
