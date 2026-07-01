<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 3 — Phê duyệt chung + tài chính vận hành: approval_requests(+steps),
 * funds(+fund_transactions), payment_requests (đề nghị chi), cash_vouchers (phiếu thu/chi).
 * Scope 3 lớp qua tenant_id + project_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('type')->default('expense'); // expense|payment|statement|purchase|other
            $table->nullableMorphs('subject');           // liên kết statement/payment_request...
            $table->string('title');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status')->default('pending'); // draft|pending|approved|rejected|need_more
            $table->unsignedInteger('current_step')->default(1);
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_no')->default(1);
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected|skipped
            $table->string('note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('type')->default('operating'); // operating|reserve|sinking|maintenance
            $table->decimal('opening_balance', 16, 2)->default(0);
            $table->decimal('current_balance', 16, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fund_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approval_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('title');
            $table->string('payee')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('category')->nullable(); // maintenance|utility|salary|supply|other
            $table->string('status')->default('draft'); // draft|pending|approved|paid|rejected
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('cash_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fund_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('type')->default('payment'); // receipt(thu)|payment(chi)
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('party')->nullable();        // người nộp/nhận
            $table->string('description')->nullable();
            $table->date('voucher_date')->nullable();
            $table->string('status')->default('posted'); // draft|posted|void
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('in');       // in|out
            $table->decimal('amount', 16, 2)->default(0);
            $table->decimal('balance_after', 16, 2)->default(0);
            $table->string('description')->nullable();
            $table->date('transaction_date')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transactions');
        Schema::dropIfExists('cash_vouchers');
        Schema::dropIfExists('payment_requests');
        Schema::dropIfExists('funds');
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_requests');
    }
};
