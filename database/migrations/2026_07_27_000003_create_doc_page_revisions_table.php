<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lịch sử version của trang tài liệu. Mỗi lần title/body thay đổi → thêm bản
 * mới (version tăng dần theo page). Cho phép xem lại & khôi phục revision cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('doc_pages')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['page_id', 'version']);
            $table->index(['page_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_page_revisions');
    }
};
