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
use App\Models\Tenant;
use App\Models\User;
use App\Support\Import\Profiles\FeeNotificationImportProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Luồng dữ liệu import mẫu cũ → HIỂN THỊ: admin thấy bảng kê pending; cư dân CHỈ
 * thấy sau khi phát hành, với số tiền hệ thống ĐÃ TÍNH đúng.
 */
class FeeNotificationDisplayTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx;

    private array $env;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['code' => 'TEN-DSP', 'name' => 'DSP']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-DSP', 'name' => 'DSP']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-DSP', 'name' => 'Toà']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'A-01']);
        BillingPeriod::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => '202605', 'label' => 'Kỳ 202605', 'period_month' => '2026-05-01', 'due_date' => '2026-05-25', 'is_current' => true]);
        $user = User::create(['name' => 'CD', 'email' => 'cd-dsp@test.vn', 'password' => bcrypt('secret'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => 'RES-DSP', 'full_name' => 'Cư dân DSP']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);

        foreach ([['PQL', 'Phí quản lý', 'management'], ['NUOC', 'Phí nước', 'utility']] as [$c, $n, $cat]) {
            FeeType::create(['tenant_id' => $tenant->id, 'code' => $c, 'name' => $n, 'category' => $cat, 'is_critical' => false, 'payment_priority' => 100]);
        }

        $this->env = compact('tenant', 'building', 'apartment', 'user');
        $this->ctx = ['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id];
    }

    private function importSample(): void
    {
        $p = new FeeNotificationImportProfile;
        $p->commitRow(['apartment_code' => 'A-01', 'period_code' => '202605', 'service_code' => 'PQL', 'price_type' => 1, 'quantity' => 1, 'fixed_unit_price' => 1911000, 'service_period_start' => '2026-05-01', 'service_period_end' => '2026-05-31', 'due_date' => '2026-05-25'], $this->ctx);
        $p->commitRow(['apartment_code' => 'A-01', 'period_code' => '202605', 'service_code' => 'NUOC', 'price_type' => 2, 'previous_reading' => '481', 'current_reading' => '505', 'tier1_qty' => 24, 'tier1_price' => 12075, 'service_period_start' => '2026-04-28', 'service_period_end' => '2026-04-28'], $this->ctx);
    }

    public function test_admin_thay_bang_ke_pending_voi_tong_dung(): void
    {
        $this->importSample();

        $statement = Statement::where('code', 'BK-202605-A-01')->firstOrFail();
        // Admin/BQL thấy bảng kê ở trạng thái chờ duyệt, tổng = 1.911.000 + 289.800 = 2.200.800
        $this->assertSame(Statement::APPROVAL_PENDING, $statement->approval_status);
        $this->assertSame('2200800.00', (string) $statement->total_amount);
        $this->assertSame(2, $statement->lines()->count());
    }

    public function test_cu_dan_khong_thay_khi_pending_thay_sau_phat_hanh(): void
    {
        $this->importSample();
        Sanctum::actingAs($this->env['user'], ['resident']);

        // Pending → không thấy
        $this->getJson('/api/v1/resident/statements')->assertOk()->assertJsonCount(0, 'data');

        // Phát hành
        Statement::where('code', 'BK-202605-A-01')->update(['approval_status' => Statement::APPROVAL_PUBLISHED, 'published_at' => now()]);

        $res = $this->getJson('/api/v1/resident/statements')->assertOk();
        $this->assertCount(1, $res->json('data'));
        $statementId = $res->json('data.0.id');

        // Chi tiết: cư dân thấy các dòng với số tiền đã tính
        $detail = $this->getJson("/api/v1/resident/statements/{$statementId}")->assertOk();
        $amounts = collect($detail->json('data.lines'))->pluck('amount')->map(fn ($a) => (string) $a)->sort()->values()->all();
        $this->assertContains('1911000.00', $amounts);
        $this->assertContains('289800.00', $amounts);
    }
}
