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
 * Số bình luận của bài (feed + chi tiết) đếm THẬT từ community_comments (chỉ
 * visible) — không phải cột comment_count lệch, cũng không phải polymorphic cũ.
 */
class CommunityCommentCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_dem_comment_that_visible_khong_theo_cot_lech(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-CC', 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-CC', 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-CC', 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'APT-CC']);
        $user = User::create(['name' => 'U', 'email' => 'cc@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => 'RES-CC', 'full_name' => 'U']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);

        // Bài có comment_count cột CỐ TÌNH sai (0) để chứng minh không phụ thuộc nó.
        $post = CommunityPost::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'body' => 'Bài', 'status' => 'published',
            'comment_count' => 0,
        ]);
        foreach (['visible', 'visible', 'hidden'] as $i => $st) {
            CommunityComment::create([
                'community_post_id' => $post->id, 'tenant_id' => $tenant->id, 'project_id' => $project->id,
                'user_id' => $user->id, 'author_name' => 'U', 'body' => "c$i", 'status' => $st,
            ]);
        }

        Sanctum::actingAs($user, ['resident']);

        // Feed: đếm 2 visible (bỏ 1 hidden), dù cột = 0.
        $feed = $this->getJson('/api/v1/resident/community/posts?per_page=10')->assertOk();
        $row = collect($feed->json('data'))->firstWhere('id', (string) $post->id);
        $this->assertSame(2, $row['comments'], 'feed đếm 2 comment visible');

        // Chi tiết bài: cũng 2.
        $this->getJson("/api/v1/resident/community/posts/{$post->id}")->assertOk()
            ->assertJsonPath('data.comments', 2);
    }
}
