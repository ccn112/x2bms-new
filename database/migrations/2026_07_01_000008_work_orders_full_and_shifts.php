<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 3 — Work order đầy đủ + SLA config + ca trực. Làm giàu `work_orders` (ADD-ONLY)
 * và thêm con: assignments, checklists(+items), attachments (C6), signatures.
 * sla_policies (C4 config; sla_events runtime đã có). shifts + duty_rosters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('building_id')->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->after('department_id')->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('assigned_to_id')->constrained()->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
            $table->text('description')->nullable()->after('title');
            $table->string('category')->nullable()->after('description'); // electrical|plumbing|cleaning|security|other
            $table->timestamp('scheduled_at')->nullable()->after('due_at');
            $table->timestamp('started_at')->nullable()->after('scheduled_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->decimal('cost', 14, 2)->default(0)->after('completed_at');
        });

        Schema::create('work_order_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->nullable();               // primary|support
            $table->string('status')->default('assigned');    // assigned|accepted|done|reassigned
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('work_order_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('work_order_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_checklist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_done')->default(false);
            $table->foreignId('done_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('work_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('type')->default('photo'); // photo|doc|before|after|signature
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('work_order_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('signer_name')->nullable();
            $table->string('signer_role')->default('technician'); // technician|resident|supervisor
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('applies_to')->default('feedback_request'); // feedback_request|work_order
            $table->string('priority')->nullable();      // low|normal|high|urgent (áp cho mức nào)
            $table->unsignedInteger('response_minutes')->default(60);
            $table->unsignedInteger('resolve_minutes')->default(1440);
            $table->boolean('business_hours_only')->default(false);
            $table->string('status')->default('active'); // active|inactive
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');           // Ca sáng|Ca chiều|Ca đêm
            $table->string('start_time');     // 06:00
            $table->string('end_time');       // 14:00
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('duty_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('duty_date');
            $table->string('status')->default('scheduled'); // scheduled|present|absent|leave
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['shift_id', 'duty_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_rosters');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('sla_policies');
        Schema::dropIfExists('work_order_signatures');
        Schema::dropIfExists('work_order_attachments');
        Schema::dropIfExists('work_order_checklist_items');
        Schema::dropIfExists('work_order_checklists');
        Schema::dropIfExists('work_order_assignments');
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('apartment_id');
            $table->dropConstrainedForeignId('assigned_to_id');
            $table->dropConstrainedForeignId('team_id');
            $table->dropConstrainedForeignId('created_by_id');
            $table->dropColumn(['description', 'category', 'scheduled_at', 'started_at', 'completed_at', 'cost']);
        });
    }
};
