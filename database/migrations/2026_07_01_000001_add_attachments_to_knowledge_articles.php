<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-UX-09-04 — bài viết KB cho phép đính kèm nhiều tệp (PDF/DOC/DOCX) để X2AI
 * đọc/tham chiếu. Lưu danh sách tệp dạng JSON `[{path, name, size, mime}, ...]`.
 * ADD-ONLY.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
