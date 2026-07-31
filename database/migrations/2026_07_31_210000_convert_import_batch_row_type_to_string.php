<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `import_batch_rows.row_type` là ENUM khai từ `2026_07_02_000001_create_hq01_project_org.php`
 * (`project|employee|assignment`), mở rộng thêm `resident` bằng `ALTER ... MODIFY`
 * CHỈ trên MySQL (`2026_07_20_000001_extend_import_batches_for_residents.php`).
 *
 * Trên SQLite (DB test) CHECK constraint GỐC vẫn còn nguyên — mọi `row_type` ngoài 3
 * giá trị đầu, kể cả `resident` đã dùng thật, đều vỡ CHECK khi chạy test. Không ai gặp
 * vì trước bản này KHÔNG có test nào chạm luồng import (`BillingChargeImportTest`,
 * `row_type = billing_charge`, là test đầu tiên chạm bảng này).
 *
 * Đổi hẳn sang `string` — bỏ enum cứng ở tầng DB. `ImportProfile::rowType()` tự khai
 * báo giá trị, không phải sửa migration mỗi lần thêm loại import mới.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('import_batch_rows', 'row_type_v2')) {
            return;
        }

        Schema::table('import_batch_rows', function (Blueprint $table) {
            $table->string('row_type_v2')->nullable()->after('row_type');
        });

        DB::table('import_batch_rows')->update(['row_type_v2' => DB::raw('row_type')]);

        Schema::table('import_batch_rows', function (Blueprint $table) {
            $table->dropColumn('row_type');
        });

        Schema::table('import_batch_rows', function (Blueprint $table) {
            $table->renameColumn('row_type_v2', 'row_type');
        });

        DB::table('import_batch_rows')->whereNull('row_type')->update(['row_type' => 'project']);
    }

    public function down(): void
    {
        // Không hạ cấp lại ENUM — bản ghi có thể đã mang row_type ngoài 3 giá trị gốc.
    }
};
