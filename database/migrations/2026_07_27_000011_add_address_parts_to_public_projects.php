<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Addendum — tách địa chỉ dự án thành cấu trúc phường/quận + toạ độ bản đồ.
 * `province` (đã có) giữ nguyên. Idempotent (hasColumn guard).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('public_projects', 'ward')) {
                $table->string('ward')->nullable()->after('address');      // Phường/Xã/Thị trấn
            }
            if (! Schema::hasColumn('public_projects', 'district')) {
                $table->string('district')->nullable()->after('ward');     // Quận/Huyện/Thành phố/Thị xã
            }
            if (! Schema::hasColumn('public_projects', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('province');
            }
            if (! Schema::hasColumn('public_projects', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('public_projects', function (Blueprint $table) {
            foreach (['ward', 'district', 'latitude', 'longitude'] as $c) {
                if (Schema::hasColumn('public_projects', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
