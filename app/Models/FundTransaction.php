<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 3 — Giao dịch thu/chi của quỹ. */
class FundTransaction extends Model
{
    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'balance_after' => 'decimal:2', 'transaction_date' => 'date'];

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function cashVoucher(): BelongsTo
    {
        return $this->belongsTo(CashVoucher::class);
    }
}
