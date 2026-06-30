<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatementLine extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(Statement::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }
}
