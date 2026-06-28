<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Operations — work_orders / sla_events / ioc_alerts (canonical, see C4/C10).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('feedback_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('title');
            $table->string('status')->default('pending'); // pending|in_progress|done|overdue
            $table->string('priority')->default('normal');
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sla_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('subject'); // feedback_request / work_order
            $table->string('type')->default('breach'); // due_soon|breach
            $table->string('status')->default('open'); // open|resolved
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('ioc_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('source')->nullable(); // device / camera / meter
            $table->string('severity')->default('warning'); // info|warning|critical
            $table->string('title');
            $table->string('status')->default('open'); // open|ack|resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ioc_alerts');
        Schema::dropIfExists('sla_events');
        Schema::dropIfExists('work_orders');
    }
};
