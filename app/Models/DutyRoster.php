<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 3 — Lịch phân ca (ai trực ca nào ngày nào). */
class DutyRoster extends Model
{
    protected $guarded = [];

    protected $casts = ['duty_date' => 'date'];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
