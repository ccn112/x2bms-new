<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một lý do cụ thể vì sao một membership tồn tại (Giai đoạn 3, COM-007).
 * Một `CommunityGroupMember` có thể có NHIỀU grant cùng lúc (vd hai căn hộ
 * khác nhau trong cùng một dự án) — membership chỉ bị coi là "rời nhóm" khi
 * KHÔNG còn grant nào `active` (`MembershipService`).
 */
class CommunityMembershipGrant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const SOURCE_RESIDENT_RELATION = 'resident_relation';

    public const SOURCE_MANUAL_JOIN = 'manual_join';

    public const SOURCE_INVITATION = 'invitation';

    public const SOURCE_SYSTEM_ENROLLMENT = 'system_enrollment';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    public function membership(): BelongsTo
    {
        return $this->belongsTo(CommunityGroupMember::class, 'membership_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}
