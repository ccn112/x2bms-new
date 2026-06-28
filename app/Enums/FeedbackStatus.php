<?php

namespace App\Enums;

enum FeedbackStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Mới',
            self::Assigned => 'Đã phân công',
            self::InProgress => 'Đang xử lý',
            self::Resolved => 'Đã xử lý',
            self::Closed => 'Đã đóng',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'red',
            self::Assigned => 'amber',
            self::InProgress => 'blue',
            self::Resolved => 'green',
            self::Closed => 'slate',
        };
    }

    /** Statuses considered "chờ xử lý" (pending) for KPI counting. */
    public static function pendingValues(): array
    {
        return [self::New->value, self::Assigned->value, self::InProgress->value];
    }
}
