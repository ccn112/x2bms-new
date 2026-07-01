<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum — KB governance + AI governance:
 *  - knowledge_documents (+ knowledge_scopes) : KB 3 cấp cho AI/vận hành.
 *  - ai_guardrail_policies, ai_retrieval_logs.
 *  - MỞ RỘNG ai_prompt_templates (đã có) theo spec addendum (use_case/system_prompt...).
 * `knowledge_articles` giữ nguyên làm KB vận hành per-tenant (có UI + X2AI search).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('document_type')->default('policy'); // policy|sop|faq|guide|manual|legal|maintenance|resident_rule
            $table->string('owner_scope')->default('platform'); // platform|tenant|company|project|building
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->foreignId('source_template_id')->nullable()->constrained('document_templates')->nullOnDelete();
            $table->string('file_url')->nullable();
            $table->longText('content_markdown')->nullable();
            $table->string('language')->default('vi');
            $table->string('status')->default('active'); // draft|active|archived
            $table->string('ai_index_status')->default('not_indexed'); // not_indexed|queued|indexed|failed
            $table->timestamp('ai_indexed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('sensitivity')->default('internal'); // public|internal|confidential|restricted
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['owner_scope', 'status']);
            $table->index('ai_index_status');
        });

        Schema::create('knowledge_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_document_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type'); // platform|tenant|company|project|building|role|user
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('permission')->default('read'); // read|ai_read|manage|share
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('ai_guardrail_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('policy_type')->default('privacy'); // privacy|finance|legal|safety|hallucination|escalation
            $table->json('rule_json')->nullable();
            $table->string('severity')->default('medium'); // low|medium|high|critical
            $table->string('action')->default('warn'); // warn|block|require_human_approval|log_only
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_retrieval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question')->nullable();
            $table->text('answer_summary')->nullable();
            $table->json('retrieved_document_ids_json')->nullable();
            $table->json('blocked_document_ids_json')->nullable();
            $table->json('permission_snapshot_json')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('token_input')->default(0);
            $table->unsignedInteger('token_output')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->timestamps();
        });

        // Mở rộng ai_prompt_templates (đã có ở AI Engine) theo spec addendum.
        Schema::table('ai_prompt_templates', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->string('use_case')->nullable()->after('category'); // resident_qa|bql_copilot|support_agent|finance_explain|work_order_triage
            $table->text('system_prompt')->nullable()->after('use_case');
            $table->text('user_prompt_template')->nullable()->after('system_prompt');
            $table->json('variables_json')->nullable()->after('user_prompt_template');
            $table->string('owner_scope')->default('platform')->after('variables_json');
            $table->unsignedBigInteger('owner_id')->nullable()->after('owner_scope');
        });
    }

    public function down(): void
    {
        Schema::table('ai_prompt_templates', function (Blueprint $table) {
            $table->dropColumn(['code', 'use_case', 'system_prompt', 'user_prompt_template', 'variables_json', 'owner_scope', 'owner_id']);
        });
        Schema::dropIfExists('ai_retrieval_logs');
        Schema::dropIfExists('ai_guardrail_policies');
        Schema::dropIfExists('knowledge_scopes');
        Schema::dropIfExists('knowledge_documents');
    }
};
