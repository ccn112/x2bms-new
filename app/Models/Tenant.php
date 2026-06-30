<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'app_config' => 'array',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
