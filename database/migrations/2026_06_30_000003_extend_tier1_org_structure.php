<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 2 — complete Tier-1 org structure to back the create forms
 * WEB-FORM-01-01..04 / 03-01 / 03-04.
 *
 * Adds the rich columns the thin foundation tables were missing (tenant company
 * profile, project legal/scale/contacts, building scale, apartment attributes)
 * and the still-missing Tier-1 tables: companies, blocks,
 * apartment_status_histories, staff_profiles, teams.
 *
 * Pure additive — no existing migration is edited (canonical rule).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Operating company under a tenant (a tenant/SaaS customer may run several).
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('legal_representative')->nullable();
            $table->string('status')->default('active'); // active|inactive
            $table->timestamps();
        });

        // Block / phân khu inside a project (groups buildings).
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // --- tenants: management-company profile (form 01-01 sections) ---
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('short_name')->nullable()->after('name');
            $table->string('tax_code')->nullable()->after('short_name');
            $table->string('phone')->nullable()->after('tax_code');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('address')->nullable()->after('website');
            $table->string('city')->nullable()->after('address');
            $table->string('legal_representative')->nullable()->after('city');
            $table->string('contact_person')->nullable()->after('legal_representative');
            $table->string('contact_phone')->nullable()->after('contact_person');
            $table->string('plan')->default('standard')->after('contact_phone'); // trial|standard|enterprise
            $table->string('status')->default('active')->after('plan');          // active|inactive|trial
            $table->string('logo_path')->nullable()->after('status');
            $table->string('primary_color')->nullable()->after('logo_path');
            $table->string('secondary_color')->nullable()->after('primary_color');
            $table->json('app_config')->nullable()->after('secondary_color');
            $table->text('note')->nullable()->after('app_config');
        });

        // --- projects: scale, legal, location, contacts (form 01-02) ---
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->string('type')->default('apartment')->after('name'); // apartment|urban_area|complex|office
            $table->string('status')->default('active')->after('type');  // planning|construction|active|inactive
            $table->string('address')->nullable()->after('status');
            $table->string('ward')->nullable()->after('address');
            $table->string('district')->nullable()->after('ward');
            $table->string('city')->nullable()->after('district');
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('land_area_sqm', 12, 2)->nullable()->after('longitude');
            $table->unsignedInteger('building_count')->default(0)->after('land_area_sqm');
            $table->unsignedInteger('apartment_count')->default(0)->after('building_count');
            $table->string('investor')->nullable()->after('apartment_count');     // chủ đầu tư
            $table->string('legal_no')->nullable()->after('investor');
            $table->date('handover_date')->nullable()->after('legal_no');
            $table->string('contact_person')->nullable()->after('handover_date');
            $table->string('contact_phone')->nullable()->after('contact_person');
            $table->text('description')->nullable()->after('contact_phone');
        });

        // --- buildings: block link + scale (form 01-03) ---
        Schema::table('buildings', function (Blueprint $table) {
            $table->foreignId('block_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->string('type')->default('residential')->after('name'); // residential|office|mixed
            $table->string('status')->default('active')->after('type');     // active|inactive|construction
            $table->string('address')->nullable()->after('status');
            $table->unsignedInteger('floor_count')->default(0)->after('apartment_count');
            $table->unsignedInteger('basement_count')->default(0)->after('floor_count');
            $table->unsignedInteger('elevator_count')->default(0)->after('basement_count');
            $table->date('handover_date')->nullable()->after('elevator_count');
            $table->text('note')->nullable()->after('handover_date');
        });

        // --- apartments: layout attributes (form 01-04) ---
        Schema::table('apartments', function (Blueprint $table) {
            $table->unsignedTinyInteger('bedroom_count')->nullable()->after('area_sqm');
            $table->unsignedTinyInteger('bathroom_count')->nullable()->after('bedroom_count');
            $table->string('direction')->nullable()->after('bathroom_count'); // hướng căn
        });

        // Status change history (form 01-04 "trạng thái căn").
        Schema::create('apartment_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();
        });

        // Staff HR profile (1:1 user) — form 03-01.
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code')->nullable();
            $table->string('position')->nullable();  // chức danh
            $table->string('phone')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('id_no')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('status')->default('active'); // active|suspended|left
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Team within a project/department — form 03-04 scope assignment.
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('apartment_status_histories');

        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn(['bedroom_count', 'bathroom_count', 'direction']);
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('block_id');
            $table->dropColumn(['type', 'status', 'address', 'floor_count', 'basement_count', 'elevator_count', 'handover_date', 'note']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['type', 'status', 'address', 'ward', 'district', 'city', 'latitude', 'longitude', 'land_area_sqm', 'building_count', 'apartment_count', 'investor', 'legal_no', 'handover_date', 'contact_person', 'contact_phone', 'description']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['short_name', 'tax_code', 'phone', 'email', 'website', 'address', 'city', 'legal_representative', 'contact_person', 'contact_phone', 'plan', 'status', 'logo_path', 'primary_color', 'secondary_color', 'app_config', 'note']);
        });

        Schema::dropIfExists('blocks');
        Schema::dropIfExists('companies');
    }
};
