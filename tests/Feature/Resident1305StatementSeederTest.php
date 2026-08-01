<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\Resident1305StatementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Seed bảng kê ĐÃ PHÁT HÀNH cho căn demo DP-08.12 (Đại Phúc) — trước đây 0
 * statement nên màn Hoá đơn/Ví rỗng. Test khoá: seeder tạo đúng bảng kê +
 * dòng phí, căn THẤY được qua /resident/statements (qua gate D1), idempotent.
 */
class Resident1305StatementSeederTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{user:User,apartment:Apartment} */
    private function seedDaiPhuc(): array
    {
        $tenant = Tenant::create(['code' => 'T-DAIPHUC', 'name' => 'Đại Phúc OM']);
        $project = Project::create([
            'tenant_id' => $tenant->id, 'code' => 'DAIPHUC-RS',
            'name' => 'Đại Phúc Riverside', 'city' => 'TP. Hồ Chí Minh',
        ]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'code' => 'DP-A', 'name' => 'Đại Phúc Riverside - Tòa A',
        ]);
        $apartment = Apartment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id,
            'code' => 'DP-08.12', 'area_sqm' => 88,
        ]);
        $user = User::create([
            'name' => 'Nguyễn Văn Anh', 'email' => 'dp-res@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => 'CD-DP', 'full_name' => 'Anh A',
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id,
            'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true,
        ]);

        return ['user' => $user, 'apartment' => $apartment];
    }

    public function test_seeder_phat_hanh_bang_ke_published_kem_2_dong_phi(): void
    {
        $ctx = $this->seedDaiPhuc();

        (new Resident1305StatementSeeder())->run();

        $st = Statement::withoutGlobalScopes()
            ->where('apartment_id', $ctx['apartment']->id)->first();
        $this->assertNotNull($st, 'phải có bảng kê cho căn DP-08.12');
        $this->assertSame('published', $st->approval_status);
        $this->assertNotNull($st->published_at);
        // 88 m² × 16.500 + 50.000 vệ sinh = 1.502.000.
        $this->assertEqualsWithDelta(1_502_000, (float) $st->total_amount, 1);
        $this->assertSame(2, StatementLine::withoutGlobalScopes()
            ->where('statement_id', $st->id)->count());
    }

    public function test_can_thay_bang_ke_qua_api_statements(): void
    {
        $ctx = $this->seedDaiPhuc();
        (new Resident1305StatementSeeder())->run();

        Sanctum::actingAs($ctx['user'], ['resident']);

        $res = $this->getJson('/api/v1/resident/statements')
            ->assertOk();
        $codes = collect($res->json('data'))->pluck('code');
        $this->assertContains('BK-DP-2607', $codes,
            'căn phải thấy bảng kê đã phát hành trong màn Hoá đơn');
    }

    public function test_idempotent_khong_tao_trung(): void
    {
        $ctx = $this->seedDaiPhuc();

        (new Resident1305StatementSeeder())->run();
        (new Resident1305StatementSeeder())->run();

        $this->assertSame(1, Statement::withoutGlobalScopes()
            ->where('apartment_id', $ctx['apartment']->id)->count());
    }
}
