<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanChangeRequestItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'effective_date' => 'date',
        'amount_delta' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PlanChangeRequest::class, 'plan_change_request_id');
    }
}
