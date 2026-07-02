<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HQ-03 — Biểu mẫu, tài liệu dùng chung & tri thức AI.
 *
 * ADD-ONLY. Tái sử dụng: dynamic_forms/form_* (form builder), knowledge_categories/articles (KB),
 * document_templates (mẫu). Delta mới: thư viện tài liệu chung, SOP/checklist, gán xuống dự án,
 * quy tắc kế thừa, nguồn tri thức AI + kiểm thử dẫn nguồn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_libraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('document_libraries')->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->integer('doc_count')->default(0);
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_id')->nullable()->constrained('document_libraries')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->enum('type', ['sop', 'policy', 'guide', 'contract', 'appendix', 'form_attachment'])->default('sop');
            $table->string('version')->default('v1.0');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope')->nullable();
            $table->enum('ai_sync_status', ['synced', 'pending', 'error'])->default('synced');
            $table->string('file_path')->nullable();
            $table->integer('size_kb')->default(0);
            $table->text('summary')->nullable();
            $table->enum('status', ['active', 'draft', 'expired', 'pending_approval'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type', 'status']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->string('note')->nullable();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('sop_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('version')->default('v1.0');
            $table->json('steps')->nullable();
            $table->enum('status', ['active', 'draft', 'archived'])->default('active');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('category')->nullable();
            $table->integer('item_count')->default(0);
            $table->string('version')->default('v1.0');
            $table->enum('status', ['active', 'draft', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->integer('sort')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        // Gán biểu mẫu/SOP/tài liệu xuống dự án (HQ-03-06).
        Schema::create('template_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('assignable_type');   // document|form|sop|checklist
            $table->unsignedBigInteger('assignable_id');
            $table->string('resource_name')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('mode', ['apply', 'inherit', 'override', 'force'])->default('apply');
            $table->enum('status', ['active', 'pending', 'overridden'])->default('active');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'assignable_type']);
        });

        // Quy tắc kế thừa & override (HQ-03-07).
        Schema::create('config_inheritance_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type');     // document|form|sop|kb
            $table->string('scope_from');        // platform|tenant|project
            $table->string('scope_to');
            $table->enum('mode', ['inherit', 'override', 'force', 'block'])->default('inherit');
            $table->integer('priority')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Nguồn tri thức AI (HQ-03-09).
        Schema::create('ai_knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('provider')->nullable();  // SharePoint|Google Drive|Upload|SOP|FAQ
            $table->string('type')->default('file'); // file|drive|form|sop|faq
            $table->enum('status', ['synced', 'syncing', 'error', 'pending'])->default('synced');
            $table->decimal('size_gb', 10, 2)->default(0);
            $table->integer('indexed_items')->default(0);
            $table->boolean('auto_sync')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_knowledge_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('ai_knowledge_sources')->nullOnDelete();
            $table->string('event')->nullable();
            $table->integer('items_new')->default(0);
            $table->integer('items_updated')->default(0);
            $table->integer('errors')->default(0);
            $table->enum('status', ['success', 'partial', 'failed'])->default('success');
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();
        });

        // Kiểm thử AI dẫn nguồn (HQ-03-10).
        Schema::create('ai_test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->string('category')->nullable();
            $table->string('expected_source')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });

        Schema::create('ai_test_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('ai_test_questions')->nullOnDelete();
            $table->text('answer')->nullable();
            $table->json('cited_sources')->nullable();
            $table->boolean('has_citation')->default(false);
            $table->decimal('score', 6, 2)->default(0);
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['ai_test_runs', 'ai_test_questions', 'ai_knowledge_sync_logs', 'ai_knowledge_sources', 'config_inheritance_rules', 'template_assignments', 'checklist_items', 'checklist_templates', 'sop_templates', 'document_versions', 'documents', 'document_libraries'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
