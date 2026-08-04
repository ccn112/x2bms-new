<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ① POC hard-lock tenant tầng DB (ADR-001) — CHỐNG LAI-TENANT bằng composite FK.
 *
 * `notifications(tenant_id, building_id) → buildings(tenant_id, id)`: DB TỪ CHỐI mọi
 * insert/update mà building_id trỏ tòa KHÁC tenant — ở MỌI code path (kể cả
 * withoutGlobalScopes / raw SQL / seeder ẩu). Đây là "write-integrity" mà app scope
 * không đảm bảo được.
 *
 * MySQL-only (guard theo driver): FK composite là ràng buộc của prod MySQL; test suite
 * chạy sqlite nên bỏ qua để không phải rebuild bảng mỗi test. `building_id`/`tenant_id`
 * nullable → MATCH SIMPLE: chỉ kiểm khi CẢ HAI cùng non-null (thông báo không nhắm tòa
 * vẫn insert bình thường).
 *
 * GIỚI HẠN đã biết: audience nhắm-đối-tượng là POLYMORPHIC
 * (`notification_audiences.scope_id`) nên KHÔNG đặt composite FK được — chỗ đó dựa
 * validate phía server (đã có) + cổng ③; hard-lock polymorphic cần TRIGGER (②).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Đích của composite FK phải là một KEY: thêm UNIQUE(tenant_id, id) cho buildings.
        if (! $this->hasIndex('buildings', 'buildings_tenant_id_id_unique')) {
            Schema::table('buildings', function (Blueprint $t) {
                $t->unique(['tenant_id', 'id'], 'buildings_tenant_id_id_unique');
            });
        }

        // Bỏ FK đơn building_id→buildings(id) (được BAO bởi composite), rồi gắn composite.
        Schema::table('notifications', function (Blueprint $t) {
            $t->dropForeign('notifications_building_id_foreign');
        });
        Schema::table('notifications', function (Blueprint $t) {
            $t->index(['tenant_id', 'building_id'], 'notifications_tenant_building_idx');
            $t->foreign(['tenant_id', 'building_id'], 'notifications_tenant_building_fk')
                ->references(['tenant_id', 'id'])->on('buildings')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('notifications', function (Blueprint $t) {
            $t->dropForeign('notifications_tenant_building_fk');
            $t->dropIndex('notifications_tenant_building_idx');
            $t->foreign('building_id')->references('id')->on('buildings')->nullOnDelete();
        });
        Schema::table('buildings', function (Blueprint $t) {
            $t->dropUnique('buildings_tenant_id_id_unique');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::selectOne(
            'select 1 from information_schema.statistics where table_schema=database() and table_name=? and index_name=? limit 1',
            [$table, $index],
        ) !== null;
    }
};
