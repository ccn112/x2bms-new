<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Resident extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class, 'resident_apartment_relations')
            ->withPivot(['role', 'is_primary', 'start_date'])
            ->withTimestamps();
    }
}
