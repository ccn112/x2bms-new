<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\BillingPeriod;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * D6 — GET /api/v1/resident/debts/by-service.
 *
 * Gom các dòng phí CÒN NỢ của căn hộ thành cây family › fee_type › tài sản › tháng.
 * Ba mệnh đề phải đúng cùng lúc:
 *  - Dòng gắn XE gom về đúng một tài sản (51K-838888) với đủ 3 tháng nợ.
 *  - Dòng ĐÃ TRẢ ĐỦ bị loại (scopeOutstanding).
 *  - Dòng KHÔNG gắn tài sản (phí quản lý) gom dưới subject_type = 'apartment'.
 */
class DebtByServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cay_cong_no_theo_dich_vu_gom_theo_xe_loai_dong_da_tra(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-D6', 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-D6', 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-D6', 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'APT-D6']);

        $user = User::create([
            'name' => 'Chủ xe', 'email' => 'd6-owner@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => 'RES-D6', 'full_name' => 'Chủ xe',
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);

        $oto = FeeType::create([
            'tenant_id' => $tenant->id, 'code' => 'OTO', 'name' => 'Phí gửi ô tô',
            'category' => 'parking', 'unit' => 'per_vehicle', 'is_recurring' => true, 'status' => 'active',
        ]);
        $ql = FeeType::create([
            'tenant_id' => $tenant->id, 'code' => 'QL', 'name' => 'Phí quản lý',
            'category' => 'management', 'unit' => 'per_sqm', 'is_recurring' => true, 'status' => 'active',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'apartment_id' => $apartment->id,
            'resident_id' => $resident->id, 'plate_no' => '51K-838888', 'type' => 'car',
            'monthly_fee' => 1_500_000, 'status' => 'active',
        ]);

        // 3 tháng nợ phí gửi ô tô (mỗi tháng một bảng kê ĐÃ PHÁT HÀNH).
        foreach (['2026-05', '2026-06', '2026-07'] as $ym) {
            $statement = $this->publishedStatement($tenant, $building, $apartment, $ym, '1500000');
            StatementLine::create([
                'statement_id' => $statement->id, 'fee_type' => 'Phí gửi ô tô',
                'fee_type_id' => $oto->id, 'fee_category' => 'parking',
                'subject_type' => $vehicle->getMorphClass(), 'subject_id' => $vehicle->id,
                'service_period_start' => $ym.'-01', 'service_period_end' => $ym.'-28',
                'amount' => 1_500_000, 'paid_amount' => 0,
            ]);
        }

        // Bảng kê tháng 07 mang thêm: 1 phí quản lý CHƯA trả (không gắn tài sản) +
        // 1 phí quản lý ĐÃ trả đủ (phải bị loại khỏi cây).
        $julyStmt = Statement::where('apartment_id', $apartment->id)
            ->whereHas('billingPeriod', fn ($q) => $q->where('code', '2026-07'))->first();
        StatementLine::create([
            'statement_id' => $julyStmt->id, 'fee_type' => 'Phí quản lý',
            'fee_type_id' => $ql->id, 'fee_category' => 'management',
            'service_period_start' => '2026-07-01',
            'amount' => 500_000, 'paid_amount' => 0,
        ]);
        StatementLine::create([
            'statement_id' => $julyStmt->id, 'fee_type' => 'Phí quản lý',
            'fee_type_id' => $ql->id, 'fee_category' => 'management',
            'service_period_start' => '2026-06-01',
            'amount' => 300_000, 'paid_amount' => 300_000, // đã trả đủ → LOẠI
        ]);

        Sanctum::actingAs($user, ['resident']);

        $res = $this->getJson('/api/v1/resident/debts/by-service')->assertOk();
        $data = $res->json('data');

        // Tổng nợ = 3×1.5tr (xe) + 500k (quản lý) = 5.000.000; dòng đã trả 300k KHÔNG cộng.
        $this->assertSame('5000000.00', $data['total_outstanding']);

        $families = collect($data['families'])->keyBy('family');
        $this->assertTrue($families->has('parking'), 'có family phương tiện');
        $this->assertSame('Phương tiện', $families['parking']['label']);
        $this->assertSame('4500000.00', $families['parking']['outstanding']);

        // fee_type › subject xe với đủ 3 tháng.
        $parkingFt = collect($families['parking']['fee_types'])->firstWhere('name', 'Phí gửi ô tô');
        $this->assertNotNull($parkingFt);
        $this->assertSame('per_vehicle', $parkingFt['unit']);
        $carSubject = collect($parkingFt['subjects'])->firstWhere('label', '51K-838888');
        $this->assertNotNull($carSubject, 'tài sản là chiếc xe 51K-838888');
        $this->assertSame('vehicle', $carSubject['subject_type']);
        $this->assertSame((string) $vehicle->id, $carSubject['subject_id']);
        $this->assertSame('Ô tô', $carSubject['sublabel']);
        $this->assertCount(3, $carSubject['months'], '3 tháng nợ');
        $this->assertSame('4500000.00', $carSubject['outstanding']);

        // Phí quản lý (không gắn tài sản) gom dưới subject_type = 'apartment'.
        $this->assertTrue($families->has('management'), 'có family quản lý');
        $this->assertSame('500000.00', $families['management']['outstanding']);
        $mgmtFt = collect($families['management']['fee_types'])->firstWhere('name', 'Phí quản lý');
        $this->assertNotNull($mgmtFt);
        $aptSubject = collect($mgmtFt['subjects'])->firstWhere('subject_type', 'apartment');
        $this->assertNotNull($aptSubject, 'phí quản lý gom theo căn hộ');
        $this->assertNull($aptSubject['subject_id']);
        $this->assertCount(1, $aptSubject['months'], 'chỉ dòng chưa trả, dòng đã trả bị loại');
        $this->assertSame('500000.00', $aptSubject['outstanding']);
    }

    private function publishedStatement(Tenant $tenant, Building $building, Apartment $apartment, string $ym, string $total): Statement
    {
        $period = BillingPeriod::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id,
            'code' => $ym, 'label' => 'Tháng '.$ym, 'period_month' => $ym.'-01',
        ]);

        return Statement::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id,
            'apartment_id' => $apartment->id, 'billing_period_id' => $period->id,
            'total_amount' => $total, 'paid_amount' => 0, 'status' => 'issued',
            'approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now(),
        ]);
    }
}
