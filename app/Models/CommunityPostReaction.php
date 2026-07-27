<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cảm xúc trên bài cộng đồng — MỘT người MỘT cảm xúc trên một bài
 * (unique post+user), đổi emoji là UPDATE chứ không thêm dòng.
 *
 * Lưu MÃ chứ không lưu ký tự emoji: đổi bộ icon ở app không phải migrate data.
 */
class CommunityPostReaction extends Model
{
    protected $guarded = [];

    /** Bộ mã hợp lệ — app map sang 👍❤️😆😮😢😡. */
    public const CODES = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
