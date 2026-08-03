<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A4 — TÁCH NGUỒN feed: `notifications.source` phân biệt
 *   - `bql`         : thông báo CHÍNH THỐNG do quản lý (BQL/HQ/SuperAdmin) soạn qua
 *                     NotificationCenter — màn "Thông báo BQL" (NTF-03) chỉ lấy nhóm này.
 *   - `interaction` : (tương lai) item ĐẨY sinh từ tương tác (bình luận bài, khách
 *                     đến, nhắc phí…) — chỉ vào Hộp thư hợp nhất (chuông), KHÔNG vào
 *                     màn BQL.
 *
 * Mọi thông báo hiện có đều do quản lý soạn nên default + backfill = 'bql'. Additive,
 * reversible. Seam để khi thêm item tương tác thì hai feed tự tách, app không phải đổi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'source')) {
                $table->string('source')->default('bql')->after('owner_level');
                $table->index(['source', 'status']);
            }
        });

        DB::table('notifications')->whereNull('source')->update(['source' => 'bql']);
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'source')) {
                $table->dropIndex(['source', 'status']);
                $table->dropColumn('source');
            }
        });
    }
};
