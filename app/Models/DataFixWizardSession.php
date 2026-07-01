<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 10 — Controlled data fix wizard session (step state). */
class DataFixWizardSession extends Model
{
    protected $guarded = [];

    protected $casts = ['state_json' => 'array'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(DataCorrectionRequest::class, 'data_correction_request_id');
    }
}
