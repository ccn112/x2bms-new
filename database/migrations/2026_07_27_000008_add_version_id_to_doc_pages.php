<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gắn trang tài liệu vào một phiên bản sản phẩm.
 * Quy ước: version_id = null → trang CHUNG (hiện ở mọi version);
 * có version_id → chỉ hiện khi đang xem đúng version đó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doc_pages', function (Blueprint $table) {
            $table->foreignId('version_id')->nullable()->after('space_id')
                ->constrained('doc_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('doc_pages', function (Blueprint $table) {
            $table->dropForeign(['version_id']);
            $table->dropColumn('version_id');
        });
    }
};
