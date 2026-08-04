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
 * B8 — cư dân CHẤM SAO phản ánh của mình sau khi BQL xử lý xong (resolved/closed).
 * Chỉ chủ phản ánh; không chấm khi còn mở; điểm 1–5.
 */
class FeedbackRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_chu_cham_sao_phan_anh_da_xu_ly(): void
    {
        [$user, $ctx] = $this->resident('RA');
        $fb = $this->feedback($ctx, $user, 'resolved');

        Sanctum::actingAs($user, ['resident']);
        $this->postJson("/api/v1/resident/feedback/{$fb->id}/rating", ['rating' => 5, 'rating_comment' => 'Nhanh, tốt'])
            ->assertOk()->assertJsonPath('data.rating', 5);

        $this->assertSame(5, (int) $fb->fresh()->rating);
        $this->assertSame('Nhanh, tốt', $fb->fresh()->rating_comment);
    }

    public function test_khong_cham_khi_con_mo(): void
    {
        [$user, $ctx] = $this->resident('RB');
        $fb = $this->feedback($ctx, $user, 'new');

        Sanctum::actingAs($user, ['resident']);
        $this->postJson("/api/v1/resident/feedback/{$fb->id}/rating", ['rating' => 4])
            ->assertStatus(409);
        $this->assertNull($fb->fresh()->rating);
    }

    public function test_nguoi_khac_khong_cham_duoc_404(): void
    {
        [$owner, $ctx] = $this->resident('RC');
        [$other] = $this->resident('RD');
        $fb = $this->feedback($ctx, $owner, 'resolved');

        Sanctum::actingAs($other, ['resident']);
        $this->postJson("/api/v1/resident/feedback/{$fb->id}/rating", ['rating' => 5])
            ->assertNotFound();
        $this->assertNull($fb->fresh()->rating);
    }

    public function test_diem_ngoai_1_5_bi_tu_choi(): void
    {
        [$user, $ctx] = $this->resident('RE');
        $fb = $this->feedback($ctx, $user, 'resolved');

        Sanctum::actingAs($user, ['resident']);
        $this->postJson("/api/v1/resident/feedback/{$fb->id}/rating", ['rating' => 6])
            ->assertStatus(422);
    }

    /** @return array{0:User,1:array{tenant:Tenant,project:Project,building:Building,apartment:Apartment,resident:Resident}} */
    private function resident(string $tag): array
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $p = Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => "BLD-$tag", 'name' => "B $tag"]);
        $apt = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => "APT-$tag"]);
        $u = User::create(['name' => "CD $tag", 'email' => strtolower($tag).'@fb.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $r = Resident::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'user_id' => $u->id, 'code' => "RES-$tag", 'full_name' => "CD $tag"]);
        ResidentApartmentRelation::create(['tenant_id' => $t->id, 'resident_id' => $r->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);

        return [$u, ['tenant' => $t, 'project' => $p, 'building' => $b, 'apartment' => $apt, 'resident' => $r]];
    }

    private function feedback(array $ctx, User $owner, string $status): FeedbackRequest
    {
        return FeedbackRequest::create([
            'code' => 'FB-'.strtoupper(substr(md5($owner->email.$status), 0, 6)),
            'tenant_id' => $ctx['tenant']->id, 'building_id' => $ctx['building']->id,
            'project_id' => $ctx['project']->id, 'apartment_id' => $ctx['apartment']->id,
            'resident_id' => $ctx['resident']->id, 'user_id' => $owner->id,
            'title' => 'Hỏng đèn hành lang', 'description' => 'Đèn tầng 5 không sáng', 'status' => $status,
        ]);
    }
}
