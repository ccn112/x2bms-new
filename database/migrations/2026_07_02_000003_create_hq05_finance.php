<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HQ-05 — Báo cáo công nợ, tài chính, thu chi đa dự án.
 *
 * ADD-ONLY. Dashboard/aggregate dùng `metric_snapshots` (đã tạo ở HQ-02) — không tạo bảng
 * report riêng từng màn. Delta dưới đây là các thực thể nghiệp vụ mới: nhắc nợ/chiến dịch,
 * quỹ & thu chi, lịch/xuất báo cáo, và insight rủi ro AI.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Chiến dịch thu hồi công nợ (HQ-05-08).
        Schema::create('debt_reminder_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('scope')->nullable();      // toàn công ty | cư dân | tòa nhà
            $table->enum('channel', ['sms', 'zalo', 'email', 'app', 'call', 'mixed'])->default('sms');
            $table->enum('status', ['draft', 'running', 'paused', 'completed'])->default('draft');
            $table->integer('target_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->decimal('response_rate', 6, 2)->default(0);
            $table->decimal('committed_amount', 18, 2)->default(0);
            $table->decimal('collected_amount', 18, 2)->default(0);
            $table->json('content_template')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        // Log từng lượt nhắc nợ (append-only).
        Schema::create('debt_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('debt_reminder_campaigns')->nullOnDelete();
            $table->string('debtor_ref')->nullable();
            $table->string('channel')->nullable();
            $table->enum('status', ['sent', 'delivered', 'failed', 'responded', 'committed', 'escalated'])->default('sent');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'campaign_id']);
        });

        // Quỹ tiền (thu chi đa dự án — HQ-05-07).
        Schema::create('cash_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->enum('type', ['operating', 'reserve', 'sinking'])->default('operating');
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('currency', 8)->default('VND');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_fund_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->string('category')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('description')->nullable();
            $table->string('reference_no')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
        });

        // Đề nghị chi (HQ-05-07 phê duyệt chờ xử lý).
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('category')->nullable();
            $table->string('vendor')->nullable();
            $table->decimal('amount', 18, 2);
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->string('description')->nullable();
            $table->date('incurred_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        // Lịch gửi báo cáo & job xuất (HQ-05-09).
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('report_type');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly'])->default('monthly');
            $table->enum('format', ['pdf', 'excel', 'both'])->default('pdf');
            $table->json('recipients')->nullable();
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('report_export_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('report_type');
            $table->enum('format', ['pdf', 'excel'])->default('pdf');
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->string('file_path')->nullable();
            $table->json('params')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Insight rủi ro tài chính AI (HQ-05-10).
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');        // debt_risk|cashflow|collection
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('title');
            $table->text('body')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->string('recommendation')->nullable();
            $table->enum('status', ['new', 'reviewed', 'actioned', 'dismissed'])->default('new');
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'category', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
        Schema::dropIfExists('report_export_jobs');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('cash_funds');
        Schema::dropIfExists('debt_reminder_logs');
        Schema::dropIfExists('debt_reminder_campaigns');
    }
};
