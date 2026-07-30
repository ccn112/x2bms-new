<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tier 5 — Quyền rao tin đã XÁC MINH cho người thuê/môi giới.
 *
 * Quyết định 2026-07-30 #1: chủ căn (quan hệ `role=owner` trong
 * `resident_apartment_relations`) được rao trực tiếp; người thuê/môi giới phải
 * được BQL cấp quyền RÕ RÀNG cho một (căn hộ, người) cụ thể — không phải một
 * quyền chung cho cả dự án, để BQL biết chính xác ai chịu trách nhiệm khi có
 * tranh chấp về căn đó.
 */
class ListingPostingGrant extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
