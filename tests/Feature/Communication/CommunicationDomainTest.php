<?php

namespace Tests\Feature\Communication;

use App\Enums\CommunicationApprovalStatus;
use App\Enums\CommunicationWorkflowStatus as WS;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\AudienceResolver;
use App\Services\Notifications\AudienceRuleValidator;
use App\Services\Notifications\CampaignStateMachine;
use App\Services\Notifications\NotificationApprovalService;
use App\Services\Notifications\NotificationSnapshotService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * T1 — nền tảng domain truyền thông BQL. Máy trạng thái, DSL audience (whitelist),
 * resolver (dedupe + MUST_NOT_LEAK cross-tenant), snapshot bất biến, tuyến duyệt
 * maker-checker. Chạy sqlite in-memory → cũng xác thực toàn bộ migration additive.
 */
class CommunicationDomainTest extends TestCase
{
    use RefreshDatabase;

    // ---- State machine ---------------------------------------------------

    public function test_chuyen_trang_thai_hop_le_va_map_sang_status_cu_dan(): void
    {
        $n = $this->campaign(['workflow_status' => 'draft', 'status' => 'draft']);
        $sm = app(CampaignStateMachine::class);

        $sm->transition($n, WS::PendingApproval);
        $this->assertSame(WS::PendingApproval, $n->fresh()->workflow_status);
        $this->assertSame('draft', $n->fresh()->status, 'chờ duyệt vẫn ẩn với cư dân');

        // draft → approved → sending → sent : chuyển qua các bước hợp lệ.
        $n2 = $this->campaign(['workflow_status' => 'approved', 'status' => 'draft']);
        $sm->transition($n2, WS::Queued);
        $sm->transition($n2->fresh(), WS::Sending);
        $fresh = $n2->fresh();
        $this->assertSame('published', $fresh->status, 'đang gửi → cư dân thấy (status=published)');
        $this->assertNotNull($fresh->published_at);
    }

    public function test_chuyen_trang_thai_khong_hop_le_bi_chan(): void
    {
        $n = $this->campaign(['workflow_status' => 'draft', 'status' => 'draft']);
        $this->expectException(DomainException::class);
        app(CampaignStateMachine::class)->transition($n, WS::Sent); // draft → sent không hợp lệ
    }

    // ---- Audience DSL validator -----------------------------------------

    public function test_validator_tu_choi_field_va_operator_ngoai_whitelist(): void
    {
        $v = app(AudienceRuleValidator::class);

        $this->assertTrue($v->isValid(['scope' => ['building_ids' => [1]], 'include' => [
            ['field' => 'relationship_roles', 'operator' => 'in', 'value' => ['owner']],
        ]]));

        $this->expectException(InvalidArgumentException::class);
        $v->validate(['include' => [['field' => 'secret_column', 'operator' => 'in', 'value' => [1]]]]);
    }

    public function test_validator_chuan_hoa_shape_phang(): void
    {
        $v = app(AudienceRuleValidator::class);
        $norm = $v->normalize(['building_codes' => ['S1'], 'relationship_roles' => ['owner'], 'resident_status' => ['verified']]);

        $this->assertSame(['S1'], $norm['scope']['building_codes']);
        $this->assertCount(2, $norm['include']);
    }

    // ---- Audience resolver: dedupe + tenant isolation -------------------

    public function test_resolver_dedupe_cu_dan_nhieu_can_va_cach_ly_tenant(): void
    {
        [$tA, $pA, $bA] = $this->org('A');
        [$tB, $pB, $bB] = $this->org('B');

        // Cư dân A có HAI căn trong tòa → phải dedupe còn 1 recipient, 2 reasons.
        $userA = User::create(['name' => 'CD A', 'email' => 'cda@t.vn', 'password' => bcrypt('x'), 'account_type' => 'resident', 'tenant_id' => $tA->id]);
        $resA = Resident::create(['tenant_id' => $tA->id, 'building_id' => $bA->id, 'user_id' => $userA->id, 'code' => 'RA', 'full_name' => 'Cư dân A', 'status' => 'active']);
        $ap1 = Apartment::create(['tenant_id' => $tA->id, 'building_id' => $bA->id, 'code' => 'A-01', 'status' => 'occupied']);
        $ap2 = Apartment::create(['tenant_id' => $tA->id, 'building_id' => $bA->id, 'code' => 'A-02', 'status' => 'occupied']);
        ResidentApartmentRelation::create(['tenant_id' => $tA->id, 'resident_id' => $resA->id, 'apartment_id' => $ap1->id, 'role' => 'owner']);
        ResidentApartmentRelation::create(['tenant_id' => $tA->id, 'resident_id' => $resA->id, 'apartment_id' => $ap2->id, 'role' => 'tenant']);

        // Cư dân tenant B — MUST_NOT_LEAK: không được vào tập của chiến dịch tenant A.
        $resB = Resident::create(['tenant_id' => $tB->id, 'building_id' => $bB->id, 'code' => 'RB', 'full_name' => 'Cư dân B', 'status' => 'active']);
        $apB = Apartment::create(['tenant_id' => $tB->id, 'building_id' => $bB->id, 'code' => 'B-01', 'status' => 'occupied']);
        ResidentApartmentRelation::create(['tenant_id' => $tB->id, 'resident_id' => $resB->id, 'apartment_id' => $apB->id, 'role' => 'owner']);

        $campaign = $this->campaign([
            'tenant_id' => $tA->id, 'project_id' => $pA->id, 'building_id' => $bA->id,
            'audience_rule' => ['building_ids' => [$bA->id]],
        ]);

        $count = app(AudienceResolver::class)->resolve($campaign);

        $this->assertSame(1, $count, 'dedupe: 1 cư dân dù có 2 căn');
        $this->assertSame(1, $campaign->fresh()->recipient_count);
        $rec = $campaign->recipients()->first();
        $this->assertSame($resA->id, $rec->resident_id);
        $this->assertCount(2, $rec->audience_reasons, '2 lý do (2 căn)');
        $this->assertDatabaseMissing('notification_recipients', ['notification_id' => $campaign->id, 'resident_id' => $resB->id]);
    }

