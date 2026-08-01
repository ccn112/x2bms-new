<?php

namespace Tests\Feature;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * GĐ7 — BQL kiểm duyệt bình luận cộng đồng (ẩn/xoá/khôi phục qua cột status);
 * quyền = kiểm duyệt được BÀI (UserRoleScope dự án). Bình luận ẩn/xoá không lọt
 * vào feed (status != visible).
 */
class CommunityCommentModerationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{staff:User,project:Project,comment:CommunityComment,post:CommunityPost} */
    private function scene(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $staff = User::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'name' => "Staff $tag",
            'email' => strtolower($tag).'-ccm@test.vn', 'password' => bcrypt('secret'), 'account_type' => 'staff',
        ]);
        UserRoleScope::create([
            'user_id' => $staff->id, 'scope_type' => UserRoleScope::SCOPE_PROJECT, 'project_id' => $project->id,
        ]);
        $post = CommunityPost::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'body' => "Bài $tag", 'status' => 'published',
        ]);
        $comment = CommunityComment::create([
            'community_post_id' => $post->id, 'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'author_name' => 'Cư dân', 'body' => 'Nội dung xấu', 'status' => 'visible',
        ]);

        return compact('staff', 'project', 'comment', 'post');
    }

    public function test_staff_an_va_khoi_phuc_binh_luan(): void
    {
        $s = $this->scene('X1');
        $url = "/api/v1/resident/community/posts/{$s['post']->id}/comments/{$s['comment']->id}/moderate";
        Sanctum::actingAs($s['staff'], ['staff']);

        $this->postJson($url, ['action' => 'hide', 'reason' => 'Spam'])
            ->assertOk()->assertJsonPath('data.status', 'hidden');
        $this->assertSame('hidden', $s['comment']->refresh()->status);

        $this->postJson($url, ['action' => 'unhide'])
            ->assertOk()->assertJsonPath('data.status', 'visible');
        $this->assertSame('visible', $s['comment']->refresh()->status);
    }

    public function test_cu_dan_thuong_khong_kiem_duyet_duoc(): void
    {
        $s = $this->scene('X2');
        $resident = User::create([
            'name' => 'Cư dân', 'email' => 'x2-res@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        Sanctum::actingAs($resident, ['resident']);

        $this->postJson(
            "/api/v1/resident/community/posts/{$s['post']->id}/comments/{$s['comment']->id}/moderate",
            ['action' => 'hide'],
        )->assertStatus(403);
    }
}
