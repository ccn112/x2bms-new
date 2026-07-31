<?php

namespace Tests\Feature;

use App\Actions\Community\ModerateCommunityPostAction;
use App\Models\CommunityPost;
use App\Models\CommunityPostReport;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase B6 — Kiểm duyệt cộng đồng. Trước bản này KHÔNG có test backend nào cho
 * miền cộng đồng (`docs/delivery/04_INITIAL_PHASE_PLAN.md` Phase B6).
 */
class CommunityModerationTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $tag): Tenant
    {
        return Tenant::create(['code' => "TEN-CM-$tag", 'name' => "Tenant CM $tag"]);
    }

    private function project(Tenant $tenant, string $tag): Project
    {
        return Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-CM-$tag", 'name' => "Project CM $tag"]);
    }

    private function staffUser(Tenant $tenant, Project $project, string $tag): User
    {
        $user = User::create([
            'tenant_id' => $tenant->id,
            'project_id' => $project->id,
            'name' => "Staff $tag",
            'email' => strtolower($tag).'-staff@test.vn',
            'password' => bcrypt('secret'),
            'account_type' => 'staff',
        ]);
        UserRoleScope::create([
            'user_id' => $user->id,
            'scope_type' => UserRoleScope::SCOPE_PROJECT,
            'project_id' => $project->id,
        ]);

        return $user;
    }

    private function makePost(Tenant $tenant, Project $project, string $tag): CommunityPost
    {
        return CommunityPost::create([
            'tenant_id' => $tenant->id,
            'project_id' => $project->id,
            'body' => "Bài $tag",
            'status' => 'published',
        ]);
    }

    // ── ModerateCommunityPostAction — state machine ─────────────────────────

    public function test_hide_doi_status_va_ghi_ly_do(): void
    {
        $tenant = $this->tenant('A1');
        $project = $this->project($tenant, 'A1');
        $staff = $this->staffUser($tenant, $project, 'A1');
        $post = $this->makePost($tenant, $project, 'A1');

        $updated = (new ModerateCommunityPostAction)->execute($post, 'hide', 'Nội dung spam', $staff);

        $this->assertSame('hidden', $updated->status);
        $this->assertSame('Nội dung spam', $updated->moderation_reason);
        $this->assertSame($staff->id, $updated->moderated_by_user_id);
        $this->assertNotNull($updated->moderated_at);
    }

    public function test_hide_thieu_ly_do_bi_tu_choi(): void
    {
        $tenant = $this->tenant('A2');
        $project = $this->project($tenant, 'A2');
        $staff = $this->staffUser($tenant, $project, 'A2');
        $post = $this->makePost($tenant, $project, 'A2');

        $this->expectException(InvalidArgumentException::class);
        (new ModerateCommunityPostAction)->execute($post, 'hide', '', $staff);
    }

    public function test_lock_khong_can_ly_do_van_di_qua_duoc_nhung_hide_thi_can(): void
    {
        // "lock" NẰM TRONG danh sách bắt buộc lý do (spec) — khoá riêng ca ngược lại
        // 'unlock' để không ai lỡ tay đảo REQUIRES_REASON.
        $tenant = $this->tenant('A3');
        $project = $this->project($tenant, 'A3');
        $staff = $this->staffUser($tenant, $project, 'A3');
        $post = $this->makePost($tenant, $project, 'A3');

        (new ModerateCommunityPostAction)->execute($post, 'lock', 'Tranh cãi căng', $staff);
        $this->assertNotNull($post->fresh()->locked_at);

        $unlocked = (new ModerateCommunityPostAction)->execute($post->fresh(), 'unlock', null, $staff);
        $this->assertNull($unlocked->locked_at);
    }

    public function test_xoa_mem_giu_lai_ban_ghi_va_khoi_phuc_duoc(): void
    {
        $tenant = $this->tenant('A4');
        $project = $this->project($tenant, 'A4');
        $staff = $this->staffUser($tenant, $project, 'A4');
        $post = $this->makePost($tenant, $project, 'A4');

        (new ModerateCommunityPostAction)->execute($post, 'delete', 'Spam rõ ràng', $staff);
        $this->assertSoftDeleted('community_posts', ['id' => $post->id]);

        $trashed = CommunityPost::withTrashed()->findOrFail($post->id);
        $restored = (new ModerateCommunityPostAction)->execute($trashed, 'restore', null, $staff);
        $this->assertNull($restored->deleted_at);
    }

    public function test_hanh_dong_khong_hop_le_nem_loi(): void
    {
        $tenant = $this->tenant('A5');
        $project = $this->project($tenant, 'A5');
        $staff = $this->staffUser($tenant, $project, 'A5');
        $post = $this->makePost($tenant, $project, 'A5');

        $this->expectException(InvalidArgumentException::class);
        (new ModerateCommunityPostAction)->execute($post, 'archive', null, $staff);
    }

    // ── Endpoint HTTP — cô lập theo dự án ───────────────────────────────────

    public function test_bql_du_an_khac_khong_kiem_duyet_duoc_bai_du_an_nay(): void
    {
        $tenant = $this->tenant('B1');
        $projectMine = $this->project($tenant, 'B1-mine');
        $projectOther = $this->project($tenant, 'B1-other');
        $staffOther = $this->staffUser($tenant, $projectOther, 'B1');
        $post = $this->makePost($tenant, $projectMine, 'B1');

        Sanctum::actingAs($staffOther, ['staff']);

        $this->postJson("/api/v1/resident/community/posts/{$post->id}/moderate", [
            'action' => 'hide', 'reason' => 'thử',
        ])->assertStatus(403);

        $this->assertSame('published', $post->fresh()->status, 'không được đổi trạng thái khi ngoài phạm vi');
    }

    public function test_bql_dung_du_an_kiem_duyet_thanh_cong_qua_http(): void
    {
        $tenant = $this->tenant('B2');
        $project = $this->project($tenant, 'B2');
        $staff = $this->staffUser($tenant, $project, 'B2');
        $post = $this->makePost($tenant, $project, 'B2');

        Sanctum::actingAs($staff, ['staff']);

        $this->postJson("/api/v1/resident/community/posts/{$post->id}/moderate", [
            'action' => 'lock', 'reason' => 'Đang tranh cãi',
        ])->assertOk();

        $this->assertNotNull($post->fresh()->locked_at);
    }

    public function test_thieu_ly_do_qua_http_tra_422(): void
    {
        $tenant = $this->tenant('B3');
        $project = $this->project($tenant, 'B3');
        $staff = $this->staffUser($tenant, $project, 'B3');
        $post = $this->makePost($tenant, $project, 'B3');

        Sanctum::actingAs($staff, ['staff']);

        $this->postJson("/api/v1/resident/community/posts/{$post->id}/moderate", [
            'action' => 'delete',
        ])->assertStatus(422);
    }

    // ── Report resolve/dismiss ───────────────────────────────────────────────

    public function test_resolve_va_dismiss_ghi_dung_nguoi_va_moc_thoi_gian(): void
    {
        $tenant = $this->tenant('C1');
        $project = $this->project($tenant, 'C1');
        $staff = $this->staffUser($tenant, $project, 'C1');
        $post = $this->makePost($tenant, $project, 'C1');
        $reporter = User::create(['tenant_id' => $tenant->id, 'name' => 'Reporter', 'email' => 'reporter-c1@test.vn', 'password' => bcrypt('secret')]);

        $report = CommunityPostReport::create([
            'community_post_id' => $post->id,
            'reported_by_user_id' => $reporter->id,
            'reason' => 'spam',
            'status' => 'open',
        ]);

        $report->markResolved($staff);
        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame($staff->id, $report->fresh()->resolved_by_user_id);
        $this->assertNotNull($report->fresh()->resolved_at);

        $reporter2 = User::create(['tenant_id' => $tenant->id, 'name' => 'Reporter2', 'email' => 'reporter2-c1@test.vn', 'password' => bcrypt('secret')]);
        $report2 = CommunityPostReport::create([
            'community_post_id' => $post->id,
            'reported_by_user_id' => $reporter2->id,
            'reason' => 'other',
            'status' => 'open',
        ]);
        $report2->markDismissed($staff);
        $this->assertSame('dismissed', $report2->fresh()->status);
    }
}
