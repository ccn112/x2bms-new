<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 10 — Before/after diff item for a data fix. */
class DataFixDiffItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function request(): BelongsTo
    {
        return $this->belongsTo(DataCorrectionRequest::class, 'data_correction_request_id');
    }
}
