<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingRateCard extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'markup_percent' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];
}
