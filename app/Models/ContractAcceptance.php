<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Nghiệm thu hợp đồng/hạng mục. */
class ContractAcceptance extends Model
{
    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'accepted_at' => 'datetime'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ContractPackage::class, 'contract_package_id');
    }
}
