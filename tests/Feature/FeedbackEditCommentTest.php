<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\FeedbackRequest;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Ý kiến kiến nghị — cư dân SỬA phản ánh khi BQL chưa tiếp nhận + BÌNH LUẬN 2
 * chiều với BQL (feedback_comments, is_internal=false). Bổ sung cho luồng
 * create-only cũ (UX review 2026-08-01, ý 1).
 */
class FeedbackEditCommentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{user:User,tenant:Tenant,apartment:Apartment,resident:Resident,building:Building} */
    private function makeResident(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "Tenant $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "Project $tag"]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'code' => "BLD-$tag", 'name' => "Building $tag",
        ]);
        $apartment = Apartment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag",
        ]);
        $user = User::create([
            'name' => "User $tag", 'email' => strtolower($tag).'-fb@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-$tag", 'full_name' => "Resident $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);

        return compact('user', 'tenant', 'apartment', 'resident', 'building');
    }

    private function feedback(array $r, string $status = 'new'): FeedbackRequest
    {
        return FeedbackRequest::create([
            'tenant_id' => $r['tenant']->id,
            'building_id' => $r['building']->id,
            'apartment_id' => $r['apartment']->id,
            'resident_id' => $r['resident']->id,
            'user_id' => $r['user']->id,
            'code' => 'PA'.strtoupper(substr(md5($r['user']->email.$status), 0, 8)),
            'title' => 'Thang máy kêu to',
            'description' => 'Thang máy tòa A kêu lạ.',
            'priority' => 'normal',
            'channel' => 'app',
            'status' => $status,
        ]);
    }

    public function test_sua_phan_anh_khi_status_new(): void
    {
        $r = $this->makeResident('F1');
        $fb = $this->feedback($r);
        Sanctum::actingAs($r['user'], ['resident']);

        $res = $this->putJson("/api/v1/resident/feedback/{$fb->id}", [
            'title' => 'Thang máy kêu rất to (đã cập nhật)',
            'priority' => 'high',
        ])->assertOk();

        $this->assertSame('Thang máy kêu rất to (đã cập nhật)', $res->json('data.title'));
        $this->assertSame('high', $res->json('data.priority'));
        $this->assertTrue($res->json('data.can_edit'));
    }

    public function test_khong_sua_duoc_khi_da_tiep_nhan(): void
    {
        $r = $this->makeResident('F2');
        $fb = $this->feedback($r, 'in_progress');
        Sanctum::actingAs($r['user'], ['resident']);

        $this->putJson("/api/v1/resident/feedback/{$fb->id}", ['title' => 'X'])
            ->assertStatus(422);
    }

    public function test_khong_sua_duoc_phan_anh_cua_nguoi_khac(): void
    {
        $me = $this->makeResident('F3');
        $other = $this->makeResident('F4');
        $foreign = $this->feedback($other);
        Sanctum::actingAs($me['user'], ['resident']);

        $this->putJson("/api/v1/resident/feedback/{$foreign->id}", ['title' => 'X'])
            ->assertNotFound();
    }

    public function test_binh_luan_2_chieu(): void
    {
        $r = $this->makeResident('F5');
        $fb = $this->feedback($r);
        Sanctum::actingAs($r['user'], ['resident']);

        $this->postJson("/api/v1/resident/feedback/{$fb->id}/comments", ['body' => 'Cho hỏi tình hình?'])
            ->assertCreated()
            ->assertJsonPath('data.is_mine', true)
            ->assertJsonPath('data.is_staff', false);

        $list = $this->getJson("/api/v1/resident/feedback/{$fb->id}/comments")->assertOk();
        $this->assertCount(1, $list->json('data'));
        $this->assertSame('Cho hỏi tình hình?', $list->json('data.0.body'));
    }

    public function test_khong_binh_luan_phan_anh_nguoi_khac(): void
    {
        $me = $this->makeResident('F6');
        $other = $this->makeResident('F7');
        $foreign = $this->feedback($other);
        Sanctum::actingAs($me['user'], ['resident']);

        $this->postJson("/api/v1/resident/feedback/{$foreign->id}/comments", ['body' => 'hi'])
            ->assertNotFound();
    }
}
