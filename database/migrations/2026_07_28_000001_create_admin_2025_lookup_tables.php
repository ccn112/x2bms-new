<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỊA CHỈ MỚI 2025 — bảng tra cứu đơn vị hành chính sau Nghị quyết 202/2025/QH15
 * (bỏ cấp huyện, 63 -> 34 tỉnh/thành). Chỉ tạo bảng tra cứu MỚI, không đụng cột địa chỉ gốc.
 *
 * - admin_provinces_2025    : 34 tỉnh/thành mới (mã BNV).
 * - admin_wards_2025        : xã/phường mới, thuộc tỉnh mới (không còn cấp huyện).
 * - admin_old_provinces_2025: tỉnh CŨ (63) -> trỏ tới tỉnh mới (map hành chính cấp tỉnh).
 * - admin_old_to_new        : đơn vị cũ (quận/huyện cũ) -> xã/phường mới + tỉnh mới.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_provinces_2025')) {
            Schema::create('admin_provinces_2025', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();      // mã BNV, vd "01"
                $table->string('full_name');                // "Thành phố Hà Nội"
                $table->string('name_norm')->index();       // chuẩn hoá không dấu, bỏ tiền tố
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_wards_2025')) {
            Schema::create('admin_wards_2025', function (Blueprint $table) {
                $table->id();
                $table->string('code', 12)->unique();       // mã phường/xã mới
                $table->string('full_name');                // "Phường Hoàn Kiếm"
                $table->string('name_norm')->index();
                $table->string('province_code', 10)->index();
                $table->string('province_name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_old_provinces_2025')) {
            Schema::create('admin_old_provinces_2025', function (Blueprint $table) {
                $table->id();
                $table->string('old_name');                 // "Bình Dương"
                $table->string('old_name_norm')->index();   // "binh duong"
                $table->string('new_province_code', 10);
                $table->string('new_province_name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_old_to_new')) {
            Schema::create('admin_old_to_new', function (Blueprint $table) {
                $table->id();
                $table->string('old_district_name');            // "Quận Hoàng Mai"
                $table->string('old_district_norm')->index();   // "hoang mai"
                $table->string('new_province_code', 10)->index();
                $table->string('new_province_name');
                $table->string('new_ward_code', 12)->nullable();
                $table->string('new_ward_name');                // "Phường Hoàng Mai"
                $table->string('new_ward_norm')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_old_to_new');
        Schema::dropIfExists('admin_old_provinces_2025');
        Schema::dropIfExists('admin_wards_2025');
        Schema::dropIfExists('admin_provinces_2025');
    }
};
