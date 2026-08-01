<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityMembershipGrant;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Community\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Giai đoạn 3 Community Domain (2026-08-01) — grants & membership.
 * `COMMUNITY_DB_MAPPING.md` §3, `COMMUNITY_RISK_ROLLBACK.md` R2 (kiểm chứng
 * BẮT BUỘC): tài khoản có 2 căn ở 2 dự án, gỡ MỘT quan hệ chỉ mất quyền ĐÚNG
 * MỘT nhóm — nhóm kia còn nguyên; và hai grant cùng một membership, thu hồi
 * một grant KHÔNG kéo mất membership, chỉ thu hồi grant CUỐI CÙNG mới mất.
 */
class CommunityMembershipGrantsTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $tag): Tenant
    {
        return Tenant::create(['code' => "TEN-MG-$tag", 'name' => "Tenant MG $tag"]);
    }

    /** Dự án + toà + căn hộ + nhóm cư dân chính thức (official_resident_group) của dự án đó. */
    private function projectWithOfficialGroup(Tenant $tenant, string $tag): array
    {
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-MG-$tag", 'name' => "Project MG $tag"]);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-MG-$tag", 'name' => "Building MG $tag"]);
        $group = CommunityGroup::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'name' => "Cư dân MG $tag",
            'kind' => 'project_resident', 'group_type' => 'official_resident_group',
            'scope_type' => 'project', 'scope_id' => $project->id, 'status' => 'active', 'is_default' => true,
        ]);

        return compact('project', 'building', 'group');
    }

    private function apartment(Tenant $tenant, Building $building, string $tag): Apartment
    {
        return Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-MG-$tag"]);
    }

    // ── R2: tài khoản demo #6 — 2 căn, 2 dự án ──────────────────────────────

    public function test_go_mot_quan_he_can_ho_chi_mat_quyen_dung_mot_du_an(): void
    {
        $tenant = $this->tenant('D6');
        $ctxA = $this->projectWithOfficialGroup($tenant, 'D6A');
        $ctxB = $this->projectWithOfficialGroup($tenant, 'D6B');
        $aptA = $this->apartment($tenant, $ctxA['building'], 'D6A');
        $aptB = $this->apartment($tenant, $ctxB['building'], 'D6B');

        $user = User::create(['name' => 'Demo 6', 'email' => 'demo6@test.vn', 'password' => bcrypt('secret')]);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'code' => 'RES-MG-D6', 'full_name' => 'Demo 6']);
        $relationA = ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $aptA->id, 'role' => 'owner', 'is_primary' => true]);
        $relationB = ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $aptB->id, 'role' => 'owner', 'is_primary' => false]);

        $service = app(MembershipService::class);
        $memberA = $service->grantResidentRelation($relationA);
        $memberB = $service->grantResidentRelation($relationB);

        $this->assertNotNull($memberA);
        $this->assertNotNull($memberB);
        $this->assertTrue($memberA->hasActiveGrant());
        $this->assertTrue($memberB->hasActiveGrant());
        $ctxA['group']->refresh();
        $ctxB['group']->refresh();
        $this->assertSame(1, $ctxA['group']->member_count);
        $this->assertSame(1, $ctxB['group']->member_count);

        // Gỡ quan hệ căn hộ ở dự án A.
        $service->revokeResidentRelation($relationA);

        $memberA->refresh();
        $memberB->refresh();
        $ctxA['group']->refresh();
        $ctxB['group']->refresh();

        $this->assertFalse($memberA->hasActiveGrant(), 'mất quyền nhóm A vì quan hệ A đã gỡ');
        $this->assertNotNull($memberA->left_at, 'left_at phải được set để app gắn nhãn "cư dân cũ"');
        $this->assertSame(0, $ctxA['group']->member_count);

        $this->assertTrue($memberB->hasActiveGrant(), 'nhóm B KHÔNG được đụng — quan hệ B vẫn active');
        $this->assertNull($memberB->left_at);
        $this->assertSame(1, $ctxB['group']->member_count);
    }

    // ── Hai grant cùng một membership ───────────────────────────────────────

    public function test_hai_can_ho_cung_du_an_la_hai_grant_cung_mot_membership(): void
    {
        $tenant = $this->tenant('MU');
        $ctx = $this->projectWithOfficialGroup($tenant, 'MU');
        $apt1 = $this->apartment($tenant, $ctx['building'], 'MU1');
        $apt2 = $this->apartment($tenant, $ctx['building'], 'MU2');

        $user = User::create(['name' => 'Multi Unit', 'email' => 'multiunit@test.vn', 'password' => bcrypt('secret')]);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'code' => 'RES-MG-MU', 'full_name' => 'Multi Unit']);
        $relation1 = ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apt1->id, 'role' => 'owner', 'is_primary' => true]);
        $relation2 = ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apt2->id, 'role' => 'owner', 'is_primary' => false]);

        $service = app(MembershipService::class);
        $member1 = $service->grantResidentRelation($relation1);
        $member2 = $service->grantResidentRelation($relation2);

        $this->assertSame($member1->id, $member2->id, 'cùng nhóm cư dân dự án → MỘT membership duy nhất');
        $this->assertSame(2, CommunityMembershipGrant::where('membership_id', $member1->id)->where('status', 'active')->count());
        $ctx['group']->refresh();
        $this->assertSame(1, $ctx['group']->member_count, 'hai grant nhưng chỉ MỘT thành viên — không đếm hai lần');

        // Thu hồi quan hệ 1 — còn quan hệ 2 giữ membership.
        $service->revokeResidentRelation($relation1);
        $member1->refresh();
        $this->assertTrue($member1->hasActiveGrant(), 'còn grant của quan hệ 2 — KHÔNG được coi là rời nhóm');
        $this->assertNull($member1->left_at);
        $ctx['group']->refresh();
        $this->assertSame(1, $ctx['group']->member_count);

        // Thu hồi luôn quan hệ 2 — hết active grant, giờ mới thật sự mất quyền.
        $service->revokeResidentRelation($relation2);
        $member1->refresh();
        $this->assertFalse($member1->hasActiveGrant());
        $this->assertNotNull($member1->left_at);
        $ctx['group']->refresh();
        $this->assertSame(0, $ctx['group']->member_count);
    }

    public function test_resident_relation_va_manual_join_cung_nhom_giu_duoc_khi_revoke_mot(): void
    {
        $tenant = $this->tenant('MJ');
        $ctx = $this->projectWithOfficialGroup($tenant, 'MJ');
        $apt = $this->apartment($tenant, $ctx['building'], 'MJ');

        $user = User::create(['name' => 'Manual Join', 'email' => 'manualjoin@test.vn', 'password' => bcrypt('secret')]);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'code' => 'RES-MG-MJ', 'full_name' => 'Manual Join']);
        $relation = ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);

        $service = app(MembershipService::class);
        $member = $service->grantResidentRelation($relation);
        $service->grantManualJoin($ctx['group'], $resident); // cùng nhóm, grant thứ hai khác nguồn

        $member->refresh();
        $this->assertSame(2, CommunityMembershipGrant::where('membership_id', $member->id)->where('status', 'active')->count());

        $service->revokeResidentRelation($relation);
        $member->refresh();
        $this->assertTrue($member->hasActiveGrant(), 'còn grant manual_join — chưa rời nhóm');
        $this->assertNull($member->left_at);

        $service->revokeManualJoin($ctx['group'], $resident);
        $member->refresh();
        $this->assertFalse($member->hasActiveGrant());
        $this->assertNotNull($member->left_at);
    }

    public function test_grant_va_revoke_goi_lai_la_idempotent(): void
    {
        $tenant = $this->tenant('ID');
        $ctx = $this->projectWithOfficialGroup($tenant, 'ID');
        $apt = $this->apartment($tenant, $ctx['building'], 'ID');
        $user = User::create(['name' => 'Idem', 'email' => 'idem@test.vn', 'password' => bcrypt('secret')]);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'code' => 'RES-MG-ID', 'full_name' => 'Idem']);
        $relation = ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);

        $service = app(MembershipService::class);
        $service->grantResidentRelation($relation);
        $service->grantResidentRelation($relation);

        $this->assertSame(1, CommunityGroupMember::where('community_group_id', $ctx['group']->id)->count());
        $this->assertSame(1, CommunityMembershipGrant::where('source_type', 'resident_relation')->where('source_id', $relation->id)->count());
        $ctx['group']->refresh();
        $this->assertSame(1, $ctx['group']->member_count, 'gọi grant lại hai lần không tăng member_count lần hai');

        $service->revokeResidentRelation($relation);
        $service->revokeResidentRelation($relation);
        $ctx['group']->refresh();
        $this->assertSame(0, $ctx['group']->member_count, 'gọi revoke lại hai lần không trừ âm');
    }

    // ── Join/leave thủ công qua HTTP (nhóm không phải is_default) ───────────

    public function test_join_leave_endpoint_dung_left_at_thay_vi_xoa_cung(): void
    {
        $tenant = $this->tenant('JL');
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-MG-JL', 'name' => 'Project JL']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-MG-JL', 'name' => 'Building JL']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'APT-MG-JL']);
        $user = User::create(['name' => 'Join Leave', 'email' => 'joinleave@test.vn', 'password' => bcrypt('secret'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => 'RES-MG-JL', 'full_name' => 'Join Leave']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);

        $custom = CommunityGroup::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'name' => 'Nhóm tự lập JL',
            'kind' => 'private', 'group_type' => 'resident_custom_group',
            'scope_type' => 'project', 'scope_id' => $project->id, 'status' => 'active', 'is_default' => false,
        ]);

        Sanctum::actingAs($user, ['resident']);

        $this->postJson("/api/v1/resident/community/groups/{$custom->id}/join")->assertOk()
            ->assertJsonPath('data.joined', true);
        $member = CommunityGroupMember::where('community_group_id', $custom->id)->where('resident_id', $resident->id)->first();
        $this->assertNotNull($member);
        $this->assertNull($member->left_at);

        $this->deleteJson("/api/v1/resident/community/groups/{$custom->id}/join")->assertOk()
            ->assertJsonPath('data.joined', false);
        $member->refresh();
        $this->assertNotNull($member->left_at, 'rời nhóm chỉ set left_at — KHÔNG xoá dòng (giữ lịch sử)');
        $this->assertSame(1, CommunityGroupMember::where('community_group_id', $custom->id)->count(), 'không xoá cứng');

        // Danh sách nhóm phải phản ánh "đã rời" — không còn joined=true.
        $res = $this->getJson('/api/v1/resident/community/groups')->assertOk();
        $item = collect($res->json('data'))->firstWhere('id', (string) $custom->id);
        $this->assertFalse($item['joined']);

        // Tham gia lại — hồi sinh đúng membership cũ (không tạo dòng thứ hai).
        $this->postJson("/api/v1/resident/community/groups/{$custom->id}/join")->assertOk()
            ->assertJsonPath('data.joined', true);
        $member->refresh();
        $this->assertNull($member->left_at);
        $this->assertSame(1, CommunityGroupMember::where('community_group_id', $custom->id)->count());
    }

    public function test_leave_nhom_is_default_khong_lam_gi_vi_khong_co_manual_join_grant(): void
    {
        $tenant = $this->tenant('DF');
        $ctx = $this->projectWithOfficialGroup($tenant, 'DF');
        $apt = $this->apartment($tenant, $ctx['building'], 'DF');
        $user = User::create(['name' => 'Default Group', 'email' => 'defaultgroup@test.vn', 'password' => bcrypt('secret'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'code' => 'RES-MG-DF', 'full_name' => 'Default Group']);
        $relation = ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);

        $member = app(MembershipService::class)->grantResidentRelation($relation);

        Sanctum::actingAs($user, ['resident']);
        $this->deleteJson("/api/v1/resident/community/groups/{$ctx['group']->id}/join")->assertOk();

        $member->refresh();
        $this->assertTrue($member->hasActiveGrant(), 'không có grant manual_join để thu hồi — nhóm mặc định không mất qua nút rời');
        $this->assertNull($member->left_at);
    }

    // ── Auto-enroll X2Living tại bootstrap (mọi tier, kể cả member thuần) ───

    public function test_bootstrap_tu_dong_enroll_platform_community_cho_member_thuan(): void
    {
        $tenant = $this->tenant('PLAT');
        $platform = CommunityGroup::create([
            'tenant_id' => $tenant->id, 'name' => 'Cộng đồng X2 Living', 'kind' => 'platform',
            'group_type' => 'platform_community', 'scope_type' => 'platform', 'status' => 'active', 'is_default' => true,
        ]);

        $user = User::create(['name' => 'Member thuần', 'email' => 'member-thuan@test.vn', 'password' => bcrypt('secret')]);
        Sanctum::actingAs($user, ['member']);

        $this->getJson('/api/v1/me/bootstrap')->assertOk();

        $this->assertDatabaseHas('community_group_members', [
            'community_group_id' => $platform->id,
            'user_id' => $user->id,
            'resident_id' => null,
            'left_at' => null,
        ]);
        $this->assertSame(1, CommunityMembershipGrant::query()
            ->whereHas('membership', fn ($q) => $q->where('community_group_id', $platform->id)->where('user_id', $user->id))
            ->where('source_type', 'system_enrollment')->count());

        // Gọi lại (mở app lần 2) — không tạo dòng thứ hai.
        $this->getJson('/api/v1/me/bootstrap')->assertOk();
        $this->assertSame(1, CommunityGroupMember::where('community_group_id', $platform->id)->where('user_id', $user->id)->count());
    }

    // ── Backfill command (mỗi community_group_members hiện có → một grant) ──

    public function test_backfill_gan_source_type_theo_is_default(): void
    {
        $tenant = $this->tenant('BF');
        $ctx = $this->projectWithOfficialGroup($tenant, 'BF'); // is_default = true
        $custom = CommunityGroup::create([
            'tenant_id' => $tenant->id, 'project_id' => $ctx['project']->id, 'name' => 'Nhóm tự lập BF',
            'kind' => 'private', 'group_type' => 'resident_custom_group',
            'scope_type' => 'project', 'scope_id' => $ctx['project']->id, 'status' => 'active', 'is_default' => false,
        ]);
        $apt = $this->apartment($tenant, $ctx['building'], 'BF');
        $user = User::create(['name' => 'Backfill', 'email' => 'backfill@test.vn', 'password' => bcrypt('secret')]);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'code' => 'RES-MG-BF', 'full_name' => 'Backfill']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);

        // Dữ liệu CŨ trước khi grants tồn tại — tạo thẳng membership, không qua service.
        $memberDefault = CommunityGroupMember::create(['community_group_id' => $ctx['group']->id, 'resident_id' => $resident->id, 'role' => 'member', 'joined_at' => now()]);
        $memberCustom = CommunityGroupMember::create(['community_group_id' => $custom->id, 'resident_id' => $resident->id, 'role' => 'member', 'joined_at' => now()]);

        Artisan::call('community:backfill-membership-grants', ['--dry-run' => true]);
        $this->assertSame(0, CommunityMembershipGrant::count(), 'dry-run không ghi gì');

        Artisan::call('community:backfill-membership-grants');

        $this->assertDatabaseHas('community_membership_grants', ['membership_id' => $memberDefault->id, 'source_type' => 'system_enrollment', 'source_id' => null]);
        $this->assertDatabaseHas('community_membership_grants', ['membership_id' => $memberCustom->id, 'source_type' => 'manual_join', 'source_id' => null]);
        $this->assertSame(2, CommunityMembershipGrant::count());
    }

    public function test_backfill_idempotent_va_rollback(): void
    {
        $tenant = $this->tenant('BF2');
        $ctx = $this->projectWithOfficialGroup($tenant, 'BF2');
        $apt = $this->apartment($tenant, $ctx['building'], 'BF2');
        $user = User::create(['name' => 'Backfill 2', 'email' => 'backfill2@test.vn', 'password' => bcrypt('secret')]);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'code' => 'RES-MG-BF2', 'full_name' => 'Backfill 2']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);
        $member = CommunityGroupMember::create(['community_group_id' => $ctx['group']->id, 'resident_id' => $resident->id, 'role' => 'member', 'joined_at' => now()]);

        Artisan::call('community:backfill-membership-grants');
        Artisan::call('community:backfill-membership-grants');
        $this->assertSame(1, CommunityMembershipGrant::where('membership_id', $member->id)->count(), 'chạy lại không tạo trùng');

        Artisan::call('community:backfill-membership-grants', ['--rollback' => true]);
        $this->assertSame(0, CommunityMembershipGrant::count());
        // rollback chỉ xoá community_membership_grants — community_group_members giữ nguyên.
        $this->assertDatabaseHas('community_group_members', ['id' => $member->id]);
    }
}
