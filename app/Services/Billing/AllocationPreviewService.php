<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\StatementLine;

/**
 * PREVIEW phân bổ tiền — THUẦN, KHÔNG ghi gì (P4 / canonical "preview allocation
 * bắt buộc trước thanh toán").
 *
 * Cho các dòng phí ĐÃ SẮP theo thứ tự phân bổ (`allocationSortKey`) + số tiền,
 * trả về: mỗi dòng nhận bao nhiêu (cap ở phần còn nợ), tổng phân bổ, phần thừa
 * chưa phân bổ. Cùng luật với đường ghi thật (claim/ví) để cư dân thấy TRƯỚC
 * đúng cái sẽ xảy ra — không bao giờ phân bổ vượt phần còn nợ.
 */
class AllocationPreviewService
{
    /**
     * @param  iterable<StatementLine>  $sortedLines  các dòng ĐÃ sắp theo allocationSortKey
     * @return array{per_line: array<int,string>, allocated: string, unallocated: string}
     */
    public function preview(iterable $sortedLines, string $amount): array
    {
        $remaining = $amount;
        $allocated = '0';
        $perLine = [];

        foreach ($sortedLines as $line) {
            $owed = $line->outstanding();
            if (bccomp($remaining, '0', 2) <= 0 || bccomp($owed, '0', 2) <= 0) {
                $perLine[$line->id] = '0';

                continue;
            }
            $take = bccomp($remaining, $owed, 2) >= 0 ? $owed : $remaining;
            $perLine[$line->id] = $take;
            $remaining = bcsub($remaining, $take, 2);
            $allocated = bcadd($allocated, $take, 2);
        }

        return [
            'per_line' => $perLine,
            'allocated' => $allocated,
            'unallocated' => $remaining,
        ];
    }
}
