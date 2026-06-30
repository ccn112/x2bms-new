<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 3b — Billing run + statement issuance/approval (WEB-FORM-07).
 * Enriches existing billing_periods / statements / statement_lines (additive,
 * dashboard-safe) and adds billing_runs(+items), statement_approvals,
 * statement_publish_logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_periods', function (Blueprint $table) {
            $table->string('status')->default('open')->after('is_current'); // open|locked|published
            $table->date('due_date')->nullable()->after('status');
        });

        Schema::table('statements', function (Blueprint $table) {
            $table->string('code')->nullable()->after('apartment_id');
            $table->timestamp('issued_at')->nullable()->after('status');
            $table->timestamp('published_at')->nullable()->after('issued_at');
        });

        Schema::table('statement_lines', function (Blueprint $table) {
            $table->foreignId('fee_type_id')->nullable()->after('statement_id')->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 2)->nullable()->after('fee_type');
            $table->decimal('unit_price', 16, 2)->nullable()->after('quantity');
        });

        Schema::create('billing_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('billing_period_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('status')->default('draft'); // draft|running|completed|failed
            $table->decimal('total_billed', 16, 2)->default(0);
            $table->unsignedInteger('statements_count')->default(0);
            $table->timestamp('run_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('statement_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('status')->default('ok'); // ok|skipped|error
            $table->timestamps();
        });

        Schema::create('statement_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_period_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('statement_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->string('note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('statement_publish_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('published_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel')->default('app'); // app|email|sms|zalo
            $table->unsignedInteger('statements_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_publish_logs');
        Schema::dropIfExists('statement_approvals');
        Schema::dropIfExists('billing_run_items');
        Schema::dropIfExists('billing_runs');

        Schema::table('statement_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_type_id');
            $table->dropColumn(['quantity', 'unit_price']);
        });
        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn(['code', 'issued_at', 'published_at']);
        });
        Schema::table('billing_periods', function (Blueprint $table) {
            $table->dropColumn(['status', 'due_date']);
        });
    }
};
