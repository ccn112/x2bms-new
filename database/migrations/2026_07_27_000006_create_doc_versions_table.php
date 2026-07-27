<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — PHIÊN BẢN SẢN PHẨM/TÀI LIỆU (v1.0/v2.0…), KHÁC với revision từng
 * trang (doc_page_revisions). Mỗi version là 1 đợt phát triển toàn site, có
 * backlog hạng mục (doc_version_items) và trang gắn theo version (doc_pages.version_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_versions', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();       // 'v1.0', 'v2.0' …
            $table->string('name')->nullable();       // tên đợt (vd "Ra mắt")
            $table->date('released_at')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'released'])->default('planned');
            $table->boolean('is_current')->default(false); // version mặc định hiển thị
            $table->unsignedInteger('sort')->default(0);
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index(['status', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_versions');
    }
};
