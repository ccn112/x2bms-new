<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ① Hard-lock tenant tầng DB cho NHÓM TIỀN (G10, ADR-001) — composite FK chống lai-tenant.
 *
 * Con(tenant_id, X_id) → cha(tenant_id, id): DB TỪ CHỐI mọi bản ghi tiền trỏ tài sản
 * (tòa/căn/kỳ/thanh toán/cư dân) KHÁC tenant, ở MỌI code path. Bảo vệ bất biến tiền
 * khỏi ghi lai-tenant (payment/statement/debt/wallet của tenant A không thể gắn vào
 * căn/tòa của tenant B).
 *
 * MySQL-only (guard driver); test suite chạy sqlite → bỏ qua. Data đã kiểm 0 lệch trước
 * khi thêm. FK nullable → MATCH SIMPLE (chỉ kiểm khi cả hai non-null).
 *
 * GIỚI HẠN: `statement_lines` và `payment_allocations` KHÔNG có `tenant_id` → không đặt
 * được composite FK. Rủi ro cao nhất là allocation nối payment↔statement_line lai-tenant
 * — cần TRIGGER (②) hoặc thêm cột tenant_id. Ghi TECH_DEBT.
 */
return new class extends Migration
{
    /** Cha cần UNIQUE(tenant_id, id) làm đích composite FK (buildings đã có từ 000002). */
    private array $parents = ['apartments', 'billing_periods', 'payments', 'residents'];

    /** [child, fk_col, parent] — đều đã verify 0 lệch tenant. */
    private array $rels = [
        ['statements', 'building_id', 'buildings'],
        ['statements', 'apartment_id', 'apartments'],
        ['statements', 'billing_period_id', 'billing_periods'],
        ['payments', 'building_id', 'buildings'],
        ['payments', 'apartment_id', 'apartments'],
        ['payments', 'resident_id', 'residents'],
        ['receipts', 'payment_id', 'payments'],
        ['debts', 'building_id', 'buildings'],
        ['debts', 'apartment_id', 'apartments'],
        ['apartment_wallets', 'building_id', 'buildings'],
        ['apartment_wallets', 'apartment_id', 'apartments'],
        ['liability_periods', 'apartment_id', 'apartments'],
        ['liability_periods', 'resident_id', 'residents'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->parents as $p) {
            if (! $this->hasIndex($p, "{$p}_tenant_id_id_unique")) {
                Schema::table($p, fn (Blueprint $t) => $t->unique(['tenant_id', 'id'], "{$p}_tenant_id_id_unique"));
            }
        }

        foreach ($this->rels as [$c, $fk, $p]) {
            $comp = "{$c}_{$fk}_tenant_fk";
            if ($this->hasFk($c, $comp)) {
                continue;   // idempotent
            }
            if ($this->hasFk($c, "{$c}_{$fk}_foreign")) {
                Schema::table($c, fn (Blueprint $t) => $t->dropForeign("{$c}_{$fk}_foreign"));
            }
            Schema::table($c, function (Blueprint $t) use ($c, $fk, $p, $comp) {
                $t->index(['tenant_id', $fk], "{$c}_{$fk}_tenant_idx");
                $t->foreign(['tenant_id', $fk], $comp)
                    ->references(['tenant_id', 'id'])->on($p)
                    ->restrictOnDelete()->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (array_reverse($this->rels) as [$c, $fk, $p]) {
            $comp = "{$c}_{$fk}_tenant_fk";
            if (! $this->hasFk($c, $comp)) {
                continue;
            }
            Schema::table($c, function (Blueprint $t) use ($c, $fk, $p, $comp) {
                $t->dropForeign($comp);
                $t->dropIndex("{$c}_{$fk}_tenant_idx");
                $t->foreign($fk)->references('id')->on($p)->nullOnDelete();
            });
        }
        foreach ($this->parents as $p) {
            if ($this->hasIndex($p, "{$p}_tenant_id_id_unique")) {
                Schema::table($p, fn (Blueprint $t) => $t->dropUnique("{$p}_tenant_id_id_unique"));
            }
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::selectOne(
            'select 1 from information_schema.statistics where table_schema=database() and table_name=? and index_name=? limit 1',
            [$table, $index],
        ) !== null;
    }

    private function hasFk(string $table, string $constraint): bool
    {
        return DB::selectOne(
            "select 1 from information_schema.table_constraints where table_schema=database() and table_name=? and constraint_name=? and constraint_type='FOREIGN KEY' limit 1",
            [$table, $constraint],
        ) !== null;
    }
};
