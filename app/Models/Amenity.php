<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 2 — Tiện ích nội khu (gym, hồ bơi, BBQ...). Scope tenant/project/building. */
class Amenity extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'requires_approval' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AmenitySlot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(AmenityBooking::class);
    }
}
