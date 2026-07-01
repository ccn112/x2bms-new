<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 4 — SaaS billing (C2): saas_plans(+plan_features), subscriptions,
 * subscription_invoices(+lines), tenant_modules, usage_metering.
 * saas_plans/plan_features = platform-global (không tenant). Còn lại theo tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('price_monthly', 14, 2)->default(0);
            $table->decimal('price_yearly', 14, 2)->default(0);
            $table->unsignedInteger('max_projects')->nullable();
            $table->unsignedInteger('max_units')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_plan_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->string('value')->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('saas_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active'); // trial|active|past_due|cancelled
            $table->string('billing_cycle')->default('monthly'); // monthly|yearly
            $table->unsignedInteger('seats')->default(1);
            $table->decimal('price', 14, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status')->default('issued'); // draft|issued|paid|overdue|void
            $table->timestamp('issued_at')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('module_key');    // finance|feedback|operations|ai|marketplace...
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'module_key']);
        });

        Schema::create('usage_metering', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('metric');        // units|storage_gb|ai_calls|sms|api_calls
            $table->string('period')->nullable(); // 2026-07
            $table->decimal('quantity', 16, 2)->default(0);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_metering');
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('subscription_invoice_lines');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('saas_plans');
    }
};
