<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Đăng ký tham dự sự kiện. */
class EventRegistration extends Model
{
    /** registered|waitlisted|cancelled|checked_in|no_show. */
    public const STATUSES = ['registered', 'waitlisted', 'cancelled', 'checked_in', 'no_show'];

    protected $guarded = [];

    protected $casts = ['checked_in_at' => 'datetime', 'waitlisted_at' => 'datetime'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
