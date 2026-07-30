<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tier 5 — Tin đăng mua/bán/thuê căn hộ.
 *
 * Hai trục trạng thái ĐỘC LẬP (xem migration `add_listing_approval_workflow`):
 *   - `status`: vòng đời GIAO DỊCH — active|pending|sold|rented|expired.
 *   - `approval_status`: vòng đời KIỂM DUYỆT — pending|approved|rejected.
 */
class RealEstateListing extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'area' => 'decimal:2',
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    /** pending|approved|rejected — xem migration `add_listing_approval_workflow`. */
    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'owner_resident_id');
    }

    /** Tài khoản đã đăng tin (chủ căn hoặc người được cấp quyền — xem ListingPostingGrant). */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** Người đã duyệt/từ chối — có thể là nhân sự BQL hoặc SuperAdmin (xem escalatedBy). */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /** BQL đã đẩy tin này lên SuperAdmin xét — chỉ là tín hiệu ưu tiên, xem comment migration escalation. */
    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by_user_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(ListingInquiry::class);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    /** Đang hiển thị công khai: đã duyệt + còn hiệu lực giao dịch. */
    public function isPubliclyVisible(): bool
    {
        return $this->isApproved() && $this->status === 'active';
    }

    /** Từng được BQL xin ý kiến SuperAdmin — không phụ thuộc trạng thái duyệt hiện tại. */
    public function wasEscalated(): bool
    {
        return $this->escalated_at !== null;
    }
}
