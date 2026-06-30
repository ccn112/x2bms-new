<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'area_sqm' => 'decimal:2',
        'handover_date' => 'date',
    ];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApartmentStatusHistory::class);
    }
}
