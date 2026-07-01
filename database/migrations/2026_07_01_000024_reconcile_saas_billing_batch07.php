<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 07 — SaaS Billing/Metering/Revenue (canonical, reconcile).
 *
 * Owner decision 2026-07-01: Batch 07 = canonical → DROP các bảng saas sơ khai cũ
 * (subscriptions/subscription_invoices(+lines)/usage_metering từ slice B1) và thay bằng
 * bộ bảng đầy đủ theo BATCH_07_DB_API_MAPPING + CRUD_FLOW_CONTRACT.
 * GIỮ NGUYÊN feature-gate layer: plans/plan_features/modules/features/tenant_entitlements/tenant_module_overrides.
 * `tenant_subscriptions.plan_id` trỏ `plans` (catalog feature-gate hiện có).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Gỡ các bảng saas cũ (thứ tự FK-safe).
        Schema::dropIfExists('subscription_invoice_lines');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('usage_metering');
        Schema::dropIfExists('subscriptions');

        // 2) Giá theo chu kỳ cho từng plan (plan giữ base price; đây là bảng giá chi tiết).
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('billing_cycle')->default('monthly'); // monthly|quarterly|yearly
            $table->string('currency', 8)->default('VND');
            $table->decimal('price', 16, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['plan_id', 'billing_cycle', 'currency']);
        });

        Schema::create('subscription_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('contract_no')->unique();
            $table->string('contract_type')->default('standard'); // standard|trial|enterprise|custom
            $table->string('status')->default('draft'); // draft|active|near_expiry|renewal_pending|renewed|expired|terminated
            $table->string('file_url')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('annual_value', 16, 2)->default(0);
            $table->string('payment_terms')->nullable();
            $table->string('sla_code')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active'); // trial|active|pending_renewal|past_due|suspended|cancelled
            $table->string('billing_cycle')->default('monthly'); // monthly|quarterly|yearly
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->decimal('mrr', 16, 2)->default(0);
            $table->decimal('arr', 16, 2)->default(0);
            $table->string('currency', 8)->default('VND');
            $table->foreignId('contract_id')->nullable()->constrained('subscription_contracts')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('tenant_subscriptions')->cascadeOnDelete();
            $table->string('item_type')->default('plan'); // plan|addon|discount
            $table->string('name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->decimal('amount', 16, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('subscription_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('tenant_subscriptions')->cascadeOnDelete();
            $table->string('addon_code');
            $table->string('name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->decimal('mrr', 16, 2)->default(0);
            $table->string('wallet_type')->nullable();
            $table->string('status')->default('active'); // active|cancelled
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('subscription_contracts')->nullOnDelete();
            $table->string('stage')->default('pending'); // pending|negotiation|approved|rejected|renewed
            $table->date('target_date')->nullable();
            $table->decimal('proposed_value', 16, 2)->default(0);
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('usage_meters', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // buildings|apartments|storage_gb|sms_count|ai_tokens...
            $table->string('name');
            $table->string('unit')->nullable();
            $table->boolean('is_billable')->default(true);
            $table->timestamps();
        });

        Schema::create('usage_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('status')->default('open'); // open|calculating|locked
            $table->timestamp('locked_at')->nullable();
            $table->string('locked_by')->nullable();
            $table->timestamps();
        });

        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usage_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('meter_type'); // buildings|apartments|storage_gb|sms_count|ai_tokens...
            $table->decimal('usage_value', 18, 2)->default(0);
            $table->decimal('included_limit', 18, 2)->default(0);
            $table->decimal('overage_value', 18, 2)->default(0);
            $table->decimal('overage_amount', 16, 2)->default(0);
            $table->string('source')->default('collected'); // collected|imported|manual
            $table->string('status')->default('draft'); // draft|calculated|locked
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'meter_type']);
        });

        Schema::create('quota_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('usage_period_id')->nullable()->constrained()->nullOnDelete();
            $table->string('meter_type');
            $table->decimal('usage_value', 18, 2)->default(0);
            $table->decimal('included_limit', 18, 2)->default(0);
            $table->decimal('over_percent', 6, 2)->default(0);
            $table->decimal('estimated_fee', 16, 2)->default(0);
            $table->string('recommendation')->nullable();
            $table->string('status')->default('open'); // open|assigned|resolved|dismissed|converted_to_addon|converted_to_upgrade
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions')->nullOnDelete();
            $table->string('period')->nullable(); // 2026-05
            $table->string('status')->default('draft'); // draft|pending_approval|issued|sent|partially_paid|paid|overdue|voided|credited
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('discount_total', 16, 2)->default(0);
            $table->decimal('tax_total', 16, 2)->default(0);
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->decimal('paid_amount', 16, 2)->default(0);
            $table->decimal('remaining_amount', 16, 2)->default(0);
            $table->string('currency', 8)->default('VND');
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('billing_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->string('line_type')->default('subscription'); // subscription|addon|usage_overage|pass_through|discount|tax|adjustment|credit_note
            $table->string('description');
            $table->decimal('quantity', 14, 2)->default(1);
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->decimal('amount', 16, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method')->default('bank_transfer');
            $table->decimal('amount', 16, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->string('status')->default('confirmed'); // pending|confirmed|failed|refunded
            $table->unsignedBigInteger('reconciliation_id')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('billing_invoices')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('billing_payments')->nullOnDelete();
            $table->string('bank_transaction_ref')->nullable();
            $table->string('status')->default('pending'); // pending|matched|mismatch
            $table->decimal('difference_amount', 16, 2)->default(0);
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('case_id')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('billing_invoices')->nullOnDelete();
            $table->string('adjustment_type')->default('usage_adjustment'); // overcharge_sms|duplicate_overage|tax_correction|usage_adjustment|courtesy_discount|credit_note_issued
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('reason')->nullable();
            $table->string('evidence_file_url')->nullable();
            $table->string('status')->default('pending_approval'); // pending_approval|need_more_info|approved|rejected
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_no')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('billing_invoices')->nullOnDelete();
            $table->foreignId('adjustment_id')->nullable()->constrained('billing_adjustments')->nullOnDelete();
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('reason')->nullable();
            $table->string('status')->default('issued'); // issued|applied|void
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pass_through_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('wallet_type'); // sms|zalo|email|ai_token|payment_fee|e_invoice|api_calls|storage
            $table->decimal('balance', 16, 2)->default(0);
            $table->string('currency', 8)->default('VND');
            $table->decimal('monthly_target', 16, 2)->default(0);
            $table->decimal('low_balance_threshold', 16, 2)->default(0);
            $table->boolean('auto_topup_enabled')->default(false);
            $table->decimal('auto_topup_amount', 16, 2)->default(0);
            $table->string('status')->default('active'); // active|frozen
            $table->timestamps();
            $table->unique(['tenant_id', 'wallet_type']);
        });

        Schema::create('pass_through_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('pass_through_wallets')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type'); // top_up|deduct|refund|adjustment
            $table->decimal('amount', 16, 2)->default(0);
            $table->decimal('balance_after', 16, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->string('status')->default('confirmed'); // pending|confirmed|rejected
            $table->timestamps();
        });

        Schema::create('billing_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action');
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->string('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['entity_type', 'entity_id']);
            $table->index(['tenant_id', 'action']);
        });
    }

    public function down(): void
    {
        foreach ([
            'billing_audit_logs', 'pass_through_transactions', 'pass_through_wallets', 'credit_notes',
            'billing_adjustments', 'billing_reconciliations', 'billing_payments', 'billing_invoice_lines',
            'billing_invoices', 'quota_alerts', 'usage_records', 'usage_periods', 'usage_meters',
            'subscription_renewals', 'subscription_addons', 'subscription_items', 'tenant_subscriptions',
            'subscription_contracts', 'plan_prices',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
