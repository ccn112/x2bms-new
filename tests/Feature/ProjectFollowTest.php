<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\PublicProject;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserProjectFollow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Giai đoạn 4 Community Domain (2026-07-31) — follow dự án. Follow KHÔNG cấp
 * quyền, không cho vào nhóm nào (`docs/COMMUNITY_DB_MAPPING.md` §4).
 */
class ProjectFollowTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $tag): Tenant
    {
        return Tenant::create(['code' => "TEN-PF-$tag", 'name' => "Tenant PF $tag"]);
    }

    public function test_member_thuan_chua_co_can_ho_van_follow_duoc(): void
    {
        // Đúng lý do kênh project_interest_channel tồn tại: cho người CHƯA phải cư dân.
        $tenant = $this->tenant('A1');
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-PF-A1', 'name' => 'Project A1']);
        $user = User::create(['name' => 'Member A1', 'email' => 'member-a1@test.vn', 'password' => bcrypt('secret')]);

        Sanctum::actingAs($user, ['member']);

        $this->postJson('/api/v1/me/project-follows', ['project_id' => $project->id])->assertCreated();
        $this->assertDatabaseHas('user_project_follows', ['user_id' => $user->id, 'project_id' => $project->id]);

        $res = $this->getJson('/api/v1/me/project-follows')->assertOk();
        $this->assertSame('Project A1', $res->json('data.0.project_name'));
    }

    public function test_follow_lai_khong_nhan_doi(): void
    {
        $tenant = $this->tenant('A2');
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-PF-A2', 'name' => 'Project A2']);
        $user = User::create(['name' => 'Member A2', 'email' => 'member-a2@test.vn', 'password' => bcrypt('secret')]);

        Sanctum::actingAs($user, ['member']);

        $this->postJson('/api/v1/me/project-follows', ['project_id' => $project->id])->assertCreated();
        $this->postJson('/api/v1/me/project-follows', ['project_id' => $project->id])->assertCreated();

        $this->assertSame(1, UserProjectFollow::where('user_id', $user->id)->count());
    }

    public function test_bo_theo_doi_xoa_dung_dong(): void
    {
        $tenant = $this->tenant('A3');
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-PF-A3', 'name' => 'Project A3']);
        $user = User::create(['name' => 'Member A3', 'email' => 'member-a3@test.vn', 'password' => bcrypt('secret')]);
        UserProjectFollow::create(['user_id' => $user->id, 'project_id' => $project->id, 'followed_at' => now()]);

        Sanctum::actingAs($user, ['member']);

        $this->deleteJson("/api/v1/me/project-follows/{$project->id}")->assertOk();
        $this->assertDatabaseMissing('user_project_follows', ['user_id' => $user->id, 'project_id' => $project->id]);
    }

    public function test_khong_follow_duoc_du_an_khong_ton_tai(): void
    {
        $user = User::create(['name' => 'Member A4', 'email' => 'member-a4@test.vn', 'password' => bcrypt('secret')]);
        Sanctum::actingAs($user, ['member']);

        $this->postJson('/api/v1/me/project-follows', ['project_id' => 999999])->assertStatus(422);
    }

    // ── Backfill command (Cách A: chỉ dự án đã nối chính xác) ───────────────

    public function test_backfill_chi_lay_du_an_da_noi_khong_doan_mo(): void
    {
        $tenant = $this->tenant('B1');
        $publicLinked = PublicProject::create(['name' => 'Sunshine Garden (danh mục)', 'code' => 'PUB-PF-B1-LINKED']);
        $publicUnlinked = PublicProject::create(['name' => 'Dự án chưa nối', 'code' => 'PUB-PF-B1-UNLINKED']);

        $linkedProject = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-PF-B1-linked', 'name' => 'Project đã nối', 'public_project_id' => $publicLinked->id]);
        Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-PF-B1-plain', 'name' => 'Project chưa nối']);

        $userLinked = User::create(['name' => 'User B1 linked', 'email' => 'b1-linked@test.vn', 'password' => bcrypt('secret')]);
        $userUnlinked = User::create(['name' => 'User B1 unlinked', 'email' => 'b1-unlinked@test.vn', 'password' => bcrypt('secret')]);

        \DB::table('user_public_projects')->insert([
            ['user_id' => $userLinked->id, 'public_project_id' => $publicLinked->id, 'source' => 'register', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $userUnlinked->id, 'public_project_id' => $publicUnlinked->id, 'source' => 'register', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Artisan::call('community:backfill-project-follows');

        $this->assertDatabaseHas('user_project_follows', ['user_id' => $userLinked->id, 'project_id' => $linkedProject->id]);
        $this->assertSame(0, UserProjectFollow::where('user_id', $userUnlinked->id)->count(), 'dự án chưa nối không được đoán mò follow vào đâu');
        $this->assertSame(1, UserProjectFollow::count());
    }

    public function test_backfill_idempotent(): void
    {
        $tenant = $this->tenant('B2');
        $public = PublicProject::create(['name' => 'Dự án B2', 'code' => 'PUB-PF-B2']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-PF-B2', 'name' => 'Project B2', 'public_project_id' => $public->id]);
        $user = User::create(['name' => 'User B2', 'email' => 'b2@test.vn', 'password' => bcrypt('secret')]);
        \DB::table('user_public_projects')->insert(['user_id' => $user->id, 'public_project_id' => $public->id, 'source' => 'register', 'created_at' => now(), 'updated_at' => now()]);

        Artisan::call('community:backfill-project-follows');
        Artisan::call('community:backfill-project-follows');

        $this->assertSame(1, UserProjectFollow::where('user_id', $user->id)->where('project_id', $project->id)->count());
    }

    public function test_rollback_xoa_sach_khong_dung_nguon(): void
    {
        $tenant = $this->tenant('B3');
        $public = PublicProject::create(['name' => 'Dự án B3', 'code' => 'PUB-PF-B3']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-PF-B3', 'name' => 'Project B3', 'public_project_id' => $public->id]);
        $user = User::create(['name' => 'User B3', 'email' => 'b3@test.vn', 'password' => bcrypt('secret')]);
        \DB::table('user_public_projects')->insert(['user_id' => $user->id, 'public_project_id' => $public->id, 'source' => 'register', 'created_at' => now(), 'updated_at' => now()]);

        Artisan::call('community:backfill-project-follows');
        $this->assertSame(1, UserProjectFollow::count());

        Artisan::call('community:backfill-project-follows', ['--rollback' => true]);
        $this->assertSame(0, UserProjectFollow::count());
        $this->assertDatabaseHas('user_public_projects', ['user_id' => $user->id, 'public_project_id' => $public->id]);
    }
}
