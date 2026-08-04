<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Models\BuildingNotificationChannel;
use App\Models\User;

/**
 * CỔNG CHỜ cho các kênh cần nhà cung cấp trả phí CHƯA đấu nối thật (Zalo, WhatsApp,
 * Telegram, X.Space, SMS, thư tay — ADR-002). Ghi vết vào sổ gửi để BQL thấy ý định,
 * nhưng KHÔNG gửi thật. Phân biệt hai mức theo cấu hình TÒA (`meta['building_channel']`):
 *   - đã khai tham số theo tòa (status=pending) → 'provider_pending' (chờ đi live)
 *   - chưa khai gì                               → 'provider_not_configured'
 *
 * Cắm provider thật = viết một ChannelDispatcher mới (vd `ZnsChannelDispatcher`) và
 * thay trong `MultiChannelNotifier::$registry` — không đụng chỗ khác.
 */
class PendingProviderChannelDispatcher implements ChannelDispatcher
{
    public function __construct(private readonly string $channel) {}

    public function channel(): string
    {
        return $this->channel;
    }

    public function send(User $recipient, string $title, string $body, array $meta = []): array
    {
        $config = $meta['building_channel'] ?? null;
        $configured = $config instanceof BuildingNotificationChannel;

        return [
            'status' => 'queued',
            'error' => $configured ? 'provider_pending' : 'provider_not_configured',
        ];
    }
}
