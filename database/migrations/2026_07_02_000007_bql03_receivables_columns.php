<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-BQL-03-04/05/06 — extra columns needed by the statement list and the debt /
 * aging screens: statement view/overdue/assignee/channel, and a per-apartment debt
 * ledger with aging buckets, risk and recovery status. Add-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->timestamp('viewed_at')->nullable()->after('published_at');
            $table->date('due_date')->nullable()->after('viewed_at');
            $table->string('assignee_name')->nullable()->after('due_date');
            $table->string('sent_channel')->nullable()->after('assignee_name'); // app|email|sms|zalo
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->string('resident_name')->nullable()->after('apartment_id');
            $table->string('last_period_code')->nullable()->after('resident_name');
            $table->decimal('bucket_0_30', 16, 2)->default(0)->after('amount');
            $table->decimal('bucket_31_60', 16, 2)->default(0)->after('bucket_0_30');
            $table->decimal('bucket_61_90', 16, 2)->default(0)->after('bucket_31_60');
            $table->decimal('bucket_over_90', 16, 2)->default(0)->after('bucket_61_90');
            $table->string('risk_level')->nullable()->after('bucket_over_90');       // low|medium|high|critical
            $table->string('recovery_status')->nullable()->after('risk_level');       // new|in_progress|overdue_handling
            $table->string('assignee_name')->nullable()->after('recovery_status');
        });
    }

    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn(['viewed_at', 'due_date', 'assignee_name', 'sent_channel']);
        });
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn(['code', 'resident_name', 'last_period_code', 'bucket_0_30', 'bucket_31_60', 'bucket_61_90', 'bucket_over_90', 'risk_level', 'recovery_status', 'assignee_name']);
        });
    }
};
