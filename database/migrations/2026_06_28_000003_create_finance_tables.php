<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Finance — billing_periods / statements / statement_lines / debts (canonical, see C1/C8).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('code');           // e.g. 2026-07
            $table->string('label');          // e.g. Tháng 7/2026
            $table->date('period_month');
            $table->decimal('billed_amount', 16, 2)->default(0);
            $table->decimal('collected_amount', 16, 2)->default(0);
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->decimal('paid_amount', 16, 2)->default(0);
            $table->string('status')->default('issued'); // issued|partial|paid
            $table->timestamps();
        });

        Schema::create('statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_id')->constrained()->cascadeOnDelete();
            $table->string('fee_type');
            $table->decimal('amount', 16, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 16, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
        Schema::dropIfExists('statement_lines');
        Schema::dropIfExists('statements');
        Schema::dropIfExists('billing_periods');
    }
};
