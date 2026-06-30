<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 3a — Fee catalog (WEB-FORM-06: loại phí / biểu giá / công thức / áp giá).
 *
 * Canonical (CANONICAL_ENTITY_MAP Tier 2): fee_types, fee_rates (alias
 * price_lists/price_list_items), fee_formulas (+ versions), fee_scope_assignments.
 * Additive only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('category')->default('management'); // management|parking|utility|service|other
            $table->string('unit')->default('per_sqm');        // per_sqm|per_unit|fixed|per_vehicle|per_m3
            $table->boolean('is_recurring')->default(true);
            $table->string('accounting_code')->nullable();
            $table->string('status')->default('active');        // active|inactive
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // A priced tariff for a fee type (effective-dated). Alias of price_lists/items.
        Schema::create('fee_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('unit')->nullable(); // mirrors fee_type.unit for display
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status')->default('active');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('fee_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('expression');           // e.g. "area_sqm * rate"
            $table->json('variables')->nullable(); // declared variables/metadata
            $table->string('status')->default('active');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('fee_formula_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_formula_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->text('expression');
            $table->date('effective_from')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Applies a fee type/rate to a scope: project / building / apartment.
        Schema::create('fee_scope_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope_type'); // project|building|apartment
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('effective_from')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_scope_assignments');
        Schema::dropIfExists('fee_formula_versions');
        Schema::dropIfExists('fee_formulas');
        Schema::dropIfExists('fee_rates');
        Schema::dropIfExists('fee_types');
    }
};
