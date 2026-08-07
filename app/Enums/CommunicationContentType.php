<?php

namespace App\Enums;

/**
 * Loại nội dung truyền thông BQL (BQL-NOTI). Một `notifications` row là 1 chiến dịch;
 * content_type quyết định field động + panel chi tiết. Event/Poll THAM CHIẾU entity
 * canonical qua notifications.entity_type/entity_id (không nhân đôi domain — ADR-002).
 */
enum CommunicationContentType: string
{
    case Announcement = 'announcement'; // Thông báo vận hành
    case News = 'news';                 // Tin tức / bản tin
    case Event = 'event';               // Sự kiện (link Event)
    case Poll = 'poll';                 // Bình chọn / khảo sát (link Poll)

    public function label(): string
    {
        return match ($this) {
            self::Announcement => 'Thông báo',
            self::News => 'Tin tức',
            self::Event => 'Sự kiện',
            self::Poll => 'Bình chọn',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Announcement => 'heroicon-o-megaphone',
            self::News => 'heroicon-o-newspaper',
            self::Event => 'heroicon-o-calendar-days',
            self::Poll => 'heroicon-o-chart-bar',
        };
    }

    /** Content_type liên kết tới một entity domain canonical (Event/Poll). */
    public function linksEntity(): bool
    {
        return in_array($this, [self::Event, self::Poll], true);
    }

    /** @return array<int,array<string,mixed>> */
    public static function catalog(): array
    {
        return array_map(fn (self $c) => [
            'value' => $c->value,
            'label' => $c->label(),
            'icon' => $c->icon(),
        ], self::cases());
    }
}
