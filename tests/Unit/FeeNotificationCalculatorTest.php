<?php

namespace Tests\Unit;

use App\Services\Billing\FeeNotificationCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Tính tiền thông báo phí — khớp cách phần mềm CŨ tính (số liệu lấy từ file thật
 * `import_thong_bao_phi-HPO-05.2026.xlsx`).
 */
class FeeNotificationCalculatorTest extends TestCase
{
    private FeeNotificationCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new FeeNotificationCalculator;
    }

    public function test_co_dinh_qty_x_don_gia(): void
    {
        // PQL căn A-0101: Số lượng 1 × Đơn giá 1.911.000
        $r = $this->calc->compute(FeeNotificationCalculator::PRICE_FIXED, '1', '1911000');
        $this->assertSame(1911000, $r['amount']);
        $this->assertSame('fixed', $r['method']);

        // XEMAY căn A-0101: 3 xe × 120.000 = 360.000
        $this->assertSame(360000, $this->calc->compute(1, '3', '120000')['amount']);
    }

    public function test_co_dinh_gia_0_ra_0(): void
    {
        // XEDAP số lượng 0 → 0 (căn để trống/không có xe)
        $this->assertSame(0, $this->calc->compute(1, '0', '40000')['amount']);
    }

    public function test_luy_tien_1_bac(): void
    {
        // NUOC A-0101: Định mức 1 = 2, Đơn giá 1 = 12.075 → 24.150
        $tiers = [['qty' => '2', 'price' => '12075'], ['qty' => '0', 'price' => '0'], ['qty' => '0', 'price' => '0']];
        $r = $this->calc->compute(FeeNotificationCalculator::PRICE_METERED, '0', '0', $tiers, '0', '60', '62');
        $this->assertSame(24150, $r['amount']);
        $this->assertSame('metered', $r['method']);
        $this->assertSame('2', $r['snapshot']['consumption']);

        // NUOC A-0102: 24 × 12.075 = 289.800
        $tiers2 = [['qty' => '24', 'price' => '12075']];
        $this->assertSame(289800, $this->calc->compute(2, '0', '0', $tiers2, '0', '481', '505')['amount']);
    }

    public function test_luy_tien_nhieu_bac_cong_don(): void
    {
        // 3 bậc: 10×100 + 5×200 + 2×300 = 1000 + 1000 + 600 = 2600
        $tiers = [
            ['qty' => '10', 'price' => '100'],
            ['qty' => '5', 'price' => '200'],
            ['qty' => '2', 'price' => '300'],
        ];
        $r = $this->calc->compute(2, '0', '0', $tiers);
        $this->assertSame(2600, $r['amount']);
        $this->assertCount(3, $r['snapshot']['tiers']);
    }

    public function test_tru_giam_gia(): void
    {
        // Cố định 1 × 1.000.000, giảm 100.000 → 900.000
        $this->assertSame(900000, $this->calc->compute(1, '1', '1000000', [], '100000')['amount']);
    }

    public function test_giam_gia_vuot_khong_am(): void
    {
        $this->assertSame(0, $this->calc->compute(1, '1', '50000', [], '80000')['amount']);
    }

    public function test_lam_tron_half_up_toi_dong(): void
    {
        // gross lẻ .5 → làm tròn lên
        $this->assertSame(101, $this->calc->compute(1, '1', '100.5')['amount']);
        $this->assertSame(100, $this->calc->compute(1, '1', '100.4')['amount']);
    }
}
