<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQL-01-06 (Chi tiết căn hộ 360): bổ sung cột nullable cho các field thiết kế
 * chưa có nơi lưu — giá trị bàn giao, hợp đồng sở hữu, phân loại cư trú.
 * Nullable + không backfill → an toàn với dữ liệu seed hiện có.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->decimal('handover_price', 15, 2)->nullable()->after('management_fee'); // giá trị bàn giao (chưa VAT)
            $table->string('contract_no')->nullable()->after('handover_price');             // số hợp đồng sở hữu
            $table->date('contract_signed_at')->nullable()->after('contract_no');           // ngày ký HĐ
            $table->string('ownership_term')->nullable()->after('contract_signed_at');       // thời hạn (vd "Lâu dài")
        });

        Schema::table('residents', function (Blueprint $table) {
            // Phân loại cư trú cho donut Tình trạng cư trú: permanent|temporary|absent
            $table->string('residence_status')->nullable()->after('kyc_status');
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn(['handover_price', 'contract_no', 'contract_signed_at', 'ownership_term']);
        });

        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn('residence_status');
        });
    }
};
