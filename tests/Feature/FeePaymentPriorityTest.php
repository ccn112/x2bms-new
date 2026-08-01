<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\FeeTypePriorityOverride;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Services\Billing\ResidentPaymentClaimReviewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Phase B4 (D4-bis) — thứ tự ưu tiên phân bổ theo billing family + override theo dự án.
 *
 * Ba việc kiểm ở đây, đúng 3 mảnh của B4:
 *  1. `billing:backfill-fee-priority` gán ĐÚNG family (`BillingFamily::defaultPriority()`).
 *  2. Lệnh backfill AN TOÀN chạy lại — không đụng dòng đã khoá tay
 *     (`payment_priority_locked_at`), và ổn định (rerun không đổi dòng đã đúng).
 *  3. Override theo dự án (`fee_type_priority_overrides`) đổi được thứ tự phân bổ
 *     THẬT SỰ (qua `ResidentPaymentClaimReviewer`) cho MỘT dự án, dự án khác cùng
 *     tenant vẫn dùng mặc định — đúng yêu cầu D4 "override theo dự án", không phải
 *     đổi toàn tenant.
 */
class FeePaymentPriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_gan_dung_uu_tien_theo_family(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-FPP-BF', 'name' => 'Tenant FPP Backfill']);

        $management = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'QL-BF', 'name' => 'Phí quản lý', 'category' => 'management', 'payment_priority' => 250]);
        $vehicle = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'XE-BF', 'name' => 'Phí gửi xe ô tô', 'category' => 'parking', 'payment_priority' => 100]);
        $water = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'NUOC-BF', 'name' => 'Phí nước sinh hoạt', 'category' => 'utility', 'payment_priority' => 100]);
        $electricity = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'DIEN-BF', 'name' => 'Tiền điện', 'category' => 'utility', 'payment_priority' => 100]);
        $other = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'KHAC-BF', 'name' => 'Phí sự kiện', 'category' => 'service', 'payment_priority' => 100]);

        Artisan::call('billing:backfill-fee-priority');

        $this->assertSame(100, $management->fresh()->payment_priority, 'Phí quản lý → 100');
        $this->assertSame(400, $vehicle->fresh()->payment_priority, 'Phương tiện → 400');
        $this->assertSame(200, $water->fresh()->payment_priority, 'Nước → 200');
        $this->assertSame(300, $electricity->fresh()->payment_priority, 'Điện → 300');
        $this->assertSame(900, $other->fresh()->payment_priority, 'Khác → 900');
    }

    public function test_backfill_khong_dung_dong_da_khoa_tay_va_on_dinh_khi_chay_lai(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-FPP-LOCK', 'name' => 'Tenant FPP Lock']);

        $locked = FeeType::create([
            'tenant_id' => $tenant->id, 'code' => 'XE-LOCK', 'name' => 'Phí gửi xe ô tô', 'category' => 'parking',
            'payment_priority' => 350, 'payment_priority_locked_at' => now(),
        ]);
        $unlocked = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'DIEN-LOCK', 'name' => 'Tiền điện', 'category' => 'utility', 'payment_priority' => 100]);

        Artisan::call('billing:backfill-fee-priority');
        $this->assertSame(350, $locked->fresh()->payment_priority, 'Dòng đã khoá tay không bị ghi đè bởi mặc định family (400)');
        $this->assertSame(300, $unlocked->fresh()->payment_priority);

        // Chạy lại lần hai — cả hai đều ổn định, không đổi thêm lần nữa.
        Artisan::call('billing:backfill-fee-priority');
        $this->assertSame(350, $locked->fresh()->payment_priority);
        $this->assertSame(300, $unlocked->fresh()->payment_priority);
    }

    /** @return array{tenant:Tenant,projectA:Project,projectB:Project,buildingA:Building,buildingB:Building,apartmentA:Apartment,apartmentB:Apartment,management:FeeType,vehicle:FeeType} */
    private function twoProjectScope(): array
    {
        $tenant = Tenant::create(['code' => 'TEN-FPP-OVR', 'name' => 'Tenant FPP Override']);
        $projectA = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-FPP-A', 'name' => 'Project A']);
        $projectB = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-FPP-B', 'name' => 'Project B']);
        $buildingA = Building::create(['tenant_id' => $tenant->id, 'project_id' => $projectA->id, 'code' => 'BLD-FPP-A', 'name' => 'Building A']);
        $buildingB = Building::create(['tenant_id' => $tenant->id, 'project_id' => $projectB->id, 'code' => 'BLD-FPP-B', 'name' => 'Building B']);
        $apartmentA = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $buildingA->id, 'code' => 'APT-FPP-A']);
        $apartmentB = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $buildingB->id, 'code' => 'APT-FPP-B']);

        // Fee types TENANT-WIDE (dùng chung cho cả 2 dự án) — đã backfill: QL=100 < Xe=400.
        $management = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'QL-OVR', 'name' => 'Phí quản lý', 'category' => 'management', 'is_critical' => false, 'payment_priority' => 100]);
        $vehicle = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'XE-OVR', 'name' => 'Phí gửi xe ô tô', 'category' => 'parking', 'is_critical' => false, 'payment_priority' => 400]);

        return compact('tenant', 'projectA', 'projectB', 'buildingA', 'buildingB', 'apartmentA', 'apartmentB', 'management', 'vehicle');
    }

    private function statementWithTwoLines(array $s, Building $building, Apartment $apartment): Statement
    {
        $period = BillingPeriod::create(['tenant_id' => $s['tenant']->id, 'building_id' => $building->id, 'code' => '2026-07', 'label' => 'Tháng 7/2026', 'period_month' => '2026-07-01']);
        $st = Statement::create([
            'tenant_id' => $s['tenant']->id, 'building_id' => $building->id,
            'billing_period_id' => $period->id, 'apartment_id' => $apartment->id,
            'code' => 'BK-FPP-'.$apartment->id, 'total_amount' => 0, 'paid_amount' => 0,
            'status' => 'issued', 'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now(),
        ]);
        foreach (['management' => 500_000, 'vehicle' => 500_000] as $key => $amount) {
            StatementLine::create([
                'statement_id' => $st->id, 'fee_type_id' => $s[$key]->id, 'fee_type' => $s[$key]->name,
                'fee_category' => $s[$key]->category, 'amount' => $amount, 'paid_amount' => 0, 'status' => 'issued',
            ]);
        }
        $st->update(['total_amount' => 1_000_000]);

        return $st;
    }

    private function claim(array $s, Apartment $apartment, Statement $statement, float $amount): Payment
    {
        return Payment::create([
            'tenant_id' => $s['tenant']->id, 'building_id' => $apartment->building_id, 'apartment_id' => $apartment->id,
            'code' => 'TT'.strtoupper(bin2hex(random_bytes(4))), 'amount' => $amount, 'paid_at' => now(),
            'status' => Payment::STATUS_PENDING, 'source' => Payment::SOURCE_RESIDENT_APP,
            'claimed_statement_id' => $statement->id,
        ]);
    }

    public function test_khong_co_override_thi_du_an_dung_mac_dinh_tenant_ql_truoc_xe(): void
    {
        $s = $this->twoProjectScope();
        $statement = $this->statementWithTwoLines($s, $s['buildingA'], $s['apartmentA']);

        $payment = $this->claim($s, $s['apartmentA'], $statement, 500_000);
        app(ResidentPaymentClaimReviewer::class)->approve($payment, null);

        $mgmtLine = $statement->lines()->where('fee_type_id', $s['management']->id)->sole();
        $vehicleLine = $statement->lines()->where('fee_type_id', $s['vehicle']->id)->sole();
        $this->assertSame('500000.00', (string) $mgmtLine->fresh()->paid_amount, 'Mặc định tenant: QL (100) trả trước Xe (400)');
        $this->assertSame('0.00', (string) $vehicleLine->fresh()->paid_amount);
    }

    public function test_override_theo_du_an_doi_thu_tu_chi_o_du_an_do_du_an_khac_van_mac_dinh(): void
    {
        $s = $this->twoProjectScope();

        // Project A: override Xe = 50 (< 100 của QL) → Xe trả TRƯỚC quản lý, RIÊNG dự án A.
        FeeTypePriorityOverride::create([
            'tenant_id' => $s['tenant']->id, 'project_id' => $s['projectA']->id,
            'fee_type_id' => $s['vehicle']->id, 'payment_priority' => 50,
        ]);

        $statementA = $this->statementWithTwoLines($s, $s['buildingA'], $s['apartmentA']);
        $paymentA = $this->claim($s, $s['apartmentA'], $statementA, 500_000);
        app(ResidentPaymentClaimReviewer::class)->approve($paymentA, null);

        $mgmtA = $statementA->lines()->where('fee_type_id', $s['management']->id)->sole()->fresh();
        $vehicleA = $statementA->lines()->where('fee_type_id', $s['vehicle']->id)->sole()->fresh();
        $this->assertSame('500000.00', (string) $vehicleA->paid_amount, 'Dự án A có override: Xe (50) trả trước QL (100)');
        $this->assertSame('0.00', (string) $mgmtA->paid_amount);

        // Project B: KHÔNG có override → vẫn mặc định tenant-wide QL (100) trước Xe (400).
        $statementB = $this->statementWithTwoLines($s, $s['buildingB'], $s['apartmentB']);
        $paymentB = $this->claim($s, $s['apartmentB'], $statementB, 500_000);
        app(ResidentPaymentClaimReviewer::class)->approve($paymentB, null);

        $mgmtB = $statementB->lines()->where('fee_type_id', $s['management']->id)->sole()->fresh();
        $vehicleB = $statementB->lines()->where('fee_type_id', $s['vehicle']->id)->sole()->fresh();
        $this->assertSame('500000.00', (string) $mgmtB->paid_amount, 'Dự án B không override: vẫn QL (100) trước Xe (400)');
        $this->assertSame('0.00', (string) $vehicleB->paid_amount);
    }
}
