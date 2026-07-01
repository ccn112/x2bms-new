<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRunItem extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function billingRun(): BelongsTo
    {
        return $this->belongsTo(BillingRun::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(Statement::class);
    }
}
