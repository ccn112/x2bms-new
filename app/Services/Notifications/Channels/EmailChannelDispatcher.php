<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Kênh EMAIL qua Laravel Mail (driver theo `.env`: log ở dev, SMTP/SES ở prod).
 * Gửi được ngay không cần provider trả phí. `cost = 0` (email không tính tiền/tin).
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

        try {
            Mail::raw($body, function ($m) use ($email, $title) {
                $m->to($email)->subject($title);
            });

            return ['status' => 'sent', 'cost' => 0.0, 'provider_message_id' => null];
        } catch (\Throwable $e) {
            report($e);

            return ['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 200)];
        }
    }
}
