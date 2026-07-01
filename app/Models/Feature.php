<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Tính năng thuộc một module, dùng cho feature-gate. */
class Feature extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
