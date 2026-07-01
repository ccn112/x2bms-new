<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Tier 4 — Bật/tắt module theo tenant (white-label / gói). */
class TenantModule extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean', 'config' => 'array'];
}
