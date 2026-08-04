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
            // Reconcile drift TRƯỚC khi khoá: một số DB (đã seed nhiều tenant) còn
            // bản ghi tiền trỏ tài sản khác/thiếu tenant → FK sẽ 1452. Cột nullable
            // thì gỡ liên kết mồ côi (NULL); cột bắt buộc mà lệch thì dừng có thông
            // điệp rõ để chủ dữ liệu xử lý, thay vì lỗi FK khó hiểu.
            $this->reconcileOrphans($c, $fk, $p);
            if ($this->hasFk($c, "{$c}_{$fk}_foreign")) {
                Schema::table($c, fn (Blueprint $t) => $t->dropForeign("{$c}_{$fk}_foreign"));
            }
            // Index có thể đã thêm ở lần chạy trước bị lỗi FK → chỉ thêm nếu chưa có
            // (tránh "Duplicate key name" khi migrate lại).
            $needIdx = ! $this->hasIndex($c, "{$c}_{$fk}_tenant_idx");
            Schema::table($c, function (Blueprint $t) use ($c, $fk, $p, $comp, $needIdx) {
                if ($needIdx) {
                    $t->index(['tenant_id', $fk], "{$c}_{$fk}_tenant_idx");
                }
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

    /**
     * Gỡ/chặn bản ghi con trỏ cha KHÁC tenant hoặc cha không tồn tại (orphan), để
     * composite FK add được. Cột nullable → set NULL (gỡ liên kết sai). Cột bắt buộc
     * còn orphan → ném lỗi rõ ràng (không tự xoá dữ liệu tiền).
     */
    private function reconcileOrphans(string $child, string $fk, string $parent): void
    {
        $orphanWhere = "c.`{$fk}` is not null and p.id is null";
        $count = (int) (DB::selectOne(
            "select count(*) as n from `{$child}` c
             left join `{$parent}` p on p.tenant_id = c.tenant_id and p.id = c.`{$fk}`
             where {$orphanWhere}"
        )->n ?? 0);
        if ($count === 0) {
            return;
        }

        if ($this->isNullable($child, $fk)) {
            DB::statement(
                "update `{$child}` c
                 left join `{$parent}` p on p.tenant_id = c.tenant_id and p.id = c.`{$fk}`
                 set c.`{$fk}` = null
                 where {$orphanWhere}"
            );
            fwrite(STDERR, "  [tenant-fk] {$child}.{$fk}: gỡ {$count} liên kết mồ côi (khác/thiếu tenant) → NULL\n");

            return;
        }

        throw new \RuntimeException(
            "Không thể khoá {$child}.{$fk} (bắt buộc): còn {$count} bản ghi trỏ {$parent} khác/thiếu tenant. "
            .'Cần sửa dữ liệu (đúng tenant_id hoặc dời) trước khi chạy lại migrate.'
        );
    }

    private function isNullable(string $table, string $column): bool
    {
        // Alias tường minh: MySQL trả tên cột information_schema viết HOA
        // (IS_NULLABLE) khi không đặt alias → $row->is_nullable sẽ undefined.
        $row = DB::selectOne(
            'select is_nullable as nn from information_schema.columns where table_schema=database() and table_name=? and column_name=? limit 1',
            [$table, $column],
        );

        return $row !== null && strtoupper((string) $row->nn) === 'YES';
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
