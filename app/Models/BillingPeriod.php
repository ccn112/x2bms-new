<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToProject;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BillingPeriod extends Model
{
    use BelongsToTenant, SoftDeletes, BelongsToProject;

    protected $guarded = [];

    protected $casts = [
        'period_month' => 'date',
        'billed_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'is_current' => 'boolean',
    ];
}
