<?php

namespace Tests\Feature;

use App\Models\AppScreenDailyStat;
use App\Models\AppScreenEvent;
use App\Models\AppScreenReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Nhật ký màn hình + nút báo lỗi (chốt 2026-07-30: ghi theo thiết bị, có user thì
 * gắn kèm, gửi THEO LÔ định kỳ).
 */
class ScreenTelemetryTest extends TestCase
{
    use RefreshDatabase;

    private const DEV = 'device-abc-0001';

    /** @param array<int, array<string, mixed>> $events */
    private function postBatch(array $events, array $headers = [])
    {
        return $this->withHeaders(array_merge(['X-Device-Id' => self::DEV], $headers))
            ->postJson('/api/v1/telemetry/screen-views', ['events' => $events]);
    }

    private function event(array $overrides = []): array
    {
        return array_merge([
            'screen_key' => 'community.feed',
            'occurred_at' => now()->subMinutes(5)->toIso8601String(),
            'kind' => 'view',
            'duration_ms' => 4200,
            'app_version' => '1.4.0',
            'platform' => 'android',
        ], $overrides);
    }

    public function test_thiet_bi_an_danh_van_ghi_duoc_nhat_ky(): void
    {
        // Đây là điểm CỐT LÕI: bắt buộc đăng nhập thì mất sạch dữ liệu của nhóm chưa
        // đăng nhập — đúng nhóm cần biết nhất khi hỏi "tải app rồi sao không dùng".
        $res = $this->postBatch([$this->event(), $this->event(['screen_key' => 'billing.statements'])]);

        $res->assertStatus(202)->assertJsonPath('data.accepted', 2);
        $this->assertSame(2, AppScreenEvent::count());
        $this->assertNull(AppScreenEvent::first()->user_id);
        $this->assertSame(self::DEV, AppScreenEvent::first()->device_id);
    }

