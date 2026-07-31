<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_group_id');
    }

    public function verificationHistory(): HasMany
    {
        return $this->hasMany(CommunityGroupVerificationHistory::class);
    }
}
