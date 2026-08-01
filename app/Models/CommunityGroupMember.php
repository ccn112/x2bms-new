<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Thành viên nhóm cộng đồng (join/leave + tính `joined`).
 *
 * `resident_id` NULLABLE từ Giai đoạn 3 (2026-08-01): membership loại
 * `system_enrollment` vào `platform_community` chỉ có `user_id` (tier
 * `member` thuần chưa chắc có hồ sơ Resident). `left_at` do
 * `MembershipService` set khi KHÔNG còn grant active nào — giữ nguyên bài
 * viết cũ, chỉ đổi nhãn "cư dân cũ" (chưa xoá lịch sử).
 */
class CommunityGroupMember extends Model
{
    protected $guarded = [];

    protected $casts = ['joined_at' => 'datetime', 'left_at' => 'datetime'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CommunityGroup::class, 'community_group_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grants(): HasMany
    {
        return $this->hasMany(CommunityMembershipGrant::class, 'membership_id');
    }

    /** Còn ít nhất một grant `active`? Nguồn sự thật cho "còn là thành viên". */
    public function hasActiveGrant(): bool
    {
        return $this->grants()->where('status', CommunityMembershipGrant::STATUS_ACTIVE)->exists();
    }
}
