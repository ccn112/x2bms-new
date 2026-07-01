<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Addendum — Module thương mại (M01..M12). Platform-global. */
class Module extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }
}
