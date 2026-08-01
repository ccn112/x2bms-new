<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D10 — cư dân chọn TỪNG DÒNG PHÍ khi khai báo thanh toán. Lưu danh sách dòng
 * đích `[{statement_line_id, amount}]` để BQL duyệt xong phân bổ ĐÚNG dòng cư dân
 * chọn (không chạy khoá ưu tiên tenant-wide D4 cho phạm vi đã chọn). Null =
 * không chỉ định dòng → giữ hành vi D4 mặc định.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'claimed_line_items')) {
                $table->json('claimed_line_items')->nullable()->after('claimed_statement_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'claimed_line_items')) {
                $table->dropColumn('claimed_line_items');
            }
        });
    }
};
