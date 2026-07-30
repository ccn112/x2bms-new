<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mốc "cư dân huỷ" cho dòng thời gian phiếu đặt tiện ích (app cư dân,
 * BookingDetailScreen). `approved_at` có sẵn dùng chung cho lúc BQL xác nhận
 * HOẶC từ chối (một quyết định, một thời điểm); nhưng huỷ là hành động của
 * CƯ DÂN chứ không phải BQL, gộp chung vào approved_at sẽ lẫn hai chủ thể
 * khác nhau vào một cột — nên cần cột riêng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenity_bookings', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('amenity_bookings', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });
    }
};
