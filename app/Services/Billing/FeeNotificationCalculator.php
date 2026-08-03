<?php

declare(strict_types=1);

namespace App\Services\Billing;

/**
 * Tính THÀNH TIỀN cho một dòng "thông báo phí" theo ĐÚNG cách phần mềm CŨ tính,
 * để X2 nhận nguyên file import kế toán đang dùng (drop-in, không đổi quy trình).
 *
 * File cũ KHÔNG có cột "thành tiền" — chỉ có đầu vào, phần mềm tự tính:
 *   - `Loại giá áp dụng = 1` (cố định): amount = Số lượng × Đơn giá cố định.
 *   - `Loại giá áp dụng = 2` (lũy tiến điện/nước): amount = Σ (Định mức_i × Đơn giá_i),
 *     các bậc đã được kế toán chia sẵn trong dòng (Định mức/Đơn giá 1..3).
 * Trừ "Giảm giá" nếu có; làm tròn HALF-UP tới ĐỒNG (D7). Không âm.
 *
 * Bậc thang/biểu giá nằm NGAY trong dòng nên tính toán TỰ CHỨA — không phụ thuộc
 * quyết định "biểu giá nhà nước vs dự án" còn treo. `snapshot` giữ nguyên đầu vào
 * gốc để đối soát migration và giải thích cho cư dân ("vì sao hoá đơn thế này").
 *
 * Hàm THUẦN: không query, không ghi DB (để test độc lập, tái dùng cho engine Phase 2).
 */
class FeeNotificationCalculator
{
    /** Loại giá: 1 = cố định (qty × đơn giá), 2 = lũy tiến theo bậc (điện/nước). */
    public const PRICE_FIXED = 1;

    public const PRICE_METERED = 2;

    /**
     * @param  array<int, array{qty:string, price:string}>  $tiers  các bậc (Định mức_i, Đơn giá_i)
     * @return array{amount:int, method:string, snapshot:array}
     */
    public function compute(
        int $priceType,
        string $quantity,
        string $fixedUnitPrice,
        array $tiers = [],
        string $discount = '0',
        ?string $previousReading = null,
        ?string $currentReading = null,
    ): array {
        $discount = $this->num($discount);
        $metered = $priceType === self::PRICE_METERED || $this->hasReadings($previousReading, $currentReading);

        if ($metered) {
            $method = 'metered';
            $gross = '0';
            $tierSnap = [];
            foreach ($tiers as $i => $t) {
                $q = $this->num($t['qty'] ?? '0');
                $p = $this->num($t['price'] ?? '0');
                if (bccomp($q, '0', 4) === 0 && bccomp($p, '0', 4) === 0) {
                    continue;
                }
                $sub = bcmul($q, $p, 4);
                $gross = bcadd($gross, $sub, 4);
                $tierSnap[] = ['tier' => $i + 1, 'qty' => $this->trim0($q), 'price' => $this->trim0($p), 'subtotal' => $this->roundDong($sub)];
            }
            $consumption = $this->hasReadings($previousReading, $currentReading)
                ? $this->trim0(bcsub($this->num($currentReading), $this->num($previousReading), 4))
                : null;
            $snapshot = [
                'method' => 'metered',
                'previous_reading' => $previousReading !== null && trim($previousReading) !== '' ? $this->trim0($this->num($previousReading)) : null,
                'current_reading' => $currentReading !== null && trim($currentReading) !== '' ? $this->trim0($this->num($currentReading)) : null,
                'consumption' => $consumption,
                'tiers' => $tierSnap,
            ];
        } else {
            $method = 'fixed';
            $q = $this->num($quantity);
            $p = $this->num($fixedUnitPrice);
            $gross = bcmul($q, $p, 4);
            $snapshot = ['method' => 'fixed', 'quantity' => $this->trim0($q), 'unit_price' => $this->trim0($p)];
        }

        $net = bcsub($gross, $discount, 4);
        if (bccomp($net, '0', 4) < 0) {
            $net = '0';
        }
        $amount = $this->roundDong($net);

        $snapshot['source'] = 'legacy_import';
        $snapshot['price_type'] = $priceType;
        $snapshot['gross_vnd'] = $this->roundDong($gross);
        $snapshot['discount_vnd'] = $this->roundDong($discount);
        $snapshot['amount_vnd'] = $amount;

        return ['amount' => $amount, 'method' => $method, 'snapshot' => $snapshot];
    }

    private function hasReadings(?string $prev, ?string $cur): bool
    {
        return ($prev !== null && trim($prev) !== '') || ($cur !== null && trim($cur) !== '');
    }

    /** Chuẩn hoá chuỗi số về dạng bcmath dùng được (bỏ khoảng trắng/nghìn, rỗng→0). */
    private function num(?string $v): string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return '0';
        }
        // Bỏ ký tự nghìn kiểu "1 234" hoặc "1,234" (file này số nguyên thuần, an toàn).
        $v = str_replace([' ', "\u{00a0}"], '', $v);
        // Nếu có cả '.' và ',' hoặc ',' đứng làm nghìn — giữ đơn giản: bỏ ',' hàng nghìn.
        if (substr_count($v, ',') > 0 && substr_count($v, '.') === 0) {
            // ',' có thể là thập phân (hiếm) — nếu theo sau 3 số coi là nghìn, ngược lại thập phân.
            $v = preg_match('/,\d{1,2}$/', $v) ? str_replace(',', '.', $v) : str_replace(',', '', $v);
        }

        return is_numeric($v) ? $v : '0';
    }

    /** Làm tròn HALF-UP tới đồng (mọi giá trị ở đây không âm). */
    private function roundDong(string $v): int
    {
        return (int) bcadd($v, '0.5', 0);
    }

    /** Bỏ số 0 thừa sau thập phân cho snapshot gọn ("2.0000"→"2"). */
    private function trim0(string $v): string
    {
        if (! str_contains($v, '.')) {
            return $v;
        }

        return rtrim(rtrim($v, '0'), '.') ?: '0';
    }
}
