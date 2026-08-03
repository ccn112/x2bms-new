<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Models\User;

/**
 * Adapter TẠM cho các kênh cần nhà cung cấp trả phí CHƯA chốt (SMS brandname,
 * Zalo ZNS, thư tay). Ghi vết 'queued' + lý do 'provider_not_configured' để BQL
 * thấy ĐỊNH gửi trong sổ audit, nhưng KHÔNG gửi thật.
 *
 * Cắm provider thật = viết một ChannelDispatcher mới (vd `ZnsChannelDispatcher`)
 * và thay trong `MultiChannelNotifier::$registry` — không đụng chỗ khác.
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
        return ['status' => 'queued', 'error' => 'provider_not_configured'];
    }
}
