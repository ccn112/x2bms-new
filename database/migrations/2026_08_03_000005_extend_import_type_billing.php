<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P2 — Bổ sung giá trị enum `import_batches.import_type`.
 *
 * Audit phát hiện: `import_type` mới chỉ tới `('projects_employees','residents')`,
 * THIẾU `'billing_charges'` (mẫu mới) — trên MySQL `create([... 'billing_charges'])`
 * bị ENUM từ chối (SQLite lưu text nên test không lộ). Thêm cả `'fee_notification'`
 * (mẫu cũ HPO). Chỉ ALTER trên MySQL; SQLite bỏ qua (lưu string tự do).
 */
return new class extends Migration
{
    private const VALUES = "'projects_employees','residents','billing_charges','fee_notification'";

    public function up(): void
    {
        if (! Schema::hasTable('import_batches') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `import_batches` MODIFY `import_type` ENUM('.self::VALUES.') NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('import_batches') || DB::getDriverName() !== 'mysql') {
            return;
        }

        // Không thu hẹp enum khi đã có dữ liệu dùng giá trị mới (tránh cắt dữ liệu).
        DB::statement('ALTER TABLE `import_batches` MODIFY `import_type` ENUM('.self::VALUES.') NOT NULL');
    }
};
