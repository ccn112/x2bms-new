<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\NotificationChannel;
use App\Models\NotificationDeliveryLog;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder TỔNG cho toàn bộ tính năng nhóm A + module thông báo đa kênh (A1–A4, N0–N4).
 * Chạy một lệnh là có đủ dữ liệu kiểm bằng mắt trên app + màn audit BQL.
 *
 *   php artisan db:seed --class=X2NotificationsDemoSeeder --force
 *
 * Gồm:
 *  - A1 nợ chủ cũ + đã trả · A3 thông báo ack · A4 item interaction   (GroupAFeaturesDemoSeeder)
 *  - N0/N1b/N2 activity chuông: phiếu duyệt · công nợ · cảm xúc         (BellDemoSeeder)
 *  - N3/N4 SỔ GỬI đa kênh: push (đã nhận/đọc) + email (đã gửi) + zalo/whatsapp/telegram/
 *    xspace (cổng chờ 'provider_pending') + sms ('provider_not_configured') + dòng
 *    broadcast topic-level — để màn /admin/notifications/delivery-audit có dữ liệu.
 *  - ADR-002 cấu hình kênh theo tòa (BuildingChannelConfigDemoSeeder) + 1 thông báo BQL
 *    app+push+email sẵn sàng (code DEMO-A-BQLPUSH-*) để test luồng push/email xuống cư dân.
 *
 * PHỤ THUỘC: CommunityTestResidentsSeeder (2 TK test đã có căn).
 */
class X2NotificationsDemoSeeder extends Seeder
{
    private const ACCOUNTS = ['test.cudan1@x2bms.vn', 'test.cudan2@x2bms.vn'];

    public function run(): void
    {
        $this->call([
            GroupAFeaturesDemoSeeder::class,
            BellDemoSeeder::class,
            BuildingChannelConfigDemoSeeder::class,   // ADR-002 — cấu hình kênh theo tòa
        ]);

        foreach (self::ACCOUNTS as $email) {
            $this->seedBqlPushEmailNotification($email);
            $this->seedDeliveryAudit($email);
        }
    }

    /**
     * Một thông báo BQL "sẵn sàng đẩy": chọn kênh app + push + email, nhắm ĐÚNG căn
     * của TK test → dùng để test luồng BQL push + email xuống cư dân (phát hành lại
     * trên màn Trung tâm thông báo, hoặc qua NotificationPushDispatcher).
     */
    private function seedBqlPushEmailNotification(string $email): void
    {
        $user = User::where('email', $email)->first();
        if ($user === null) {
            return;
        }
        $rel = ResidentApartmentRelation::withoutGlobalScopes()
            ->whereIn('resident_id', Resident::withoutGlobalScopes()->where('user_id', $user->id)->pluck('id'))
            ->whereNotNull('apartment_id')->first();
        if ($rel === null) {
            return;
        }
        $resident = Resident::withoutGlobalScopes()->find($rel->resident_id);

        $n = Notification::withoutGlobalScopes()->firstOrCreate(
            ['code' => 'DEMO-A-BQLPUSH-'.$rel->apartment_id],
            [
                'tenant_id' => $resident->tenant_id, 'project_id' => $resident->project_id,
                'owner_level' => 'project', 'source' => 'bql',
                'type' => 'announcement', 'category' => 'announcement',
                'title' => 'Lịch phun khử khuẩn hành lang — 20:00 tối nay',
                'summary' => 'BQL phun khử khuẩn khu vực chung. Đề nghị cư dân đóng cửa sổ hướng hành lang.',
                'priority' => 'normal', 'status' => 'published', 'published_at' => now(), 'requires_ack' => false,
            ],
        );

        NotificationAudience::withoutGlobalScopes()->firstOrCreate(
            ['notification_id' => $n->id, 'scope_type' => 'apartment', 'scope_id' => $rel->apartment_id],
        );
        foreach (['app', 'push', 'email'] as $ch) {
            NotificationChannel::withoutGlobalScopes()->firstOrCreate(
                ['notification_id' => $n->id, 'channel' => $ch],
            );
        }

        $this->command?->info("Thông báo BQL push+email demo cho {$email} (căn #{$rel->apartment_id}).");
    }

