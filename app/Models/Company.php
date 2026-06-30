<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
