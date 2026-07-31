<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReconcileStatementBalancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sua_dung_statement_lech_khoi_tong_cac_dong(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-RSB-1', 'name' => 'Tenant RSB 1']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-RSB-1', 'name' => 'Project RSB 1']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-RSB-1', 'name' => 'Building RSB 1']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'APT-RSB-1']);
        $period = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-07', 'label' => 'Tháng 7/2026', 'period_month' => '2026-07-01']);
        $feeType = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'QL-RSB1', 'name' => 'Phí quản lý', 'category' => 'management']);

        $statement = Statement::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'billing_period_id' => $period->id,
            'apartment_id' => $apartment->id, 'code' => 'BK-RSB-1', 'total_amount' => 1_000_000,
            // Cố tình đặt SAI — mô phỏng lệch do một đường ghi tiền quên gọi recomputePaidAmount().
            'paid_amount' => 999_999, 'status' => 'partial',
        ]);
        StatementLine::create([
            'statement_id' => $statement->id, 'fee_type_id' => $feeType->id, 'fee_type' => $feeType->name,
            'fee_category' => 'management', 'amount' => 1_000_000, 'paid_amount' => 400_000, 'status' => 'partial',
        ]);

        Artisan::call('billing:reconcile-statement-balances');

        $this->assertSame('400000.00', (string) $statement->fresh()->paid_amount);
        $this->assertSame('partial', $statement->fresh()->status);
    }

    public function test_dry_run_khong_ghi_gi(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-RSB-2', 'name' => 'Tenant RSB 2']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-RSB-2', 'name' => 'Project RSB 2']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-RSB-2', 'name' => 'Building RSB 2']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'APT-RSB-2']);
        $period = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-07', 'label' => 'Tháng 7/2026', 'period_month' => '2026-07-01']);
        $feeType = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'QL-RSB2', 'name' => 'Phí quản lý', 'category' => 'management']);

        $statement = Statement::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'billing_period_id' => $period->id,
            'apartment_id' => $apartment->id, 'code' => 'BK-RSB-2', 'total_amount' => 1_000_000,
            'paid_amount' => 999_999, 'status' => 'partial',
        ]);
        StatementLine::create([
            'statement_id' => $statement->id, 'fee_type_id' => $feeType->id, 'fee_type' => $feeType->name,
            'fee_category' => 'management', 'amount' => 1_000_000, 'paid_amount' => 400_000, 'status' => 'partial',
        ]);

        Artisan::call('billing:reconcile-statement-balances', ['--dry-run' => true]);

        $this->assertSame('999999.00', (string) $statement->fresh()->paid_amount, 'dry-run không được ghi gì');
    }
}
