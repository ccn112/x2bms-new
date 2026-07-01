<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Tin đăng mua/bán/thuê căn hộ. */
class RealEstateListing extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['price' => 'decimal:2', 'area' => 'decimal:2', 'published_at' => 'datetime'];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'owner_resident_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(ListingInquiry::class);
    }
}
