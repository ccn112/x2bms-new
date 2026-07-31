<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase B2 (duyệt & phát hành có maker-checker, D1) —
 * `docs/delivery/04_INITIAL_PHASE_PLAN.md`.
 *
 * `created_by_user_id`: KHÔNG có cột nào ghi lại ai tạo bảng kê (kế toán import
 * qua B1 chỉ ghi vào `audit_logs`, không lưu vào chính `statements`). Không có
 * cột này thì không cách nào chặn tự duyệt bảng kê mình vừa tạo (G9).
 *
 * `approved_by_user_id`: tách khỏi `StatementApproval.approver_id` (bảng phụ,
 * cho phép nhiều cấp duyệt) — cột này là dấu vết NHANH ngay trên statement để
 * đọc mà không cần join, dùng cho danh sách/KPI.
 *
 * Additive, guarded, reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            if (! Schema::hasColumn('statements', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('apartment_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('statements', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()->after('created_by_user_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('statements', 'approval_note')) {
                $table->string('approval_note')->nullable()->after('approved_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            if (Schema::hasColumn('statements', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
            if (Schema::hasColumn('statements', 'approved_by_user_id')) {
                $table->dropConstrainedForeignId('approved_by_user_id');
            }
            if (Schema::hasColumn('statements', 'approval_note')) {
                $table->dropColumn('approval_note');
            }
        });
    }
};
