<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backlog hạng mục phát triển của mỗi phiên bản sản phẩm (doc_versions).
 * category: feature/improvement/fix/change; status: done/in_progress/planned.
 * ref_page_id: trỏ trang tài liệu liên quan (tùy chọn).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_version_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doc_version_id')->constrained('doc_versions')->cascadeOnDelete();
            $table->enum('category', ['feature', 'improvement', 'fix', 'change'])->default('feature');
            $table->string('title');
            $table->text('detail')->nullable();
            $table->enum('status', ['done', 'in_progress', 'planned'])->default('planned');
            $table->foreignId('ref_page_id')->nullable()->constrained('doc_pages')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['doc_version_id', 'category', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_version_items');
    }
};
