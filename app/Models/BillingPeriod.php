<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BillingPeriod extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'period_month' => 'date',
        'billed_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'is_current' => 'boolean',
    ];
}
