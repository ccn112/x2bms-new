<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel as ChannelEnum;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\DeviceToken;
use App\Models\Notification as NotificationModel;
use App\Models\NotificationAudience;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Push\PushService;
use App\Services\Resident\NotificationPushDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BQL phát hành thông báo → đẩy PUSH tới cư dân đích (theo audience căn hộ),
 * tôn trọng kênh 'push'. Dùng PushService giả để bắt lời gọi (không gọi FCM thật).
 */
class NotificationPushDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function spyPush(): object
    {
        $spy = new class extends PushService
        {
            public array $calls = [];

            public function toUser(User $user, string $title, string $body, array $data = [], ?ChannelEnum $channel = null): int
            {
                $this->calls[] = ['user_id' => $user->id, 'channel' => $channel?->value, 'title' => $title];

                return 1;
            }
        };
        $this->app->instance(PushService::class, $spy);

        return $spy;
    }

    private function seedResidentInApartment(): array
    {
        $tenant = Tenant::create(['code' => 'TEN-NP', 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-NP', 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-NP', 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'APT-NP']);
        $user = User::create(['name' => 'Cư dân NP', 'email' => 'np-res@test.vn', 'password' => bcrypt('secret'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => 'RES-NP', 'full_name' => 'Cư dân NP']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-np-1', 'platform' => 'android']);

        return compact('tenant', 'project', 'apartment', 'user');
    }

    private function publish(array $ctx, string $type, string $scopeType, $scopeId, array $channels): NotificationModel
    {
        $n = NotificationModel::create([
            'tenant_id' => $ctx['tenant']->id, 'project_id' => $ctx['project']->id, 'owner_level' => 'project',
            'type' => $type, 'title' => 'Thông báo thử', 'summary' => 'Nội dung ngắn', 'status' => 'published', 'published_at' => now(),
        ]);
        NotificationAudience::create(['notification_id' => $n->id, 'scope_type' => $scopeType, 'scope_id' => $scopeId]);
        foreach ($channels as $ch) {
            NotificationChannel::create(['notification_id' => $n->id, 'channel' => $ch]);
        }

        return $n;
    }

    public function test_day_push_toi_cu_dan_can_ho_dich(): void
    {
        $spy = $this->spyPush();
        $ctx = $this->seedResidentInApartment();
        $n = $this->publish($ctx, 'announcement', 'apartment', $ctx['apartment']->id, ['app', 'push']);

        $sent = app(NotificationPushDispatcher::class)->dispatch($n);

        $this->assertSame(1, $sent);
        $this->assertCount(1, $spy->calls);
        $this->assertSame($ctx['user']->id, $spy->calls[0]['user_id']);
        $this->assertSame('announcement', $spy->calls[0]['channel'], 'push đúng kênh announcement');
    }

    public function test_khong_day_khi_khong_chon_kenh_push(): void
    {
        $spy = $this->spyPush();
        $ctx = $this->seedResidentInApartment();
        $n = $this->publish($ctx, 'announcement', 'apartment', $ctx['apartment']->id, ['app']); // không có 'push'

        $sent = app(NotificationPushDispatcher::class)->dispatch($n);

        $this->assertSame(0, $sent);
        $this->assertCount(0, $spy->calls);
    }

    public function test_khong_day_khi_chua_publish(): void
    {
        $spy = $this->spyPush();
        $ctx = $this->seedResidentInApartment();
        $n = $this->publish($ctx, 'announcement', 'apartment', $ctx['apartment']->id, ['push']);
        $n->update(['status' => 'draft']);

        $this->assertSame(0, app(NotificationPushDispatcher::class)->dispatch($n));
        $this->assertCount(0, $spy->calls);
    }
}
