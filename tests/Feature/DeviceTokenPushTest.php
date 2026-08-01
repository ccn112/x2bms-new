<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Push\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Push (FCM): đăng ký/gỡ token thiết bị (app cư dân + web admin) + PushService
 * no-op khi FCM chưa bật. Không gọi FCM thật trong test.
 */
class DeviceTokenPushTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'U', 'email' => 'dt@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
    }

    public function test_dang_ky_token_updateOrCreate_khong_trung(): void
    {
        $u = $this->user();
        Sanctum::actingAs($u, ['resident']);

        $this->postJson('/api/v1/resident/device-tokens', [
            'token' => 'abc123', 'platform' => 'android',
        ])->assertOk()->assertJsonPath('data.registered', true);

        // Cùng token, đổi nền tảng (web) → cập nhật, không tạo dòng mới.
        $this->postJson('/api/v1/resident/device-tokens', [
            'token' => 'abc123', 'platform' => 'web',
        ])->assertOk();

        $this->assertSame(1, DeviceToken::where('token', 'abc123')->count());
        $this->assertSame('web', DeviceToken::where('token', 'abc123')->value('platform'));
        $this->assertSame($u->id, (int) DeviceToken::where('token', 'abc123')->value('user_id'));
    }

    public function test_go_token_khi_dang_xuat(): void
    {
        $u = $this->user();
        DeviceToken::create(['user_id' => $u->id, 'token' => 'tok', 'platform' => 'ios']);
        Sanctum::actingAs($u, ['resident']);

        $this->deleteJson('/api/v1/resident/device-tokens', ['token' => 'tok'])->assertOk();
        $this->assertSame(0, DeviceToken::where('token', 'tok')->count());
    }

    public function test_push_service_no_op_khi_chua_bat(): void
    {
        config(['services.firebase.enabled' => false]);
        $u = $this->user();
        DeviceToken::create(['user_id' => $u->id, 'token' => 'tok2', 'platform' => 'android']);

        // enabled() false → toUser trả 0, KHÔNG gọi FCM.
        $this->assertFalse(app(PushService::class)->enabled());
        $this->assertSame(0, app(PushService::class)->toUser($u, 'Tiêu đề', 'Nội dung'));
    }
}
