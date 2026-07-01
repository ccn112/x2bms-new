<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 2 — Kênh gửi thông báo (app|email|sms|zalo|push). */
class NotificationChannel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
