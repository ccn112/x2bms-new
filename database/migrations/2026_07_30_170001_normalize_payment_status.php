<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `payments.status` đang có BA vốn từ song song — đo trên DB dev 30/07:
 * `confirmed` 9 · `completed` 2 · `pending` 2.
 *
 * `confirmed` là giá trị chốt (đúng như migration gốc
 * `create_payments_and_reconciliation` ghi trong ghi chú cột, và là giá trị mà
 * code nghiệp vụ đọc). `completed` chỉ do MỘT chỗ sinh ra:
 * `ResidentDemoContentSeeder` — đã sửa cùng lượt này.
 *
 * Vì sao phải dọn chứ không bỏ qua: hàng chờ duyệt của BQL lọc theo
 * `status = 'pending'` và báo cáo đã thu lọc `confirmed`; khoản mang
 * `completed` sẽ **không lọt vào nhóm nào** — tức tiền đã thu mà không hiện ở
 * đâu. Đúng loại lỗi đã gặp với `events.status = 'published'` làm sự kiện BQL
 * tạo không lên được app.
 *
 * Không có `down()` khôi phục: không phân biệt được bản ghi nào vốn là
 * `completed` với bản ghi vốn đã là `confirmed`. Muốn lùi thì nạp lại bản dump
 * trước khi deploy (xem docs/DEPLOY_VPS_UPDATE_RUNBOOK.md §2).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')->where('status', 'completed')
            ->update(['status' => 'confirmed']);
    }

    public function down(): void
    {
        // Cố ý để trống — xem ghi chú ở đầu file.
    }
};
