<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum — Thư viện đối tác dùng chung (platform): shared_partner_categories,
 * shared_partners(+certifications,+products), tenant_partner_assignments.
 * Khác `contractors`/`service_providers` (per-tenant) — đây là kho dùng chung toàn nền tảng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_partner_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('partner_type')->default('contractor'); // contractor|supplier|service_provider
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shared_partners', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('partner_type')->default('contractor');
            $table->foreignId('category_id')->nullable()->constrained('shared_partner_categories')->nullOnDelete();
            $table->string('tax_code')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('service_area')->nullable();
            $table->string('verification_status')->default('unverified'); // unverified|verified|preferred|blacklisted
            $table->decimal('rating_avg', 3, 1)->default(0);
            $table->decimal('kpi_score', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('metadata_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['partner_type', 'verification_status']);
        });

        Schema::create('shared_partner_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('shared_partners')->cascadeOnDelete();
            $table->string('name');
            $table->string('certificate_no')->nullable();
            $table->string('issued_by')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expired_at')->nullable();
            $table->string('file_url')->nullable();
            $table->timestamps();
        });

        Schema::create('shared_partner_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('shared_partners')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('reference_price', 14, 2)->default(0);
            $table->unsignedInteger('warranty_months')->default(0);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenant_partner_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_id')->constrained('shared_partners')->cascadeOnDelete();
            $table->string('assignment_type')->default('approved_vendor'); // approved_vendor|contracted_vendor|blacklist|favorite
            $table->string('contract_no')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_partner_assignments');
        Schema::dropIfExists('shared_partner_products');
        Schema::dropIfExists('shared_partner_certifications');
        Schema::dropIfExists('shared_partners');
        Schema::dropIfExists('shared_partner_categories');
    }
};
