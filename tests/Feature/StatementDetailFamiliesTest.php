<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * FIN-11 — chi tiết bảng kê gom theo 5 billing family + cột tiền + chỉ số điện/nước.
 */
class StatementDetailFamiliesTest extends TestCase
{
    use RefreshDatabase;

    public function test_chi_tiet_bang_ke_gom_5_family_co_chi_so_va_no_truoc(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-F11', 'name' => 'F11']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-F11', 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-F11', 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'A-05']);
        $user = User::create(['name' => 'CD', 'email' => 'f11@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => 'RES-F11', 'full_name' => 'CD']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);

        $pql = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'PQL', 'name' => 'Phí quản lý', 'category' => 'management', 'payment_priority' => 100]);
        $nuoc = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'NUOC', 'name' => 'Phí nước', 'category' => 'utility', 'payment_priority' => 200]);
        $oto = FeeType::create(['tenant_id' => $tenant->id, 'code' => 'OTO', 'name' => 'Phí ô tô', 'category' => 'parking', 'payment_priority' => 400]);

        $prevPeriod = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-04', 'label' => 'T4', 'period_month' => '2026-04-01']);
        $period = BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '2026-05', 'label' => 'T5', 'period_month' => '2026-05-01']);

        // Bảng kê kỳ TRƯỚC (đã phát hành) còn nợ phí quản lý 500.000 → previous_debt.
        $prev = $this->publishedStatement($tenant, $building, $prevPeriod, $apartment, 'BK-2026-04-A-05');
        StatementLine::create(['statement_id' => $prev->id, 'fee_type_id' => $pql->id, 'fee_type' => 'Phí quản lý', 'fee_category' => 'management', 'amount' => 500000, 'paid_amount' => 0, 'status' => 'issued', 'service_period_start' => '2026-04-01']);

        // Bảng kê kỳ NÀY (đã phát hành): quản lý + nước (metered) + ô tô.
        $st = $this->publishedStatement($tenant, $building, $period, $apartment, 'BK-2026-05-A-05');
        StatementLine::create(['statement_id' => $st->id, 'fee_type_id' => $pql->id, 'fee_type' => 'Phí quản lý', 'fee_category' => 'management', 'amount' => 1911000, 'paid_amount' => 0, 'status' => 'issued', 'service_period_start' => '2026-05-01']);
        StatementLine::create(['statement_id' => $st->id, 'fee_type_id' => $nuoc->id, 'fee_type' => 'Phí nước', 'fee_category' => 'water', 'amount' => 24150, 'paid_amount' => 0, 'status' => 'issued', 'service_period_start' => '2026-04-28', 'calculation_snapshot' => ['method' => 'metered', 'previous_reading' => '60', 'current_reading' => '62', 'consumption' => '2', 'tiers' => [['tier' => 1, 'qty' => '2', 'price' => '12075', 'subtotal' => 24150]]]]);
        StatementLine::create(['statement_id' => $st->id, 'fee_type_id' => $oto->id, 'fee_type' => 'Phí ô tô', 'fee_category' => 'vehicle', 'amount' => 800000, 'paid_amount' => 800000, 'status' => 'paid', 'service_period_start' => '2026-05-01']);

        Sanctum::actingAs($user, ['resident']);
        $res = $this->getJson("/api/v1/resident/statements/{$st->id}")->assertOk();

        $families = collect($res->json('data.families'));
        // Thứ tự hiển thị: management → water → vehicle (electricity/other rỗng bị ẩn).
        $this->assertSame(['management', 'water', 'vehicle'], $families->pluck('code')->all());

        $mgmt = $families->firstWhere('code', 'management');
        $this->assertSame('1911000', $mgmt['amounts']['current_period']);
        $this->assertSame('500000', $mgmt['amounts']['previous_debt']);
        // Còn nợ = phát sinh kỳ + nợ trước = 1.911.000 + 500.000
        $this->assertSame('2411000', $mgmt['amounts']['outstanding']);

        $water = $families->firstWhere('code', 'water');
        $line = $water['fee_definitions'][0]['lines'][0];
        $this->assertSame('2', $line['meter']['consumption']);
        $this->assertSame('60', $line['meter']['previous_reading']);
        $this->assertSame('24150', $line['amount']);

        $vehicle = $families->firstWhere('code', 'vehicle');
        $this->assertSame('0', $vehicle['amounts']['outstanding']); // đã trả hết
    }

    private function publishedStatement($tenant, $building, $period, $apartment, string $code): Statement
    {
        return Statement::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'billing_period_id' => $period->id,
            'apartment_id' => $apartment->id, 'code' => $code, 'total_amount' => 0, 'paid_amount' => 0,
            'status' => 'issued', 'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now(),
        ]);
    }
}
