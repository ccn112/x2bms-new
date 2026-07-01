<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 3 — Lượt đi tuần thực tế của bảo vệ. */
class PatrolSession extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];

    public function route(): BelongsTo
    {
        return $this->belongsTo(PatrolRoute::class, 'patrol_route_id');
    }

    public function guardUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guard_id');
    }
}
