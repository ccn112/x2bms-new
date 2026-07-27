<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lớp GHI + kiểm duyệt cho bài cộng đồng (docs/COMMUNITY_WRITE_MODERATION_DESIGN.md).
 * Chốt nghiệp vụ: KHÔNG duyệt trước — cư dân đăng là `published` ngay, hậu kiểm.
 *
 * BA hành động kiểm duyệt TÁCH BẠCH, đừng gộp:
 *  - `locked_at`      → bài CÒN hiện, cấm bình luận/thả cảm xúc.
 *  - `status=hidden`  → gỡ khỏi feed; tác giả vẫn thấy kèm `moderation_reason`.
 *  - `deleted_at`     → xóa mềm (đã có sẵn từ 2026_07_01_000025), tác giả tự xóa được.
 *
 * ADD-ONLY + guard hasTable/hasColumn để chạy lại nhiều lần không vỡ (DB thật đã
 * có dữ liệu bài viết từ seeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            // Tác giả cấp NGƯỜI DÙNG: `author_resident_id` không đủ để biết
            // "bài của tôi" (1 user có nhiều resident membership) và không cover
            // bài do nhân sự BQL đăng.
            if (! Schema::hasColumn('community_posts', 'author_user_id')) {
                $table->foreignId('author_user_id')->nullable()->after('author_resident_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('community_posts', 'author_kind')) {
                $table->string('author_kind')->default('resident')->after('author_user_id');
            }
            if (! Schema::hasColumn('community_posts', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('community_posts', 'locked_by_user_id')) {
                $table->foreignId('locked_by_user_id')->nullable()->after('locked_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('community_posts', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable()->after('locked_by_user_id');
            }
            if (! Schema::hasColumn('community_posts', 'moderated_by_user_id')) {
                $table->foreignId('moderated_by_user_id')->nullable()->after('moderated_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('community_posts', 'moderation_reason')) {
                $table->string('moderation_reason')->nullable()->after('moderated_by_user_id');
            }
            if (! Schema::hasColumn('community_posts', 'report_count')) {
                $table->unsignedInteger('report_count')->default(0)->after('moderation_reason');
            }
        });

        // Một người MỘT cảm xúc trên một bài: đổi emoji = UPDATE, không thêm dòng.
        if (! Schema::hasTable('community_post_reactions')) {
            Schema::create('community_post_reactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                // Lưu MÃ (like|love|haha|wow|sad|angry), không lưu ký tự emoji —
                // đổi bộ icon sau này không phải migrate dữ liệu.
                $table->string('emoji', 16);
                $table->timestamps();

                $table->unique(['community_post_id', 'user_id']);
                $table->index(['community_post_id', 'emoji']);
            });
        }

        // Hậu kiểm cần đầu vào: không có report thì BQL không biết bài nào phải xem.
        if (! Schema::hasTable('community_post_reports')) {
            Schema::create('community_post_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('reason', 32); // spam|offensive|false_info|other
                $table->text('note')->nullable();
                $table->string('status', 16)->default('open'); // open|resolved|dismissed
                $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                // Đặt tên tay: tên tự sinh dài 66 ký tự, vượt giới hạn 64 của MySQL.
                $table->unique(['community_post_id', 'reported_by_user_id'], 'cp_reports_post_user_unique');
                $table->index(['status', 'community_post_id'], 'cp_reports_status_post_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_reports');
        Schema::dropIfExists('community_post_reactions');

        Schema::table('community_posts', function (Blueprint $table) {
            foreach (['author_user_id', 'locked_by_user_id', 'moderated_by_user_id'] as $fk) {
                if (Schema::hasColumn('community_posts', $fk)) {
                    $table->dropConstrainedForeignKey($fk);
                }
            }
            foreach ([
                'author_kind', 'locked_at', 'moderated_at', 'moderation_reason', 'report_count',
            ] as $col) {
                if (Schema::hasColumn('community_posts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
