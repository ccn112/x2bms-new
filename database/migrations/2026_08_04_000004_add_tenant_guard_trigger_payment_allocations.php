<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ② Hard-lock tenant tầng DB bằng TRIGGER cho `payment_allocations` (ADR-001, G10).
 *
 * payment_allocations là bảng JUNCTION KHÔNG có `tenant_id` (nối payment ↔ statement /
 * statement_line) → không đặt composite FK theo tenant được (①). Rủi ro cao nhất trong
 * luồng tiền: một allocation nối `payment` của tenant A với `statement`/`statement_line`
 * của tenant B (rò/ghi chéo tenant). Trigger BEFORE INSERT/UPDATE ép:
 *   payment.tenant_id = statement.tenant_id = statement_line→statement.tenant_id
 * lệch thì SIGNAL (reject) ở MỌI code path — kể cả raw SQL / bỏ app scope.
 *
 * MySQL-only (guard driver). Data đã kiểm 0 allocation lai-tenant trước khi thêm.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        foreach (['ins' => 'INSERT', 'upd' => 'UPDATE'] as $suffix => $event) {
            DB::unprepared("DROP TRIGGER IF EXISTS payment_allocations_tenant_guard_{$suffix}");
            DB::unprepared(
                "CREATE TRIGGER payment_allocations_tenant_guard_{$suffix} BEFORE {$event} ON payment_allocations
                FOR EACH ROW
                BEGIN
                    DECLARE v_pt BIGINT DEFAULT NULL;
                    DECLARE v_st BIGINT DEFAULT NULL;
                    DECLARE v_slt BIGINT DEFAULT NULL;
                    SELECT tenant_id INTO v_pt FROM payments WHERE id = NEW.payment_id;
                    IF NEW.statement_id IS NOT NULL THEN
                        SELECT tenant_id INTO v_st FROM statements WHERE id = NEW.statement_id;
                        IF v_st IS NOT NULL AND v_pt IS NOT NULL AND v_st <> v_pt THEN
                            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payment_allocations: payment va statement khac tenant';
                        END IF;
                    END IF;
                    IF NEW.statement_line_id IS NOT NULL THEN
                        SELECT s.tenant_id INTO v_slt
                          FROM statement_lines sl JOIN statements s ON s.id = sl.statement_id
                          WHERE sl.id = NEW.statement_line_id;
                        IF v_slt IS NOT NULL AND v_pt IS NOT NULL AND v_slt <> v_pt THEN
                            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payment_allocations: payment va statement_line khac tenant';
                        END IF;
                    END IF;
                END"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::unprepared('DROP TRIGGER IF EXISTS payment_allocations_tenant_guard_ins');
        DB::unprepared('DROP TRIGGER IF EXISTS payment_allocations_tenant_guard_upd');
    }
};
