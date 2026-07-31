<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dòng phí: thêm CHIỀU TÀI SẢN và KỲ DỊCH VỤ RIÊNG.
 *
 * Quyết định chủ dự án D3 + D6 (`docs/BILLING_OWNER_DECISIONS_20260731.md`, 2026-07-31).
 *
 * 1. `subject_type` / `subject_id` — cấp 3 của mô hình phí (family › fee_type › tài sản).
 *    Hôm nay dòng "Phí gửi ô tô" KHÔNG nói được là xe nào, nên "nợ của BKS 51K-838888" là
 *    câu không truy vấn được. Và quan trọng hơn: D6 chốt tiền thừa phải vào **ngăn của
 *    chính xe đó** — không có chiều này thì không biết vào ngăn nào. Đoán sai ngăn là sai
 *    tiền, không phải sai hiển thị.
 *
 * 2. `service_period_start` / `service_period_end` — kỳ dịch vụ THẬT của dòng phí, tách
 *    khỏi `statements.billing_period_id` (kỳ của bảng kê). Cần cho ví dụ chuẩn của chủ dự
 *    án ở D3: bảng kê tháng 4 chứa cả tiền điện tháng 4 (400k) LẪN nợ cũ tháng 3 (200k) —
 *    hai dòng cùng loại phí, khác kỳ dịch vụ. Không có hai cột này thì không phân biệt
 *    được, và "trả trước 3 tháng cho một xe" cũng không biểu diễn được.
 *
 * 3. `due_date` cấp dòng — nợ cũ dồn kỳ có hạn khác phí kỳ hiện tại.
 *
 * Additive + guarded + reversible. Không phá cột nào. `nullableMorphs` để dòng phí không
 * gắn tài sản (phí quản lý, phí vệ sinh) vẫn hợp lệ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statement_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('statement_lines', 'subject_type')) {
                // morph → vehicles | meters (mở rộng được, vd amenity/asset về sau)
                $table->nullableMorphs('subject');
            }

            if (! Schema::hasColumn('statement_lines', 'service_period_start')) {
                $table->date('service_period_start')->nullable()->after('fee_category');
            }

            if (! Schema::hasColumn('statement_lines', 'service_period_end')) {
                $table->date('service_period_end')->nullable()->after('service_period_start');
            }

            if (! Schema::hasColumn('statement_lines', 'due_date')) {
                $table->date('due_date')->nullable()->after('service_period_end');
            }
        });

        // Index phục vụ đúng hai truy vấn D6 sinh ra:
        //  - "còn nợ gì của tài sản này, xuyên nhiều kỳ" → màn công nợ theo dịch vụ
        //  - "dòng phí thuộc kỳ dịch vụ nào" → phân bổ cũ-nhất-trước
        // `nullableMorphs` đã tự tạo index (subject_type, subject_id) nên không thêm lại.
        Schema::table('statement_lines', function (Blueprint $table) {
            if (! $this->hasIndex('statement_lines', 'stmt_lines_subject_period_idx')) {
                $table->index(
                    ['subject_type', 'subject_id', 'service_period_start'],
                    'stmt_lines_subject_period_idx'
                );
            }

            if (! $this->hasIndex('statement_lines', 'stmt_lines_family_period_idx')) {
                $table->index(
                    ['fee_category', 'service_period_start'],
                    'stmt_lines_family_period_idx'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('statement_lines', function (Blueprint $table) {
            foreach (['stmt_lines_subject_period_idx', 'stmt_lines_family_period_idx'] as $index) {
                if ($this->hasIndex('statement_lines', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table('statement_lines', function (Blueprint $table) {
            if (Schema::hasColumn('statement_lines', 'subject_type')) {
                $table->dropMorphs('subject');
            }

            foreach (['service_period_start', 'service_period_end', 'due_date'] as $column) {
                if (Schema::hasColumn('statement_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /** MySQL/SQLite đều trả về qua doctrine-less introspection của Laravel 11+. */
    private function hasIndex(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $existing) {
            if (($existing['name'] ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};
