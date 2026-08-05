<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cho phép XÓA MỀM quan hệ cư dân↔căn hộ để cascade soft-delete + restore nhất quán
 * khi xóa/khôi phục căn hộ hoặc cư dân (ApartmentObserver/ResidentObserver).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resident_apartment_relations', function (Blueprint $table) {
            if (! Schema::hasColumn('resident_apartment_relations', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('resident_apartment_relations', function (Blueprint $table) {
            if (Schema::hasColumn('resident_apartment_relations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
