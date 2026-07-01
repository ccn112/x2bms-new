<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Batch 08 — API key (client_id + secret hash, scopes, rate limit, IP allowlist). */
class IntegrationApiKey extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['secret_hash'];

    protected $casts = [
        'allowed_ips_json' => 'array',
        'metadata_json' => 'array',
        'expires_at' => 'date',
        'last_used_at' => 'datetime',
        'require_hmac' => 'boolean',
        'require_ip_allowlist' => 'boolean',
    ];

    public function scopes(): HasMany
    {
        return $this->hasMany(IntegrationApiKeyScope::class, 'api_key_id');
    }

    public function rotations(): HasMany
    {
        return $this->hasMany(IntegrationApiKeyRotation::class, 'api_key_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
