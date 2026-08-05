<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * MUST_NOT_LEAK tài chính (G3/G5): cư dân CHỈ thấy bảng kê của căn hộ mình; truy cập
 * trực tiếp {statement} của cư dân/tenant khác → 404 (không lộ tồn tại).
 */
class ResidentStatementIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_khong_lo_bang_ke_cua_cu_dan_khac(): void
    {
        [$me, , $myStatement] = $this->scenario('SA');
        [, , $otherStatement] = $this->scenario('SB');

        Sanctum::actingAs($me, ['resident']);

        // Danh sách chỉ có bảng kê của mình.
        $res = $this->getJson('/api/v1/resident/statements')->assertOk();
        $ids = collect($res->json('data'))->pluck('id')->map(fn ($v) => (string) $v)->all();
        $this->assertContains((string) $myStatement->id, $ids);
        $this->assertNotContains((string) $otherStatement->id, $ids, 'không được thấy bảng kê tenant khác');

        // Truy cập trực tiếp bảng kê người khác → 404 (không lộ tồn tại).
        $this->getJson("/api/v1/resident/statements/{$otherStatement->id}")->assertStatus(404);
        // Bảng kê của mình → xem được.
        $this->getJson("/api/v1/resident/statements/{$myStatement->id}")->assertOk();
    }

    /** @return array{0:User,1:Apartment,2:Statement} */
    private function scenario(string $tag): array
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $p = Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => "BLD-$tag", 'name' => "B $tag"]);
        $apt = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => "APT-$tag"]);
        $u = User::create(['name' => "CD $tag", 'email' => strtolower($tag).'@st.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $r = Resident::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'user_id' => $u->id, 'code' => "RES-$tag", 'full_name' => "CD $tag"]);
        ResidentApartmentRelation::create(['tenant_id' => $t->id, 'resident_id' => $r->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);
        $period = BillingPeriod::create([
            'tenant_id' => $t->id, 'building_id' => $b->id, 'code' => "2026-07-$tag", 'label' => "T7 $tag", 'period_month' => '2026-07-01',
        ]);
        $st = Statement::create([
            'tenant_id' => $t->id, 'building_id' => $b->id, 'billing_period_id' => $period->id, 'apartment_id' => $apt->id,
            'total_amount' => 1_000_000, 'status' => 'issued',
            'approval_status' => 'published', 'published_at' => now(),
        ]);

        return [$u, $apt, $st];
    }
}
