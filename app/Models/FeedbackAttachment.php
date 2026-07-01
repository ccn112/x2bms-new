<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 2 — Tệp đính kèm của phản ánh (ảnh hiện trường...). */
class FeedbackAttachment extends Model
{
    protected $guarded = [];

    public function request(): BelongsTo
    {
        return $this->belongsTo(FeedbackRequest::class, 'feedback_request_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(FeedbackComment::class, 'feedback_comment_id');
    }
}
