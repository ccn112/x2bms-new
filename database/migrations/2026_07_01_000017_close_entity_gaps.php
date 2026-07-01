<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đóng nốt các entity còn thiếu để phủ CANONICAL_ENTITY_MAP:
 *  - Tier 1: activity_logs (feed hoạt động chung, C9 — bên cạnh audit_logs bắt buộc).
 *  - Tier 6: ai_requests, ai_approvals (human-in-the-loop), automation_steps
 *    (bước workflow, bổ sung cạnh steps JSON), knowledge_chunks (RAG).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('log_name')->nullable();
            $table->string('description');
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_chat_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mode')->default('context'); // context|lookup|action
            $table->string('model')->nullable();
            $table->text('prompt')->nullable();
            $table->string('status')->default('success'); // success|failed|pending
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_usage_log_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->nullable();
            $table->string('risk_level')->default('high');
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('automation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_no')->default(1);
            $table->string('type')->default('action'); // trigger|ai|condition|action
            $table->string('label');
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->longText('content')->nullable();
            $table->json('embedding')->nullable(); // vector lưu tạm dạng JSON (RAG sau)
            $table->unsignedInteger('tokens')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('automation_steps');
        Schema::dropIfExists('ai_approvals');
        Schema::dropIfExists('ai_requests');
        Schema::dropIfExists('activity_logs');
    }
};
