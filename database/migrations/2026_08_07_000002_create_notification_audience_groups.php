<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nhóm người nhận đã lưu (saved audience segments) — BQL-NOTI-03. Tenant/building
 * scoped, rule DSL JSON (spec 07). Khác community_groups (feed cộng đồng). Composite
 * FK (tenant_id, building_id) → buildings trên MySQL chống lai-tenant (ADR-001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_audience_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedBigInteger('building_id')->nullable();
            $t->string('seed_key')->nullable();
            $t->string('name');
            $t->string('description')->nullable();
            $t->json('rule')->nullable();           // DSL JSON (whitelist field/operator)
            $t->unsignedInteger('estimated_count')->nullable();
            $t->timestamp('estimated_at')->nullable();
            $t->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();

            $t->unique(['tenant_id', 'seed_key'], 'notif_aud_group_tenant_seed_unique');
            $t->index(['tenant_id', 'building_id'], 'notif_aud_group_tenant_building_idx');
        });

        // Composite FK chống lai-tenant chỉ trên MySQL (prod). Sqlite (test) không ALTER
        // thêm FK được → để cột building_id trơn; cô lập test chứng minh ở tầng service.
        if (DB::getDriverName() === 'mysql') {
            Schema::table('notification_audience_groups', function (Blueprint $t) {
                $t->foreign(['tenant_id', 'building_id'], 'notif_aud_group_tenant_building_fk')
                    ->references(['tenant_id', 'id'])->on('buildings')
                    ->restrictOnDelete()->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('notification_audience_groups', function (Blueprint $t) {
                $t->dropForeign('notif_aud_group_tenant_building_fk');
            });
        }
        Schema::dropIfExists('notification_audience_groups');
    }
};
