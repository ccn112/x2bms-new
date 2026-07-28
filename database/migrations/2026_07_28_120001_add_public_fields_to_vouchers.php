<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ưu đãi CÔNG KHAI (tab "Ưu đãi" của app cư dân, màn M01-PUB-14).
 *
 * Bảng `vouchers` vốn chỉ có name/type/value/points_cost — đủ cho ưu đãi nội bộ
 * nhưng KHÔNG đủ cho màn công khai: khuôn cần ảnh thương hiệu, mô tả, danh mục
 * (ăn uống · làm đẹp · giáo dục · mua sắm) và tên đối tác.
 *
 * `is_public` là cờ RIÊNG, không suy từ `owner_level`: một voucher platform vẫn
 * có thể chỉ dành cho nội bộ. Voucher platform đang có được bật sẵn vì bản chất
 * chúng đã là ưu đãi toàn nền tảng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('vouchers', 'partner_name')) {
                $table->string('partner_name')->nullable()->after('description');
            }
            if (! Schema::hasColumn('vouchers', 'category')) {
                // food | beauty | education | shopping | health | other
                $table->string('category', 32)->nullable()->after('partner_name');
            }
            if (! Schema::hasColumn('vouchers', 'image_url')) {
                $table->string('image_url', 1024)->nullable()->after('category');
            }
            if (! Schema::hasColumn('vouchers', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('image_url');
            }
        });

        if (Schema::hasColumn('vouchers', 'is_public')) {
            DB::table('vouchers')->where('owner_level', 'platform')->update(['is_public' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            foreach (['description', 'partner_name', 'category', 'image_url', 'is_public'] as $column) {
                if (Schema::hasColumn('vouchers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
