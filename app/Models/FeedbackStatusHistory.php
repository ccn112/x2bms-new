<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 2 — Lịch sử chuyển trạng thái phản ánh (audit vòng đời). */
class FeedbackStatusHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(FeedbackRequest::class, 'feedback_request_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
