<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chuông targeted (module notifications-multichannel, N0). Mỗi dòng nhắm MỘT người
 * (`recipient_user_id`) — đây là biên bảo mật: query cư dân LUÔN lọc theo cột này,
 * KHÔNG dựa tenant global scope (cư dân tenant_id = NULL). Vì vậy model không dùng
 * BelongsToTenant; scope tường minh ở service.
 */
class ActivityNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
        'coalesce_count' => 'integer',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'announcement_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
