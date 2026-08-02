<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đếm lượt chia sẻ bài cộng đồng (GĐ7). Trước đây "chia sẻ" chỉ là copy link,
 * không lưu — nên feed không hiện được số. Thêm cột đếm để app hiện + bump.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('community_posts', 'share_count')) {
                $table->unsignedInteger('share_count')->default(0)->after('comment_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            if (Schema::hasColumn('community_posts', 'share_count')) {
                $table->dropColumn('share_count');
            }
        });
    }
};
