<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeRate;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Services\Billing\Engine\BillingRunner;
use App\Services\Billing\Engine\ManagementFeeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C / P2.1 — engine tính phí quản lý. Generator THUẦN test không cần DB; runner ghi
 * `pending` + idempotent + số nguyên đồng half-up.
 */
class BillingEngineManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_generator_thuan_lam_tron_half_up_va_vat(): void
    {
        $g = new ManagementFeeGenerator;
        $ft = ['fee_type_id' => 1, 'fee_type_name' => 'Phí quản lý', 'vat_percent' => 0];

        // 100 m² × 15.000 = 1.500.000
        $this->assertSame(1_500_000, $g->generate($ft, 100.0, 15000, '2026-07-01', '2026-07-31', 5)->amount);

        // Half-up: 100.5 × 15.001 = 1.507.600,5 → 1.507.601
        $this->assertSame(1_507_601, $g->generate($ft, 100.5, 15001, '2026-07-01', '2026-07-31')->amount);

        // VAT 8%: base 1.000.000 + 80.000 = 1.080.000
        $ftVat = ['fee_type_id' => 1, 'fee_type_name' => 'PQL', 'vat_percent' => 8];
        $this->assertSame(1_080_000, $g->generate($ftVat, 100.0, 10000, '2026-07-01', '2026-07-31')->amount);

        // Thiếu diện tích/giá → null (runner bỏ qua, không ghi 0đ).
        $this->assertNull($g->generate($ft, 0.0, 15000, '2026-07-01', '2026-07-31'));
        $this->assertNull($g->generate($ft, 100.0, 0, '2026-07-01', '2026-07-31'));

        // snapshot có công thức + input (phục vụ "vì sao hóa đơn cao").
        $snap = $g->generate($ft, 100.0, 15000, '2026-07-01', '2026-07-31', 5)->snapshot;
        $this->assertSame(15000, $snap['unit_price']);
        $this->assertSame(1_500_000, $snap['total']);
        $this->assertArrayHasKey('formula', $snap);
    }

    public function test_runner_dry_run_khong_ghi_va_tong_dung(): void
    {
        [$t, $b, $p] = $this->scaffold([100, 50]);   // 2 căn 100 & 50 m², đơn giá 15.000

        $r = app(BillingRunner::class)->runManagement($t->id, $b->id, $p->id, '2026-07-01', '2026-07-31', commit: false);

        $this->assertFalse($r['committed']);
        $this->assertSame(2, $r['apartments']);
        $this->assertSame(2_250_000, $r['total']);   // 1.500.000 + 750.000
        $this->assertDatabaseCount('statements', 0); // dry-run KHÔNG ghi
    }

    public function test_runner_commit_ghi_pending_va_idempotent(): void
    {
        [$t, $b, $p] = $this->scaffold([100, 50]);
        $runner = app(BillingRunner::class);

        $r = $runner->runManagement($t->id, $b->id, $p->id, '2026-07-01', '2026-07-31', commit: true);
        $this->assertTrue($r['committed']);
        $this->assertDatabaseCount('statements', 2);
        $this->assertSame(2, StatementLine::count());

        // Engine ghi PENDING (không tự phát hành) + source=engine.
        foreach (Statement::all() as $s) {
            $this->assertSame(Statement::APPROVAL_PENDING, $s->approval_status);
        }
        $this->assertSame(2, StatementLine::where('source', 'engine')->count());

        // Chạy lại cùng kỳ → KHÔNG nhân đôi (idempotent theo natural key).
        $runner->runManagement($t->id, $b->id, $p->id, '2026-07-01', '2026-07-31', commit: true);
        $this->assertDatabaseCount('statements', 2);
        $this->assertSame(2, StatementLine::count());
    }

    /** @return array{0:Tenant,1:Building,2:BillingPeriod} */
    private function scaffold(array $areas): array
    {
        $t = Tenant::create(['code' => 'TEN-ENG', 'name' => 'T']);
        $proj = Project::create(['tenant_id' => $t->id, 'code' => 'PRJ-ENG', 'name' => 'P']);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $proj->id, 'code' => 'BLD-ENG', 'name' => 'B']);
        foreach ($areas as $i => $area) {
            Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => 'APT-'.$i, 'area_sqm' => $area]);
        }
        $ft = FeeType::create(['tenant_id' => $t->id, 'code' => 'QLDV', 'name' => 'Phí quản lý', 'category' => 'management', 'unit' => 'per_sqm', 'status' => 'active', 'vat_percent' => 0]);
        FeeRate::create(['tenant_id' => $t->id, 'fee_type_id' => $ft->id, 'code' => 'QL-2026', 'name' => 'Giá QL 2026', 'amount' => 15000, 'unit' => 'per_sqm', 'effective_from' => '2026-01-01', 'status' => 'active']);
        $p = BillingPeriod::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => '2026-07', 'label' => 'T7', 'period_month' => '2026-07-01']);

        return [$t, $b, $p];
    }
}
