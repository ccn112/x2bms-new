<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 3 — Điểm chốt trên tuyến tuần tra (quét QR). */
class PatrolCheckpoint extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function route(): BelongsTo
    {
        return $this->belongsTo(PatrolRoute::class, 'patrol_route_id');
    }
}
