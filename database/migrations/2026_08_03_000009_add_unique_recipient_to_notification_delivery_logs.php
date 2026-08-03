<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A2 — khoá idempotency cho nhật ký gửi per-recipient: MỘT dòng cho mỗi
 * (thông báo, người nhận, kênh). Để `NotificationPushDispatcher` ghi Ý ĐỊNH
 * gửi TRƯỚC khi bắn FCM và replay/gửi-lại KHÔNG nhân đôi dòng (AR-05: duplicate
 * delivery safe).
 *
 * Additive. Bảng hiện chưa có writer nào (chỉ khai quan hệ) nên thực tế rỗng —
 * thêm unique an toàn. Có guard `hasTable`/`hasColumn` để no-op nếu thiếu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_delivery_logs')) {
            return;
        }

        Schema::table('notification_delivery_logs', function (Blueprint $table) {
            $table->unique(['notification_id', 'user_id', 'channel'], 'ndl_recipient_channel_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_delivery_logs')) {
            return;
        }

        Schema::table('notification_delivery_logs', function (Blueprint $table) {
            $table->dropUnique('ndl_recipient_channel_unique');
        });
    }
};
