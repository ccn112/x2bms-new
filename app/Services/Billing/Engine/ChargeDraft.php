<?php

declare(strict_types=1);

namespace App\Services\Billing\Engine;

/**
 * Engine tính phí (Phase 2, ADR/plan `docs/BILLING_FEE_ENGINE_PHASE2_PLAN.md`).
 *
 * Một DÒNG PHÍ dự thảo do generator sinh ra — value object THUẦN, KHÔNG chạm DB.
 * BillingRunner mới là nơi ghi ra `statement_lines`. Nhờ tách vậy mỗi công thức test
 * được độc lập (§4 nguyên tắc 1).
 *
 * `amount` = **số nguyên đồng** đã làm tròn half-up từng dòng (D7). `snapshot` lưu đầu
 * vào + từng bước để phục vụ đối soát + màn "vì sao hóa đơn cao" (§4 nguyên tắc 2).
 */
final class ChargeDraft
{
    /** @param array<string,mixed> $snapshot */
    public function __construct(
        public readonly int $feeTypeId,
        public readonly string $feeCategory,
        public readonly string $feeTypeName,
        public readonly int $amount,
        public readonly string $servicePeriodStart,
        public readonly string $servicePeriodEnd,
        public readonly ?string $subjectType = null,
        public readonly ?int $subjectId = null,
        public readonly ?float $quantity = null,
        public readonly ?int $unitPrice = null,
        public readonly ?int $feeRateId = null,
        public readonly array $snapshot = [],
    ) {}

    /** Khóa upsert idempotent — khớp import (§4 nguyên tắc 5). */
    public function naturalKey(): array
    {
        return [
            'fee_type_id' => $this->feeTypeId,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'service_period_start' => $this->servicePeriodStart,
        ];
    }

    /** Payload ghi vào statement_lines (không gồm statement_id). */
    public function toLineAttributes(): array
    {
        return [
            'fee_type' => $this->feeTypeName,
            'fee_category' => $this->feeCategory,
            'service_period_end' => $this->servicePeriodEnd,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'amount' => $this->amount,
            'source' => 'engine',
            'calculation_snapshot' => $this->snapshot,
        ];
    }
}
