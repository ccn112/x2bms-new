<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 4 — Nhà thầu + hợp đồng (C7) + tài sản + đồng hồ + IoT:
 * contractors, contracts(+packages,+acceptances), contractor_kpis, contractor_settlements,
 * asset_categories, assets, maintenance_plans, meters(+readings), iot_devices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('tax_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('service_type')->nullable(); // elevator|cleaning|security|landscaping|mep
            $table->decimal('rating', 3, 1)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('title');
            $table->string('type')->default('service'); // service|construction|maintenance
            $table->decimal('value', 16, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // draft|active|expired|terminated
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('value', 16, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('contract_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('title');
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('status')->default('pending'); // pending|accepted|rejected
            $table->foreignId('accepted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contractor_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->string('period')->nullable(); // 2026-06
            $table->decimal('score', 5, 2)->default(0);
            $table->decimal('on_time_rate', 5, 2)->default(0);
            $table->decimal('quality_score', 5, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('contractor_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period')->nullable();
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('status')->default('pending'); // pending|paid
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('serial_no')->nullable();
            $table->string('location')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('value', 16, 2)->default(0);
            $table->date('warranty_until')->nullable();
            $table->string('status')->default('active'); // active|maintenance|retired
            $table->timestamps();
        });

        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('frequency')->default('monthly'); // weekly|monthly|quarterly|yearly
            $table->timestamp('next_due_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('type')->default('electric'); // electric|water|gas
            $table->string('unit')->default('kWh');
            $table->decimal('last_reading', 16, 2)->default(0);
            $table->string('status')->default('active');
            $table->date('installed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_id')->constrained()->cascadeOnDelete();
            $table->string('period')->nullable(); // 2026-06
            $table->decimal('previous_reading', 16, 2)->default(0);
            $table->decimal('current_reading', 16, 2)->default(0);
            $table->decimal('consumption', 16, 2)->default(0);
            $table->date('reading_date')->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('type')->default('sensor'); // sensor|gateway|actuator
            $table->string('protocol')->nullable();     // mqtt|modbus|lora|zigbee
            $table->string('status')->default('online'); // online|offline
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_devices');
        Schema::dropIfExists('meter_readings');
        Schema::dropIfExists('meters');
        Schema::dropIfExists('maintenance_plans');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
        Schema::dropIfExists('contractor_settlements');
        Schema::dropIfExists('contractor_kpis');
        Schema::dropIfExists('contract_acceptances');
        Schema::dropIfExists('contract_packages');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contractors');
    }
};
