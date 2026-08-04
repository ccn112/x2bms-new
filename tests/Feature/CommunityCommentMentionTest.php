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
 * GĐ7 + audit BOLA — @mention: bình luận cộng đồng lưu danh sách người được nhắc
 * ([{user_id, name}]). CHỈ giữ người (a) có thật VÀ (b) là **cư dân CÙNG DỰ ÁN** với
 * người nhắc — chống chèn thông báo/push xuyên tenant (mention user bất kỳ toàn hệ).
 */
class CommunityCommentMentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_chi_nhac_duoc_cu_dan_cung_du_an(): void
    {
        [$tenant, $project, $building] = $this->tenantProjectBuilding('MN');
        $author = $this->resident($tenant, $building, 'Người bình luận', 'mn-u@test.vn');

        // Cùng dự án → hợp lệ.
        $mate = $this->resident($tenant, $building, 'Anh Ba', 'mn-b@test.vn');

        // Dự án KHÁC (tenant khác) → phải bị loại.
        [$tenant2, , $building2] = $this->tenantProjectBuilding('MN2');
        $outsider = $this->resident($tenant2, $building2, 'Người ngoài', 'mn-out@test.vn');

        // User không phải cư dân → cũng bị loại.
        $ghost = User::create(['name' => 'Ma', 'email' => 'mn-ghost@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);

        $post = CommunityPost::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'body' => 'Bài', 'status' => 'published']);

        Sanctum::actingAs($author, ['resident']);

        $res = $this->postJson("/api/v1/resident/community/posts/{$post->id}/comments", [
            'body' => 'Chào mọi người',
            'mentioned_user_ids' => [$mate->id, $outsider->id, $ghost->id, 999999],
        ])->assertCreated();

        $names = collect($res->json('data.mentions'))->pluck('name')->all();
        $this->assertSame(['Anh Ba'], $names, 'chỉ giữ cư dân cùng dự án; loại người ngoài/ghost/id ma');

        $saved = CommunityComment::first();
        $this->assertCount(1, $saved->mentions);
        $this->assertSame((string) $mate->id, $saved->mentions[0]['user_id']);
    }

    /** @return array{0:Tenant,1:Project,2:Building} */
    private function tenantProjectBuilding(string $tag): array
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $p = Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => "BLD-$tag", 'name' => "B $tag"]);

        return [$t, $p, $b];
    }

    private function resident(Tenant $t, Building $b, string $name, string $email): User
    {
        $u = User::create(['name' => $name, 'email' => $email, 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $apt = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => 'APT-'.substr(md5($email), 0, 5)]);
        $r = Resident::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'user_id' => $u->id, 'code' => 'RES-'.substr(md5($email), 0, 5), 'full_name' => $name]);
        ResidentApartmentRelation::create(['tenant_id' => $t->id, 'resident_id' => $r->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);

        return $u;
    }
}
