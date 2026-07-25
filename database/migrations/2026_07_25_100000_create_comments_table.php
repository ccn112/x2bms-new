<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module bình luận DÙNG CHUNG (polymorphic) — gắn vào bất kỳ model nào qua
 * `commentable` (thông báo, bài cộng đồng, phản ánh/kiến nghị, ticket BQL…).
 * Đa cấp 1 lớp (parent_id, kiểu Facebook). Tác giả denormalized:
 * cư dân = tên + mã căn hộ; nhân sự BQL = "Ban quản lý" + tên dự án (is_staff).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable'); // commentable_type + commentable_id (+index)
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->string('author_subtitle')->nullable();
            $table->boolean('is_staff')->default(false);
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
