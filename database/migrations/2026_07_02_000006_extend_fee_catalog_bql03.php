<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WEB-BQL-03-01 — display columns for the fee catalogue screen ("Biểu phí & quy
 * tắc tính phí"). The catalogue table needs applied-to target, collection
 * frequency, VAT %, a human formula string, effective date and a complex-formula
 * flag that are not on the base fee_types table. Add-only, nullable/defaulted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->string('applies_to')->nullable()->after('unit');       // Cư dân, Khách thuê, Văn phòng...
            $table->string('frequency')->default('monthly')->after('applies_to'); // monthly|per_use|quarterly|yearly
            $table->decimal('vat_percent', 5, 2)->default(0)->after('frequency');
            $table->string('formula_text')->nullable()->after('vat_percent'); // human-readable calc rule
            $table->date('effective_from')->nullable()->after('formula_text');
            $table->boolean('is_complex')->default(false)->after('effective_from'); // "Công thức phức tạp"
        });
    }

    public function down(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->dropColumn(['applies_to', 'frequency', 'vat_percent', 'formula_text', 'effective_from', 'is_complex']);
        });
    }
};
