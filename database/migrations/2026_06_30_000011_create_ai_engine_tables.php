<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-UX-09 — X2AI Copilot "AI Engine" section. Backs the four bespoke /admin
 * screens with real, tenant-scoped data (no hardcoded UI):
 *   - ai_usage_logs  → Trung tâm AI (09-01) usage + Governance & Audit AI (09-02) log.
 *   - ai_policies / ai_prompt_templates → Governance tabs (Chính sách / Prompt & phân loại).
 *   - ai_workflows / ai_workflow_runs   → Workflow Automation (09-03).
 *   - knowledge_categories / knowledge_articles → Cơ sở tri thức KB (09-04).
 * ADD-ONLY (existing audit_logs / ai_suggestions are untouched).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 09-01 usage + 09-02 audit trail of every AI interaction.
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('surface')->nullable();          // route/màn hình gọi AI
            $table->string('mode')->default('context');     // context|lookup|action
            $table->string('model')->default('claude-haiku-4-5');
            $table->string('action')->default('chat');      // chat|summarize|draft|analyze|lookup
            $table->string('risk_level')->default('low');   // low|medium|high
            $table->string('status')->default('success');   // success|failed|pending_approval|rejected
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('prompt_excerpt')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['risk_level', 'status']);
        });

        // 09-02 · Chính sách AI tab.
        Schema::create('ai_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('category')->default('data');     // data|access|risk|content
            $table->string('risk_level')->default('low');    // low|medium|high
            $table->string('status')->default('active');     // active|inactive
            $table->json('config')->nullable();
            $table->timestamps();
        });

        // 09-02 · Prompt & phân loại tab + 09-01 Gợi ý nhanh.
        Schema::create('ai_prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->default('general');
            $table->string('classification')->nullable();    // nhãn phân loại
            $table->string('surface')->nullable();           // màn hình áp dụng
            $table->text('body')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->string('status')->default('active');      // active|inactive
            $table->timestamps();
        });

        // 09-03 · Workflow Automation.
        Schema::create('ai_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('trigger_type')->default('event'); // event|schedule|manual
            $table->json('trigger_config')->nullable();
            $table->string('schedule')->nullable();            // mô tả lịch (cron/ngôn ngữ tự nhiên)
            $table->string('status')->default('active');       // active|paused|draft
            $table->json('steps')->nullable();                 // các bước của workflow
            $table->unsignedInteger('runs_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_workflow_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('success');     // success|failed|running
            $table->string('trigger_source')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('note')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        // 09-04 · Cơ sở tri thức (KB).
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('articles_count')->default(0);
            $table->timestamps();
        });

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('status')->default('published');    // published|draft|archived
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_categories');
        Schema::dropIfExists('ai_workflow_runs');
        Schema::dropIfExists('ai_workflows');
        Schema::dropIfExists('ai_prompt_templates');
        Schema::dropIfExists('ai_policies');
        Schema::dropIfExists('ai_usage_logs');
    }
};