    /** N3/N4 — dựng sổ gửi đa kênh cho thông báo ack demo của một TK test. */
    private function seedDeliveryAudit(string $email): void
    {
        $user = User::where('email', $email)->first();
        if ($user === null) {
            return;
        }
        $rel = ResidentApartmentRelation::withoutGlobalScopes()
            ->whereIn('resident_id', Resident::withoutGlobalScopes()->where('user_id', $user->id)->pluck('id'))
            ->whereNotNull('apartment_id')->first();
        if ($rel === null) {
            return;
        }
        $resident = Resident::withoutGlobalScopes()->find($rel->resident_id);

        // Thông báo ack demo do GroupAFeaturesDemoSeeder tạo (code DEMO-A-ACK-<apt>).
        $notification = Notification::withoutGlobalScopes()
            ->where('code', 'DEMO-A-ACK-'.$rel->apartment_id)->first();
        if ($notification === null) {
            return;
        }

        $morph = $notification->getMorphClass();
        $now = now();

        // Push per-user — đã gửi → đã nhận → đã đọc (vòng đời đầy đủ).
        NotificationDeliveryLog::updateOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id, 'channel' => 'push'],
            [
                'source_type' => $morph, 'source_id' => $notification->id, 'status' => 'read',
                'queued_at' => $now->copy()->subMinutes(6), 'sent_at' => $now->copy()->subMinutes(5),
                'delivered_at' => $now->copy()->subMinutes(4), 'read_at' => $now->copy()->subMinutes(2),
                'provider_message_id' => 'fcm-demo-'.$user->id,
            ],
        );

        // Email — đã gửi (không tính phí).
        NotificationDeliveryLog::updateOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id, 'channel' => 'email'],
            [
                'source_type' => $morph, 'source_id' => $notification->id, 'status' => 'sent',
                'queued_at' => $now->copy()->subMinutes(6), 'sent_at' => $now->copy()->subMinutes(5),
                'cost' => 0, 'provider_message_id' => 'ses-demo-'.$user->id,
            ],
        );

        // Cổng chờ (ADR-002): tòa ĐÃ khai tham số Zalo/WhatsApp/Telegram/X.Space →
        // 'provider_pending'. SMS chưa khai cấu hình tòa → 'provider_not_configured'.
        $pending = ['zalo' => 300, 'whatsapp' => 250, 'telegram' => 0, 'xspace' => 0];
        foreach ($pending as $ch => $cost) {
            NotificationDeliveryLog::updateOrCreate(
                ['notification_id' => $notification->id, 'user_id' => $user->id, 'channel' => $ch],
                [
                    'source_type' => $morph, 'source_id' => $notification->id, 'status' => 'queued',
                    'queued_at' => $now, 'error' => 'provider_pending', 'cost' => $cost ?: null,
                ],
            );
        }
        NotificationDeliveryLog::updateOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id, 'channel' => 'sms'],
            [
                'source_type' => $morph, 'source_id' => $notification->id, 'status' => 'queued',
                'queued_at' => $now, 'error' => 'provider_not_configured', 'cost' => 850,
            ],
        );

        // Broadcast topic-level (push tới cả toà) — audit topic-level (recipient null).
        NotificationDeliveryLog::updateOrCreate(
            ['notification_id' => $notification->id, 'channel' => 'push', 'topic' => 'building_'.$resident->building_id],
            [
                'source_type' => $morph, 'source_id' => $notification->id, 'status' => 'sent',
                'queued_at' => $now, 'sent_at' => $now,
            ],
        );

        $this->command?->info("Sổ gửi demo cho {$email}: push(đọc)+email(gửi)+sms/zalo(chờ)+topic.");
    }
}
