<?php

namespace App\Enums;

/**
 * Kênh thông báo push — cư dân bật/tắt từng kênh (trừ kênh KHẨN CẤP luôn bật).
 * Backend gửi push kèm `channel`; PushService bỏ qua thiết bị của người đã tắt
 * kênh đó. Khớp `notifications.type` + mở rộng cho các luồng app.
 */
enum NotificationChannel: string
{
    case Emergency = 'emergency';       // khẩn cấp — KHÔNG tắt được
    case Billing = 'billing';           // hoá đơn & công nợ
    case Community = 'community';       // cộng đồng (bình luận/nhắc tên/bài mới)
    case Maintenance = 'maintenance';   // kỹ thuật / bảo trì
    case Security = 'security';         // an ninh (khách đến…)
    case Feedback = 'feedback';         // phản ánh (BQL trả lời)
    case Amenity = 'amenity';           // đặt tiện ích (duyệt/nhắc lịch)
    case Announcement = 'announcement'; // thông báo BQL
    case System = 'system';             // hệ thống

    public function label(): string
    {
        return match ($this) {
            self::Emergency => 'Khẩn cấp',
            self::Billing => 'Hoá đơn & công nợ',
            self::Community => 'Cộng đồng',
            self::Maintenance => 'Kỹ thuật / bảo trì',
            self::Security => 'An ninh',
            self::Feedback => 'Phản ánh',
            self::Amenity => 'Tiện ích',
            self::Announcement => 'Thông báo BQL',
            self::System => 'Hệ thống',
        };
    }

    /** Kênh khẩn cấp không cho tắt (an toàn cư dân). */
    public function canDisable(): bool
    {
        return $this !== self::Emergency;
    }

    /** Mặc định BẬT (chưa có tuỳ chọn = coi như bật). */
    public function defaultOn(): bool
    {
        return true;
    }

    /** @return array<int,array<string,mixed>> mô tả cho app hiện màn cài đặt. */
    public static function catalog(): array
    {
        return array_map(fn (self $c) => [
            'channel' => $c->value,
            'label' => $c->label(),
            'can_disable' => $c->canDisable(),
            'default_on' => $c->defaultOn(),
        ], self::cases());
    }
}
