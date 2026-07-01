<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 08 — scope gán cho một API key (pivot, không timestamps). */
class IntegrationApiKeyScope extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(IntegrationApiKey::class, 'api_key_id');
    }
}
