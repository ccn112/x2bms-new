<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\CommunityPost;
use App\Models\CommunityPostReaction;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Gợi ý @mention gồm CẢ người đã thả cảm xúc (thích) — payload bài trả về
 * `reactors: [{user_id,name}]`, chỉ cư dân (bỏ nhân sự BQL).
 */
class CommunityReactorMentionTest extends TestCase
{
    use RefreshDatabase;

    private function resident(Tenant $tenant, Building $building, string $tag): User
    {
        $apt = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag"]);
        $user = User::create(['name' => "Cư dân $tag", 'email' => strtolower($tag).'@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $res = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => "RES-$tag", 'full_name' => "Cư dân $tag"]);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $res->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);

        return $user;
    }

    public function test_nguoi_da_thich_xuat_hien_trong_reactors_cua_bai(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-RM', 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-RM', 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-RM', 'name' => 'B']);

        $viewer = $this->resident($tenant, $building, 'VIEW');
        $liker = $this->resident($tenant, $building, 'LIKE');
        $staff = User::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'name' => 'BQL', 'email' => 'bql@test.vn', 'password' => bcrypt('x'), 'account_type' => 'staff']);

        $post = CommunityPost::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'author_user_id' => $viewer->id, 'body' => 'Bài để thả tim', 'status' => 'published',
        ]);

        // Một cư dân + một BQL cùng thả cảm xúc.
        CommunityPostReaction::create(['community_post_id' => $post->id, 'user_id' => $liker->id, 'emoji' => CommunityPostReaction::CODES[0]]);
        CommunityPostReaction::create(['community_post_id' => $post->id, 'user_id' => $staff->id, 'emoji' => CommunityPostReaction::CODES[0]]);

        Sanctum::actingAs($viewer, ['resident']);

        // Chi tiết bài: reactors chỉ có cư dân đã thích (bỏ BQL).
        $detail = $this->getJson("/api/v1/resident/community/posts/{$post->id}")->assertOk();
        $reactors = collect($detail->json('data.reactors'));
        $this->assertTrue($reactors->contains(fn ($r) => $r['user_id'] === (string) $liker->id), 'cư dân đã thích phải có trong reactors');
        $this->assertFalse($reactors->contains(fn ($r) => $r['user_id'] === (string) $staff->id), 'BQL không nằm trong danh sách @mention');

        // Feed cũng mang reactors để màn chi tiết (đọc từ snapshot feed) dựng gợi ý.
        $feed = $this->getJson('/api/v1/resident/community/posts?per_page=10')->assertOk();
        $row = collect($feed->json('data'))->firstWhere('id', (string) $post->id);
        $this->assertContains((string) $liker->id, collect($row['reactors'])->pluck('user_id')->all());
    }
}
