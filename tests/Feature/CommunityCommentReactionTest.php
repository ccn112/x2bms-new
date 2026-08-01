<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * GĐ7 — cảm xúc trên bình luận cộng đồng: một cảm xúc / người / bình luận
 * (updateOrCreate), reaction_count cấp dòng cập nhật, bỏ cảm xúc về 0.
 */
class CommunityCommentReactionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{user:User,comment:CommunityComment} */
    private function scene(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-$tag", 'name' => "B $tag",
        ]);
        $apartment = Apartment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag",
        ]);
        $user = User::create([
            'name' => "U $tag", 'email' => strtolower($tag).'-ccr@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-$tag", 'full_name' => "R $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);
        $post = CommunityPost::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'body' => "Bài $tag", 'status' => 'published',
        ]);
        $comment = CommunityComment::create([
            'community_post_id' => $post->id, 'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'user_id' => $user->id, 'author_name' => 'Cư dân', 'body' => 'Bình luận', 'status' => 'visible',
        ]);

        return ['user' => $user, 'comment' => $comment, 'post' => $post];
    }

    public function test_react_doi_va_unreact_cap_nhat_count(): void
    {
        $s = $this->scene('R1');
        $post = $s['post']->id;
        $cid = $s['comment']->id;
        Sanctum::actingAs($s['user'], ['resident']);

        // React 'like'.
        $this->postJson("/api/v1/resident/community/posts/$post/comments/$cid/reactions", ['emoji' => 'like'])
            ->assertOk()->assertJsonPath('data.reaction_count', 1)->assertJsonPath('data.mine', 'like');

        // Đổi sang 'love' — vẫn 1 (một cảm xúc/người).
        $this->postJson("/api/v1/resident/community/posts/$post/comments/$cid/reactions", ['emoji' => 'love'])
            ->assertOk()->assertJsonPath('data.reaction_count', 1)->assertJsonPath('data.mine', 'love');

        $this->assertSame(1, (int) $s['comment']->refresh()->reaction_count);

        // Bỏ cảm xúc.
        $this->deleteJson("/api/v1/resident/community/posts/$post/comments/$cid/reactions")
            ->assertOk()->assertJsonPath('data.reaction_count', 0)->assertJsonPath('data.mine', null);
        $this->assertSame(0, (int) $s['comment']->refresh()->reaction_count);
    }

    public function test_emoji_khong_hop_le_bi_tu_choi(): void
    {
        $s = $this->scene('R2');
        Sanctum::actingAs($s['user'], ['resident']);
        $this->postJson("/api/v1/resident/community/posts/{$s['post']->id}/comments/{$s['comment']->id}/reactions",
            ['emoji' => 'explode'])->assertStatus(422);
    }
}
