<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bậc thang nhóm cộng đồng (chủ dự án chốt 2026-07-29).
 *
 * Bốn nấc, trùng với thang trải nghiệm app đã có (public → member → verified
 * resident):
 *
 *   platform         — Cộng đồng X2, mọi người thấy; chỉ X2/BQL đăng
 *   project_interest — khách QUAN TÂM dự án; chỉ CĐT/BQL đăng
 *   project_resident — cư dân ĐÃ XÁC THỰC của dự án; cư dân đăng tự do
 *   private          — nhóm riêng, phải được duyệt
 *
 * ## Vì sao có `projects.public_project_id`
 *
 * "Dự án" đang là HAI bảng không nối với nhau: `projects` (27 dòng, vận hành,
 * có tenant/toà/căn) và `public_projects` (6.005 dòng, danh mục batdongsan).
 * Lúc đăng ký, "dự án quan tâm" lưu vào `user_public_projects.public_project_id`
 * → neo vào bảng danh mục. Còn nhóm cư dân neo vào bảng vận hành.
 *
 * Không có khoá nối thì "khách quan tâm Sunshine Garden" và "cư dân Sunshine
 * Garden" là hai chữ Sunshine Garden khác nhau — khách mua nhà xong, thành cư
 * dân, mà hệ thống không biết đó là cùng một dự án. Bậc thang đứt ở nấc giữa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Nullable: phần lớn dự án vận hành chưa có bản ghi tương ứng trong
            // danh mục công khai, và ngược lại. Chỉ nối khi thật sự là một.
            $table->foreignId('public_project_id')->nullable()->after('code')
                ->constrained('public_projects')->nullOnDelete();
        });

        Schema::table('community_groups', function (Blueprint $table) {
            $table->string('kind')->default('project_resident')->after('name');

            // Ai được ĐĂNG BÀI. Tách khỏi `kind` vì hai thứ khác nhau: nhóm
            // riêng của cư dân thì thành viên đăng, nhóm riêng của BQL thì
            // không — cùng `kind=private`.
            $table->string('post_policy')->default('members')->after('kind'); // members|staff

            // Nhóm mặc định của dự án/hệ thống — cư dân KHÔNG rời được, và
            // không hiện nút rời nhóm.
            $table->boolean('is_default')->default(false)->after('post_policy');

            $table->index(['tenant_id', 'kind']);
        });

        Schema::table('community_group_members', function (Blueprint $table) {
            // Thành viên nhóm "quan tâm dự án" CHƯA phải cư dân — họ mới chỉ là
            // user đã chọn quan tâm lúc đăng ký. Khoá theo resident_id thì
            // không biểu diễn được họ.
            $table->foreignId('user_id')->nullable()->after('resident_id')
                ->constrained()->cascadeOnDelete();

            // Cư dân bán nhà / hết hạn thuê: mất quyền nhóm nhưng BÀI CŨ GIỮ
            // NGUYÊN (xoá lịch sử thảo luận làm hỏng ngữ cảnh của người khác
            // đang đọc) — đánh dấu ở đây để app gắn nhãn "cư dân cũ".
            $table->timestamp('left_at')->nullable()->after('joined_at');
        });

        Schema::table('community_group_members', function (Blueprint $table) {
            $table->index(['community_group_id', 'left_at']);
        });
    }

    public function down(): void
    {
        Schema::table('community_group_members', function (Blueprint $table) {
            $table->dropIndex(['community_group_id', 'left_at']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('left_at');
        });

        Schema::table('community_groups', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'kind']);
            $table->dropColumn(['kind', 'post_policy', 'is_default']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('public_project_id');
        });
    }
};
