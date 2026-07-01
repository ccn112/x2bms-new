<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — KPI đánh giá nhà thầu theo kỳ. */
class ContractorKpi extends Model
{
    protected $guarded = [];

    protected $casts = ['score' => 'decimal:2', 'on_time_rate' => 'decimal:2', 'quality_score' => 'decimal:2'];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }
}
