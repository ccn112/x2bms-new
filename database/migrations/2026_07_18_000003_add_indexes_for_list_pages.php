<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the Apartment/Resident directory hot paths (docs/PERF_LIST_PAGES_OPTIMIZATION.md):
 * holder lookup + resident_count group by apartment_id/role, and overdue-debt sum/count.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resident_apartment_relations', function (Blueprint $table) {
            $table->index(['apartment_id', 'role'], 'rar_apartment_role_idx');
        });
        Schema::table('debts', function (Blueprint $table) {
            $table->index(['apartment_id', 'is_overdue'], 'debts_apartment_overdue_idx');
        });
    }

    public function down(): void
    {
        Schema::table('resident_apartment_relations', function (Blueprint $table) {
            $table->dropIndex('rar_apartment_role_idx');
        });
        Schema::table('debts', function (Blueprint $table) {
            $table->dropIndex('debts_apartment_overdue_idx');
        });
    }
};
