<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vòng đời lưu trữ tenant (churn): active → dormant_archived (off, chỉ giữ bundle) →
 * purged (hết hạn retention). Điều khiển chặn đăng nhập + sweep tự động.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('lifecycle_status')->default('active')->after('name'); // active|dormant_archived|purged
            $table->timestamp('dormant_at')->nullable()->after('lifecycle_status');
            $table->date('retention_until')->nullable()->after('dormant_at'); // được phép purge sau mốc này
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['lifecycle_status', 'dormant_at', 'retention_until']);
        });
    }
};
