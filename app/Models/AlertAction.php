<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 3 — Hành động xử lý trên cảnh báo IOC (C10). */
class AlertAction extends Model
{
    protected $guarded = [];

    protected $casts = ['acted_at' => 'datetime'];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(IocAlert::class, 'ioc_alert_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
