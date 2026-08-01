<?php

namespace App\Services\Push;

use App\Models\DeviceToken;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Gửi push qua FCM (HTTP v1, service account). Tắt (FCM_ENABLED=false) thì mọi
 * lời gọi là no-op → tính năng gọi push không vỡ khi chưa bật. Token chết
 * (invalid/unknown) được dọn tự động sau mỗi lần gửi.
 */
class PushService
{
    public function enabled(): bool
    {
        return (bool) config('services.firebase.enabled')
            && is_string(config('services.firebase.credentials'))
            && file_exists(config('services.firebase.credentials'));
    }

    private function messaging()
    {
        return (new Factory)
            ->withServiceAccount(config('services.firebase.credentials'))
            ->createMessaging();
    }

    /** Gửi tới MỌI thiết bị của một user. Trả số thiết bị nhận thành công. */
    public function toUser(User $user, string $title, string $body, array $data = []): int
    {
        return $this->toTokens(
            DeviceToken::where('user_id', $user->id)->pluck('token')->all(),
            $title, $body, $data,
        );
    }

    /** @param array<string> $tokens */
    public function toTokens(array $tokens, string $title, string $body, array $data = []): int
    {
        $tokens = array_values(array_filter($tokens));
        if (empty($tokens) || ! $this->enabled()) {
            return 0;
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData(array_map(fn ($v) => (string) $v, $data));

        try {
            $report = $this->messaging()->sendMulticast($message, $tokens);
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }

        // Dọn token không còn hợp lệ để lần sau không gửi vô ích.
        foreach ([...$report->invalidTokens(), ...$report->unknownTokens()] as $dead) {
            DeviceToken::where('token', $dead)->delete();
        }

        return $report->successes()->count();
    }
}
