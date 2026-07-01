<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 2 — vá nốt các entity còn thiếu (CANONICAL_ENTITY_MAP):
 * emergency_alerts, qr_payment_tokens, service_evaluations, access_logs, intercom_events.
 * Scope 3 lớp qua tenant_id + project_id + building_id nơi phù hợp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('type')->default('other');      // fire|flood|security|health|other
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('severity')->default('warning'); // info|warning|critical
            $table->string('status')->default('active');    // active|resolved|cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('qr_payment_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('statement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('debt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('provider')->default('vietqr'); // vietqr|momo|zalopay|vnpay
            $table->string('status')->default('active');   // active|used|expired|cancelled
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('service_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feedback_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating')->default(5); // 1..5
            $table->json('criteria')->nullable();               // {tinh_than:5, thoi_gian:4, ...}
            $table->string('comment')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visitor_pass_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('access_card_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_name')->nullable();
            $table->string('gate')->nullable();
            $table->string('direction')->default('in');   // in|out
            $table->string('method')->default('card');    // card|qr|face|plate|manual
            $table->string('status')->default('granted'); // granted|denied
            $table->timestamp('event_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'event_at']);
        });

        Schema::create('intercom_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_device')->nullable();     // lobby_gate|door_station
            $table->string('direction')->default('incoming'); // incoming|outgoing
            $table->string('status')->default('answered'); // answered|missed|rejected
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('event_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intercom_events');
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('service_evaluations');
        Schema::dropIfExists('qr_payment_tokens');
        Schema::dropIfExists('emergency_alerts');
    }
};
