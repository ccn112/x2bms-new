<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase B4 (D4-bis) — chuẩn bị cho `billing:backfill-fee-priority`.
 *
 * `fee_types.payment_priority` (thêm ở `2026_07_26_000001_create_apartment_wallets.php`)
 * mặc định `100` cho MỌI dòng — chưa từng có UI nào ghi giá trị khác (đã grep
 * `app/Filament/Resources/FeeTypes`: không cột `payment_priority` trong form/table).
 * Lệnh backfill (migration sau) sẽ ghi đè giá trị này theo `BillingFamily::defaultPriority()`
 * — AN TOÀN hôm nay vì chưa ai từng sửa tay.
 *
 * Nhưng an toàn HÔM NAY không có nghĩa an toàn MÃI MÃI: một khi có UI cho BQL sửa tay
 * `payment_priority` ở cấp tenant (khác override theo dự án ở `fee_type_priority_overrides`),
 * lệnh backfill chạy lại lần nữa (vd. sau khi thêm fee_type mới) sẽ XOÁ MẤT giá trị BQL
 * vừa sửa tay nếu không có cách phân biệt "giá trị mặc định" với "giá trị BQL cố ý đặt".
 *
 * `payment_priority_locked_at`: NULL = chưa ai từng sửa tay, lệnh backfill được ghi đè
 * tự do. Khác NULL = một người dùng thật đã đặt giá trị này, lệnh backfill PHẢI bỏ qua
 * dòng này. Cố tình dùng timestamp (không phải boolean) để có luôn bằng chứng "khi nào".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_types', 'payment_priority_locked_at')) {
                $table->timestamp('payment_priority_locked_at')->nullable()->after('payment_priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            if (Schema::hasColumn('fee_types', 'payment_priority_locked_at')) {
                $table->dropColumn('payment_priority_locked_at');
            }
        });
    }
};
