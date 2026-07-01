<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-UX-09-04 — Cơ sở tri thức 3 cấp (khớp RBAC 3 tầng):
 *   - Platform (superadmin): sở hữu tài liệu chung, chia sẻ xuống công ty / dự án.
 *   - Tenant (công ty vận hành): sở hữu tài liệu công ty + quản lý tài liệu dự án
 *     trong công ty; chia sẻ xuống các dự án.
 *   - Project (BQL): chỉ tài liệu dự án mình đẩy lên + tài liệu được chia sẻ xuống.
 *
 * owner_level = platform|tenant|project · share_mode = private|descendants|custom.
 * `knowledge_article_shares` = chia sẻ tường minh (custom) tới 1 tenant / project.
 * `content_text` = text đã trích từ body + tệp đính kèm (PDF/DOCX) để X2AI đọc.
 * ADD-ONLY (chỉ nới tenant_id thành nullable cho tài liệu platform).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->string('owner_level')->default('project')->after('tenant_id'); // platform|tenant|project
            $table->foreignId('project_id')->nullable()->after('owner_level')->constrained()->nullOnDelete();
            $table->string('share_mode')->default('private')->after('project_id'); // private|descendants|custom
            $table->longText('content_text')->nullable()->after('body');
        });

        // Tài liệu platform không thuộc tenant nào → cho phép tenant_id null.
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->change();
        });

        Schema::create('knowledge_article_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type');   // tenant|project
            $table->unsignedBigInteger('scope_id');
            $table->timestamps();

            $table->unique(['knowledge_article_id', 'scope_type', 'scope_id'], 'kb_share_unique');
            $table->index(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_shares');
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['owner_level', 'share_mode', 'content_text']);
        });
    }
};
