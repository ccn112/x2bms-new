<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 10 — Platform Support, Ticket & Data Correction (platform-level).
 *
 * Reconcile: DROP the primitive support_tickets / support_ticket_comments /
 * data_fix_requests (migration 000012) and rebuild the canonical Support Center
 * (27 tables). Add-only. Support rows reference tenant_id as a FK but are operated
 * by platform SuperAdmin (no BelongsToTenant scope).
 */
return new class extends Migration
{
    private array $archive = ['support_audit_logs', 'support_ticket_status_logs', 'support_sla_events'];

    public function up(): void
    {
        Schema::dropIfExists('support_ticket_comments');
        Schema::dropIfExists('data_fix_requests');
        Schema::dropIfExists('support_tickets');

        Schema::create('support_teams', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('level')->default('L1'); // L1|L2|L3|account
            $t->unsignedInteger('sla_target_response_minutes')->default(60);
            $t->unsignedInteger('member_count')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('support_team_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_team_id')->constrained('support_teams')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('member_name')->nullable();
            $t->string('role')->nullable();
            $t->boolean('is_on_call')->default(false);
            $t->unsignedInteger('open_tickets')->default(0);
            $t->timestamps();
        });

        Schema::create('support_sla_policies', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('priority')->default('medium');
            $t->unsignedInteger('response_minutes')->default(60);
            $t->unsignedInteger('resolution_minutes')->default(480);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_no')->unique();
            $t->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $t->string('subject');
            $t->longText('description')->nullable();
            $t->string('module')->nullable();
            $t->string('category')->nullable();
            $t->string('priority')->default('medium');    // low|medium|high|critical
            $t->string('status')->default('new');          // new|open|in_progress|waiting_customer|escalated|resolved|closed|reopened
            $t->string('environment')->default('production');
            $t->string('channel')->default('web');
            $t->foreignId('sla_policy_id')->nullable()->constrained('support_sla_policies')->nullOnDelete();
            $t->string('sla_state')->default('within_sla'); // within_sla|near_breach|breached|paused_waiting_customer|resolved
            $t->timestamp('sla_due_at')->nullable();
            $t->timestamp('first_response_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('team_id')->nullable()->constrained('support_teams')->nullOnDelete();
            $t->string('requester_name')->nullable();
            $t->string('requester_contact')->nullable();
            $t->decimal('csat_score', 3, 2)->nullable();
            $t->longText('resolution_summary')->nullable();
            $t->json('tags')->nullable();
            $t->unsignedInteger('reopen_count')->default(0);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'priority']);
            $t->index('sla_state');
        });

        Schema::create('support_ticket_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('author_name')->nullable();
            $t->string('type')->default('internal'); // internal|customer|system
            $t->longText('body')->nullable();
            $t->timestamps();
        });

        Schema::create('support_ticket_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $t->string('file_name');
            $t->string('file_path')->nullable();
            $t->unsignedBigInteger('size_bytes')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('support_ticket_status_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $t->string('from_status')->nullable();
            $t->string('to_status');
            $t->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('note')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('support_sla_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $t->string('event');       // start|pause|resume|breach|close
            $t->string('sla_state')->nullable();
            $t->timestamp('occurred_at')->nullable();
            $t->string('note')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('support_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('team_id')->nullable()->constrained('support_teams')->nullOnDelete();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('method')->default('manual'); // manual|auto
            $t->timestamps();
        });

        Schema::create('support_escalations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $t->string('from_level')->nullable();
            $t->string('to_level');
            $t->string('reason')->nullable();
            $t->string('status')->default('active'); // active|resolved
            $t->foreignId('escalated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('tenant_support_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $t->string('support_plan')->nullable();
            $t->string('tier')->nullable();
            $t->decimal('health_score', 5, 2)->nullable();
            $t->decimal('csat', 3, 2)->nullable();
            $t->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $t->longText('vip_notes')->nullable();
            $t->timestamps();
        });

        Schema::create('tenant_support_contacts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('role')->nullable();
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
        });

        Schema::create('support_entitlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $t->string('code');
            $t->string('name');
            $t->string('value')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('data_correction_requests', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $t->foreignId('support_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
            $t->string('data_type');
            $t->string('target_entity')->nullable();
            $t->unsignedInteger('affected_records')->default(0);
            $t->string('risk')->default('medium');   // low|medium|high|critical
            $t->string('status')->default('draft');  // draft|pending_approval|approved|rejected|executing|executed|rollback_requested|rolled_back|cancelled
            $t->longText('reason')->nullable();
            $t->longText('rollback_plan')->nullable();
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('execution_window_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('data_correction_affected_records', function (Blueprint $t) {
            $t->id();
            // explicit short FK name — auto name would exceed MySQL's 64-char limit
            $t->foreignId('data_correction_request_id');
            $t->foreign('data_correction_request_id', 'dcar_request_fk')
                ->references('id')->on('data_correction_requests')->cascadeOnDelete();
            $t->string('entity');
            $t->string('record_id')->nullable();
            $t->string('identifier')->nullable();
        });

        Schema::create('data_fix_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('data_correction_request_id')->constrained('data_correction_requests')->cascadeOnDelete();
            $t->longText('snapshot_json')->nullable();
            $t->unsignedInteger('record_count')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('data_fix_wizard_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('data_correction_request_id')->constrained('data_correction_requests')->cascadeOnDelete();
            $t->string('current_step')->default('choose_request');
            $t->json('state_json')->nullable();
            $t->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('data_fix_diff_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('data_correction_request_id')->constrained('data_correction_requests')->cascadeOnDelete();
            $t->string('entity')->nullable();
            $t->string('record_id')->nullable();
            $t->string('field');
            $t->text('before_value')->nullable();
            $t->text('after_value')->nullable();
        });

        Schema::create('data_fix_approvals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('data_correction_request_id')->constrained('data_correction_requests')->cascadeOnDelete();
            $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('decision')->default('approved'); // approved|rejected
            $t->string('reason')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('data_fix_executions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('data_correction_request_id')->constrained('data_correction_requests')->cascadeOnDelete();
            $t->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status')->default('executed');
            $t->unsignedInteger('affected_count')->default(0);
            $t->timestamp('executed_at')->nullable();
            $t->longText('log')->nullable();
            $t->timestamps();
        });

        Schema::create('data_fix_rollbacks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('data_correction_request_id')->constrained('data_correction_requests')->cascadeOnDelete();
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status')->default('requested');
            $t->unsignedInteger('restored_count')->default(0);
            $t->timestamp('rolled_back_at')->nullable();
            $t->timestamps();
        });

        Schema::create('support_kb_categories', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('description')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('support_kb_articles', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('title');
            $t->foreignId('category_id')->nullable()->constrained('support_kb_categories')->nullOnDelete();
            $t->longText('body')->nullable();
            $t->string('status')->default('draft'); // draft|in_review|published|archived
            $t->decimal('rating', 3, 2)->nullable();
            $t->unsignedInteger('views')->default(0);
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('support_kb_article_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_kb_article_id')->constrained('support_kb_articles')->cascadeOnDelete();
            $t->unsignedInteger('version')->default(1);
            $t->longText('body')->nullable();
            $t->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('support_kb_draft_workflows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_kb_article_id')->constrained('support_kb_articles')->cascadeOnDelete();
            $t->string('state')->default('draft'); // draft|submitted|approved|rejected
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('support_reports', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->string('period')->nullable();
            $t->string('type')->default('resolution'); // resolution|dashboard_snapshot
            $t->json('metrics_json')->nullable();
            $t->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('support_audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $t->string('entity_type');
            $t->string('entity_id')->nullable();
            $t->string('action');
            $t->json('before_json')->nullable();
            $t->json('after_json')->nullable();
            $t->string('reason')->nullable();
            $t->string('ip_address')->nullable();
            $t->string('user_agent')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['entity_type', 'entity_id']);
        });

        $driver = DB::connection()->getDriverName();
        foreach ($this->archive as $table) {
            if (! Schema::hasTable($table.'_archive')) {
                if (in_array($driver, ['mysql', 'mariadb'], true)) {
                    DB::statement("CREATE TABLE `{$table}_archive` LIKE `{$table}`");
                } else {
                    DB::statement("CREATE TABLE \"{$table}_archive\" AS SELECT * FROM \"{$table}\" WHERE 1 = 0");
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->archive as $table) {
            Schema::dropIfExists($table.'_archive');
        }
        foreach ([
            'support_audit_logs', 'support_reports', 'support_kb_draft_workflows', 'support_kb_article_versions',
            'support_kb_articles', 'support_kb_categories', 'data_fix_rollbacks', 'data_fix_executions',
            'data_fix_approvals', 'data_fix_diff_items', 'data_fix_wizard_sessions', 'data_fix_snapshots',
            'data_correction_affected_records', 'data_correction_requests', 'support_entitlements',
            'tenant_support_contacts', 'tenant_support_profiles', 'support_escalations', 'support_assignments',
            'support_sla_events', 'support_ticket_status_logs', 'support_ticket_attachments',
            'support_ticket_messages', 'support_tickets', 'support_sla_policies', 'support_team_members', 'support_teams',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        // restore the old primitive tables for a consistent rollback
        Schema::create('support_tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('code')->nullable();
            $t->string('subject');
            $t->text('description')->nullable();
            $t->string('category')->nullable();
            $t->string('priority')->default('normal');
            $t->string('status')->default('open');
            $t->string('channel')->default('web');
            $t->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
        Schema::create('support_ticket_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->text('body');
            $t->boolean('is_internal')->default(false);
            $t->timestamps();
        });
        Schema::create('data_fix_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('code')->nullable();
            $t->string('entity');
            $t->unsignedBigInteger('target_id')->nullable();
            $t->text('reason')->nullable();
            $t->json('requested_change')->nullable();
            $t->string('status')->default('pending');
            $t->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('applied_at')->nullable();
            $t->timestamps();
        });
    }
};
