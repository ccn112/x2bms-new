<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Models\BuildingNotificationChannel;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Kênh EMAIL qua Laravel Mail (Elastic Email SMTP theo `.env`; log ở dev).
 * Gửi được ngay không cần provider trả phí. `cost = 0` (email không tính tiền/tin).
 *
 * ADR-002 — nếu tòa có cấu hình email (`meta['building_channel']`) thì áp
 * `from_name` / `from_address` / `reply_to` riêng của tòa; không có thì dùng
 * mặc định `mail.from` toàn hệ.
 */
class EmailChannelDispatcher implements ChannelDispatcher
{
    public function channel(): string
    {
        return 'email';
    }

    public function send(User $recipient, string $title, string $body, array $meta = []): array
    {
        $email = $recipient->email;
        if (empty($email)) {
            return ['status' => 'failed', 'error' => 'no_email'];
        }

        $cfg = $meta['building_channel'] ?? null;
        $overrides = $cfg instanceof BuildingNotificationChannel ? ($cfg->config ?? []) : [];

        try {
            Mail::raw($body, function ($m) use ($email, $title, $overrides) {
                $m->to($email)->subject($title);
                if (! empty($overrides['from_address'])) {
                    $m->from($overrides['from_address'], $overrides['from_name'] ?? null);
                }
                if (! empty($overrides['reply_to'])) {
                    $m->replyTo($overrides['reply_to']);
                }
            });

            return ['status' => 'sent', 'cost' => 0.0, 'provider_message_id' => null];
        } catch (\Throwable $e) {
            report($e);

            return ['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 200)];
        }
    }
}
