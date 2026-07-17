<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToProject;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    use BelongsToTenant, SoftDeletes, BelongsToProject;

    protected $guarded = [];

    protected $casts = [
        'area_sqm' => 'decimal:2',
        'handover_date' => 'date',
        'handover_price' => 'decimal:2',
        'contract_signed_at' => 'date',
        'documents' => 'array',
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

    public function residents(): BelongsToMany
    {
        return $this->belongsToMany(Resident::class, 'resident_apartment_relations')
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    public function statements(): HasMany
    {
        return $this->hasMany(Statement::class);
    }
}
