<?php

namespace App\Models;

use App\Enums\VehicleType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'type' => VehicleType::class,
        'monthly_fee' => 'decimal:2',
        'valid_to' => 'date',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
