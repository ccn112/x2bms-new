<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\FeedbackRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Resident\Interaction\InteractionAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Trung tâm tương tác (handoff v1.1) — read-model hợp nhất: gom feedback + payment
 * (+ service) của cư dân; KPI đúng; KHÔNG lộ phiếu cư dân/tenant khác (BOLA).
 */
class InteractionCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_gom_da_nguon_va_kpi_dung(): void
    {
        [$user, $ctx] = $this->resident('IA');
        $this->feedback($ctx, $user, 'in_progress');
        $this->payment($ctx, $user, 'pending');     // → in_progress
        $this->payment($ctx, $user, 'confirmed');   // → done

        $agg = app(InteractionAggregator::class);
        $s = $agg->summary($user);
        $this->assertSame(3, $s['total_count']);
        $this->assertSame(2, $s['pending_count'], 'feedback in_progress + payment pending');

        $items = $agg->list($user, [])['items'];
        $this->assertCount(3, $items);
        $types = collect($items)->pluck('type')->unique()->sort()->values()->all();
        $this->assertSame(['feedback', 'payment_confirmation'], $types);
    }

    public function test_loc_theo_type_va_status(): void
    {
        [$user, $ctx] = $this->resident('IB');
        $this->feedback($ctx, $user, 'resolved');    // done
        $this->payment($ctx, $user, 'pending');      // in_progress
        $agg = app(InteractionAggregator::class);

        $this->assertCount(1, $agg->list($user, ['type' => 'feedback'])['items']);
        $this->assertCount(1, $agg->list($user, ['status_family' => 'in_progress'])['items']);
        $this->assertCount(1, $agg->list($user, ['status_family' => 'done'])['items']);
        $this->assertCount(0, $agg->list($user, ['status_family' => 'cancelled'])['items']);
    }

    public function test_khong_lo_phieu_tenant_khac(): void
    {
        [$me, $ctx] = $this->resident('IC');
        $this->feedback($ctx, $me, 'new');
        // Cư dân/tenant khác:
        [$other, $ctxB] = $this->resident('ID');
        $this->feedback($ctxB, $other, 'new');
        $this->payment($ctxB, $other, 'pending');

        $items = app(InteractionAggregator::class)->list($me, [])['items'];
        $this->assertCount(1, $items, 'chỉ thấy phiếu của mình');
    }

    public function test_http_summary_va_list(): void
    {
        [$user, $ctx] = $this->resident('IE');
        $this->feedback($ctx, $user, 'new');

        Sanctum::actingAs($user, ['resident']);
        $this->getJson('/api/v1/resident/interactions/summary')
            ->assertOk()->assertJsonPath('data.total_count', 1);
        $this->getJson('/api/v1/resident/interactions')
            ->assertOk()->assertJsonPath('data.0.type', 'feedback');
    }

    public function test_http_chi_tiet_hop_nhat_va_timeline(): void
    {
        [$user, $ctx] = $this->resident('IF');
        $fb = $this->feedback($ctx, $user, 'in_progress');
        \DB::table('feedback_comments')->insert([
            'feedback_request_id' => $fb->id, 'resident_id' => $ctx['resident']->id,
            'author_name' => 'CD IF', 'body' => 'Cho hỏi tiến độ', 'is_internal' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \DB::table('feedback_comments')->insert([
            'feedback_request_id' => $fb->id, 'user_id' => $user->id, // giả lập BQL: resident_id null → is_staff
            'author_name' => 'BQL', 'body' => 'Đang xử lý', 'is_internal' => false,
            'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute(),
        ]);
        \DB::table('feedback_comments')->insert([
            'feedback_request_id' => $fb->id, 'author_name' => 'BQL', 'body' => 'ghi chú nội bộ',
            'is_internal' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        Sanctum::actingAs($user, ['resident']);
        $res = $this->getJson("/api/v1/resident/interactions/feedback/{$fb->id}")->assertOk();
        $res->assertJsonPath('data.source_type', 'feedback')
            ->assertJsonPath('data.description', 'Nội dung')
            ->assertJsonPath('data.timeline.0.body', 'Cho hỏi tiến độ')
            ->assertJsonPath('data.timeline.1.body', 'Đang xử lý');
        $this->assertCount(2, $res->json('data.timeline'), 'ẩn ghi chú nội bộ');
        $this->assertFalse($res->json('data.timeline.0.is_staff'));
        $this->assertTrue($res->json('data.timeline.1.is_staff'));
    }

    public function test_chi_tiet_khong_lo_phieu_cu_dan_khac(): void
    {
        [$me] = $this->resident('IG');
        [$other, $ctxB] = $this->resident('IH');
        $fb = $this->feedback($ctxB, $other, 'new');

        Sanctum::actingAs($me, ['resident']);
        $this->getJson("/api/v1/resident/interactions/feedback/{$fb->id}")->assertStatus(404);
    }

    /** @return array{0:User,1:array{tenant:Tenant,building:Building,apartment:Apartment,resident:Resident}} */
    private function resident(string $tag): array
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $p = Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => "BLD-$tag", 'name' => "B $tag"]);
        $apt = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => "APT-$tag"]);
        $u = User::create(['name' => "CD $tag", 'email' => strtolower($tag).'@ic.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $r = Resident::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'user_id' => $u->id, 'code' => "RES-$tag", 'full_name' => "CD $tag"]);
        ResidentApartmentRelation::create(['tenant_id' => $t->id, 'resident_id' => $r->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);

        return [$u, ['tenant' => $t, 'building' => $b, 'apartment' => $apt, 'resident' => $r]];
    }

    private function feedback(array $c, User $u, string $status): FeedbackRequest
    {
        return FeedbackRequest::create([
            'code' => 'FB-'.strtoupper(substr(md5($u->email.$status.microtime()), 0, 6)),
            'tenant_id' => $c['tenant']->id, 'building_id' => $c['building']->id, 'project_id' => $c['building']->project_id,
            'apartment_id' => $c['apartment']->id, 'resident_id' => $c['resident']->id, 'user_id' => $u->id,
            'title' => 'Phiếu '.$status, 'description' => 'Nội dung', 'status' => $status,
        ]);
    }

    private function payment(array $c, User $u, string $status): Payment
    {
        return Payment::create([
            'tenant_id' => $c['tenant']->id, 'building_id' => $c['building']->id,
            'apartment_id' => $c['apartment']->id, 'resident_id' => $c['resident']->id,
            'code' => 'PM-'.strtoupper(substr(md5($u->email.$status.microtime()), 0, 6)),
            'amount' => 1_500_000, 'status' => $status, 'submitted_at' => now(),
        ]);
    }
}
