<?php

namespace Tests\Feature\Communication;

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
use App\Services\Notifications\NotificationApprovalService;
use App\Services\Notifications\NotificationPublisher;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** T4 — vòng đời phát hành: gửi duyệt → duyệt (khác người) → phát hành → completed + delivery inbox. */
class CommunicationPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_duyet_va_phat_hanh_tao_delivery_inbox(): void
    {
        [$t, $p, $b] = $this->org();
        $user = User::create(['name' => 'CD', 'email' => 'cd@t.vn', 'password' => bcrypt('x'), 'account_type' => 'resident', 'tenant_id' => $t->id]);
        $res = Resident::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'user_id' => $user->id, 'code' => 'R1', 'full_name' => 'Cư dân 1', 'status' => 'active']);
        $ap = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => 'A-1', 'status' => 'occupied']);
        ResidentApartmentRelation::create(['tenant_id' => $t->id, 'resident_id' => $res->id, 'apartment_id' => $ap->id, 'role' => 'owner']);

        $creator = User::create(['name' => 'Tạo', 'email' => 'creator@t.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'tenant_id' => $t->id]);
        $approver = User::create(['name' => 'Duyệt', 'email' => 'approver@t.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'tenant_id' => $t->id]);

        $n = Notification::create([
            'owner_level' => 'project', 'tenant_id' => $t->id, 'project_id' => $p->id, 'building_id' => $b->id,
            'content_type' => 'announcement', 'type' => 'announcement', 'workflow_status' => 'draft', 'status' => 'draft',
            'title' => 'Thông báo bảo trì', 'priority' => 'normal', 'created_by_id' => $creator->id,
            'audience_rule' => ['building_ids' => [$b->id]],
        ]);
        $n->channels()->create(['channel' => 'app', 'enabled' => true]);

        app(AudienceResolver::class)->resolve($n->fresh('channels'));
        $this->assertSame(1, (int) $n->fresh()->recipient_count);

        // Gửi duyệt (route mặc định 1 bước), duyệt bởi người khác → approved.
        $approval = app(NotificationApprovalService::class)->requestApproval($n->fresh(), $creator->id);
        app(NotificationApprovalService::class)->act($approval, $approver->id, 'approved');
        $this->assertSame(WS::Approved, $n->fresh()->workflow_status);

        // Phát hành → completed + status=published + delivery inbox.
        app(NotificationPublisher::class)->publish($n->fresh(), $approver->id);

        $fresh = $n->fresh();
        $this->assertSame(WS::Completed, $fresh->workflow_status);
        $this->assertSame('published', $fresh->status, 'cư dân thấy');
        $this->assertNotNull($fresh->published_at);
        $this->assertDatabaseHas('notification_delivery_logs', [
            'notification_id' => $n->id, 'user_id' => $user->id, 'channel' => 'app', 'status' => 'sent',
        ]);
    }

    public function test_khong_the_phat_hanh_khi_chua_duyet(): void
    {
        [$t, $p, $b] = $this->org();
        $n = Notification::create([
            'owner_level' => 'project', 'tenant_id' => $t->id, 'project_id' => $p->id,
            'content_type' => 'announcement', 'workflow_status' => 'draft', 'status' => 'draft',
            'title' => 'Chưa duyệt', 'priority' => 'normal',
        ]);

        $this->expectException(DomainException::class);
        app(NotificationPublisher::class)->publish($n, 1);
    }

    /** @return array{0:Tenant,1:Project,2:Building} */
    private function org(): array
    {
        $t = Tenant::create(['code' => 'TEN-PUB', 'name' => 'T']);
        $p = Project::create(['tenant_id' => $t->id, 'code' => 'PRJ-PUB', 'name' => 'P']);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => 'S-PUB', 'name' => 'B']);

        return [$t, $p, $b];
    }
}
