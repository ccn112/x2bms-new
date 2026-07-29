<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Giai đoạn 1–2 Community Domain: `content_type` cho bài + `verification_level`
 * cho nhóm.
 *
 * Hai thứ này đi cùng nhau vì UI cần cả hai để dựng được COM-01:
 * - Hàng tab lọc theo **loại nội dung** (Tất cả · Thông báo BQL · Sự kiện · Bình chọn).
 * - Thẻ bài hiện **tích xanh/vàng** theo mức xác minh của nhóm/tác giả.
 *
 * Additive hoàn toàn: cột cũ (`kind`, `status`, `is_pinned`) giữ nguyên, app đang
 * đọc chúng vẫn chạy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            // status | official_announcement_ref | news_ref | link_share |
            // event_ref | poll_ref | system_update
            $table->string('content_type')->default('status')->after('community_group_id');

            // Tham chiếu tới entity gốc — announcement/event/poll giữ MỘT nguồn sự
            // thật, feed chỉ trỏ tới chứ không sao chép nội dung sang.
            $table->string('source_type')->nullable()->after('content_type');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            $table->timestamp('published_at')->nullable()->after('status');

            $table->index(['content_type', 'published_at'], 'cp_type_published_idx');
            $table->index(['community_group_id', 'status', 'published_at'], 'cp_group_state_idx');
        });

        Schema::table('community_groups', function (Blueprint $table) {
            // none | bql_official | platform_verified
            $table->string('verification_level')->default('none')->after('kind');

            // Tách khỏi `post_policy`: "ai vào được" và "ai đăng được" là hai câu
            // hỏi khác nhau — nhóm riêng của BQL thì thành viên vào tự do nhưng
            // không đăng được.
            $table->string('join_policy')->default('open')->after('post_policy');

            $table->index(['tenant_id', 'verification_level'], 'cg_tenant_verif_idx');
        });

        // Backfill trong migration vì dữ liệu còn rất nhỏ (40 bài / 16 nhóm) và
        // đây là giá trị suy trực tiếp từ cột sẵn có, không có gì để sai.
        DB::table('community_posts')->update([
            'content_type' => 'status',
            'published_at' => DB::raw('created_at'),
        ]);

        DB::table('community_groups')->where('kind', 'project_resident')
            ->update(['verification_level' => 'bql_official', 'join_policy' => 'auto_join_resident']);
        DB::table('community_groups')->where('kind', 'platform')
            ->update(['join_policy' => 'auto_enroll']);
        DB::table('community_groups')->where('kind', 'project_interest')
            ->update(['join_policy' => 'follow_based']);
    }

    public function down(): void
    {
        Schema::table('community_groups', function (Blueprint $table) {
            $table->dropIndex('cg_tenant_verif_idx');
            $table->dropColumn(['verification_level', 'join_policy']);
        });

        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropIndex('cp_type_published_idx');
            $table->dropIndex('cp_group_state_idx');
            $table->dropColumn(['content_type', 'source_type', 'source_id', 'published_at']);
        });
    }
};
