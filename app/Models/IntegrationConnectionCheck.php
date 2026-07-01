<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 08 — lịch sử test connection (append-only log). */
class IntegrationConnectionCheck extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'checked_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class, 'connection_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
