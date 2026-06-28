<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xử lý',
            self::InProgress => 'Đang xử lý',
            self::Done => 'Hoàn thành',
            self::Overdue => 'Quá hạn',
        };
    }

    /** Maps to X2StatusBadge tone. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::InProgress => 'blue',
            self::Done => 'green',
            self::Overdue => 'red',
        };
    }
}
