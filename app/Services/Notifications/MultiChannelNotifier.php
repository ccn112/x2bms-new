<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\NotificationDeliveryLog;
use App\Models\User;
use App\Services\Notifications\Channels\ChannelDispatcher;
use App\Services\Notifications\Channels\EmailChannelDispatcher;
use App\Services\Notifications\Channels\PendingProviderChannelDispatcher;

/**
 * Gửi một nội dung tới MỘT người qua NHIỀU kênh ngoài (email/sms/zalo/thư tay) và
 * ghi SỔ GỬI per-(người × kênh) đầy đủ vòng đời (N4, ADR-001 mục 4 — audit per-người
 * bắt buộc cho email/SMS/Zalo).
 *
 * Idempotent theo (source, người, kênh): đã 'sent' thì bỏ qua. Kênh chưa có provider
 * (SMS/Zalo/thư tay) ghi 'queued' + 'provider_not_configured' — cắm provider thật chỉ
 * cần thay adapter trong $registry, không đụng chỗ gọi.
 *
 * KHÔNG dùng cho push (đã có NotificationPushDispatcher + FCM topic) và KHÔNG dùng
 * cho broadcast rộng qua email/SMS (email/SMS không có 'topic' — gửi rộng = tốn phí
 * per-người; để BQL chọn phạm vi nhỏ/targeted).
 *
 * @var array<string, ChannelDispatcher> $registry
 */
class MultiChannelNotifier
{
    private array $registry;

    public function __construct(EmailChannelDispatcher $email)
    {
        $this->registry = [
            'email' => $email,
            // Chưa chốt provider — ghi vết ý định, chưa gửi thật.
            'sms' => new PendingProviderChannelDispatcher('sms'),
            'zalo' => new PendingProviderChannelDispatcher('zalo'),
            'postal' => new PendingProviderChannelDispatcher('postal'),
        ];
    }

    /**
     * @param  list<string>  $channels  email|sms|zalo|postal
     * @param  array<string,mixed>  $meta
     */
    public function notify(
        string $sourceType,
        int $sourceId,
        User $recipient,
        array $channels,
        string $title,
        string $body,
        ?int $notificationId = null,
        array $meta = [],
    ): void {
        foreach (array_unique($channels) as $ch) {
            $log = NotificationDeliveryLog::query()->firstOrNew([
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'user_id' => $recipient->id,
                'channel' => $ch,
            ]);
            if ($log->exists && $log->status === 'sent') {
                continue;   // idempotent — đã gửi thành công thì không gửi lại.
            }

            $log->fill([
                'notification_id' => $notificationId,
                'status' => 'queued',
                'queued_at' => $log->queued_at ?? now(),
            ])->save();

            $dispatcher = $this->registry[$ch] ?? null;
            if ($dispatcher === null) {
                $log->fill(['status' => 'failed', 'error' => 'unknown_channel'])->save();

                continue;
            }

            $r = $dispatcher->send($recipient, $title, $body, $meta);
            $delivered = in_array($r['status'], ['sent', 'delivered'], true);
            $log->fill([
                'status' => $r['status'],
                'error' => $r['error'] ?? null,
                'provider_message_id' => $r['provider_message_id'] ?? null,
                'cost' => $r['cost'] ?? null,
                'sent_at' => $delivered ? now() : $log->sent_at,
            ])->save();
        }
    }
}
