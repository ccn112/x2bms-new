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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C/P2.1 — công cụ đối soát "số vàng" `billing:reconcile-engine`: engine dry-run so
 * từng dòng với số kế toán import; phải phân đúng khớp / lệch thật.
 */
class BillingEngineReconcileTest extends TestCase
{
    use RefreshDatabase;

    public function test_doi_soat_bat_dung_khop_va_lech_that(): void
    {
        $t = Tenant::create(['code' => 'TEN-REC', 'name' => 'T']);
        $proj = Project::create(['tenant_id' => $t->id, 'code' => 'PRJ-REC', 'name' => 'P']);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $proj->id, 'code' => 'BLD-REC', 'name' => 'B']);
        $ft = FeeType::create(['tenant_id' => $t->id, 'code' => 'QLDV', 'name' => 'Phí quản lý', 'category' => 'management', 'unit' => 'per_sqm', 'status' => 'active', 'vat_percent' => 0]);
        FeeRate::create(['tenant_id' => $t->id, 'fee_type_id' => $ft->id, 'code' => 'QL', 'name' => 'Giá', 'amount' => 15000, 'unit' => 'per_sqm', 'effective_from' => '2026-01-01', 'status' => 'active']);
        $period = BillingPeriod::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => '2026-08', 'label' => 'T8', 'period_month' => '2026-08-01']);

        // [area, golden kế toán]: 2 khớp (area×15000), 1 lệch thật.
        foreach ([[100.0, 1_500_000], [75.5, 1_132_500], [50.0, 900_000]] as $i => [$area, $golden]) {
            $apt = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => "REC-$i", 'area_sqm' => $area]);
            $stmt = Statement::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'apartment_id' => $apt->id, 'billing_period_id' => $period->id, 'total_amount' => $golden, 'paid_amount' => 0, 'status' => 'issued', 'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now()]);
            StatementLine::create(['statement_id' => $stmt->id, 'fee_type' => 'Phí quản lý', 'fee_type_id' => $ft->id, 'fee_category' => 'management', 'service_period_start' => '2026-08-01', 'service_period_end' => '2026-08-31', 'amount' => $golden, 'paid_amount' => 0, 'status' => 'issued', 'source' => 'import']);
        }

        $this->artisan('billing:reconcile-engine', ['building' => $b->id, 'period' => '2026-08'])
            ->assertSuccessful()
            ->expectsOutputToContain('Khớp tuyệt đối')
            ->expectsOutputToContain('lệch -150,000');   // căn 50m²: engine 750k vs kế toán 900k
    }
}
