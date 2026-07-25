<?php

namespace App\Models\Concerns;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Gắn khả năng bình luận (polymorphic) cho một model — dùng chung cho thông báo,
 * bài cộng đồng, phản ánh, ticket… `withCount('comments')` để lấy comment_count.
 */
trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
