<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Quyền tính năng hiệu lực của tenant (plan|add_on|manual_override|trial). */
class TenantEntitlement extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean', 'limits_json' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
