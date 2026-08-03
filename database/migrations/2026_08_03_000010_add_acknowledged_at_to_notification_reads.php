<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A3 — cư dân XÁC NHẬN ĐÃ TIẾP NHẬN thông báo khẩn (`notifications.requires_ack`).
 * Ack là per-user, cùng grain với `read_at` nên gắn thẳng vào `notification_reads`
 * thay vì bảng mới. Additive, nullable → không phá read cũ (ack ⊇ read).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_reads', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_reads', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('read_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notification_reads', function (Blueprint $table) {
            if (Schema::hasColumn('notification_reads', 'acknowledged_at')) {
                $table->dropColumn('acknowledged_at');
            }
        });
    }
};
