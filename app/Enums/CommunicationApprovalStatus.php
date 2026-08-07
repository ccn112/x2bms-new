<?php

namespace App\Enums;

/** Trạng thái một bước/tuyến duyệt chiến dịch truyền thông (spec 06 §4, 09). */
enum CommunicationApprovalStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ChangesRequested = 'changes_requested';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Chờ duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Từ chối',
            self::ChangesRequested => 'Yêu cầu chỉnh sửa',
            self::Expired => 'Hết hạn',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::ChangesRequested => 'amber',
            self::Expired => 'slate',
        };
    }

    public function isResolved(): bool
    {
        return $this !== self::Requested;
    }
}
