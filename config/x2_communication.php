<?php

/*
|--------------------------------------------------------------------------
| BQL Communication (BQL-NOTI) — cấu hình tuyến duyệt, giá kênh, quiet hours
|--------------------------------------------------------------------------
| Tuyến duyệt là CONFIG (không hardcode role trong service — spec 09). Có thể
| override per-tenant sau này. Giá kênh cho ước tính chi phí (spec 04 §Cost);
| chi phí THẬT lưu từ provider response ở notification_delivery_logs.cost.
*/

return [

    // Tuyến duyệt, chọn theo điều kiện; route đầu tiên khớp sẽ áp (thứ tự = ưu tiên).
    'approval_routes' => [
        [
            'key' => 'approval-emergency',
            'name' => 'Tuyến cảnh báo khẩn cấp',
            'conditions' => ['priority' => ['urgent', 'emergency']],
            'steps' => [
                ['role' => 'bql_manager', 'sla_minutes' => 15],
                ['role' => 'project_director', 'sla_minutes' => 15],
            ],
            'allow_quiet_hours_bypass' => true,
        ],
        [
            'key' => 'approval-paid-channel',
            'name' => 'Tuyến có chi phí kênh',
            'conditions' => ['paid_cost_gt' => 500000],
            'steps' => [
                ['role' => 'bql_manager', 'sla_hours' => 2],
                ['role' => 'project_director', 'sla_hours' => 4],
            ],
        ],
        [
            'key' => 'approval-large-audience',
            'name' => 'Tuyến chiến dịch lớn',
            'conditions' => ['min_audience' => 5001],
            'steps' => [
                ['role' => 'bql_manager', 'sla_hours' => 2],
                ['role' => 'project_director', 'sla_hours' => 4],
            ],
        ],
        [
            'key' => 'approval-default',
            'name' => 'Tuyến duyệt mặc định',
            'conditions' => [], // fallback
            'steps' => [
                ['role' => 'bql_manager', 'sla_hours' => 2],
            ],
        ],
    ],

    // Giá ước tính per (người × kênh), VND số nguyên. Kênh miễn phí = 0.
    'channel_pricing' => [
        'app' => 0, 'push' => 0, 'email' => 0, 'web' => 0,
        'sms' => 800, 'zalo' => 300, 'whatsapp' => 0, 'telegram' => 0, 'xspace' => 0, 'postal' => 0,
    ],

    // Kênh tính phí (dùng để chọn tuyến duyệt paid-channel + cảnh báo cost).
    'paid_channels' => ['sms', 'zalo', 'whatsapp'],

    // Giờ yên tĩnh (giờ nghiệp vụ, config('x2.timezone')). Nội dung thường tôn trọng;
    // khẩn cấp có thể bypass nếu route allow + có xác nhận.
    'quiet_hours' => ['start' => '22:00', 'end' => '07:00'],
];
