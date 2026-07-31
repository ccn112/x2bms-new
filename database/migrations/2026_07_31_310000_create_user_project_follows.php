<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Giai đoạn 4 (follow dự án) của `COMMUNITY_IMPLEMENTATION_PLAN.md` —
 * `COMMUNITY_DB_MAPPING.md` §4.
 *
 * Trỏ vào `projects` (bảng VẬN HÀNH, 27 dòng) — KHÔNG phải `public_projects`
 * (danh mục, 6.005 dòng) mà `user_public_projects` đang trỏ vào. Đây là bảng
 * follow THẬT cho kênh "Quan tâm dự án" trong app; `user_public_projects` giữ
 * nguyên vai trò cũ (quan tâm ở danh mục công khai, cho người chưa là cư dân).
 *
 * Chốt 2026-07-31: FOLLOW KHÔNG CẤP QUYỀN, không cho vào nhóm — chỉ là tín
 * hiệu ưu tiên hiển thị trong feed. Không join/rời/grant nào đi kèm bảng này.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_project_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamp('followed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
            $table->index(['project_id', 'followed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_project_follows');
    }
};
