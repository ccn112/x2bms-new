<?php

/**
 * Cấu hình "Tìm ảnh & thông tin" chính thống cho dự án (ProjectEnrichmentService).
 * provider = mock (mặc định, không cần key) | google_cse | serpapi.
 * Admin duyệt ứng viên trước khi lưu → metadata_json.official_*.
 */
return [
    'provider' => env('ENRICH_PROVIDER', 'mock'),

    'google_cse' => [
        'key' => env('GOOGLE_CSE_KEY'),
        'cx'  => env('GOOGLE_CSE_CX'),
    ],

    'serpapi' => [
        'key' => env('SERPAPI_KEY'),
    ],

    'max_images' => 8,
    'max_info'   => 5,
    'timeout'    => 20,
];
