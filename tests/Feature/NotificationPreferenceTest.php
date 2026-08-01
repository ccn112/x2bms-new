<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Push\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cư dân bật/tắt từng KÊNH thông báo push. Kênh khẩn cấp không tắt được; PushService
 * tôn trọng tuỳ chọn (bỏ qua kênh đã tắt).
 */
class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'U', 'email' => 'np@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
    }

    public function test_catalog_mac_dinh_bat_va_tat_duoc_mot_kenh(): void
    {
        $u = $this->user();
        Sanctum::actingAs($u, ['resident']);

        $res = $this->getJson('/api/v1/resident/notification-preferences')->assertOk();
        $channels = collect($res->json('data.channels'));
        $this->assertTrue($channels->firstWhere('channel', 'community')['enabled'], 'mặc định bật');
        $this->assertFalse($channels->firstWhere('channel', 'emergency')['can_disable'], 'khẩn cấp không tắt được');

        $this->putJson('/api/v1/resident/notification-preferences',
            ['channel' => 'community', 'enabled' => false])->assertOk();

        $res2 = $this->getJson('/api/v1/resident/notification-preferences')->assertOk();
        $this->assertFalse(collect($res2->json('data.channels'))
            ->firstWhere('channel', 'community')['enabled']);
    }

    public function test_khong_tat_duoc_kenh_khan_cap(): void
    {
        $u = $this->user();
        Sanctum::actingAs($u, ['resident']);

        $this->putJson('/api/v1/resident/notification-preferences',
            ['channel' => 'emergency', 'enabled' => false])->assertStatus(422);
    }

    public function test_pushservice_ton_trong_tuy_chon_kenh(): void
    {
        $u = $this->user();
        $push = app(PushService::class);

        // Mặc định: nhận community.
        $this->assertTrue($push->userAllows($u, NotificationChannel::Community));

        NotificationPreference::create([
            'user_id' => $u->id, 'channel' => 'community', 'enabled' => false,
        ]);
        $this->assertFalse($push->userAllows($u, NotificationChannel::Community));

        // Khẩn cấp luôn nhận dù có tắt.
        NotificationPreference::create([
            'user_id' => $u->id, 'channel' => 'emergency', 'enabled' => false,
        ]);
        $this->assertTrue($push->userAllows($u, NotificationChannel::Emergency));
    }
}
