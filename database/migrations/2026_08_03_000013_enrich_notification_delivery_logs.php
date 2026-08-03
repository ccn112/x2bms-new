<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * N3 — làm giàu `notification_delivery_logs` thành SỔ AUDIT đa kênh đầy đủ vòng đời
 * (ADR-001 mục 4). Thêm:
 *  - source_type/source_id : polymorphic nguồn (notifications | activity_notifications | topic)
 *    để ghi được cả push targeted lẫn activity lẫn dòng topic-level. `notification_id`
 *    thành nullable (dòng topic/activity không gắn 1 notification cụ thể).
 *  - delivered_at/read_at   : "đã nhận"/"đã đọc" từ callback provider (khác `sent`).
 *  - provider_message_id    : đối soát FCM/SES/ZNS/nhà mạng.
 *  - cost                   : chi phí (SMS/Zalo tốn tiền) để đối soát.
 *  - topic                  : dòng broadcast topic-level (recipient_user_id null).
 *
 * Additive, nullable, reversible. Không đụng dữ liệu A2 hiện có.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_delivery_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_delivery_logs', 'source_type')) {
                $table->string('source_type')->nullable()->after('id');
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                $table->index(['source_type', 'source_id']);
            }
            if (! Schema::hasColumn('notification_delivery_logs', 'queued_at')) {
                $table->timestamp('queued_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('notification_delivery_logs', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_at');
            }
            if (! Schema::hasColumn('notification_delivery_logs', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('delivered_at');
            }
            if (! Schema::hasColumn('notification_delivery_logs', 'provider_message_id')) {
                $table->string('provider_message_id')->nullable()->after('read_at');
            }
            if (! Schema::hasColumn('notification_delivery_logs', 'cost')) {
                $table->decimal('cost', 12, 2)->nullable()->after('provider_message_id');
            }
            if (! Schema::hasColumn('notification_delivery_logs', 'topic')) {
                $table->string('topic')->nullable()->after('channel');
            }
            $table->index(['user_id', 'created_at'], 'ndl_user_created_idx');
        });

        // notification_id → nullable (dòng topic/activity không gắn 1 notification).
        // Dùng SQL thô để không cần doctrine/dbal; chỉ khi là MySQL.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE notification_delivery_logs MODIFY notification_id BIGINT UNSIGNED NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::table('notification_delivery_logs', function (Blueprint $table) {
            $table->dropIndex('ndl_user_created_idx');
            foreach (['source_type', 'source_id', 'queued_at', 'delivered_at', 'read_at', 'provider_message_id', 'cost', 'topic'] as $col) {
                if (Schema::hasColumn('notification_delivery_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
