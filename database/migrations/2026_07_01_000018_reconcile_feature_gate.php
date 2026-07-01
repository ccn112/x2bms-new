<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum (SuperAdmin) — chuẩn hoá feature-gate. Thay bản first-cut Tier 4
 * (saas_plans/plan_features/tenant_modules) bằng mô hình addendum:
 * modules, features, plans (popular|full|intelligent), plan_features,
 * tenant_entitlements, tenant_module_overrides. Repoint subscriptions → plans.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Gỡ liên kết & bảng first-cut.
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('saas_plan_id');
        });
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('saas_plans');

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module_id', 'code']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // popular|full|intelligent
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('monthly_base_price', 14, 2)->default(0);
            $table->decimal('yearly_base_price', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->json('limits_json')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'feature_id']);
        });

        Schema::create('tenant_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->string('source')->default('plan'); // plan|add_on|manual_override|trial
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('limits_json')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_module_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'module_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('tenant_id')->constrained('plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });
        Schema::dropIfExists('tenant_module_overrides');
        Schema::dropIfExists('tenant_entitlements');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('features');
        Schema::dropIfExists('modules');
    }
};
