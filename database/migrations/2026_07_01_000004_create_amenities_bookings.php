<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 2 — Amenity / Booking (canonical: amenities, amenity_slots, amenity_bookings,
 * booking_qr_passes). Scope 3 lớp qua tenant_id + project_id + building_id để RBAC lọc
 * (platform: tất cả · công ty: toàn tenant · BQL: dự án của mình).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('type')->default('common');   // gym|pool|bbq|function_room|tennis|common
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->string('open_time')->nullable();      // 06:00
            $table->string('close_time')->nullable();     // 22:00
            $table->string('booking_unit')->default('slot'); // slot|hour|day
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('requires_approval')->default(false);
            $table->string('status')->default('active');  // active|inactive|maintenance
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'project_id']);
        });

        Schema::create('amenity_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0..6, null = mọi ngày
            $table->string('start_time');   // 06:00
            $table->string('end_time');     // 08:00
            $table->unsignedInteger('capacity')->default(1);
            $table->string('status')->default('open'); // open|closed
            $table->timestamps();
        });

        Schema::create('amenity_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->date('booking_date');
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->unsignedInteger('party_size')->default(1);
            $table->string('status')->default('pending'); // pending|confirmed|rejected|cancelled|completed|no_show
            $table->decimal('price', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['amenity_id', 'booking_date']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('booking_qr_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amenity_booking_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->string('status')->default('active'); // active|used|expired|revoked
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_qr_passes');
        Schema::dropIfExists('amenity_bookings');
        Schema::dropIfExists('amenity_slots');
        Schema::dropIfExists('amenities');
    }
};
