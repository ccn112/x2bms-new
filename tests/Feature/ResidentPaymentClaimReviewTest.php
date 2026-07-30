<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\Statement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\ResidentPaymentClaimReviewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * BQL duyệt / từ chối chứng từ chuyển khoản cư dân nộp (chốt 2026-07-30 ý 2).
 *
 * Đây là nghiệp vụ TIỀN nên test đi vào từng con số: phân bổ bao nhiêu,
 * `paid_amount` thành bao nhiêu, `status` hoá đơn đổi thế nào, có bao nhiêu biên
 * lai. Test ở tầng service (`ResidentPaymentClaimReviewer`) — cùng cách tiếp cận
 * với `ListingModerationTest`.
 */
class ResidentPaymentClaimReviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeContext(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "Tenant $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "Project $tag"]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'code' => "BLD-$tag", 'name' => "Building $tag",
        ]);
        $apartment = Apartment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag",
        ]);
        $resident = User::create([
            'name' => "Cu dan $tag", 'email' => strtolower($tag).'-res@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $staff = User::create([
            'name' => "BQL $tag", 'email' => strtolower($tag).'-staff@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'staff',
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id,
        ]);

        // `statements.billing_period_id` là NOT NULL — hoá đơn luôn thuộc một kỳ
        // phí, không có hoá đơn "trôi nổi".
        $period = BillingPeriod::create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'code' => '2026-07',
            'label' => 'Tháng 7/2026',
            'period_month' => '2026-07-01',
            'is_current' => true,
        ]);

        return compact('tenant', 'project', 'building', 'apartment', 'resident', 'staff', 'period');
    }

    private function statement(array $ctx, float $total, float $paid = 0, string $status = 'issued'): Statement
    {
        return Statement::create([
            'tenant_id' => $ctx['tenant']->id,
            'building_id' => $ctx['building']->id,
            'billing_period_id' => $ctx['period']->id,
            'apartment_id' => $ctx['apartment']->id,
            'code' => 'ST-'.$ctx['apartment']->code.'-'.random_int(1000, 9999),
            'total_amount' => $total,
            'paid_amount' => $paid,
            'status' => $status,
        ]);
    }

    private function claim(array $ctx, float $amount, ?Statement $statement = null): Payment
    {
        return Payment::create([
            'tenant_id' => $ctx['tenant']->id,
            'building_id' => $ctx['building']->id,
            'apartment_id' => $ctx['apartment']->id,
            'code' => 'TT'.strtoupper(bin2hex(random_bytes(4))),
            'amount' => $amount,
            'paid_at' => now()->subHours(3),
            'status' => Payment::STATUS_PENDING,
            'source' => Payment::SOURCE_RESIDENT_APP,
            'submitted_by_id' => $ctx['resident']->id,
            'submitted_at' => now()->subHours(2),
            'claimed_statement_id' => $statement?->id,
        ]);
    }

    private function reviewer(): ResidentPaymentClaimReviewer
    {
        return app(ResidentPaymentClaimReviewer::class);
    }

    public function test_duyet_ghi_nhan_tien_va_giam_cong_no(): void
    {
        $ctx = $this->makeContext('AP1');
        $st = $this->statement($ctx, 5_000_000);
        $claim = $this->claim($ctx, 5_000_000, $st);

        $this->reviewer()->approve($claim, $ctx['staff']);

        $claim->refresh();
        $st->refresh();

        $this->assertSame(Payment::STATUS_CONFIRMED, $claim->status);
        $this->assertSame($ctx['staff']->id, $claim->reviewed_by_id);
        $this->assertNotNull($claim->reviewed_at);

        $this->assertSame('5000000.00', (string) $st->paid_amount);
        $this->assertSame('paid', $st->status, 'trả đủ thì hoá đơn phải sang paid');

        $this->assertSame(1, PaymentAllocation::where('payment_id', $claim->id)->count());
        $this->assertSame(1, Receipt::where('payment_id', $claim->id)->count());
    }

    public function test_tra_mot_phan_thi_hoa_don_sang_partial(): void
    {
        $ctx = $this->makeContext('AP2');
        $st = $this->statement($ctx, 5_000_000);
        $claim = $this->claim($ctx, 2_000_000, $st);

        $this->reviewer()->approve($claim, $ctx['staff']);
        $st->refresh();

        $this->assertSame('2000000.00', (string) $st->paid_amount);
        $this->assertSame('partial', $st->status);
    }

    public function test_khong_bao_gio_phan_bo_vuot_phan_con_no(): void
    {
        // Cư dân trả GỘP nhiều kỳ hoặc trả dư. `paid_amount` vượt `total_amount`
        // thì hoá đơn hiện "đã trả 6 triệu / tổng 5 triệu" và mọi báo cáo công nợ
        // sai theo — phần vượt phải để nguyên chưa phân bổ.
        $ctx = $this->makeContext('AP3');
        $st = $this->statement($ctx, 5_000_000, 3_000_000, 'partial');
        $claim = $this->claim($ctx, 4_000_000, $st);

        $this->reviewer()->approve($claim, $ctx['staff']);
        $st->refresh();

        $this->assertSame('5000000.00', (string) $st->paid_amount,
            'chỉ được phân bổ đúng 2.000.000 còn nợ, không phải cả 4.000.000');
        $this->assertSame('paid', $st->status);
        $this->assertSame('2000000.00',
            (string) PaymentAllocation::where('payment_id', $claim->id)->value('amount'));
    }

    public function test_hoa_don_da_tra_du_thi_khong_tao_phan_bo_rong(): void
    {
        $ctx = $this->makeContext('AP4');
        $st = $this->statement($ctx, 5_000_000, 5_000_000, 'paid');
        $claim = $this->claim($ctx, 1_000_000, $st);

        $this->reviewer()->approve($claim, $ctx['staff']);

        $this->assertSame(0, PaymentAllocation::where('payment_id', $claim->id)->count(),
            'không tạo phân bổ 0 đồng — nó chỉ làm rác sổ phân bổ');
        $this->assertSame(Payment::STATUS_CONFIRMED, $claim->refresh()->status,
            'tiền vẫn được ghi nhận, chỉ là chưa gán vào hoá đơn nào');
        $this->assertSame(1, Receipt::where('payment_id', $claim->id)->count());
    }

    public function test_khai_khong_gan_hoa_don_van_duyet_duoc(): void
    {
        $ctx = $this->makeContext('AP5');
        $claim = $this->claim($ctx, 800_000);

        $this->reviewer()->approve($claim, $ctx['staff']);

        $this->assertSame(Payment::STATUS_CONFIRMED, $claim->refresh()->status);
        $this->assertSame(0, PaymentAllocation::where('payment_id', $claim->id)->count());
        $this->assertSame(1, Receipt::where('payment_id', $claim->id)->count());
    }

    public function test_duyet_hai_lan_khong_ghi_nhan_tien_gap_doi(): void
    {
        // Hai người của BQL bấm Duyệt gần như đồng thời. Không idempotent thì:
        // hai phân bổ, hai biên lai, công nợ trừ gấp đôi.
        $ctx = $this->makeContext('AP6');
        $st = $this->statement($ctx, 5_000_000);
        $claim = $this->claim($ctx, 2_000_000, $st);

        $this->reviewer()->approve($claim, $ctx['staff']);
        $this->reviewer()->approve($claim, $ctx['staff']);
        $this->reviewer()->approve($claim->fresh(), $ctx['staff']);

        $st->refresh();
        $this->assertSame('2000000.00', (string) $st->paid_amount);
        $this->assertSame(1, PaymentAllocation::where('payment_id', $claim->id)->count());
        $this->assertSame(1, Receipt::where('payment_id', $claim->id)->count());
    }

    public function test_tu_choi_bat_buoc_co_ly_do(): void
    {
        $ctx = $this->makeContext('RJ1');
        $claim = $this->claim($ctx, 1_000_000);

        $this->expectException(InvalidArgumentException::class);
        $this->reviewer()->reject($claim, $ctx['staff'], '   ');
    }

    public function test_tu_choi_khong_ghi_nhan_dong_nao(): void
    {
        $ctx = $this->makeContext('RJ2');
        $st = $this->statement($ctx, 5_000_000);
        $claim = $this->claim($ctx, 5_000_000, $st);

        $this->reviewer()->reject($claim, $ctx['staff'], 'Số tiền trên ảnh không khớp sao kê');

        $claim->refresh();
        $st->refresh();

        $this->assertSame(Payment::STATUS_REJECTED, $claim->status);
        $this->assertSame('Số tiền trên ảnh không khớp sao kê', $claim->review_note);
        $this->assertSame('0.00', (string) $st->paid_amount);
        $this->assertSame('issued', $st->status);
        $this->assertSame(0, PaymentAllocation::where('payment_id', $claim->id)->count());
        $this->assertSame(0, Receipt::where('payment_id', $claim->id)->count());
    }

    public function test_da_tu_choi_thi_khong_duyet_lai_duoc(): void
    {
        // Quyết định tuần tự hoá theo ai xong trước; không có đường đi ngược từ
        // rejected sang confirmed mà không có người mở lại tường minh.
        $ctx = $this->makeContext('RJ3');
        $st = $this->statement($ctx, 5_000_000);
        $claim = $this->claim($ctx, 5_000_000, $st);

        $this->reviewer()->reject($claim, $ctx['staff'], 'Ảnh mờ không đọc được');
        $this->reviewer()->approve($claim->fresh(), $ctx['staff']);

        $this->assertSame(Payment::STATUS_REJECTED, $claim->refresh()->status);
        $this->assertSame('0.00', (string) $st->refresh()->paid_amount);
        $this->assertSame(0, Receipt::where('payment_id', $claim->id)->count());
    }

    public function test_khoan_cua_bq_l_nhap_tay_khong_bi_service_nay_cham_vao(): void
    {
        // Service chỉ xử lý khoản đang `pending`. Khoản BQL nhập tay đã
        // `confirmed` từ đầu → no-op, không sinh biên lai/phân bổ lần hai.
        $ctx = $this->makeContext('ST1');
        $st = $this->statement($ctx, 5_000_000);
        $staffPayment = Payment::create([
            'tenant_id' => $ctx['tenant']->id,
            'building_id' => $ctx['building']->id,
            'apartment_id' => $ctx['apartment']->id,
            'code' => 'PM-STAFF-1',
            'amount' => 5_000_000,
            'paid_at' => now()->subDay(),
            'status' => Payment::STATUS_CONFIRMED,
            'source' => Payment::SOURCE_STAFF,
            'claimed_statement_id' => $st->id,
        ]);

        $this->reviewer()->approve($staffPayment, $ctx['staff']);

        $this->assertSame('0.00', (string) $st->refresh()->paid_amount);
        $this->assertSame(0, Receipt::where('payment_id', $staffPayment->id)->count());
    }

    public function test_ma_bien_lai_dung_dinh_dang_va_tang_dan(): void
    {
        $ctx = $this->makeContext('BL1');
        $prefix = 'BL-'.now()->format('ym').'-';

        $first = $this->claim($ctx, 1_000_000);
        $second = $this->claim($ctx, 2_000_000);
        $this->reviewer()->approve($first, $ctx['staff']);
        $this->reviewer()->approve($second, $ctx['staff']);

        $codes = Receipt::whereIn('payment_id', [$first->id, $second->id])
            ->orderBy('id')->pluck('code')->all();

        $this->assertSame([$prefix.'001', $prefix.'002'], $codes);
    }
}
