<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationWorkflowStatus as WS;
use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Phát hành chiến dịch đã duyệt (BQL-NOTI-06/07). KHÔNG dispatch trực tiếp trong request
 * lớn — với demo/tòa nhỏ chạy đồng bộ; audience lớn nên đẩy job (T4 backlog). Đóng vòng:
 * approved → queued → sending → (ghi delivery app-inbox + gọi push/external dispatcher) →
 * sent → completed. Snapshot đã chốt ở bước gửi duyệt; ở đây KHÔNG sửa nội dung snapshot.
 *
 * Delivery ledger canonical = notification_delivery_logs (ADR-002). App-inbox ghi ở đây;
 * push/email/zalo do dispatcher sẵn có ghi (idempotent theo unique recipient+channel).
 */
class NotificationPublisher
{
    public function __construct(private readonly CampaignStateMachine $stateMachine) {}

    public function publish(Notification $n, ?int $actorId = null): Notification
    {
        $status = $n->workflow_status instanceof WS ? $n->workflow_status : WS::from((string) $n->workflow_status);
        if (! in_array($status, [WS::Approved, WS::Scheduled], true)) {
            throw new DomainException('Chỉ phát hành chiến dịch đã duyệt hoặc đã hẹn giờ.');
        }

        $this->stateMachine->transition($n, WS::Queued, ['actor_id' => $actorId]);
        $this->stateMachine->transition($n->fresh(), WS::Sending, ['actor_id' => $actorId]);
        $n->refresh();

        $this->writeInboxDeliveries($n);

        // Push + kênh ngoài qua dispatcher sẵn có (bọc try: lỗi kênh KHÔNG làm hỏng phát hành).
        try {
            app(\App\Services\Resident\NotificationPushDispatcher::class)->dispatch($n);
        } catch (\Throwable $e) {
            report($e);
        }
        try {
            app(NotificationExternalChannelDispatcher::class)->dispatch($n);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->stateMachine->transition($n->fresh(), WS::Sent, ['actor_id' => $actorId]);
        $this->stateMachine->transition($n->fresh(), WS::Completed, ['actor_id' => $actorId]);

        return $n->fresh();
    }

    /** Ghi delivery app-inbox cho từng người nhận (kênh 'app' luôn tới hộp thư). */
    private function writeInboxDeliveries(Notification $n): void
    {
        $hasApp = $n->channels()->where('channel', 'app')->where('enabled', true)->exists();
        if (! $hasApp) {
            return;
        }

        DB::transaction(function () use ($n) {
            $now = now();
            $n->recipients()->whereNotNull('user_id')->select(['user_id', 'resident_id'])->orderBy('id')
                ->chunk(500, function ($recipients) use ($n, $now) {
                    $rows = $recipients->map(fn ($r) => [
                        'notification_id' => $n->id,
                        'user_id' => $r->user_id,
                        'resident_id' => $r->resident_id,
                        'channel' => 'app',
                        'status' => 'sent',
                        'sent_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    // upsert idempotent theo (notification_id,user_id,channel).
                    NotificationDeliveryLog::upsert(
                        $rows,
                        ['notification_id', 'user_id', 'channel'],
                        ['status', 'sent_at', 'updated_at'],
                    );
                });
        });
    }
}
