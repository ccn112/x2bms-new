<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier 3 — An ninh & thiết bị: patrol_routes(+checkpoints,+sessions), security_incidents,
 * sos_alerts, access_devices, cameras, alert_actions (hành động trên ioc_alerts, C10).
 * Scope 3 lớp qua tenant_id + project_id + building_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrol_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('expected_minutes')->default(30);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('patrol_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_route_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('qr_code')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('patrol_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('in_progress'); // in_progress|completed|missed
            $table->unsignedInteger('checkpoints_scanned')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('type')->default('other'); // theft|fight|fire|trespass|vandalism|other
            $table->string('severity')->default('medium'); // low|medium|high|critical
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('open'); // open|investigating|resolved|closed
            $table->foreignId('reported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('app'); // app|panic_button|intercom
            $table->string('status')->default('triggered'); // triggered|acknowledged|responding|resolved|false_alarm
            $table->string('location')->nullable();
            $table->timestamp('triggered_at')->nullable();
            $table->foreignId('acknowledged_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('access_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('type')->default('card_reader'); // card_reader|face|barrier|turnstile|door
            $table->string('location')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('status')->default('online'); // online|offline|maintenance
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('type')->default('dome'); // dome|bullet|ptz
            $table->string('stream_url')->nullable();
            $table->string('status')->default('online'); // online|offline
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('alert_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ioc_alert_id')->constrained()->cascadeOnDelete();
            $table->string('action')->default('acknowledge'); // acknowledge|dispatch|resolve|comment
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_actions');
        Schema::dropIfExists('cameras');
        Schema::dropIfExists('access_devices');
        Schema::dropIfExists('sos_alerts');
        Schema::dropIfExists('security_incidents');
        Schema::dropIfExists('patrol_sessions');
        Schema::dropIfExists('patrol_checkpoints');
        Schema::dropIfExists('patrol_routes');
    }
};
