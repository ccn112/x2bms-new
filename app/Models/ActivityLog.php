<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Tier 1 — Feed hoạt động chung (C9, cạnh audit_logs bắt buộc). */
class ActivityLog extends Model
{
    protected $guarded = [];

    protected $casts = ['properties' => 'array'];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
