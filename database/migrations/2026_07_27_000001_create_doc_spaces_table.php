<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MODULE TÀI LIỆU (docs CMS kiểu GitBook) — không gian tài liệu.
 * Mỗi space gom một nhóm trang theo đối tượng đọc (audience). Reader lọc
 * space theo quyền `docs.view.{audience}` của người dùng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_spaces', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // slug định danh (dùng trong URL /docs/{key})
            $table->string('title');
            $table->string('description')->nullable();
            $table->enum('audience', ['dev', 'ops', 'bql', 'hq', 'sa', 'resident'])->default('dev');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['audience', 'is_published', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_spaces');
    }
};
