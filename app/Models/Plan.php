<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Addendum — Gói SaaS (popular|full|intelligent). */
class Plan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'monthly_base_price' => 'decimal:2',
        'yearly_base_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')
            ->withPivot(['enabled', 'limits_json'])->withTimestamps();
    }
}
