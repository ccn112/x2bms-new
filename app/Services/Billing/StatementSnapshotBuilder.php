<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Statement;

/**
 * Dựng snapshot BẤT BIẾN của một bảng kê (P3 / canonical D15).
 *
 * Snapshot là ảnh chụp nội dung tại thời điểm phát hành: các dòng phí (nhãn, nhóm,
 * tài sản, kỳ dịch vụ, số lượng/đơn giá, thành tiền) + tổng. `checksum` = sha256 của
 * JSON chuẩn hoá (khóa sắp cố định) → phát hiện mọi thay đổi 1 byte.
 *
 * Hàm THUẦN (chỉ đọc), tái dùng cho cả publish lẫn command verify drift.
 */
class StatementSnapshotBuilder
{
    /** @return array<string,mixed> */
    public function build(Statement $statement): array
    {
        $statement->loadMissing('lines');

        $lines = $statement->lines
            ->sortBy('id')
            ->map(fn ($l) => [
                'line_id' => (int) $l->id,
                'fee_type_id' => $l->fee_type_id !== null ? (int) $l->fee_type_id : null,
                'fee_type' => $l->fee_type,
                'fee_category' => $l->fee_category,
                'subject_type' => $l->subject_type,
                'subject_id' => $l->subject_id !== null ? (int) $l->subject_id : null,
                'service_period_start' => $this->dateStr($l->service_period_start),
                'service_period_end' => $this->dateStr($l->service_period_end),
                'quantity' => $l->quantity !== null ? (string) $l->quantity : null,
                'unit_price' => $l->unit_price !== null ? (string) $l->unit_price : null,
                'amount' => (string) $l->amount,
            ])
            ->values()
            ->all();

        return [
            'statement_code' => $statement->code,
            'tenant_id' => (int) $statement->tenant_id,
            'apartment_id' => (int) $statement->apartment_id,
            'billing_period_id' => (int) $statement->billing_period_id,
            'due_date' => $this->dateStr($statement->due_date),
            'total_amount' => (string) $statement->lines->sum('amount'),
            'line_count' => count($lines),
            'lines' => $lines,
        ];
    }

    /** Checksum ổn định — JSON không escape unicode, giữ thứ tự khóa như `build()`. */
    public function checksum(array $snapshot): string
    {
        return hash('sha256', (string) json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function dateStr($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
    }
}
