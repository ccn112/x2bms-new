<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Các bảng thuộc tenant (xuất theo tenant_id) khi backup/export
    |--------------------------------------------------------------------------
    |
    | Dịch vụ backup sẽ xuất từng bảng dưới đây lọc theo `tenant_id` (chỉ xuất bảng
    | THẬT SỰ có cột tenant_id — tự bỏ qua nếu không có). Thêm dần khi mở rộng.
    | Đây là backup LOGIC (NDJSON) để tenant mang đi / khôi phục — độc lập DB engine.
    |
    */
    'tables' => [
        'projects',
        'buildings',
        'floors',
        'apartments',
        'residents',
        'resident_apartment_relations',
        'resident_emergency_contacts',
        'vehicles',
        'access_cards',
        'import_batches',
        'import_batch_rows',
        'audit_logs',
    ],

    /*
    | Số bản ghi ghi ra mỗi lần (cursor) — an toàn bộ nhớ.
    */
    'chunk' => (int) env('TENANT_BACKUP_CHUNK', 1000),

    /*
    | Số ngày giữ bundle sau khi off (dormant) trước khi được phép purge.
    | Mặc định ~3 năm (đủ cho kịch bản off 2 năm rồi resume).
    */
    'retention_days' => (int) env('TENANT_RETENTION_DAYS', 1095),

    /*
    | Số ngày ân hạn sau khi thuê bao hết hạn trước khi tự động off (dormant).
    */
    'grace_days' => (int) env('TENANT_GRACE_DAYS', 60),

];
