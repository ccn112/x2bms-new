<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Số lượt cài app lấy từ Google Play + App Store (chốt 2026-07-30).
 *
 * KHÔNG có tenant_id: đây là số liệu của **một ứng dụng trên store**, thuộc nhà
 * cung cấp phần mềm (tầng SuperAdmin), không thuộc công ty quản lý nào. Một cư
 * dân của tenant A và một của tenant B cùng tải chung một app — không có cách nào
 * chia số lượt cài của store theo tenant.
 *
 * Vì vậy trong báo cáo phải gọi đúng tên: đây là **"số lượt cài (từ store)"**, KHÁC
 * với **"số thiết bị đã đăng ký"** (bảng `mobile_devices`, có `user_id`, chia được
 * theo tenant/dự án). Trộn hai con số là báo cáo sai.
 *
 * Khoá duy nhất (source, stat_date): mỗi ngày mỗi store một dòng. Cả hai store đều
 * chốt số chậm và **sửa lại số của những ngày trước**, nên đồng bộ dùng
 * updateOrCreate theo khoá này chứ không insert thêm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_install_stats', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);              // google_play | app_store
            $table->date('stat_date');

            // Google trả đủ cả 4; Apple chỉ có `installs` (cột Units của báo cáo
            // SALES/SUMMARY) — các cột còn lại để null, KHÔNG điền 0, vì 0 và
            // "không có dữ liệu" là hai chuyện khác nhau khi vẽ biểu đồ.
            $table->unsignedInteger('installs')->nullable();
            $table->unsignedInteger('uninstalls')->nullable();
            $table->unsignedInteger('updates')->nullable();
            $table->unsignedBigInteger('active_devices')->nullable();

            // Giữ nguyên dòng thô đã bóc được, để còn đối chiếu khi số liệu trông
            // sai mà không phải tải lại báo cáo từ store.
            $table->json('raw')->nullable();

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'stat_date']);
            $table->index('stat_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_install_stats');
    }
};
