<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\NotificationDeliveryLog;
use App\Models\User;
use App\Services\Notifications\Channels\ChannelDispatcher;
use App\Services\Notifications\Channels\EmailChannelDispatcher;
use App\Services\Notifications\Channels\PendingProviderChannelDispatcher;

/**
 * Gửi một nội dung tới MỘT người qua NHIỀU kênh ngoài (email/zalo/whatsapp/telegram/
 * xspace/sms/thư tay) và ghi SỔ GỬI per-(người × kênh) đầy đủ vòng đời (N4, ADR-001
 * mục 4 — audit per-người bắt buộc cho kênh trả phí).
 *
 * ADR-002 — BUILDING-AWARE: mỗi kênh (trừ email) đọc cấu hình theo TÒA qua
 * `ChannelConfigResolver`. Tòa quyết định bật/tắt + tham số provider:
 *   - email          : gửi THẬT (Elastic Email); tòa có thể override from/reply-to.
 *   - zalo/whatsapp/telegram/xspace/sms/postal : CỔNG CHỜ — ghi 'queued' +
 *       'provider_pending' (tòa đã khai tham số) hoặc 'provider_not_configured'
 *       (chưa khai). Kênh bị tòa TẮT (enabled=false) → 'suppressed'+'channel_disabled'.
 *
 * Idempotent theo (source, người, kênh): đã 'sent' thì bỏ qua. Cắm provider thật chỉ
 * cần thay adapter trong $registry, không đụng chỗ gọi.
 *
 * KHÔNG dùng cho push (đã có NotificationPushDispatcher + FCM topic) và KHÔNG dùng
 * cho broadcast rộng qua email/SMS (không có 'topic' — gửi rộng = tốn phí per-người).
 *
 * @var array<string, ChannelDispatcher> $registry
 */
class MultiChannelNotifier
{
    private array $registry;

    public function __construct(
        EmailChannelDispatcher $email,
        private readonly ChannelConfigResolver $config,
    ) {
        $this->registry = [
            'email' => $email,
            // Cổng chờ — ghi vết ý định, chưa gửi thật (ADR-002).
            'sms' => new PendingProviderChannelDispatcher('sms'),
            'zalo' => new PendingProviderChannelDispatcher('zalo'),
            'whatsapp' => new PendingProviderChannelDispatcher('whatsapp'),
            'telegram' => new PendingProviderChannelDispatcher('telegram'),
            'xspace' => new PendingProviderChannelDispatcher('xspace'),
            'postal' => new PendingProviderChannelDispatcher('postal'),
        ];
    }

    /**
     * @param  list<string>  $channels  email|sms|zalo|whatsapp|telegram|xspace|postal
     * @param  array<string,mixed>  $meta
     * @param  ?int  $buildingId  tòa của người nhận → resolve cấu hình kênh theo tòa.
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
        ?int $buildingId = null,
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

            // Cấu hình kênh theo tòa: tòa TẮT kênh → không gửi, ghi 'suppressed'.
            $channelConfig = $this->config->for($buildingId, $ch);
            if ($channelConfig !== null && ! $channelConfig->enabled) {
                $log->fill(['status' => 'suppressed', 'error' => 'channel_disabled'])->save();

                continue;
            }

            $r = $dispatcher->send($recipient, $title, $body, $meta + ['building_channel' => $channelConfig]);
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
