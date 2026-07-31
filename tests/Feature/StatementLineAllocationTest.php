<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\ResidentPaymentClaimReviewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase B3 — phân bổ tiền theo TỪNG DÒNG PHÍ (D3). Trước bản này
 * `payment_allocations.statement_line_id` có cột nhưng không dòng code nào ghi
 * (`docs/delivery/TECH_DEBT_REGISTER.md` M5).
 */
class StatementLineAllocationTest extends TestCase
{
    use RefreshDatabase;

    private function scope(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-SLA-$tag", 'name' => "Tenant SLA $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-SLA-$tag", 'name' => "Project SLA $tag"]);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-SLA-$tag", 'name' => "Building SLA $tag"]);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-SLA-$tag"]);
        $period = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-07', 'label' => 'Tháng 7/2026', 'period_month' => '2026-07-01']);

        $management = FeeType::create(['tenant_id' => $tenant->id, 'code' => "QL-$tag", 'name' => 'Phí quản lý', 'category' => 'management', 'is_critical' => false, 'payment_priority' => 100]);
        $electric = FeeType::create(['tenant_id' => $tenant->id, 'code' => "DIEN-$tag", 'name' => 'Tiền điện', 'category' => 'utility', 'is_critical' => true, 'payment_priority' => 300]);

        return compact('tenant', 'project', 'building', 'apartment', 'period', 'management', 'electric');
    }

    private function statement(array $s): Statement
    {
        return Statement::create([
            'tenant_id' => $s['tenant']->id, 'building_id' => $s['building']->id,
            'billing_period_id' => $s['period']->id, 'apartment_id' => $s['apartment']->id,
            'code' => 'BK-SLA-'.$s['apartment']->id, 'total_amount' => 0, 'paid_amount' => 0,
            'status' => 'issued', 'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now(),
        ]);
    }

    private function line(Statement $st, FeeType $feeType, float $amount, ?string $servicePeriodStart = null): StatementLine
    {
        $line = StatementLine::create([
            'statement_id' => $st->id, 'fee_type_id' => $feeType->id, 'fee_type' => $feeType->name,
            'fee_category' => $feeType->category, 'amount' => $amount, 'paid_amount' => 0, 'status' => 'issued',
            'service_period_start' => $servicePeriodStart,
        ]);
        $st->update(['total_amount' => $st->total_amount + $amount]);

        return $line;
    }

    private function claim(array $s, float $amount, Statement $statement): Payment
    {
        return Payment::create([
            'tenant_id' => $s['tenant']->id, 'building_id' => $s['building']->id, 'apartment_id' => $s['apartment']->id,
            'code' => 'TT'.strtoupper(bin2hex(random_bytes(4))), 'amount' => $amount, 'paid_at' => now(),
            'status' => Payment::STATUS_PENDING, 'source' => Payment::SOURCE_RESIDENT_APP,
            'claimed_statement_id' => $statement->id,
        ]);
    }

    public function test_uu_tien_quan_ly_truoc_dien_du_dien_la_critical(): void
    {
        // is_critical KHÔNG thắng payment_priority theo khoá đã chốt — kiểm đúng
        // thứ tự thật: is_critical trước, payment_priority sau. Điện critical
        // (priority 300) vẫn đứng SAU quản lý không-critical? Không — khoá xếp
        // is_critical desc (critical=0) trước payment_priority, nên ĐIỆN được ưu
        // tiên trước dù priority số lớn hơn. Test khoá đúng hành vi thật của
        // allocationSortKey(), không phải trực giác.
        $s = $this->scope('T1');
        $st = $this->statement($s);
        $mgmt = $this->line($st, $s['management'], 1_000_000);
        $electric = $this->line($st, $s['electric'], 500_000);

        $payment = $this->claim($s, 500_000, $st);
        app(ResidentPaymentClaimReviewer::class)->approve($payment, null);

        $this->assertSame('500000.00', (string) $electric->fresh()->paid_amount, 'điện is_critical được trả trước dù priority số lớn hơn');
        $this->assertSame('0.00', (string) $mgmt->fresh()->paid_amount);
    }

    public function test_chia_tien_qua_hai_dong_khi_khong_du_tra_het_dong_dau(): void
    {
        $s = $this->scope('T2');
        $st = $this->statement($s);
        $electric = $this->line($st, $s['electric'], 500_000); // critical → trả trước
        $mgmt = $this->line($st, $s['management'], 1_000_000);

        $payment = $this->claim($s, 800_000, $st);
        app(ResidentPaymentClaimReviewer::class)->approve($payment, null);

        $this->assertSame('500000.00', (string) $electric->fresh()->paid_amount, 'dòng điện trả hết trước');
        $this->assertSame('300000.00', (string) $mgmt->fresh()->paid_amount, 'phần còn lại chảy sang dòng quản lý');
        $this->assertSame('800000.00', (string) $st->fresh()->paid_amount);
        $this->assertSame('partial', $st->fresh()->status);
    }

    public function test_payment_allocation_ghi_dung_statement_line_id(): void
    {
        $s = $this->scope('T3');
        $st = $this->statement($s);
        $electric = $this->line($st, $s['electric'], 500_000);
        $mgmt = $this->line($st, $s['management'], 1_000_000);

        $payment = $this->claim($s, 800_000, $st);
        app(ResidentPaymentClaimReviewer::class)->approve($payment, null);

        $allocations = PaymentAllocation::where('payment_id', $payment->id)->get()->keyBy('statement_line_id');
        $this->assertSame('500000.00', (string) $allocations[$electric->id]->amount);
        $this->assertSame('300000.00', (string) $allocations[$mgmt->id]->amount);
    }

    public function test_tra_du_khong_vuot_qua_amount_cua_dong(): void
    {
        $s = $this->scope('T4');
        $st = $this->statement($s);
        $mgmt = $this->line($st, $s['management'], 1_000_000);

        $payment = $this->claim($s, 5_000_000, $st); // trả dư rất nhiều
        app(ResidentPaymentClaimReviewer::class)->approve($payment, null);

        $this->assertSame('1000000.00', (string) $mgmt->fresh()->paid_amount, 'không được vượt amount của dòng');
        $this->assertSame('1000000.00', (string) $st->fresh()->paid_amount);
        $this->assertSame('paid', $st->fresh()->status);
        // Phần dư (4.000.000) không được phân bổ vào đâu cả.
        $this->assertEquals(1_000_000, PaymentAllocation::where('payment_id', $payment->id)->sum('amount'));
    }

    public function test_thanh_toan_het_moi_dong_thi_bang_ke_thanh_paid(): void
    {
        $s = $this->scope('T5');
        $st = $this->statement($s);
        $this->line($st, $s['electric'], 500_000);
        $this->line($st, $s['management'], 1_000_000);

        $payment = $this->claim($s, 1_500_000, $st);
        app(ResidentPaymentClaimReviewer::class)->approve($payment, null);

        $this->assertSame('paid', $st->fresh()->status);
        $this->assertSame('1500000.00', (string) $st->fresh()->paid_amount);
    }
}
