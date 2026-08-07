<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Snapshot bất biến của chiến dịch (spec 06 §7). const UPDATED_AT=null → chỉ created_at. */
class NotificationSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
        'audience' => 'array',
        'channels' => 'array',
        'approval' => 'array',
        'created_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
