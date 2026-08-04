<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingNotificationChannel;
use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\MultiChannelNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * N4 + ADR-002 — gửi đa kênh building-aware + ghi SỔ GỬI per-(người × kênh).
 *  - email: gửi thật qua Mail + ghi 'sent'.
 *  - kênh cổng chờ CÓ cấu hình tòa → 'queued'+'provider_pending'; CHƯA cấu hình →
 *    'provider_not_configured'; tòa TẮT kênh → 'suppressed'+'channel_disabled'.
 *  - idempotent: gửi lại KHÔNG gửi email lần hai.
 */
class MultiChannelNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_gui_email_va_ghi_so_gui_stub_cho_sms_zalo(): void
    {
        Mail::fake();
        $user = User::create(['name' => 'CD', 'email' => 'mc@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $n = Notification::create(['title' => 'Nhắc phí', 'status' => 'published', 'owner_level' => 'project']);

        app(MultiChannelNotifier::class)->notify(
            'notifications', $n->id, $user, ['email', 'sms', 'zalo'],
            'Nhắc phí tháng 8', 'Quý cư dân vui lòng thanh toán trước 10/08.', notificationId: $n->id,
        );

        // email 'sent' = EmailChannelDispatcher gọi Mail thành công (không throw).
        $email = NotificationDeliveryLog::where('user_id', $user->id)->where('channel', 'email')->first();
        $this->assertSame('sent', $email->status);
        $this->assertSame(0.0, (float) $email->cost, 'email không tính phí');
        $this->assertSame('notifications', $email->source_type);
        $this->assertSame($n->id, (int) $email->source_id);

        // Không truyền buildingId → chưa cấu hình tòa → provider_not_configured.
        foreach (['sms', 'zalo'] as $ch) {
            $row = NotificationDeliveryLog::where('user_id', $user->id)->where('channel', $ch)->first();
            $this->assertSame('queued', $row->status, "$ch chờ provider");
            $this->assertSame('provider_not_configured', $row->error);
        }
    }

    public function test_idempotent_khong_gui_email_lan_hai(): void
    {
        Mail::fake();
        $user = User::create(['name' => 'CD', 'email' => 'mc2@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $n = Notification::create(['title' => 'T', 'status' => 'published', 'owner_level' => 'project']);
        $notifier = app(MultiChannelNotifier::class);

        $notifier->notify('notifications', $n->id, $user, ['email'], 'T', 'B', notificationId: $n->id);
        $sentAt = NotificationDeliveryLog::where('user_id', $user->id)->where('channel', 'email')->value('sent_at');
        $notifier->notify('notifications', $n->id, $user, ['email'], 'T', 'B', notificationId: $n->id);

        // Chỉ 1 dòng, và lần hai KHÔNG gửi lại (mốc sent_at giữ nguyên).
        $rows = NotificationDeliveryLog::where('user_id', $user->id)->where('channel', 'email')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('sent', $rows->first()->status);
        $this->assertEquals($sentAt, $rows->first()->sent_at, 'không gửi lại lần hai');
    }

    public function test_cong_cho_phan_biet_theo_cau_hinh_toa(): void
    {
        Mail::fake();
        [$building, $user, $n] = $this->seedBuildingUserNotification('CFG1');

        // Tòa ĐÃ khai Zalo (cổng chờ) — nhưng CHƯA khai Telegram.
        BuildingNotificationChannel::create([
            'tenant_id' => $building->tenant_id, 'building_id' => $building->id,
            'channel' => 'zalo', 'enabled' => true, 'status' => 'pending',
            'config' => ['oa_id' => 'OA-1', 'access_token' => 'X'],
        ]);

        app(MultiChannelNotifier::class)->notify(
            'notifications', $n->id, $user, ['zalo', 'telegram'],
            'TB', 'Nội dung', notificationId: $n->id, buildingId: $building->id,
        );

        $zalo = NotificationDeliveryLog::where('user_id', $user->id)->where('channel', 'zalo')->first();
        $this->assertSame('queued', $zalo->status);
        $this->assertSame('provider_pending', $zalo->error, 'đã khai tham số → chờ đi live');

        $telegram = NotificationDeliveryLog::where('user_id', $user->id)->where('channel', 'telegram')->first();
        $this->assertSame('provider_not_configured', $telegram->error, 'chưa khai → chưa cấu hình');
    }

    public function test_toa_tat_kenh_ghi_suppressed(): void
    {
        Mail::fake();
        [$building, $user, $n] = $this->seedBuildingUserNotification('CFG2');

        // Tòa TẮT email.
        BuildingNotificationChannel::create([
            'tenant_id' => $building->tenant_id, 'building_id' => $building->id,
            'channel' => 'email', 'enabled' => false, 'status' => 'active',
        ]);

        app(MultiChannelNotifier::class)->notify(
            'notifications', $n->id, $user, ['email'],
            'TB', 'Nội dung', notificationId: $n->id, buildingId: $building->id,
        );

        $row = NotificationDeliveryLog::where('user_id', $user->id)->where('channel', 'email')->first();
        $this->assertSame('suppressed', $row->status);
        $this->assertSame('channel_disabled', $row->error);
        Mail::assertNothingSent();
    }

    /** @return array{0:Building,1:User,2:Notification} */
    private function seedBuildingUserNotification(string $suffix): array
    {
        $tenant = Tenant::create(['code' => "TEN-{$suffix}", 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-{$suffix}", 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-{$suffix}", 'name' => 'B']);
        $user = User::create(['name' => 'CD', 'email' => strtolower($suffix).'@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $n = Notification::create(['title' => 'TB', 'status' => 'published', 'owner_level' => 'project', 'tenant_id' => $tenant->id]);

        return [$building, $user, $n];
    }
}
