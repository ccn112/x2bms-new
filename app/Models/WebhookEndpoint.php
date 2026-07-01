<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Batch 08 — webhook endpoint (URL, event group, HMAC, retry policy). */
class WebhookEndpoint extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['signing_secret_hash'];

    protected $casts = [
        'metadata_json' => 'array',
        'last_delivery_at' => 'datetime',
        'success_rate' => 'decimal:2',
    ];

    public function eventGroup(): BelongsTo
    {
        return $this->belongsTo(WebhookEventGroup::class, 'event_group_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDeliveryAttempt::class, 'webhook_endpoint_id');
    }
}
