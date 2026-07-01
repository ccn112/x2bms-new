<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 08 — lần gửi webhook (append-only log, có correlation_id). */
class WebhookDeliveryAttempt extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
