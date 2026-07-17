<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BQL-01-06 (bản giàu thông tin, tham chiếu BQL-01-03 07-02): bổ sung cột nullable
 * cho tab "Thông tin căn hộ" chi tiết — hướng ban công, vị trí, nội thất, mục đích,
 * loại HĐ, số công tơ điện/nước/gas, danh sách tài liệu (json).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->string('balcony_direction')->nullable()->after('direction');
            $table->string('position')->nullable()->after('balcony_direction');       // vị trí căn: Giữa/Góc…
            $table->string('furniture_status')->nullable()->after('position');          // tình trạng nội thất
            $table->string('purpose')->nullable()->after('furniture_status');           // mục đích sử dụng
            $table->string('contract_type')->nullable()->after('ownership_term');        // loại hợp đồng
            $table->string('electric_meter_no')->nullable()->after('contract_type');
            $table->string('water_meter_no')->nullable()->after('electric_meter_no');
            $table->string('gas_meter_no')->nullable()->after('water_meter_no');
            $table->json('documents')->nullable()->after('gas_meter_no');               // [{name,type,size,date}]
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn([
                'balcony_direction', 'position', 'furniture_status', 'purpose',
                'contract_type', 'electric_meter_no', 'water_meter_no', 'gas_meter_no', 'documents',
            ]);
        });
    }
};
