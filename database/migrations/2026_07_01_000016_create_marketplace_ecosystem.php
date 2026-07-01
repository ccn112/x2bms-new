<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 5 — Hệ sinh thái: marketplace_products/orders(+items), service_providers/orders,
 * loyalty_accounts/transactions, vouchers, real_estate_listings/listing_inquiries,
 * smart_home_accounts/smart_devices/smart_scenes/sensor_events/energy_readings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_resident_id')->nullable()->constrained('residents')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2)->default(0);
            $table->string('category')->nullable();
            $table->string('condition')->default('used'); // new|used
            $table->string('status')->default('active'); // active|sold|hidden
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_resident_id')->nullable()->constrained('residents')->nullOnDelete();
            $table->foreignId('seller_resident_id')->nullable()->constrained('residents')->nullOnDelete();
            $table->string('code')->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status')->default('pending'); // pending|paid|completed|cancelled
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable(); // cleaning|laundry|food|repair|beauty
            $table->string('phone')->nullable();
            $table->decimal('rating', 3, 1)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status')->default('pending'); // pending|confirmed|completed|cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('points_balance')->default(0);
            $table->string('tier')->default('silver'); // silver|gold|platinum
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('earn'); // earn|redeem
            $table->integer('points')->default(0);
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('transacted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->default('discount'); // discount|gift
            $table->decimal('value', 14, 2)->default(0);
            $table->unsignedInteger('points_cost')->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('real_estate_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_resident_id')->nullable()->constrained('residents')->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('type')->default('sale'); // sale|rent
            $table->string('title');
            $table->decimal('price', 16, 2)->default(0);
            $table->decimal('area', 8, 2)->nullable();
            $table->unsignedInteger('bedrooms')->nullable();
            $table->string('status')->default('active'); // active|pending|sold|rented|expired
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('listing_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('real_estate_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('message')->nullable();
            $table->string('status')->default('new'); // new|contacted|closed
            $table->timestamps();
        });

        Schema::create('smart_home_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->nullable(); // fpt|lumi|tuya|homeassistant
            $table->string('status')->default('active');
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('smart_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smart_home_account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('light'); // light|lock|ac|curtain|sensor|camera
            $table->string('room')->nullable();
            $table->string('status')->default('offline'); // on|off|online|offline
            $table->timestamps();
        });

        Schema::create('smart_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smart_home_account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sensor_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('smart_device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->nullable(); // motion|temperature|smoke|door
            $table->string('value')->nullable();
            $table->timestamp('event_at')->nullable();
            $table->timestamps();
        });

        Schema::create('energy_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('smart_home_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period')->nullable(); // 2026-06
            $table->decimal('kwh', 12, 2)->default(0);
            $table->decimal('cost', 14, 2)->default(0);
            $table->date('reading_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_readings');
        Schema::dropIfExists('sensor_events');
        Schema::dropIfExists('smart_scenes');
        Schema::dropIfExists('smart_devices');
        Schema::dropIfExists('smart_home_accounts');
        Schema::dropIfExists('listing_inquiries');
        Schema::dropIfExists('real_estate_listings');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_accounts');
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('service_providers');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('marketplace_products');
    }
};
