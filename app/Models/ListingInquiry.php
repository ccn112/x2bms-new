<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Liên hệ hỏi tin bất động sản. */
class ListingInquiry extends Model
{
    protected $guarded = [];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(RealEstateListing::class, 'real_estate_listing_id');
    }
}
