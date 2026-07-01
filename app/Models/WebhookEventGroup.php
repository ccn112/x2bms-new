<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 08 — nhóm sự kiện webhook (payment/notification/work_order/...). */
class WebhookEventGroup extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function endpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class, 'event_group_id');
    }
}
