<?php

/**
 * Cấu hình thu thập metadata dự án từ batdongsan.com.vn.
 *
 * Dùng bởi App\Services\Projects\BdsProjectImporter (nút "Lấy tiếp" ở /sa
 * và command projects:fetch-more). Chỉ lấy METADATA thư mục công khai.
 */
return [

    // Gốc site + các khu vực đã kiểm chứng (slug trang lọc theo tỉnh/TP).
    'base_url' => 'https://batdongsan.com.vn/',

    'cities' => [
        'ha-noi'  => ['label' => 'Hà Nội',          'slug' => 'du-an-bat-dong-san-ha-noi',  'province' => 'Hà Nội'],
        'tp-hcm'  => ['label' => 'TP. Hồ Chí Minh', 'slug' => 'du-an-bat-dong-san-tp-hcm',  'province' => 'TP. Hồ Chí Minh'],
        'da-nang' => ['label' => 'Đà Nẵng',         'slug' => 'du-an-bat-dong-san-da-nang', 'province' => 'Đà Nẵng'],
        // Phú Quốc: nếu slug này rỗng/404, service tự thử fallback 'du-an-bat-dong-san-kien-giang'.
        'phu-quoc' => ['label' => 'Phú Quốc',        'slug' => 'du-an-bat-dong-san-phu-quoc', 'province' => 'Kiên Giang',
            'slug_fallback' => 'du-an-bat-dong-san-kien-giang'],
    ],

    // Số trang lấy mỗi lần bấm "Lấy tiếp" (mặc định) và độ trễ giữa request.
    'pages_per_run' => 3,
    'delay_ms'      => 400,

    // Làm giàu metadata từ TRANG CHI TIẾT dự án (bảng "Thông tin dự án" + FAQ).
    // Tắt bằng --no-detail (command) hoặc BDS_ENRICH_DETAIL=false.
    'enrich_detail' => env('BDS_ENRICH_DETAIL', true),

    // Header giả trình duyệt.
    'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'accept_language' => 'vi,en;q=0.9',
    'timeout'         => 30,

    /*
     * KIỂM CHỨNG CHỐNG BOT (2026-07-27):
     * batdongsan.com.vn đặt sau Cloudflare "managed challenge" (JA3/TLS fingerprint).
     *  - PHP Guzzle/ext-curl (OpenSSL) bị chặn: trả 403 + trang challenge.
     *  - Binary curl.exe của Windows (Schannel) hoặc Git (Schannel) LẠI QUA ĐƯỢC: 200 + đủ card.
     * Do đó service thử Http (Guzzle) trước, nếu phát hiện bị chặn thì fallback shell ra
     * curl binary (Schannel). Đặt 'transport' = 'curl' để dùng thẳng curl cho nhanh.
     */
    // 'curl' = mặc định (Http/Guzzle bị Cloudflare chặn chắc chắn nên bỏ qua cho nhanh);
    // 'auto' = thử Http trước rồi fallback curl; 'http' = chỉ Http (sẽ bị chặn).
    'transport'   => env('BDS_TRANSPORT', 'curl'), // curl | auto | http
    'curl_binary' => env('BDS_CURL_BINARY', 'curl'), // Windows 10+ có sẵn C:\Windows\System32\curl.exe (Schannel)
];
