<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Chuẩn hoá `events.status` về đúng tập giá trị của schema.
 *
 * Migration gốc (`2026_07_01_000015_create_handover_community.php`) khai báo:
 *
 *     $table->string('status')->default('upcoming'); // upcoming|ongoing|finished|cancelled
 *
 * Không có `published` trong tập đó. Giá trị này lọt vào qua hai seeder chép
 * nhầm quy ước của các bảng NỘI DUNG (notifications, articles, community_posts —
 * ở đó `published` là đúng). Hệ quả nghiêm trọng hơn rác dữ liệu: endpoint cư
 * dân lọc `where('status', 'published')`, trong khi form Filament tạo sự kiện
 * mặc định `upcoming` — nên MỌI sự kiện do Ban quản lý tạo qua web đều không cư
 * dân nào xem được.
 *
 * `published` ↔ `upcoming` là ánh xạ đúng: cả hai đều mang nghĩa "đã công bố,
 * chưa diễn ra".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('events')->where('status', 'published')->update(['status' => 'upcoming']);
    }

    public function down(): void
    {
        // KHÔNG khôi phục `published`: nó chưa bao giờ là giá trị hợp lệ của cột
        // này. Rollback rồi ghi lại giá trị sai là mang lỗi trở lại.
    }
};
