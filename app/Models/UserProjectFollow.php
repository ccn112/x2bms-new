<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Quan tâm dự án" của một tài khoản — nguồn CHUẨN cho kênh
 * `project_interest_channel` (chốt 2026-07-31). KHÔNG cấp quyền, KHÔNG cho
 * vào nhóm — chỉ là tín hiệu ưu tiên hiển thị trong feed. Xem
 * `docs/COMMUNITY_DB_MAPPING.md` §4.
 */
class UserProjectFollow extends Model
{
    protected $guarded = [];

    protected $casts = ['followed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
