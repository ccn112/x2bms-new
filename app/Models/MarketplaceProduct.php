<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Sản phẩm rao bán trong marketplace nội khu. */
class MarketplaceProduct extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['price' => 'decimal:2'];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'seller_resident_id');
    }
}
