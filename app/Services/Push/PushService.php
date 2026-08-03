<?php

namespace App\Services\Push;

use App\Enums\NotificationChannel;
use App\Models\DeviceToken;
use App\Models\NotificationPreference;
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

    /**
     * Gửi tới MỌI thiết bị của một user, tôn trọng tuỳ chọn KÊNH. Người đã tắt
     * kênh (trừ khẩn cấp) thì bỏ qua. Trả số thiết bị nhận thành công.
     */
    public function toUser(
        User $user,
        string $title,
        string $body,
        array $data = [],
        ?NotificationChannel $channel = null,
        ?string $imageUrl = null,
    ): int {
        if ($channel !== null && ! $this->userAllows($user, $channel)) {
            return 0;
        }

        return $this->toTokens(
            DeviceToken::where('user_id', $user->id)->pluck('token')->all(),
            $title,
            $body,
            $channel === null ? $data : $data + ['channel' => $channel->value],
            $imageUrl,
        );
    }

    /** Người dùng có nhận kênh này không. Kênh khẩn cấp luôn nhận. */
    public function userAllows(User $user, NotificationChannel $channel): bool
    {
        if (! $channel->canDisable()) {
            return true;
        }
        $pref = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('channel', $channel->value)
            ->value('enabled');

        return $pref === null ? $channel->defaultOn() : (bool) $pref;
    }

    /**
     * BROADCAST tới một FCM TOPIC — MỘT message cho cả topic thay vì gửi lẻ triệu
     * thiết bị (ADR-001). Trả true nếu đã gửi (FCM nhận), false nếu tắt/ lỗi.
     */
    public function toTopic(string $topic, string $title, string $body, array $data = [], ?string $imageUrl = null): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $notification = Notification::create($title, $body);
        if ($imageUrl !== null && preg_match('#^https?://#', $imageUrl)) {
            $notification = $notification->withImageUrl($imageUrl);
        }
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData(array_map(fn ($v) => (string) $v, $data))
            ->toTopic($topic);

        try {
            $this->messaging()->send($message);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Đăng ký các thiết bị vào các topic (theo tenant/dự án/toà của cư dân) — để
     * broadcast bằng topic. Idempotent phía FCM. No-op khi FCM tắt.
     *
     * @param  array<string>  $tokens
     * @param  array<string>  $topics
     */
    public function subscribeToTopics(array $tokens, array $topics): void
    {
        $tokens = array_values(array_filter($tokens));
        $topics = array_values(array_filter($topics));
        if (empty($tokens) || empty($topics) || ! $this->enabled()) {
            return;
        }
        try {
            $this->messaging()->subscribeToTopics($topics, $tokens);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string>  $tokens
     * @param  array<string>  $topics
     */
    public function unsubscribeFromTopics(array $tokens, array $topics): void
    {
        $tokens = array_values(array_filter($tokens));
        $topics = array_values(array_filter($topics));
        if (empty($tokens) || empty($topics) || ! $this->enabled()) {
            return;
        }
        try {
            $this->messaging()->unsubscribeFromTopics($topics, $tokens);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string>  $tokens
     * @param  ?string  $imageUrl  Ảnh lớn của thông báo (BigPicture Android) — avatar
     *                             người viết / ảnh đính kèm. Icon app vẫn hiện nhỏ.
     */
    public function toTokens(array $tokens, string $title, string $body, array $data = [], ?string $imageUrl = null): int
    {
        $tokens = array_values(array_filter($tokens));
        if (empty($tokens) || ! $this->enabled()) {
            return 0;
        }

        $notification = Notification::create($title, $body);
        // Chỉ nhận URL http(s) tuyệt đối — FCM + máy tự tải ảnh; URL rỗng/tương đối
        // làm thông báo lỗi ảnh nên bỏ qua.
        if ($imageUrl !== null && preg_match('#^https?://#', $imageUrl)) {
            $notification = $notification->withImageUrl($imageUrl);
        }

        $message = CloudMessage::new()
            ->withNotification($notification)
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
