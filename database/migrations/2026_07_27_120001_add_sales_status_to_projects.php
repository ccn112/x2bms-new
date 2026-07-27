<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trạng thái BÁN HÀNG của dự án, hiển thị trên chip ở màn public
 * (khuôn `M01-PUB-02`: "Đang mở bán" / "Sắp bàn giao").
 *
 * ⚠️ KHÔNG dùng lại cột `projects.status` — cột đó là trạng thái VẬN HÀNH SaaS
 * (`active` / `trial` / `suspended`), hoàn toàn khác nghiệp vụ. Trước đây API
 * public map thẳng `status` xuống app nên chip luôn hiện "Dự án" (giá trị
 * `active` không khớp enum nào của app).
 *
 * Giá trị: `open_for_sale` · `handover_soon` · `handed_over` (khớp
 * `ProjectStatus` phía Flutter). NULL = suy ra từ `handover_date`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'sales_status')) {
                $table->string('sales_status', 24)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'sales_status')) {
                $table->dropColumn('sales_status');
            }
        });
    }
};
