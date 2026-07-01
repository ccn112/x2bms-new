<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 07 — Giao dịch ví pass-through (top_up/deduct/refund). */
class PassThroughTransaction extends Model
{
    protected $guarded = [];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PassThroughWallet::class, 'wallet_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
