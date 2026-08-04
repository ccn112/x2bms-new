<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ADR-002 — cấu hình một KÊNH gửi cho một TÒA (per-building provisioning).
 * Xem migration `create_building_notification_channels` cho ý nghĩa cột + hình dạng `config`.
 *
 * Kênh gửi thật hiện có: email (Elastic Email). Các kênh còn lại là CỔNG CHỜ:
 * đã khai tham số theo tòa nhưng chưa đấu nối provider (status=pending).
 */
class BuildingNotificationChannel extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
        'verified_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';   // cổng chờ — đã khai tham số, chưa đi live
    public const STATUS_ACTIVE = 'active';     // đã đấu nối provider, gửi được

    /** Các kênh cần cấu hình theo tòa. 'email' gửi thật; còn lại là cổng chờ. */
    public const CHANNELS = ['email', 'zalo', 'whatsapp', 'telegram', 'xspace'];

    /** Nhãn kênh hiển thị cho BQL. */
    public const CHANNEL_LABELS = [
        'email' => 'Email',
        'zalo' => 'Zalo OA',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'xspace' => 'X.Space (xhub)',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /** Đã bật + đã đấu nối provider thật → gửi được ngay. */
    public function isLive(): bool
    {
        return $this->enabled && $this->status === self::STATUS_ACTIVE;
    }
}
