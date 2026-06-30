<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-FORM-07-04 "Duyệt bảng kê hàng loạt" approves at the BATCH level — one row
 * per tòa/kỳ (a billing_run), not per-apartment. Adds the approval lifecycle,
 * người tạo/người duyệt, SLA and số căn to billing_runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_runs', function (Blueprint $table) {
            // draft|pending|reviewing|approved|published|rejected|need_more
            $table->string('approval_status')->default('pending')->after('status');
            $table->unsignedInteger('apartment_count')->default(0)->after('statements_count');
            $table->foreignId('created_by_id')->nullable()->after('apartment_count')->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->after('created_by_id')->constrained('users')->nullOnDelete();
            $table->timestamp('sla_due_at')->nullable()->after('approver_id');
            $table->string('approval_note')->nullable()->after('sla_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_id');
            $table->dropConstrainedForeignId('approver_id');
            $table->dropColumn(['approval_status', 'apartment_count', 'sla_due_at', 'approval_note']);
        });
    }
};
