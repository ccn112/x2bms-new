<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Người nhận đã resolve + dedupe (audience snapshot) cho 1 chiến dịch — BQL-NOTI-08.
 * Mặc định 1 dòng / user / campaign; `audience_reasons` giữ mọi lý do vào tập (audit,
 * spec 03 dedupe). `channels_planned` = kênh dự kiến gửi cho user này. Delivery thật
 * vẫn ghi ở notification_delivery_logs (canonical ledger, ADR-002); bảng này là cha
 * cấp-người-nhận để tổng hợp trạng thái theo user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedBigInteger('building_id')->nullable();
            $t->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $t->string('role')->nullable();                 // owner|co_owner|tenant|household_member
            $t->json('audience_reasons')->nullable();       // vì sao user này vào tập (nhiều căn/nhóm)
            $t->json('channels_planned')->nullable();       // ['app','email',...]
            $t->string('status')->default('pending');       // pending|resolved|suppressed
            $t->timestamps();

            $t->unique(['notification_id', 'user_id'], 'notif_recipient_user_unique');
            $t->index(['notification_id', 'status'], 'notif_recipient_status_idx');
            $t->index(['tenant_id', 'building_id'], 'notif_recipient_tenant_building_idx');
        });

        // Composite FK chống lai-tenant chỉ trên MySQL (prod); sqlite (test) để cột trơn.
        if (DB::getDriverName() === 'mysql') {
            Schema::table('notification_recipients', function (Blueprint $t) {
                $t->foreign(['tenant_id', 'building_id'], 'notif_recipient_tenant_building_fk')
                    ->references(['tenant_id', 'id'])->on('buildings')
                    ->restrictOnDelete()->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('notification_recipients', function (Blueprint $t) {
                $t->dropForeign('notif_recipient_tenant_building_fk');
            });
        }
        Schema::dropIfExists('notification_recipients');
    }
};
