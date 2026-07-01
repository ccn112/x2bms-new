<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Chỉ số đồng hồ theo kỳ. */
class MeterReading extends Model
{
    protected $guarded = [];

    protected $casts = [
        'previous_reading' => 'decimal:2', 'current_reading' => 'decimal:2',
        'consumption' => 'decimal:2', 'reading_date' => 'date',
    ];

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }
}
