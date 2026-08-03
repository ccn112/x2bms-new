<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\User;
use App\Services\Notifications\MultiChannelNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * N4 — gửi đa kênh + ghi SỔ GỬI per-(người × kênh).
 *  - email: gửi thật qua Mail + ghi 'sent'.
 *  - sms/zalo (chưa có provider): ghi 'queued' + 'provider_not_configured' (audit thấy ý định).
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
}
