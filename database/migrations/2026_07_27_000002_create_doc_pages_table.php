<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trang tài liệu — cây phân cấp (parent_id tự tham chiếu). Nội dung markdown.
 * Mỗi lần đổi body/title sẽ sinh 1 bản ghi doc_page_revisions (quản lý version).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('doc_spaces')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('doc_pages')->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->unsignedInteger('sort')->default(0);
            $table->longText('body')->nullable(); // markdown
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['space_id', 'parent_id', 'slug']);
            $table->index(['space_id', 'parent_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_pages');
    }
};
