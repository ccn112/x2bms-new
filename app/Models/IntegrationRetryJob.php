<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 08 — hàng đợi retry cho event/webhook thất bại. */
class IntegrationRetryJob extends Model
{
    protected $guarded = [];

    protected $casts = ['next_retry_at' => 'datetime'];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
