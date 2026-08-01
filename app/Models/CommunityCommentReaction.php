<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** GĐ7 — cảm xúc trên một bình luận cộng đồng. Mã cảm xúc dùng chung với post. */
class CommunityCommentReaction extends Model
{
    protected $guarded = [];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(CommunityComment::class, 'community_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
