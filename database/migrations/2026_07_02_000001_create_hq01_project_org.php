<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HQ-01 — Danh mục dự án, BQL, nhân sự & gói dịch vụ theo dự án.
 *
 * ADD-ONLY. Chỉ tạo bảng delta mới; TÁI SỬ DỤNG projects / staff_profiles(≈employees) /
 * departments / plans / modules / tenant_subscriptions đã có. "employee_id" trỏ tới
 * staff_profiles (hồ sơ nhân sự 1:1 với users). role_id trỏ Spatie roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        // BQL organization for a project.
        Schema::create('bql_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->foreignId('manager_employee_id')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->string('hotline')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'project_id', 'deleted_at']);
            $table->index(['tenant_id', 'status']);
        });

        // Assignment of company employees into projects.
        Schema::create('employee_project_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->enum('assignment_type', ['primary', 'secondary', 'temporary'])->default('primary');
            $table->unsignedTinyInteger('workload_percent')->default(100);
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('status', ['draft', 'active', 'pending_approval', 'expired', 'revoked'])->default('active');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'project_id', 'status']);
            $table->index(['tenant_id', 'employee_id', 'status']);
            $table->index(['effective_from', 'effective_to']);
        });

        // Transfer / rotation log (append-only → no soft deletes).
        Schema::create('employee_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->foreignId('from_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('to_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('old_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('new_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('old_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('new_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('transfer_code');
            $table->text('reason')->nullable();
            $table->dateTime('effective_at');
            $table->enum('status', ['pending_approval', 'approved', 'effective', 'ended', 'rejected'])->default('pending_approval');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id']);
            $table->index('effective_at');
        });

        // Project package subscription timeline (per project + kỳ hiệu lực).
        Schema::create('project_subscription_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions')->nullOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->enum('status', ['trial', 'active', 'suspended', 'expired', 'pending_approval'])->default('active');
            $table->date('started_at');
            $table->date('trial_ends_at')->nullable();
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->unsignedTinyInteger('billing_anchor_day')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->json('price_snapshot_json')->nullable();
            $table->timestamp('approved_by_platform_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'project_id', 'status']);
            $table->index(['current_period_end']);
        });

        // Per-project module add-on / override / disable state.
        Schema::create('project_module_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('module_key');
            $table->enum('source', ['package', 'addon', 'inherited', 'manual_override'])->default('package');
            $table->enum('status', ['enabled', 'disabled', 'pending', 'locked'])->default('enabled');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'project_id', 'status']);
            $table->index('module_key');
        });

        // Import session for projects/employees.
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('import_type', ['projects_employees'])->default('projects_employees');
            $table->string('file_name');
            $table->string('storage_path')->nullable();
            $table->enum('status', ['uploaded', 'mapped', 'validated', 'committed', 'failed', 'cancelled'])->default('uploaded');
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('committed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('committed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        // Row-level import validation & commit status (child → no soft deletes).
        Schema::create('import_batch_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->enum('row_type', ['project', 'employee', 'assignment'])->default('project');
            $table->string('external_code')->nullable();
            $table->json('raw_payload');
            $table->json('normalized_payload')->nullable();
            $table->enum('validation_status', ['valid', 'warning', 'error', 'skipped', 'imported'])->default('valid');
            $table->json('validation_errors')->nullable();
            $table->string('committed_entity_type')->nullable();
            $table->unsignedBigInteger('committed_entity_id')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'validation_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_rows');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('project_module_overrides');
        Schema::dropIfExists('project_subscription_periods');
        Schema::dropIfExists('employee_assignment_histories');
        Schema::dropIfExists('employee_project_assignments');
        Schema::dropIfExists('bql_teams');
    }
};