    public function test_resolver_loc_theo_vai_tro(): void
    {
        [$tA, $pA, $bA] = $this->org('R');
        $owner = Resident::create(['tenant_id' => $tA->id, 'building_id' => $bA->id, 'code' => 'OW', 'full_name' => 'Chủ', 'status' => 'active']);
        $tenantRes = Resident::create(['tenant_id' => $tA->id, 'building_id' => $bA->id, 'code' => 'TN', 'full_name' => 'Thuê', 'status' => 'active']);
        $ap = Apartment::create(['tenant_id' => $tA->id, 'building_id' => $bA->id, 'code' => 'R-01', 'status' => 'occupied']);
        ResidentApartmentRelation::create(['tenant_id' => $tA->id, 'resident_id' => $owner->id, 'apartment_id' => $ap->id, 'role' => 'owner']);
        ResidentApartmentRelation::create(['tenant_id' => $tA->id, 'resident_id' => $tenantRes->id, 'apartment_id' => $ap->id, 'role' => 'tenant']);

        $campaign = $this->campaign([
            'tenant_id' => $tA->id, 'building_id' => $bA->id,
            'audience_rule' => ['scope' => ['building_ids' => [$bA->id]], 'include' => [
                ['field' => 'relationship_roles', 'operator' => 'in', 'value' => ['owner']],
            ]],
        ]);

        app(AudienceResolver::class)->resolve($campaign);
        $this->assertSame(1, $campaign->fresh()->recipient_count, 'chỉ chủ hộ');
        $this->assertSame($owner->id, $campaign->recipients()->first()->resident_id);
    }

    // ---- Snapshot --------------------------------------------------------

    public function test_snapshot_tang_version_va_phat_hien_thay_doi(): void
    {
        $n = $this->campaign(['title' => 'Gốc']);
        $svc = app(NotificationSnapshotService::class);

        $snap = $svc->capture($n);
        $this->assertSame(1, $snap->version);
        $this->assertSame(1, $n->fresh()->snapshot_version);
        $this->assertFalse($svc->divergesFromLatest($n->fresh()), 'chưa đổi → không diverge');

        $n->update(['title' => 'Đã sửa sau duyệt']);
        $this->assertTrue($svc->divergesFromLatest($n->fresh()), 'đổi nội dung → invalidate');
    }

    // ---- Approval maker-checker -----------------------------------------

    public function test_route_khan_cap_va_luong_duyet_2_buoc_maker_checker(): void
    {
        [$tA, $pA, $bA] = $this->org('AP');
        $creator = User::create(['name' => 'Người tạo', 'email' => 'creator@t.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'tenant_id' => $tA->id]);
        $approver = User::create(['name' => 'Duyệt', 'email' => 'approver@t.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'tenant_id' => $tA->id]);

        $n = $this->campaign(['tenant_id' => $tA->id, 'project_id' => $pA->id, 'priority' => 'urgent', 'created_by_id' => $creator->id]);
        $svc = app(NotificationApprovalService::class);

        $route = $svc->resolveRoute($n);
        $this->assertSame('approval-emergency', $route['key']);

        $approval = $svc->requestApproval($n, $creator->id);
        $this->assertSame(2, $approval->total_steps);
        $this->assertSame(WS::PendingApproval, $n->fresh()->workflow_status);

        // Maker-checker: người tạo không tự duyệt.
        try {
            $svc->act($approval, $creator->id, 'approved');
            $this->fail('người tạo tự duyệt phải bị chặn');
        } catch (DomainException) {
            $this->assertTrue(true);
        }

        // Bước 1 approve → sang bước 2; bước 2 approve → campaign approved.
        $svc->act($approval->fresh('steps'), $approver->id, 'approved');
        $this->assertSame(2, $approval->fresh()->current_step);
        $svc->act($approval->fresh('steps'), $approver->id, 'approved');
        $this->assertSame(CommunicationApprovalStatus::Approved, $approval->fresh()->status);
        $this->assertSame(WS::Approved, $n->fresh()->workflow_status);
    }

    // ---- helpers ---------------------------------------------------------

    /** @return array{0:Tenant,1:Project,2:Building} */
    private function org(string $tag): array
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "Tenant $tag"]);
        $p = Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "Project $tag"]);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => "S-$tag", 'name' => "Building $tag"]);

        return [$t, $p, $b];
    }

    private function campaign(array $attrs = []): Notification
    {
        return Notification::create(array_merge([
            'owner_level' => 'project',
            'content_type' => 'announcement',
            'workflow_status' => 'draft',
            'status' => 'draft',
            'title' => 'Chiến dịch test',
            'priority' => 'normal',
        ], $attrs));
    }
}
