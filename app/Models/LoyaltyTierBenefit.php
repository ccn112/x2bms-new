<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Quyền lợi theo hạng loyalty (icon/title/subtitle). */
class LoyaltyTierBenefit extends Model
{
    protected $guarded = [];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'loyalty_tier_id');
    }
}
