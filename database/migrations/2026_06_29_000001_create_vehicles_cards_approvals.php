<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// WEB-02 — vehicles, access cards, resident approval requests (canonical names).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plate_no');
            $table->string('type')->default('motorbike'); // car|motorbike|bicycle
            $table->string('brand')->nullable();
            $table->string('parking_card_no')->nullable();
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->string('status')->default('active'); // active|inactive
            $table->date('valid_to')->nullable();
            $table->timestamps();
        });

        Schema::create('access_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('card_no');
            $table->string('type')->default('rfid'); // rfid|biometric
            $table->boolean('is_biometric')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('status')->default('active'); // active|revoked|expired
            $table->timestamps();
        });

        Schema::create('resident_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('requested_role')->default('owner'); // owner|tenant|member
            $table->unsignedTinyInteger('match_score')->default(0); // 0..100 data-match
            $table->unsignedSmallInteger('document_count')->default(0);
            $table->string('status')->default('pending'); // pending|approved|rejected|need_more
            $table->timestamp('submitted_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_approval_requests');
        Schema::dropIfExists('access_cards');
        Schema::dropIfExists('vehicles');
    }
};
