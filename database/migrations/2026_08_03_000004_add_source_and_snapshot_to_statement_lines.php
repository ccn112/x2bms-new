<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P2 — Truy vết nguồn + snapshot tính toán cho dòng phí (dùng chung 2 luồng import).
 *
 * - `source`: nguồn dòng phí ('legacy_import' = mẫu cũ HPO tự tính; 'accounting_import'
 *   = mẫu mới canonical amount-chốt; null = seed/cũ). Cho migration đối soát legacy→X2.
 * - `calculation_snapshot`: đầu vào GỐC + cách tính (đơn giá/định mức/chỉ số/giảm giá)
 *   để (a) đối soát độ trung thực khi di trú, (b) giải thích cho cư dân "vì sao hoá đơn
 *   thế này". KHÔNG dùng để tính lại (số đã chốt là chuẩn).
 * - `note`: ghi chú kế toán trên dòng.
 *
 * Additive, nullable, reversible. Khớp canonical `charges.calculation_snapshot`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statement_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('statement_lines', 'source')) {
                $table->string('source')->nullable()->after('status');
            }
            if (! Schema::hasColumn('statement_lines', 'calculation_snapshot')) {
                $table->json('calculation_snapshot')->nullable()->after('source');
            }
            if (! Schema::hasColumn('statement_lines', 'note')) {
                $table->string('note', 500)->nullable()->after('calculation_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('statement_lines', function (Blueprint $table) {
            foreach (['source', 'calculation_snapshot', 'note'] as $col) {
                if (Schema::hasColumn('statement_lines', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
