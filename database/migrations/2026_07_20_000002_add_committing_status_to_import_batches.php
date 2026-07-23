<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Thêm trạng thái `committing` (đang ghi nền qua queue) cho import_batches — phục vụ
 * import bất đồng bộ + màn Nhật ký. ADD-ONLY, an toàn trên bảng đã có dữ liệu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE import_batches MODIFY COLUMN status ENUM('uploaded','mapped','validated','committing','committed','failed','cancelled') NOT NULL DEFAULT 'uploaded'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE import_batches MODIFY COLUMN status ENUM('uploaded','mapped','validated','committed','failed','cancelled') NOT NULL DEFAULT 'uploaded'");
        }
    }
};
