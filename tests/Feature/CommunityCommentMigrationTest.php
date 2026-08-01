<?php

namespace Tests\Feature;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GĐ7 — bảng bình luận cộng đồng chuyên dụng. Lệnh community:migrate-comments
 * chuyển bình luận CŨ (bảng comments polymorphic) sang community_comments: giữ
 * 2 cấp (remap parent), idempotent (legacy_comment_id), không copy trùng.
 */
class CommunityCommentMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(string $tag): CommunityPost
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);

        return CommunityPost::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'body' => "Bài $tag", 'status' => 'published',
        ]);
    }

    private function user(string $tag): User
    {
        return User::create([
            'name' => "U $tag", 'email' => strtolower($tag).'-cc@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
    }

    public function test_migrate_giu_2_cap_va_remap_parent(): void
    {
        $post = $this->makePost('M1');
        $u = $this->user('M1');
        // Bình luận cũ (polymorphic): 1 gốc + 1 trả lời.
        $root = $post->comments()->create([
            'user_id' => $u->id, 'author_name' => 'Cư dân', 'is_staff' => false, 'body' => 'Gốc',
        ]);
        $reply = $post->comments()->create([
            'parent_id' => $root->id, 'user_id' => $u->id, 'author_name' => 'Cư dân',
            'is_staff' => false, 'body' => 'Trả lời',
        ]);

        $this->artisan('community:migrate-comments')->assertSuccessful();

        $this->assertSame(2, CommunityComment::count());
        $newRoot = CommunityComment::where('legacy_comment_id', $root->id)->first();
        $newReply = CommunityComment::where('legacy_comment_id', $reply->id)->first();
        $this->assertNotNull($newRoot);
        $this->assertNull($newRoot->parent_id);
        $this->assertSame($newRoot->id, $newReply->parent_id, 'parent phải trỏ về new id của gốc');
        $this->assertSame('Trả lời', $newReply->body);
        $this->assertSame($post->id, $newRoot->community_post_id);
    }

    public function test_idempotent_khong_copy_trung(): void
    {
        $post = $this->makePost('M2');
        $u = $this->user('M2');
        $post->comments()->create([
            'user_id' => $u->id, 'author_name' => 'Cư dân', 'is_staff' => false, 'body' => 'X',
        ]);

        $this->artisan('community:migrate-comments')->assertSuccessful();
        $this->artisan('community:migrate-comments')->assertSuccessful();

        $this->assertSame(1, CommunityComment::count());
    }
}
