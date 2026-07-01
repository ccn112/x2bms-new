<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Chỉ số tiêu thụ điện năng (nhà thông minh). */
class EnergyReading extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['kwh' => 'decimal:2', 'cost' => 'decimal:2', 'reading_date' => 'date'];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }
}
