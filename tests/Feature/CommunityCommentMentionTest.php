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
 * GĐ7 — @mention: bình luận cộng đồng lưu danh sách người được nhắc
 * ([{user_id, name}]), chỉ giữ id có thật, trả về cho app render/link.
 */
class CommunityCommentMentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_luu_va_tra_ve_nguoi_duoc_nhac_chi_id_co_that(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-MN', 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-MN', 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-MN', 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'APT-MN']);
        $user = User::create([
            'name' => 'Người bình luận', 'email' => 'mn-u@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => 'RES-MN', 'full_name' => 'Người bình luận',
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);
        $mentioned = User::create([
            'name' => 'Anh Ba', 'email' => 'mn-b@test.vn', 'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $post = CommunityPost::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'body' => 'Bài', 'status' => 'published',
        ]);

        Sanctum::actingAs($user, ['resident']);

        $res = $this->postJson("/api/v1/resident/community/posts/{$post->id}/comments", [
            'body' => 'Chào @Anh Ba nhé',
            'mentioned_user_ids' => [$mentioned->id, 999999], // 999999 không có thật → bỏ
        ])->assertCreated();

        $res->assertJsonPath('data.mentions.0.name', 'Anh Ba');
        $this->assertCount(1, $res->json('data.mentions'), 'chỉ giữ id có thật');

        $saved = CommunityComment::first();
        $this->assertSame((string) $mentioned->id, $saved->mentions[0]['user_id']);
    }
}
