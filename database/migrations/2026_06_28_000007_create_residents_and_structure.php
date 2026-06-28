<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tier-1 completion: floors, areas/zones, residents, resident↔apartment relations.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->integer('level');
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type')->default('common'); // common|parking|amenity|technical
            $table->timestamps();
        });

        Schema::table('apartments', function (Blueprint $table) {
            $table->foreignId('floor_id')->nullable()->after('building_id')->constrained()->nullOnDelete();
            $table->decimal('area_sqm', 8, 2)->nullable()->after('status');
        });

        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // login account if activated
            $table->string('code');
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('id_no')->nullable();
            $table->string('status')->default('active'); // active|pending|inactive
            $table->timestamps();
        });

        Schema::create('resident_apartment_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('owner'); // owner|tenant|member
            $table->boolean('is_primary')->default(false);
            $table->date('start_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_apartment_relations');
        Schema::dropIfExists('residents');
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('floor_id');
            $table->dropColumn('area_sqm');
        });
        Schema::dropIfExists('areas');
        Schema::dropIfExists('floors');
    }
};
