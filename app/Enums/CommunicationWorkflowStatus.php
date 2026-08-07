<?php

namespace App\Enums;

/**
 * Máy trạng thái chiến dịch truyền thông (spec 06 §2). Là tầng workflow BỔ SUNG,
 * KHÔNG thay cột `notifications.status` (status=published vẫn là cổng cư dân thấy — ADR-002).
 * Mọi chuyển trạng thái đi qua CampaignStateMachine, không sửa enum tự do từ form.
 */
enum CommunicationWorkflowStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case ChangesRequested = 'changes_requested';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Queued = 'queued';
    case Sending = 'sending';
    case PartiallySent = 'partially_sent';
    case Sent = 'sent';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::PendingApproval => 'Chờ duyệt',
            self::ChangesRequested => 'Yêu cầu chỉnh sửa',
            self::Rejected => 'Từ chối',
            self::Approved => 'Đã duyệt',
            self::Scheduled => 'Đã hẹn giờ',
            self::Queued => 'Đã vào hàng đợi',
            self::Sending => 'Đang gửi',
            self::PartiallySent => 'Gửi một phần',
            self::Sent => 'Đã gửi',
            self::Completed => 'Hoàn tất',
            self::Cancelled => 'Đã hủy',
        };
    }

    /** Maps to X2StatusBadge tone. */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::PendingApproval => 'amber',
            self::ChangesRequested => 'amber',
            self::Rejected => 'red',
            self::Approved => 'blue',
            self::Scheduled => 'indigo',
            self::Queued => 'indigo',
            self::Sending => 'blue',
            self::PartiallySent => 'amber',
            self::Sent => 'green',
            self::Completed => 'green',
            self::Cancelled => 'slate',
        };
    }

    /**
     * Chuyển trạng thái hợp lệ (spec 06 §2). send_now KHÔNG bypass approval:
     * mọi đường tới sending đều phải qua approved/scheduled/queued.
     *
     * @return array<int,self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::PendingApproval, self::Cancelled],
            self::PendingApproval => [self::Approved, self::ChangesRequested, self::Rejected, self::Cancelled],
            self::ChangesRequested => [self::Draft, self::PendingApproval, self::Cancelled],
            self::Rejected => [self::Draft, self::Cancelled],
            self::Approved => [self::Scheduled, self::Queued, self::Cancelled],
            self::Scheduled => [self::Queued, self::Cancelled],
            self::Queued => [self::Sending, self::Cancelled],
            self::Sending => [self::PartiallySent, self::Sent],
            self::PartiallySent => [self::Sent, self::Completed],
            self::Sent => [self::Completed],
            self::Completed => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /** Đã chốt snapshot (approved trở đi) → sửa nội dung/audience phải invalidate approval. */
    public function isLocked(): bool
    {
        return in_array($this, [
            self::Approved, self::Scheduled, self::Queued,
            self::Sending, self::PartiallySent, self::Sent, self::Completed,
        ], true);
    }

    /** Trạng thái kết thúc (không sửa snapshot đã gửi). */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Rejected], true);
    }

    /** Đã phân phối (đang/đã gửi) → resident có thể thấy (status=published). */
    public function isDispatched(): bool
    {
        return in_array($this, [self::Sending, self::PartiallySent, self::Sent, self::Completed], true);
    }
}
