<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Dòng sản phẩm trong đơn marketplace. */
class OrderItem extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['price' => 'decimal:2', 'amount' => 'decimal:2'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }
}
