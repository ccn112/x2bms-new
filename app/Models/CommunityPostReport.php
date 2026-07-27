<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cư dân báo cáo bài cộng đồng. Vì KHÔNG duyệt trước, đây là đầu vào chính của
 * hậu kiểm — màn kiểm duyệt BQL xếp bài theo `report_count` giảm dần.
 * Một người chỉ report một bài một lần (unique post+user).
 */
class CommunityPostReport extends Model
{
    protected $guarded = [];

    protected $casts = ['resolved_at' => 'datetime'];

    /** Lý do hợp lệ, khớp bảng chọn trong app. */
    public const REASONS = ['spam', 'offensive', 'false_info', 'other'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }
}
