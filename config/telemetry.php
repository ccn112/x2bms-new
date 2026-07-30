<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nhật ký màn hình của app
    |--------------------------------------------------------------------------
    |
    | Chủ dự án chốt 30/07: ghi theo thiết bị, có user thì gắn kèm, gửi theo lô
    | định kỳ, và "lưu lại hành vi theo thời gian quy định". Hạn lưu dưới đây LÀ
    | "thời gian quy định" đó — phải khớp với những gì nói trong Điều khoản sử
    | dụng (xem docs Track 4 / PlatformContent slug dieu-khoan-su-dung).
    */

    // Bảng thô `app_screen_events` bị dọn sau bao nhiêu ngày. 90 ngày đủ để soi
    // một lỗi theo mùa vụ mà không phình DB. Bảng TỔNG HỢP theo ngày giữ mãi nên
    // biểu đồ dài hạn không mất.
    'raw_retention_days' => (int) env('TELEMETRY_RAW_RETENTION_DAYS', 90),

    // Báo lỗi của cư dân giữ lâu hơn: còn tra lại được lịch sử một lỗi đã xử lý.
    'report_retention_days' => (int) env('TELEMETRY_REPORT_RETENTION_DAYS', 730),

    // Chặn lô quá lớn. App gom lô rồi gửi định kỳ, một lô bình thường vài chục
    // sự kiện; giới hạn để một client lỗi không đẩy được hàng chục nghìn dòng
    // trong một request.
    'max_batch_size' => (int) env('TELEMETRY_MAX_BATCH', 200),

    // Sự kiện cũ hơn mốc này thì bỏ, không ghi. App bị kill rồi mở lại sau nhiều
    // ngày vẫn còn lô cũ trong máy — ghi vào thì làm lệch số liệu của ngày hôm nay.
    'max_event_age_days' => (int) env('TELEMETRY_MAX_EVENT_AGE_DAYS', 7),

];
