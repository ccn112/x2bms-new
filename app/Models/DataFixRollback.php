<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 10 — Data fix rollback (restore snapshot). */
class DataFixRollback extends Model
{
    protected $guarded = [];

    protected $casts = ['rolled_back_at' => 'datetime'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(DataCorrectionRequest::class, 'data_correction_request_id');
    }
}
