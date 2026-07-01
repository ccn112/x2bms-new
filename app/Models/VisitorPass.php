<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 2 — Pass/QR khách đã phát hành (C12). */
class VisitorPass extends Model
{
    protected $guarded = [];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(VisitorRegistration::class, 'visitor_registration_id');
    }
}
