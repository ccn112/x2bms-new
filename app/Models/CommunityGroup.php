<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Nhóm cộng đồng cư dân. */
class CommunityGroup extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }
}
