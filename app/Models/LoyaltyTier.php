<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Hạng loyalty (silver/gold/platinum) + ngưỡng điểm. Dữ liệu tham chiếu (seed). */
class LoyaltyTier extends Model
{
    protected $guarded = [];

    public function benefits(): HasMany
    {
        return $this->hasMany(LoyaltyTierBenefit::class)->orderBy('sort');
    }
}
