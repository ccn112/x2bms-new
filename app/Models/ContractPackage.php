<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Gói hạng mục trong hợp đồng. */
class ContractPackage extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['value' => 'decimal:2'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
