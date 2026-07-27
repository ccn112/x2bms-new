<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tier 5 — Bài đăng cộng đồng.
 *
 * Kiểm duyệt HẬU KIỂM (docs/COMMUNITY_WRITE_MODERATION_DESIGN.md): cư dân đăng
 * là `published` ngay. BQL có ba can thiệp KHÁC NHAU — khóa (`locked_at`, bài
 * còn hiện nhưng cấm tương tác), ẩn (`status=hidden`, tác giả vẫn thấy kèm lý
 * do), xóa mềm (`deleted_at`).
 */
class CommunityPost extends Model
{
    use BelongsToTenant, HasAttachments, HasComments, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_important' => 'boolean',
        'image_paths' => 'array',
        'locked_at' => 'datetime',
        'moderated_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CommunityGroup::class, 'community_group_id');
    }

    /** Hồ sơ cư dân của tác giả (tên hiển thị + căn hộ). */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'author_resident_id');
    }

    /** Tài khoản người đăng — dùng cho quyền "bài của tôi". */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommunityPostReaction::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommunityPostReport::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isHidden(): bool
    {
        return $this->status === 'hidden';
    }
}
