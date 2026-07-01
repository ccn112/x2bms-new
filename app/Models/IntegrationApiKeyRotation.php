<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 08 — lịch sử rotate secret của API key (append-only). */
class IntegrationApiKeyRotation extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $hidden = ['old_secret_hash', 'new_secret_hash'];

    protected $casts = [
        'rotated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(IntegrationApiKey::class, 'api_key_id');
    }
}
