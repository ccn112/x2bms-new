<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Quyết toán/thanh toán nhà thầu. */
class ContractorSettlement extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'settled_at' => 'datetime'];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
