<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 2 — Đối tượng nhận thông báo (C5): scope_type all|tenant|project|building|apartment|role|resident|user. */
class NotificationAudience extends Model
{
    protected $guarded = [];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
