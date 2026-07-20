<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mở rộng staging import (HQ project/employee) để DÙNG CHUNG cho import cư dân BQL.
 *
 * ADD-ONLY, an toàn trên bảng đã seed:
 *  - `import_batches.import_type`  += 'residents'  (enum, mở rộng danh sách)
 *  - `import_batch_rows.row_type`  += 'resident'   (enum, mở rộng danh sách)
 *  - `import_batches.building_id`  cột nullable mới (import cư dân scope theo tòa/dự án;
 *    HQ để null). Không phá dữ liệu cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE import_batches MODIFY COLUMN import_type ENUM('projects_employees','residents') NOT NULL DEFAULT 'projects_employees'");
        DB::statement("ALTER TABLE import_batch_rows MODIFY COLUMN row_type ENUM('project','employee','assignment','resident') NOT NULL DEFAULT 'project'");

        if (! Schema::hasColumn('import_batches', 'building_id')) {
            Schema::table('import_batches', function (Blueprint $table) {
                $table->foreignId('building_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('import_batches', 'building_id')) {
            Schema::table('import_batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('building_id');
            });
        }

        // Trả enum về danh sách gốc (chỉ an toàn khi chưa có bản ghi 'residents'/'resident').
        DB::statement("ALTER TABLE import_batch_rows MODIFY COLUMN row_type ENUM('project','employee','assignment') NOT NULL DEFAULT 'project'");
        DB::statement("ALTER TABLE import_batches MODIFY COLUMN import_type ENUM('projects_employees') NOT NULL DEFAULT 'projects_employees'");
    }
};
