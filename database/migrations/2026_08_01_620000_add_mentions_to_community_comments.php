<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GĐ7 — @mention: lưu danh sách người được nhắc trong một bình luận cộng đồng
 * ([{user_id, name}]) để app render/link. (Thông báo per-user chờ hạ tầng
 * notification riêng — hiện notification là broadcast BQL.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('community_comments', 'mentions')) {
            Schema::table('community_comments', function (Blueprint $table) {
                $table->json('mentions')->nullable()->after('body');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('community_comments', 'mentions')) {
            Schema::table('community_comments', fn (Blueprint $t) => $t->dropColumn('mentions'));
        }
    }
};
