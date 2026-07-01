<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Giao dịch tích/tiêu điểm. */
class LoyaltyTransaction extends Model
{
    protected $guarded = [];

    protected $casts = ['transacted_at' => 'datetime'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class, 'loyalty_account_id');
    }
}
