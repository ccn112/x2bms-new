<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Yêu cầu bảo hành căn hộ. */
class WarrantyRequest extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['reported_at' => 'datetime', 'resolved_at' => 'datetime'];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
