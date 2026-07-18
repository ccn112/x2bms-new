<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A registered mobile install + its push token. See migration create_mobile_devices_table. */
class MobileDevice extends Model
{
    protected $guarded = [];

    protected $hidden = ['push_token'];

    protected function casts(): array
    {
        return [
            'push_token' => 'encrypted',
            'metadata' => 'array',
            'last_seen_at' => 'datetime',
            'token_refreshed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
