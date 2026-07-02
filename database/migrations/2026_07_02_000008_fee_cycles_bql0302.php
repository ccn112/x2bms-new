<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-BQL-03-02 — the fee-cycle list ("Chu kỳ phí & đợt thu") shows one cycle per
 * fee type per month (CP-2026-07-DV, -XE, -DN…), richer than the monthly
 * billing_periods used by the statement backbone. Add display columns so those
 * cycle rows carry a name, fee category, scope label and expected totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_periods', function (Blueprint $table) {
            $table->string('name')->nullable()->after('label');
            $table->string('fee_category')->nullable()->after('name');    // Phí quản lý / Phí gửi xe / Điện nước / Phí dịch vụ
            $table->string('scope_label')->nullable()->after('fee_category');
            $table->unsignedInteger('expected_units')->nullable()->after('scope_label');
            $table->decimal('expected_amount', 18, 2)->nullable()->after('expected_units');
        });
    }

    public function down(): void
    {
        Schema::table('billing_periods', function (Blueprint $table) {
            $table->dropColumn(['name', 'fee_category', 'scope_label', 'expected_units', 'expected_amount']);
        });
    }
};
