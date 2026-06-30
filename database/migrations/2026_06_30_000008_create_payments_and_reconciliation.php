<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 3c — Payments, receipts, bank reconciliation, debt allocation (WEB-FORM-08).
 * payment_allocations is the single allocation ledger (CANONICAL_ENTITY_MAP C8;
 * debt_offsets/debt_allocations are aliases, not created).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type')->default('cash'); // cash|bank|gateway|qr
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_no');
            $table->string('account_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->decimal('amount', 16, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('status')->default('confirmed'); // pending|confirmed|reversed
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Allocation ledger: a payment applied to statements / debts (C8).
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('statement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('statement_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('debt_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 16, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->decimal('amount', 16, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('file_path')->nullable();
            $table->string('status')->default('imported'); // imported|reconciling|done
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_statement_import_id')->nullable()->constrained()->nullOnDelete();
            $table->date('txn_date')->nullable();
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('direction')->default('credit'); // credit|debit
            $table->string('description')->nullable();
            $table->string('reference_no')->nullable();
            $table->boolean('is_matched')->default(false);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reconciliation_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('statement_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('status')->default('suggested'); // suggested|confirmed|rejected
            $table->foreignId('matched_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_matches');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_statement_imports');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('payment_methods');
    }
};
