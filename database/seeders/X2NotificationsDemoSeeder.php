<?php

namespace Database\Seeders;

use App\Models\Notification;
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
 *  - N3/N4 SỔ GỬI đa kênh: push (đã nhận/đọc) + email (đã gửi) + sms/zalo (chờ provider)
 *    + dòng broadcast topic-level — để màn /admin/notifications/delivery-audit có dữ liệu.
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
        ]);

        foreach (self::ACCOUNTS as $email) {
            $this->seedDeliveryAudit($email);
        }
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

        // SMS + Zalo — chờ provider (ghi ý định, chưa gửi).
        foreach (['sms' => 850, 'zalo' => 300] as $ch => $cost) {
            NotificationDeliveryLog::updateOrCreate(
                ['notification_id' => $notification->id, 'user_id' => $user->id, 'channel' => $ch],
                [
                    'source_type' => $morph, 'source_id' => $notification->id, 'status' => 'queued',
                    'queued_at' => $now, 'error' => 'provider_not_configured', 'cost' => $cost,
                ],
            );
        }

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
