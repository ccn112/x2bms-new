<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\CommunityGroup;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Giai đoạn 2 Community Domain (2026-07-31) — `community_groups` mở rộng
 * (`group_type`, `scope`, `capabilities`). Trước bản này endpoint
 * `GET resident/community/groups` không có test nào chạm tới (và không chạy
 * được trên SQLite vì `orderByRaw("FIELD(...)")` — MySQL-only, đã sửa cùng lúc).
 */
class CommunityGroupHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private function makeResident(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-CG-$tag", 'name' => "Tenant CG $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-CG-$tag", 'name' => "Project CG $tag"]);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-CG-$tag", 'name' => "Building CG $tag"]);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-CG-$tag"]);
        $user = User::create(['name' => "User $tag", 'email' => strtolower($tag).'-cg@test.vn', 'password' => bcrypt('secret'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => "RES-CG-$tag", 'full_name' => "Resident $tag"]);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);

        return compact('tenant', 'project', 'building', 'apartment', 'user', 'resident');
    }

    public function test_endpoint_tra_ca_truong_cu_lan_moi_khong_vo_tren_sqlite(): void
    {
        $ctx = $this->makeResident('G1');

        CommunityGroup::create(['tenant_id' => $ctx['tenant']->id, 'project_id' => $ctx['project']->id, 'name' => 'Cư dân test', 'kind' => 'project_resident', 'group_type' => 'official_resident_group', 'scope_type' => 'project', 'scope_id' => $ctx['project']->id, 'status' => 'active', 'is_default' => true]);
        CommunityGroup::create(['tenant_id' => $ctx['tenant']->id, 'project_id' => $ctx['project']->id, 'name' => 'Yêu bếp test', 'kind' => 'private', 'group_type' => 'resident_custom_group', 'scope_type' => 'project', 'scope_id' => $ctx['project']->id, 'status' => 'active', 'is_default' => false]);

        Sanctum::actingAs($ctx['user'], ['resident']);

        $res = $this->getJson('/api/v1/resident/community/groups')->assertOk();
        $items = collect($res->json('data'));

        $this->assertGreaterThanOrEqual(2, $items->count());

        $official = $items->firstWhere('kind', 'project_resident');
        $this->assertSame('official_resident_group', $official['group_type']);
        $this->assertTrue($official['capabilities']['can_leave'] === false, 'nhóm mặc định (is_default) không được rời');
        $this->assertSame($official['can_post'], $official['capabilities']['can_post'], 'trường cũ và mới phải khớp nhau trong release chuyển tiếp');

        $custom = $items->firstWhere('kind', 'private');
        $this->assertSame('resident_custom_group', $custom['group_type']);
        $this->assertTrue($custom['capabilities']['can_leave']);
    }

    public function test_bql_du_an_khac_khong_thay_can_moderate(): void
    {
        $ctx = $this->makeResident('G2');
        $otherProject = Project::create(['tenant_id' => $ctx['tenant']->id, 'code' => 'PRJ-CG-G2-other', 'name' => 'Other project']);
        CommunityGroup::create(['tenant_id' => $ctx['tenant']->id, 'project_id' => $otherProject->id, 'name' => 'Nhóm dự án khác', 'kind' => 'project_resident', 'group_type' => 'official_resident_group', 'scope_type' => 'project', 'scope_id' => $otherProject->id, 'status' => 'active']);
        CommunityGroup::create(['tenant_id' => $ctx['tenant']->id, 'project_id' => $ctx['project']->id, 'name' => 'Nhóm dự án mình', 'kind' => 'project_resident', 'group_type' => 'official_resident_group', 'scope_type' => 'project', 'scope_id' => $ctx['project']->id, 'status' => 'active', 'is_default' => true]);

        Sanctum::actingAs($ctx['user'], ['resident']);

        $res = $this->getJson('/api/v1/resident/community/groups')->assertOk();
        $items = collect($res->json('data'));

        // Cư dân thuần (không phải staff) — can_moderate luôn false, ở nhóm nào cũng vậy.
        foreach ($items as $item) {
            $this->assertFalse($item['capabilities']['can_moderate']);
        }
    }
}
