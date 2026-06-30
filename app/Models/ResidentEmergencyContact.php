<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentEmergencyContact extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
