<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 10 — Backup snapshot for rollback. */
class DataFixSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['snapshot_json' => 'array', 'created_at' => 'datetime'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(DataCorrectionRequest::class, 'data_correction_request_id');
    }
}