    public function test_da_dang_nhap_thi_gan_user_id_vao_cung_device(): void
    {
        // Cùng device_id: các dòng CŨ (ẩn danh) chính là hành vi trước khi định
        // danh — đó là cách ghép mà chủ dự án yêu cầu.
        $this->postBatch([$this->event()]);

        $user = User::create([
            'name' => 'Cu dan', 'email' => 'cd@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $this->actingAs($user)->withHeaders(['X-Device-Id' => self::DEV])
            ->postJson('/api/v1/telemetry/screen-views', ['events' => [$this->event()]])
            ->assertStatus(202);

        $rows = AppScreenEvent::orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertNull($rows[0]->user_id, 'dòng ẩn danh giữ nguyên');
        $this->assertSame($user->id, $rows[1]->user_id);
        $this->assertSame($rows[0]->device_id, $rows[1]->device_id,
            'cùng thiết bị → ghép được hành vi trước và sau khi đăng nhập');
    }

    public function test_thieu_device_id_thi_tu_choi(): void
    {
        $this->postJson('/api/v1/telemetry/screen-views', ['events' => [$this->event()]])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'device_id_required');
    }

    public function test_dong_xau_bi_bo_nhung_ca_lo_van_duoc_nhan(): void
    {
        // Nhật ký là dữ liệu phụ. Bắt app retry cả lô vì một dòng xấu chỉ tốn pin
        // và 4G của cư dân.
        $res = $this->postBatch([
            $this->event(),
            $this->event(['screen_key' => '']),                  // thiếu khoá màn
            ['rac' => true],                                     // không đúng hình dạng
            $this->event(['occurred_at' => 'khong-phai-ngay']),   // giờ không đọc được
        ]);

        $res->assertStatus(202)
            ->assertJsonPath('data.accepted', 1)
            ->assertJsonPath('data.skipped', 3);
        $this->assertSame(1, AppScreenEvent::count());
    }

    public function test_su_kien_qua_cu_hoac_o_tuong_lai_bi_bo(): void
    {
        // App bị kill rồi mở lại sau nhiều ngày vẫn còn lô cũ trong máy; ghi vào là
        // làm lệch số của hôm nay. Giờ tương lai = đồng hồ máy sai.
        config()->set('telemetry.max_event_age_days', 7);

        $res = $this->postBatch([
            $this->event(['occurred_at' => now()->subDays(30)->toIso8601String()]),
            $this->event(['occurred_at' => now()->addDays(2)->toIso8601String()]),
            $this->event(),
        ]);

        $res->assertJsonPath('data.accepted', 1)->assertJsonPath('data.skipped', 2);
    }

    public function test_lo_qua_lon_bi_cat_theo_max_batch(): void
    {
        config()->set('telemetry.max_batch_size', 5);

        $events = array_fill(0, 20, $this->event());
        $this->postBatch($events)->assertJsonPath('data.accepted', 5);
        $this->assertSame(5, AppScreenEvent::count());
    }

    public function test_gio_khong_kem_mui_gio_hieu_la_utc7(): void
    {
        // Cùng luật với paid_at (chủ dự án chốt UTC+7). Sai luật này thì mọi sự kiện
        // lệch 7 tiếng và biểu đồ "giờ cao điểm" sai hẳn.
        $vnLocal = Carbon::now(config('x2.timezone'))->subHours(2);

        $this->postBatch([$this->event([
            'occurred_at' => $vnLocal->format('Y-m-d\TH:i:s'),   // KHÔNG có offset
        ])])->assertStatus(202);

        $stored = AppScreenEvent::first()->occurred_at;
        $this->assertSame(
            $vnLocal->copy()->utc()->format('Y-m-d H:i'),
            $stored->copy()->utc()->format('Y-m-d H:i')
        );
    }

    // ------------------------------------------------------------ tổng hợp ngày

    public function test_tong_hop_theo_ngay_dem_dung_va_chay_lai_khong_nhan_doi(): void
    {
        $u = User::create(['name' => 'A', 'email' => 'a@test.vn',
            'password' => bcrypt('x'), 'account_type' => 'resident']);

        // 3 lượt xem + 1 thao tác trên cùng màn, từ 2 thiết bị, 1 người định danh.
        $rows = [];
        foreach ([['d1', $u->id, 'view'], ['d1', $u->id, 'view'], ['d2', null, 'view'], ['d2', null, 'action']] as [$dev, $uid, $kind]) {
            $rows[] = [
                'device_id' => $dev, 'user_id' => $uid, 'screen_key' => 'community.feed',
                'kind' => $kind, 'occurred_at' => now()->subHours(2), 'duration_ms' => 1000,
                'created_at' => now(),
            ];
        }
        AppScreenEvent::insert($rows);

        $this->artisan('x2:aggregate-telemetry --days=1 --no-prune')->assertSuccessful();
        $this->artisan('x2:aggregate-telemetry --days=1 --no-prune')->assertSuccessful();

        $stat = AppScreenDailyStat::where('screen_key', 'community.feed')->firstOrFail();
        $this->assertSame(1, AppScreenDailyStat::count(), 'chạy lại không được nhân đôi dòng');
        $this->assertSame(3, (int) $stat->views);
        $this->assertSame(1, (int) $stat->actions);
        $this->assertSame(2, (int) $stat->unique_devices);
        $this->assertSame(1, (int) $stat->unique_users);
    }

    public function test_don_du_lieu_tho_chi_sau_khi_da_tong_hop(): void
    {
        // Dọn trước khi tổng hợp là mất số vĩnh viễn — bảng thô không khôi phục được.
        config()->set('telemetry.raw_retention_days', 30);

        AppScreenEvent::insert([[
            'device_id' => 'd-old', 'screen_key' => 'home.dashboard', 'kind' => 'view',
            'occurred_at' => now()->subDays(60), 'created_at' => now()->subDays(60),
        ]]);

        $this->artisan('x2:aggregate-telemetry --days=90')->assertSuccessful();

        $this->assertSame(0, AppScreenEvent::count(), 'dòng quá hạn đã bị dọn');
        $this->assertSame(1, AppScreenDailyStat::where('screen_key', 'home.dashboard')->count(),
            'nhưng số của nó PHẢI còn trong bảng tổng hợp');
    }

    // ------------------------------------------------------------ nút báo lỗi

    public function test_bao_loi_ghi_lai_man_dang_mo(): void
    {
        // Giá trị chính của tính năng: biết lỗi Ở ĐÂU mà không phải hỏi lại.
        $res = $this->withHeaders(['X-Device-Id' => self::DEV])
            ->postJson('/api/v1/telemetry/screen-reports', [
                'message' => 'Bấm nút thanh toán thì app trắng màn hình',
                'screen_key' => 'billing.statement_detail',
                'route' => '/billing/statements/1276',
                'app_version' => '1.4.0',
                'platform' => 'android',
            ]);

        $res->assertStatus(201)->assertJsonPath('data.status', 'new');

        $r = AppScreenReport::firstOrFail();
        $this->assertSame('billing.statement_detail', $r->screen_key);
        $this->assertSame('bug', $r->kind);
        $this->assertNull($r->user_id, 'người ẩn danh vẫn báo được');
        $this->assertTrue($r->isOpen());
    }

    public function test_bao_loi_qua_ngan_thi_tu_choi(): void
    {
        $this->withHeaders(['X-Device-Id' => self::DEV])
            ->postJson('/api/v1/telemetry/screen-reports', ['message' => 'loi'])
            ->assertStatus(422);
    }
}
