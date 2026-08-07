<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationWorkflowStatus as WS;
use App\Models\Notification;
use DomainException;

/**
 * Máy trạng thái chiến dịch truyền thông (spec 06 §2). MỌI chuyển trạng thái đi qua đây —
 * không sửa enum tự do từ form (rule: "không update status bằng free text bypass service").
 *
 * Đồng bộ `workflow_status` (tầng workflow) ↔ `status` (cổng cư dân, ADR-002):
 *   - chưa phân phối → status=draft (cư dân KHÔNG thấy)
 *   - scheduled → status=scheduled
 *   - đang/đã gửi (queued..completed) → status=published (cư dân thấy)
 *   - cancelled → status=archived
 */
class CampaignStateMachine
{
    /** @param array{actor_id?:int|null} $context */
    public function transition(Notification $n, WS $to, array $context = []): Notification
    {
        $from = $n->workflow_status instanceof WS ? $n->workflow_status : WS::from((string) $n->workflow_status);

        if ($from === $to) {
            return $n;
        }
        if (! $from->canTransitionTo($to)) {
            throw new DomainException("Chuyển trạng thái không hợp lệ: {$from->value} → {$to->value}.");
        }

        $n->workflow_status = $to;
        $n->status = $this->residentStatusFor($to);

        $now = now();
        match ($to) {
            WS::Sending => $n->published_at ??= $now,
            WS::Sent => $n->sent_at ??= $now,
            WS::Completed => $n->completed_at ??= $now,
            default => null,
        };
        if (in_array($to, [WS::Queued, WS::Sending, WS::PartiallySent, WS::Sent, WS::Completed], true)) {
            $n->published_at ??= $now;
            $n->published_by_id ??= $context['actor_id'] ?? $n->published_by_id;
        }

        $n->save();

        return $n;
    }

    /** Có được phép chuyển sang $to không (không throw). */
    public function canTransition(Notification $n, WS $to): bool
    {
        $from = $n->workflow_status instanceof WS ? $n->workflow_status : WS::from((string) $n->workflow_status);

        return $from->canTransitionTo($to);
    }

    private function residentStatusFor(WS $to): string
    {
        return match ($to) {
            WS::Scheduled => 'scheduled',
            WS::Queued, WS::Sending, WS::PartiallySent, WS::Sent, WS::Completed => 'published',
            WS::Cancelled => 'archived',
            default => 'draft',
        };
    }
}
