<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Bài đăng cộng đồng. */
class CommunityPost extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_important' => 'boolean',
        'image_paths' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CommunityGroup::class, 'community_group_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'author_resident_id');
    }
}
