<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HQ-02 — Billing, ví công ty & tương tác Platform.
 *
 * ADD-ONLY. TÁI SỬ DỤNG Batch 07: billing_invoices/lines, billing_payments, usage_meters,
 * usage_records/periods, quota_alerts, billing_adjustments, billing_reconciliations,
 * pass_through_wallets/transactions. Delta mới dưới đây: ví CÔNG TY (khác ví pass-through
 * theo kênh), yêu cầu nạp ví, rate card, yêu cầu đổi gói, và metric snapshot cho dự báo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ví prepaid cấp công ty (1 ví / tenant) — số dư, hạn mức tín dụng, auto top-up.
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->decimal('balance', 18, 2)->default(0);
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->string('currency', 8)->default('VND');
            $table->boolean('auto_topup_enabled')->default(false);
            $table->decimal('auto_topup_threshold', 18, 2)->default(0);
            $table->decimal('auto_topup_amount', 18, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_account')->nullable();
            $table->enum('status', ['active', 'frozen', 'closed'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'deleted_at']);
        });

        // Sổ cái ví công ty: nạp / trừ / phân bổ dự án / hoàn / điều chỉnh.
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['top_up', 'deduct', 'allocation', 'refund', 'adjustment']);
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_after', 18, 2)->nullable();
            $table->string('reference_no')->nullable();
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('confirmed');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->index('posted_at');
        });

        // Yêu cầu nạp ví / tăng hạn mức (chờ duyệt).
        Schema::create('wallet_topup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('request_no')->nullable();
            $table->enum('kind', ['top_up', 'credit_limit'])->default('top_up');
            $table->decimal('amount', 18, 2);
            $table->string('method')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->string('reference_no')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        // Bảng giá / markup theo kênh (nền tảng + pass-through).
        Schema::create('billing_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete(); // null = mặc định toàn hệ
            $table->string('channel'); // sms|zalo|email|payment_gateway|platform
            $table->string('meter_code')->nullable();
            $table->string('name')->nullable();
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('markup_percent', 6, 2)->default(0);
            $table->string('currency', 8)->default('VND');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'channel', 'is_active']);
        });

        // Yêu cầu nâng/hạ/gia hạn gói (theo dự án).
        Schema::create('plan_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('request_no')->unique();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('change_type', ['upgrade', 'downgrade', 'renew', 'addon']);
            $table->foreignId('from_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('to_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('content')->nullable();
            $table->date('effective_date')->nullable();
            $table->decimal('estimated_delta', 18, 2)->default(0);
            $table->enum('status', ['draft', 'processing', 'pending_approval', 'completed', 'rejected'])->default('processing');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'change_type']);
        });

        Schema::create('plan_change_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('to_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->decimal('amount_delta', 18, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Read-model cho dashboard/dự báo (KHÔNG tạo bảng report riêng từng màn).
        Schema::create('metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('metric_key');   // saas_cost|platform_fee|pass_through|forecast|...
            $table->string('period')->nullable(); // 2026-07 | 2026-Q3 ...
            $table->decimal('value', 18, 2)->default(0);
            $table->json('dimension')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'metric_key', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_snapshots');
        Schema::dropIfExists('plan_change_request_items');
        Schema::dropIfExists('plan_change_requests');
        Schema::dropIfExists('billing_rate_cards');
        Schema::dropIfExists('wallet_topup_requests');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
