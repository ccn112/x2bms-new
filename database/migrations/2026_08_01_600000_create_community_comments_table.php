<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GĐ7 — bảng bình luận cộng đồng CHUYÊN DỤNG (không polymorphic). Quy mô cộng
 * đồng khác hẳn bình luận giao dịch (vài người/phiếu): hàng triệu người → tách
 * bảng riêng để phân trang keyset + index gọn, không chen chung bảng `comments`
 * polymorphic của phiếu/thông báo.
 *
 * 2 cấp: `parent_id` null = bình luận gốc; khác null = trả lời (reply-của-reply
 * gộp về cha ở tầng service). Index feed: (post, parent, id) phục vụ cả "bình
 * luận gốc của post" lẫn "trả lời của một bình luận".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('community_comments')) {
            return;
        }
        Schema::create('community_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreignId('parent_id')->nullable()
                ->constrained('community_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->string('author_subtitle')->nullable();
            $table->string('author_kind')->default('resident'); // resident|staff|system
            $table->boolean('is_staff')->default(false);
            $table->text('body');
            $table->unsignedInteger('reaction_count')->default(0);
            $table->string('status')->default('visible'); // visible|hidden|deleted (kiểm duyệt)
            // id bình luận cũ (bảng `comments` polymorphic) đã migrate sang — để
            // lệnh migrate idempotent, không copy trùng.
            $table->unsignedBigInteger('legacy_comment_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['community_post_id', 'parent_id', 'id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_comments');
    }
};
