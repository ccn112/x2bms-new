<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tuyến duyệt chiến dịch (maker-checker) — BQL-NOTI-05, spec 09. Route resolve theo
 * config (priority/audience size/paid cost/creator role); KHÔNG hardcode role trong DB.
 * Mỗi bước lưu actor/role/scope/SLA/reason/snapshot_hash/correlation_id (audit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_approvals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $t->string('route_key');                       // seed_key tuyến duyệt (approval_scenarios)
            $t->string('status')->default('requested');    // requested|approved|rejected|changes_requested|expired
            $t->unsignedSmallInteger('current_step')->default(1);
            $t->unsignedSmallInteger('total_steps')->default(1);
            $t->string('correlation_id')->nullable();
            $t->string('snapshot_hash')->nullable();       // gắn với snapshot content+audience+channel lúc gửi duyệt
            $t->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('requested_at')->nullable();
            $t->timestamp('due_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();

            $t->index(['notification_id', 'status'], 'notif_approval_status_idx');
        });

        Schema::create('notification_approval_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('approval_id')->constrained('notification_approvals')->cascadeOnDelete();
            $t->unsignedSmallInteger('step_no');
            $t->string('role');                             // vai trò cần duyệt ở bước này
            $t->string('status')->default('requested');     // requested|approved|rejected|changes_requested|expired
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('sla_due_at')->nullable();
            $t->timestamp('acted_at')->nullable();
            $t->string('reason')->nullable();
            $t->timestamps();

            $t->unique(['approval_id', 'step_no'], 'notif_approval_step_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_approval_steps');
        Schema::dropIfExists('notification_approvals');
    }
};
