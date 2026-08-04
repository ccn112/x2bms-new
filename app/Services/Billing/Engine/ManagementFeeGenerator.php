<?php

declare(strict_types=1);

namespace App\Services\Billing\Engine;

/**
 * P2.1 — Phí quản lý theo DIỆN TÍCH: `amount = round_half_up(area × unit_price)` (+ VAT).
 *
 * Generator THUẦN (§4 nguyên tắc 1 & 3): nhận input đã đọc sẵn, KHÔNG query/ghi DB,
 * KHÔNG eval chuỗi công thức — công thức là code có test. Tiền số nguyên đồng, làm tròn
 * half-up TỪNG dòng (D7). Trả `null` nếu không áp được (thiếu giá/diện tích) — runner bỏ qua.
 *
 * Prorate/miễn giảm/loại căn: khung để mở rộng (nhận `factor` = tỉ lệ ngày ở trong kỳ,
 * mặc định 1.0). P2.1 làm khung; các nhánh phức tạp bổ sung sau khi đối soát số vàng.
 */
final class ManagementFeeGenerator
{
    /**
     * @param  array{fee_type_id:int,fee_type_name:string,vat_percent?:float}  $feeType
     */
    public function generate(
        array $feeType,
        float $areaSqm,
        int $unitPrice,
        string $periodStart,
        string $periodEnd,
        ?int $feeRateId = null,
        float $factor = 1.0,
    ): ?ChargeDraft {
        if ($areaSqm <= 0 || $unitPrice <= 0) {
            return null;   // không đủ dữ liệu để tính → runner bỏ qua, không ghi 0đ.
        }

        $vatPercent = (float) ($feeType['vat_percent'] ?? 0);

        // Làm tròn half-up TỪNG bước, giữ số nguyên đồng — không ôm số lẻ trung gian.
        $base = $this->roundDong($areaSqm * $unitPrice * $factor);
        $vat = $this->roundDong($base * $vatPercent / 100);
        $total = $base + $vat;

        return new ChargeDraft(
            feeTypeId: $feeType['fee_type_id'],
            feeCategory: 'management',
            feeTypeName: $feeType['fee_type_name'],
            amount: $total,
            servicePeriodStart: $periodStart,
            servicePeriodEnd: $periodEnd,
            quantity: round($areaSqm, 2),
            unitPrice: $unitPrice,
            feeRateId: $feeRateId,
            snapshot: [
                'formula' => 'round_half_up(area_sqm × unit_price × factor) + VAT',
                'area_sqm' => round($areaSqm, 2),
                'unit_price' => $unitPrice,
                'factor' => $factor,
                'base' => $base,
                'vat_percent' => $vatPercent,
                'vat' => $vat,
                'total' => $total,
                'fee_rate_id' => $feeRateId,
                'engine' => 'ManagementFeeGenerator',
            ],
        );
    }

    /** Số nguyên đồng, half-up (D7). */
    private function roundDong(float $v): int
    {
        return (int) round($v, 0, PHP_ROUND_HALF_UP);
    }
}
