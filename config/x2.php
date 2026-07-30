<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Múi giờ NGHIỆP VỤ
    |--------------------------------------------------------------------------
    |
    | Múi giờ mà người dùng thật đang sống (chủ dự án chốt 2026-07-30: Việt Nam,
    | UTC+7). Dùng khi NHẬN một mốc thời gian do người dùng nhập mà không kèm múi
    | giờ, và khi hiển thị/nhóm theo ngày cho người Việt đọc.
    |
    | KHÁC với `config('app.timezone')` — cái đó vẫn phải là **UTC** và đừng đổi:
    | Laravel ghi mọi timestamp vào MySQL theo app.timezone, nên toàn bộ dữ liệu
    | hiện có (1.360 hoá đơn, mọi mốc payments/receipts/audit) đã được ghi dưới
    | dạng UTC. Đổi app.timezone sang Asia/Ho_Chi_Minh sẽ làm MỌI mốc lịch sử bị
    | đọc lệch 7 tiếng — biên lai ghi 14:00 hôm nay thành 21:00, và không có cách
    | phân biệt bản ghi cũ với bản ghi mới.
    |
    | Muốn đổi múi giờ nghiệp vụ (mở rộng sang nước khác) thì đổi ở đây, hoặc set
    | X2_BUSINESS_TIMEZONE trong .env.
    */
    'timezone' => env('X2_BUSINESS_TIMEZONE', 'Asia/Ho_Chi_Minh'),

];
