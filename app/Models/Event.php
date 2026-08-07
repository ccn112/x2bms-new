<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Sự kiện cộng đồng. */
class Event extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * Vòng đời sự kiện — đúng tập giá trị khai báo ở migration
     * `2026_07_01_000015_create_handover_community.php`.
     *
     * `published` KHÔNG thuộc tập này (đó là quy ước của các bảng nội dung như
     * notifications/articles). Hằng số đặt ở model để mọi nơi lọc theo cùng một
     * nguồn — trước đây controller cư dân và form Filament dùng hai bộ giá trị
     * khác nhau, và không chỗ nào phát hiện ra.
     */
    public const STATUSES = ['upcoming', 'ongoing', 'finished', 'cancelled'];

    /**
     * Trạng thái cư dân được thấy: sắp diễn ra và đang diễn ra.
     * `finished`/`cancelled` thì không còn gì để đăng ký hay tham gia.
     */
    public const RESIDENT_VISIBLE_STATUSES = ['upcoming', 'ongoing'];

    /** Trạng thái đăng ký (BQL-NOTI) — tách khỏi vòng đời sự kiện. */
    public const REGISTRATION_STATUSES = ['open', 'closed', 'full'];

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'registration_deadline' => 'datetime',
        'allow_guests' => 'boolean',
        'qr_checkin' => 'boolean',
        'fee_amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }
}
