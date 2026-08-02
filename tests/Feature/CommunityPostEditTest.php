<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
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
 * PATCH /resident/community/posts/{post} — tác giả tự sửa nội dung bài mình.
 * Chỉ tác giả sửa được; `can.edit` phải phản ánh đúng để app hiện/ẩn nút.
 */
class CommunityPostEditTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:Project,2:Tenant} một cư dân có căn (đủ ngữ cảnh dự án). */
    private function resident(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-$tag", 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag"]);
        $user = User::create(['name' => "U $tag", 'email' => strtolower($tag).'@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $res = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => "RES-$tag", 'full_name' => "U $tag"]);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $res->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);

        return [$user, $project, $tenant];
    }

    public function test_tac_gia_sua_duoc_noi_dung_bai_minh(): void
    {
        [$user, $project, $tenant] = $this->resident('EDIT');
        $post = CommunityPost::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'author_user_id' => $user->id, 'body' => 'Nội dung cũ', 'status' => 'published',
        ]);

        Sanctum::actingAs($user, ['resident']);

        $this->patchJson("/api/v1/resident/community/posts/{$post->id}", ['body' => 'Nội dung MỚI'])
            ->assertOk()
            ->assertJsonPath('data.body', 'Nội dung MỚI')
            ->assertJsonPath('data.can.edit', true);

        $this->assertSame('Nội dung MỚI', $post->fresh()->body);
    }

    public function test_nguoi_khac_khong_sua_duoc_bai_khong_phai_cua_minh(): void
    {
        [$author, $project, $tenant] = $this->resident('OWNER');
        $post = CommunityPost::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'author_user_id' => $author->id, 'body' => 'Bài của chủ', 'status' => 'published',
        ]);

        // Cư dân khác CÙNG dự án (thấy được bài nhưng không phải tác giả).
        $other = User::create(['name' => 'Kẻ khác', 'email' => 'other@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $building = $project->buildings()->first();
        $res = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $other->id, 'code' => 'RES-OTHER', 'full_name' => 'Kẻ khác']);
        $apt = Apartment::where('building_id', $building->id)->first();
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $res->id, 'apartment_id' => $apt->id, 'role' => 'member', 'is_primary' => true]);

        Sanctum::actingAs($other, ['resident']);

        $this->patchJson("/api/v1/resident/community/posts/{$post->id}", ['body' => 'Sửa trộm'])
            ->assertForbidden();

        $this->assertSame('Bài của chủ', $post->fresh()->body);

        // Và feed phải báo can.edit = false cho người không phải tác giả.
        $feed = $this->getJson('/api/v1/resident/community/posts?per_page=10')->assertOk();
        $row = collect($feed->json('data'))->firstWhere('id', (string) $post->id);
        $this->assertFalse($row['can']['edit'] ?? false, 'người khác không được thấy nút sửa');
    }

    public function test_bai_rong_sau_khi_sua_bi_tu_choi(): void
    {
        [$user, $project, $tenant] = $this->resident('EMPTY');
        $post = CommunityPost::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'author_user_id' => $user->id, 'body' => 'Có chữ', 'status' => 'published',
        ]);

        Sanctum::actingAs($user, ['resident']);

        $this->patchJson("/api/v1/resident/community/posts/{$post->id}", ['body' => '   '])
            ->assertStatus(422);

        $this->assertSame('Có chữ', $post->fresh()->body, 'sửa lỗi thì giữ nguyên bài cũ');
    }
}
