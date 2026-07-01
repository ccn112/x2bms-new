<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 10 — Record affected by a data correction request. */
class DataCorrectionAffectedRecord extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function request(): BelongsTo
    {
        return $this->belongsTo(DataCorrectionRequest::class, 'data_correction_request_id');
    }
}
