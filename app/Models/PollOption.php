<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Lựa chọn trong poll. */
class PollOption extends Model
{
    protected $guarded = [];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }
}
