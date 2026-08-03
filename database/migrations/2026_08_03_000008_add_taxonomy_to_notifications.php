<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trục taxonomy cho hộp thư hợp nhất (handoff Home/Notification 03/08). Thêm
 * additive, tất cả nullable để không phá dữ liệu/seed/test cũ:
 *  - category  : 9 nhóm (emergency/billing/community/maintenance/security/
 *                feedback/amenity/announcement(=BQL)/system) — khớp enum
 *                NotificationChannel. Backfill từ `type`.
 *  - subtype   : sự kiện cụ thể (payment_due, post_comment, guest_arrived…).
 *  - action_key + entity_type/entity_id : điều hướng qua registry allowlist
 *                (app không deep-link tùy ý từ FCM).
 *  - requires_ack : thông báo khẩn cần xác nhận đã đọc/tiếp nhận.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
            $table->string('subtype')->nullable()->after('category');
            $table->string('action_key')->nullable()->after('subtype');
            $table->string('entity_type')->nullable()->after('action_key');
            $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            $table->boolean('requires_ack')->default(false)->after('entity_id');

            $table->index('category');
            $table->index(['entity_type', 'entity_id']);
        });

        // Backfill: nhóm = type cũ để breakdown không lẫn null.
        DB::table('notifications')->whereNull('category')->update([
            'category' => DB::raw('type'),
        ]);
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['entity_type', 'entity_id']);
            $table->dropColumn([
                'category', 'subtype', 'action_key', 'entity_type', 'entity_id', 'requires_ack',
            ]);
        });
    }
};
