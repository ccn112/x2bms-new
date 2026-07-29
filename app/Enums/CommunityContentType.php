<?php

namespace App\Enums;

/**
 * Loại nội dung trong feed cộng đồng (docs 01 §8 của Community Domain Handoff).
 *
 * `*_ref` nghĩa là bài chỉ **tham chiếu** tới entity gốc (thông báo, sự kiện,
 * bình chọn) — feed không sao chép nội dung sang, để tránh có hai nguồn sự thật
 * rồi lệch nhau.
 */
enum CommunityContentType: string
{
    case Status = 'status';
    case OfficialAnnouncementRef = 'official_announcement_ref';
    case NewsRef = 'news_ref';
    case LinkShare = 'link_share';
    case EventRef = 'event_ref';
    case PollRef = 'poll_ref';
    case SystemUpdate = 'system_update';

    /**
     * Ánh xạ **tab UI → các loại nội dung** thuộc tab đó.
     *
     * Tab là khái niệm của người dùng ("Thông báo BQL"), content type là khái
     * niệm của hệ thống. Một tab có thể gom nhiều loại — nên ánh xạ ở server
     * chứ không để app tự đoán.
     */
    public static function forTab(string $tab): ?array
    {
        return match ($tab) {
            'all' => null, // không lọc
            'official_announcement' => [self::OfficialAnnouncementRef->value, self::NewsRef->value],
            'event' => [self::EventRef->value],
            'poll' => [self::PollRef->value],
            default => null,
        };
    }

    /** Tab hợp lệ, đúng thứ tự hiển thị. */
    public static function tabs(): array
    {
        return [
            ['key' => 'all', 'label' => 'Tất cả'],
            ['key' => 'official_announcement', 'label' => 'Thông báo BQL'],
            ['key' => 'event', 'label' => 'Sự kiện'],
            ['key' => 'poll', 'label' => 'Bình chọn'],
        ];
    }
}
