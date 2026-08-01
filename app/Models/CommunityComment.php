<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * GĐ7 — bình luận cộng đồng (bảng chuyên dụng `community_comments`, KHÔNG
 * polymorphic). Ảnh đính kèm vẫn dùng bảng `attachments` polymorphic chung qua
 * [HasAttachments]. 2 cấp: `parent_id` null = gốc.
 */
class CommunityComment extends Model
{
    use HasAttachments, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_staff' => 'boolean',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
