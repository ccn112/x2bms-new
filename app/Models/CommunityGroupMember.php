<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Thành viên nhóm cộng đồng (join/leave + tính `joined`). */
class CommunityGroupMember extends Model
{
    protected $guarded = [];

    protected $casts = ['joined_at' => 'datetime'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CommunityGroup::class, 'community_group_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
