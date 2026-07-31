<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\ApartmentWallet;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Services\Resident\ApartmentWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `ApartmentWalletService::autoSettleOutstanding()` — dead code trước 2026-07-31
 * (`docs/delivery/TECH_DEBT_REGISTER.md` M8): ghi `statement_lines.paid_amount`
 * nhưng bỏ qua `statements.paid_amount`, sẽ phá bất biến nếu bật nguyên trạng.
 * Sửa để gọi `Statement::recomputePaidAmount()` sau khi hạch toán từng dòng.
 */
class ApartmentWalletAutoSettleTest extends TestCase
{
    use RefreshDatabase;

    private function scope(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-AWS-$tag", 'name' => "Tenant AWS $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-AWS-$tag", 'name' => "Project AWS $tag"]);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-AWS-$tag", 'name' => "Building AWS $tag"]);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-AWS-$tag"]);
        $period = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-07', 'label' => 'Tháng 7/2026', 'period_month' => '2026-07-01']);
        $feeType = FeeType::create(['tenant_id' => $tenant->id, 'code' => "QL-$tag", 'name' => 'Phí quản lý', 'category' => 'management', 'is_critical' => false, 'payment_priority' => 100]);

        return compact('tenant', 'project', 'building', 'apartment', 'period', 'feeType');
    }

    private function statementWithLine(array $s, float $amount): Statement
    {
        $st = Statement::create([
            'tenant_id' => $s['tenant']->id, 'building_id' => $s['building']->id,
            'billing_period_id' => $s['period']->id, 'apartment_id' => $s['apartment']->id,
            'code' => 'BK-AWS-'.$s['apartment']->id.'-'.random_int(1000, 9999),
            'total_amount' => $amount, 'paid_amount' => 0, 'status' => 'issued',
        ]);
        StatementLine::create([
            'statement_id' => $st->id, 'fee_type_id' => $s['feeType']->id, 'fee_type' => $s['feeType']->name,
            'fee_category' => $s['feeType']->category, 'amount' => $amount, 'paid_amount' => 0, 'status' => 'issued',
        ]);

        return $st;
    }

    public function test_sau_hach_toan_statement_paid_amount_khop_dong(): void
    {
        $s = $this->scope('T1');
        $statement = $this->statementWithLine($s, 1_000_000);

        $service = new ApartmentWalletService;
        $wallet = $service->walletFor($s['apartment']);
        $service->credit($wallet, '1000000');

        $service->autoSettleOutstanding($wallet->fresh());

        $line = $statement->lines()->sole();
        $this->assertSame('1000000.00', (string) $line->paid_amount);
        $this->assertSame('1000000.00', (string) $statement->fresh()->paid_amount, 'M8: statement.paid_amount phải khớp tổng các dòng sau khi ví hạch toán');
        $this->assertSame('paid', $statement->fresh()->status);
    }

    public function test_chi_recompute_dung_statement_bi_cham_toi_khong_dung_ca_can_ho(): void
    {
        $s = $this->scope('T2');
        $statementA = $this->statementWithLine($s, 500_000);
        $statementB = $this->statementWithLine($s, 500_000);

        $service = new ApartmentWalletService;
        $wallet = $service->walletFor($s['apartment']);
        $service->credit($wallet, '500000'); // chỉ đủ trả MỘT bảng kê

        $service->autoSettleOutstanding($wallet->fresh());

        // Dòng nào trả trước phụ thuộc allocationSortKey (cùng priority → id tăng dần).
        $paidCount = collect([$statementA->fresh(), $statementB->fresh()])
            ->filter(fn (Statement $st) => (string) $st->paid_amount === '500000.00')
            ->count();
        $untouchedCount = collect([$statementA->fresh(), $statementB->fresh()])
            ->filter(fn (Statement $st) => (string) $st->paid_amount === '0.00')
            ->count();

        $this->assertSame(1, $paidCount);
        $this->assertSame(1, $untouchedCount);
    }
}
