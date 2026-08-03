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
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationPreference;
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

            public function toUser(User $user, string $title, string $body, array $data = [], ?ChannelEnum $channel = null, ?string $imageUrl = null): int
            {
                $this->calls[] = ['user_id' => $user->id, 'channel' => $channel?->value, 'title' => $title, 'image' => $imageUrl];

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

    // ── A2 — persist per-recipient TRƯỚC khi gửi ────────────────────────────────

    public function test_ghi_vet_gui_per_recipient_va_recipient_count(): void
    {
        $this->spyPush();
        $ctx = $this->seedResidentInApartment();
        $n = $this->publish($ctx, 'announcement', 'apartment', $ctx['apartment']->id, ['app', 'push']);

        app(NotificationPushDispatcher::class)->dispatch($n);

        $log = NotificationDeliveryLog::where('notification_id', $n->id)
            ->where('user_id', $ctx['user']->id)->where('channel', 'push')->first();
        $this->assertNotNull($log, 'có vết gửi per-recipient');
        $this->assertSame('sent', $log->status);
        $this->assertNotNull($log->sent_at);
        // N3: audit đầy đủ — nguồn polymorphic + mốc queued.
        $this->assertSame((new NotificationModel)->getMorphClass(), $log->source_type);
        $this->assertSame($n->id, (int) $log->source_id);
        $this->assertNotNull($log->queued_at);
        $this->assertSame(1, $n->fresh()->recipient_count, 'đếm người nhận dự kiến');
    }

    public function test_replay_khong_gui_trung_va_khong_nhan_doi_vet(): void
    {
        $spy = $this->spyPush();
        $ctx = $this->seedResidentInApartment();
        $n = $this->publish($ctx, 'announcement', 'apartment', $ctx['apartment']->id, ['app', 'push']);

        $first = app(NotificationPushDispatcher::class)->dispatch($n);
        $second = app(NotificationPushDispatcher::class)->dispatch($n); // replay

        $this->assertSame(1, $first);
        $this->assertSame(0, $second, 'replay bỏ qua dòng đã sent');
        $this->assertCount(1, $spy->calls, 'FCM chỉ gọi một lần cho cả hai lượt');
        $this->assertSame(1, NotificationDeliveryLog::where('notification_id', $n->id)->count(),
            'không nhân đôi vết gửi (unique notification+user+channel)');
    }

    public function test_kenh_bi_tat_ghi_suppressed_khong_goi_fcm(): void
    {
        // Spy đếm lời gọi FCM NHƯNG từ chối kênh cho user (mô phỏng đã tắt kênh).
        $spy = new class extends PushService
        {
            public array $calls = [];

            public function userAllows(User $user, ChannelEnum $channel): bool
            {
                return false;
            }

            public function toUser(User $user, string $title, string $body, array $data = [], ?ChannelEnum $channel = null, ?string $imageUrl = null): int
            {
                $this->calls[] = $user->id;

                return 1;
            }
        };
        $this->app->instance(PushService::class, $spy);

        $ctx = $this->seedResidentInApartment();
        $n = $this->publish($ctx, 'announcement', 'apartment', $ctx['apartment']->id, ['app', 'push']);

        $sent = app(NotificationPushDispatcher::class)->dispatch($n);

        $this->assertSame(0, $sent);
        $this->assertCount(0, $spy->calls, 'kênh tắt → KHÔNG gọi FCM');
        $log = NotificationDeliveryLog::where('notification_id', $n->id)
            ->where('user_id', $ctx['user']->id)->first();
        $this->assertSame('suppressed', $log->status);
        $this->assertSame('channel_disabled', $log->error);
    }

    // ── N1 — broadcast qua FCM topic (không fan-out per-người) ──────────────────

    public function test_broadcast_toa_nha_gui_topic_khong_ghi_per_nguoi(): void
    {
        $spy = new class extends PushService
        {
            public array $topics = [];
            public int $userCalls = 0;

            public function toTopic(string $topic, string $title, string $body, array $data = [], ?string $imageUrl = null): bool
            {
                $this->topics[] = $topic;

                return true;
            }

            public function toUser(User $user, string $title, string $body, array $data = [], ?ChannelEnum $channel = null, ?string $imageUrl = null): int
            {
                $this->userCalls++;

                return 1;
            }
        };
        $this->app->instance(PushService::class, $spy);

        $ctx = $this->seedResidentInApartment();
        // Audience = TOÀ NHÀ (broadcast) thay vì căn hộ.
        $n = $this->publish($ctx, 'announcement', 'building', $ctx['apartment']->building_id, ['app', 'push']);

        app(NotificationPushDispatcher::class)->dispatch($n);

        $this->assertSame(['building_'.$ctx['apartment']->building_id], $spy->topics, 'gửi 1 message tới topic toà');
        $this->assertSame(0, $spy->userCalls, 'KHÔNG gửi lẻ per-người cho broadcast');
        // N3: broadcast ghi audit TOPIC-LEVEL (1 dòng, recipient null), KHÔNG per-người.
        $this->assertSame(0, NotificationDeliveryLog::where('notification_id', $n->id)->whereNotNull('user_id')->count(),
            'broadcast KHÔNG ghi delivery-log per-người');
        $topicRow = NotificationDeliveryLog::where('notification_id', $n->id)->whereNull('user_id')->first();
        $this->assertNotNull($topicRow, 'có 1 dòng audit topic-level');
        $this->assertSame('building_'.$ctx['apartment']->building_id, $topicRow->topic);
        $this->assertSame('sent', $topicRow->status);
    }

    public function test_gui_that_bai_giu_vet_failed_de_gui_lai(): void
    {
        // Spy trả 0 (không thiết bị nào nhận) → dòng 'failed', lượt sau thử lại.
        $spy = new class extends PushService
        {
            public int $calls = 0;
            public int $returns = 0; // đổi được giữa hai lượt

            public function toUser(User $user, string $title, string $body, array $data = [], ?ChannelEnum $channel = null, ?string $imageUrl = null): int
            {
                $this->calls++;

                return $this->returns;
            }
        };
        $this->app->instance(PushService::class, $spy);

        $ctx = $this->seedResidentInApartment();
        $n = $this->publish($ctx, 'announcement', 'apartment', $ctx['apartment']->id, ['app', 'push']);

        $spy->returns = 0;
        $this->assertSame(0, app(NotificationPushDispatcher::class)->dispatch($n));
        $log = NotificationDeliveryLog::where('notification_id', $n->id)->first();
        $this->assertSame('failed', $log->status);
        $this->assertSame('no_active_token', $log->error);

        // Lượt sau thiết bị đã nhận → dòng 'failed' được thử lại và thành 'sent'.
        $spy->returns = 1;
        $this->assertSame(1, app(NotificationPushDispatcher::class)->dispatch($n));
        $this->assertSame(2, $spy->calls, 'dòng failed được gửi lại lượt sau');
        $this->assertSame('sent', $log->fresh()->status);
    }
}
