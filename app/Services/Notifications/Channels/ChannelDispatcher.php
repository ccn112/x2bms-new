<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Models\User;

/**
 * Một KÊNH GỬI (email/sms/zalo/thư tay…). Mỗi adapter cùng khuôn: nhận người nhận
 * + nội dung, trả kết quả gửi để `MultiChannelNotifier` ghi vào sổ gửi (audit).
 *
 * Adapter KHÔNG tự ghi delivery_log — notifier lo phần đó (một chỗ, idempotent).
 */
interface ChannelDispatcher
{
    /** Tên kênh khớp cột `notification_delivery_logs.channel` (email|sms|zalo|postal). */
    public function channel(): string;

    /**
     * Gửi. Trả về:
     *  status: 'sent'|'delivered'|'queued'|'failed'
     *  provider_message_id: ?string · cost: ?float · error: ?string
     *
     * @param  array<string,mixed>  $meta
     * @return array{status:string, provider_message_id?:?string, cost?:?float, error?:?string}
     */
    public function send(User $recipient, string $title, string $body, array $meta = []): array;
}
