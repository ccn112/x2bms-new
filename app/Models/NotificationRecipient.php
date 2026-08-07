<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Người nhận đã resolve + dedupe của 1 chiến dịch (audience snapshot cấp-người-nhận) —
 * BQL-NOTI-08. Scope qua notification cha (đã scope). Delivery thật ở
 * notification_delivery_logs (canonical ledger, ADR-002).
 */
class NotificationRecipient extends Model
{
    protected $guarded = [];

    protected $casts = [
        'audience_reasons' => 'array',
        'channels_planned' => 'array',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }
}
