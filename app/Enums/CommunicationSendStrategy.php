<?php

namespace App\Enums;

/**
 * Chiến lược gửi đa kênh (spec 04 BQL-NOTI-04). Không gửi fallback khi kênh trước
 * đã thành công; luật hard-fail/timeout do NotificationPublisher/Job quyết định.
 */
enum CommunicationSendStrategy: string
{
    case Parallel = 'parallel';                 // gửi tất cả kênh bật song song
    case PriorityFallback = 'priority_fallback'; // theo thứ tự ưu tiên, kênh sau chỉ khi kênh trước fail
    case Custom = 'custom';                       // điều kiện tùy biến

    public function label(): string
    {
        return match ($this) {
            self::Parallel => 'Gửi song song tất cả kênh',
            self::PriorityFallback => 'Ưu tiên & dự phòng',
            self::Custom => 'Tùy chỉnh điều kiện',
        };
    }
}
