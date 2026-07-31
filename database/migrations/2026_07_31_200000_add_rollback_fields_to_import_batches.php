<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Hoàn tác lô" cho import khoản phí (spec §5.7, `BILLING_IMPORT_SPEC_20260731.md`):
 * chỉ cho phép khi MỌI bảng kê bị ảnh hưởng còn `pending`.
 *
 * Cố ý KHÔNG thêm giá trị mới vào `import_batches.status` (ENUM ở MySQL, CHECK ở
 * SQLite dựng từ lúc tạo bảng — sửa CHECK trên SQLite phải dựng lại bảng, không sửa
 * tại chỗ được như MySQL `MODIFY COLUMN`). Một mốc thời gian riêng vừa đủ, additive,
 * chạy giống nhau trên cả hai driver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('import_batches', 'rolled_back_at')) {
                $table->timestamp('rolled_back_at')->nullable()->after('committed_at');
            }
            if (! Schema::hasColumn('import_batches', 'rolled_back_by')) {
                $table->foreignId('rolled_back_by')->nullable()->after('rolled_back_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            if (Schema::hasColumn('import_batches', 'rolled_back_by')) {
                $table->dropConstrainedForeignId('rolled_back_by');
            }
            if (Schema::hasColumn('import_batches', 'rolled_back_at')) {
                $table->dropColumn('rolled_back_at');
            }
        });
    }
};
