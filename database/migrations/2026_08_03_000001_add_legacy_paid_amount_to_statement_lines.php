<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1a — Hợp nhất ledger (ADR-003).
 *
 * `statement_lines.paid_amount` chuyển thành PROJECTION:
 *   paid_amount = legacy_paid_amount + Σ ledger
 * với ledger = payment_allocations(line) ∪ apartment_wallet_transactions(out, ref=line).
 *
 * `legacy_paid_amount` giữ phần `paid_amount` ĐÃ CÓ TRƯỚC khi có ledger backing
 * (dữ liệu seed: ~1.088 bảng kê paid/partial nhưng chỉ 13 allocations). Cột này
 * additive + nullable + reversible — KHÔNG đổi `paid_amount` hiện có. Việc chốt
 * giá trị (`max(paid_amount − Σledger, 0)`) do command `billing:backfill-legacy-line-base`
 * hoặc lazy `StatementLine::ensureLegacyBase()` thực hiện, không nhét vào migration
 * (tránh chạy logic tiền trong DDL).
 *
 * NULL = chưa chốt legacy base cho dòng này → tầng model tự chốt lazy trước lần
 * ghi ledger đầu tiên, nên migrate xong deploy code ngay vẫn an toàn.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('statement_lines', 'legacy_paid_amount')) {
            Schema::table('statement_lines', function (Blueprint $table) {
                $table->decimal('legacy_paid_amount', 16, 2)->nullable()->after('paid_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('statement_lines', 'legacy_paid_amount')) {
            Schema::table('statement_lines', function (Blueprint $table) {
                $table->dropColumn('legacy_paid_amount');
            });
        }
    }
};
