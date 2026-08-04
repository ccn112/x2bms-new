<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\NotificationAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 07-10 — số liệu hiệu quả thông báo tính đúng từ dữ liệu thật + KHÔNG lộ tenant khác.
 */
class NotificationAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): NotificationAnalyticsService
    {
        return app(NotificationAnalyticsService::class);
    }

    public function test_summary_open_rate_dung(): void
    {
        $sa = $this->admin();
        $this->published(100, 60);
        $this->published(50, 25);

        $s = $this->svc()->summary($sa);
        $this->assertSame(2, $s['published']);
        $this->assertSame(150, $s['recipients']);
        $this->assertSame(85, $s['reads']);
        $this->assertSame(56.7, $s['open_rate']);   // 85/150
    }

    public function test_channel_breakdown_pheu_dung(): void
    {
        $sa = $this->admin();
        $n = $this->published(10, 3);
        // push: 3 read + 1 sent + 1 failed = total 5, delivered 4, read 3
        $this->log($n, 'push', 'read', 3);
        $this->log($n, 'push', 'sent', 1);
        $this->log($n, 'push', 'failed', 1);
        // email: 2 sent (delivered 2, read 0)
        $this->log($n, 'email', 'sent', 2);

        $rows = collect($this->svc()->channelBreakdown($sa))->keyBy('channel');
        $push = $rows['push'];
        $this->assertSame(5, $push['total']);
        $this->assertSame(4, $push['delivered']);
        $this->assertSame(3, $push['read']);
        $this->assertSame(1, $push['failed']);
        $this->assertSame(80.0, $push['delivery_rate']);   // 4/5
        $this->assertSame(75.0, $push['read_rate']);       // 3/4
        $this->assertSame(2, $rows['email']['delivered']);
    }

    public function test_khong_tinh_thong_bao_tenant_du_an_khac(): void
    {
        // BQL dự án A; thông báo của dự án B không được tính.
        $tA = Tenant::create(['code' => 'TEN-AA', 'name' => 'A']);
        $pA = Project::create(['tenant_id' => $tA->id, 'code' => 'PRJ-AA', 'name' => 'A']);
        $bql = User::create(['name' => 'BQL', 'email' => 'an-bql@test.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'tenant_id' => $tA->id, 'project_id' => $pA->id]);
        Notification::create(['owner_level' => 'project', 'tenant_id' => $tA->id, 'project_id' => $pA->id, 'title' => 'A', 'status' => 'published', 'published_at' => now(), 'recipient_count' => 10, 'read_count' => 5]);

        $tB = Tenant::create(['code' => 'TEN-BB', 'name' => 'B']);
        $pB = Project::create(['tenant_id' => $tB->id, 'code' => 'PRJ-BB', 'name' => 'B']);
        Notification::create(['owner_level' => 'project', 'tenant_id' => $tB->id, 'project_id' => $pB->id, 'title' => 'B', 'status' => 'published', 'published_at' => now(), 'recipient_count' => 999, 'read_count' => 999]);

        $s = $this->svc()->summary($bql);
        $this->assertSame(1, $s['published'], 'chỉ thấy thông báo dự án mình');
        $this->assertSame(10, $s['recipients'], 'KHÔNG cộng recipients của dự án B');
    }

    private function admin(): User
    {
        return User::create(['name' => 'SA', 'email' => 'an-sa@test.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'is_platform_admin' => true]);
    }

    private function published(int $recipients, int $reads): Notification
    {
        return Notification::create([
            'owner_level' => 'platform', 'title' => 'TB', 'status' => 'published', 'published_at' => now(),
            'recipient_count' => $recipients, 'read_count' => $reads,
        ]);
    }

    private function log(Notification $n, string $channel, string $status, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            NotificationDeliveryLog::create([
                'notification_id' => $n->id, 'source_type' => 'notifications', 'source_id' => $n->id,
                'channel' => $channel, 'status' => $status,
            ]);
        }
    }
}
