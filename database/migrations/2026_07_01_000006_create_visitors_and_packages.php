<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 2 — Khách/visitor (canonical C12: visitor_registrations = đăng ký, visitor_passes
 * = pass/QR phát hành) + package_deliveries (bưu kiện). Scope 3 lớp qua
 * tenant_id + project_id + building_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('visitor_name');
            $table->string('visitor_phone')->nullable();
            $table->string('id_no')->nullable();
            $table->string('purpose')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->unsignedInteger('num_guests')->default(1);
            $table->timestamp('expected_at')->nullable();
            $table->timestamp('expected_leave_at')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected|checked_in|checked_out|expired
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('visitor_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_registration_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->string('status')->default('active'); // active|used|expired|revoked
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('gate')->nullable();
            $table->timestamps();
        });

        Schema::create('package_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_no')->nullable();
            $table->string('carrier')->nullable();       // GHTK|GHN|VNPost|Shopee...
            $table->string('sender')->nullable();
            $table->string('description')->nullable();
            $table->string('size')->default('small');     // small|medium|large|bulky
            $table->string('locker_no')->nullable();
            $table->string('status')->default('received'); // received|notified|picked_up|returned
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picked_up_at')->nullable();
            $table->string('picked_up_by')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('apartment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_deliveries');
        Schema::dropIfExists('visitor_passes');
        Schema::dropIfExists('visitor_registrations');
    }
};
