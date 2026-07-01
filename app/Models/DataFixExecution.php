<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 10 — Data fix execution record. */
class DataFixExecution extends Model
{
    protected $guarded = [];

    protected $casts = ['executed_at' => 'datetime'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(DataCorrectionRequest::class, 'data_correction_request_id');
    }
}
