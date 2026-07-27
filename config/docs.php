<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host site tài liệu công khai
    |--------------------------------------------------------------------------
    |
    | Subdomain phục vụ site tài liệu PUBLIC (doc.x2.fino.vn), trỏ về cùng app
    | x2bms. Khi request đến đúng host này, route root '/' map thẳng vào reader
    | tài liệu (landing) — không cần prefix '/docs'. Host chính vẫn giữ '/docs'.
    |
    */

    'host' => env('DOCS_HOST', 'doc.x2.fino.vn'),

    /*
    |--------------------------------------------------------------------------
    | Định nghĩa không gian tài liệu (space)
    |--------------------------------------------------------------------------
    |
    | Docs CMS là NƠI CHÍNH THỨC xuất bản tài liệu dev + hướng dẫn của CẢ 2 dự án
    | (x2bms + x2mobile). Space được tạo/đồng bộ bởi `docs:import` (idempotent).
    | audience: dev|ops|bql|hq|sa|resident. is_public=true → khách xem không cần login.
    |
    */

    'spaces' => [
        'dev' => ['title' => 'Tài liệu phát triển (Dev)', 'audience' => 'dev', 'is_public' => false, 'sort' => 10, 'desc' => 'UI/UX, tính năng, kiến trúc & DB backend — nội bộ dev.'],
        'mobile-dev' => ['title' => 'Tài liệu phát triển App (Mobile)', 'audience' => 'dev', 'is_public' => false, 'sort' => 15, 'desc' => 'Tài liệu phát triển app cư dân (x2mobile) — nội bộ dev.'],
        'ops' => ['title' => 'Vận hành & Tích hợp', 'audience' => 'ops', 'is_public' => true, 'sort' => 20, 'desc' => 'Chạy backend, mobile API, triển khai & mở rộng.'],
        'cu-dan' => ['title' => 'Hướng dẫn Cư dân (App)', 'audience' => 'resident', 'is_public' => true, 'sort' => 25, 'desc' => 'Hướng dẫn sử dụng app cư dân X2-BMS.'],
        'bql' => ['title' => 'Hướng dẫn Ban Quản Lý (BQL)', 'audience' => 'bql', 'is_public' => false, 'sort' => 30, 'desc' => 'Hướng dẫn nghiệp vụ cho Ban Quản lý dự án.'],
        'hq' => ['title' => 'Hướng dẫn Cổng Công ty (HQ)', 'audience' => 'hq', 'is_public' => false, 'sort' => 40, 'desc' => 'Hướng dẫn cho cổng công ty vận hành.'],
        'sa' => ['title' => 'Hướng dẫn SuperAdmin', 'audience' => 'sa', 'is_public' => false, 'sort' => 50, 'desc' => 'Hướng dẫn cho nhà cung cấp nền tảng.'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nguồn import (đa repo)
    |--------------------------------------------------------------------------
    |
    | Mỗi entry: 'path' (tương đối base_path() app — có thể trỏ sang repo cạnh như
    | ../x2mobile) + đích:
    |   - 'space'            : gom mọi .md của path vào 1 space (key trong 'spaces').
    |   - 'mode' => 'guide_audience' : map theo thư mục con (bql/hq/sa → space cùng tên,
    |                          còn lại → 'ops'). Dùng cho docs/guide của x2bms.
    | AN TOÀN: path không tồn tại (vd server không có x2mobile) → skip êm, không lỗi.
    |
    */

    'import_paths' => [
        ['path' => 'docs/dev', 'space' => 'dev'],
        ['path' => 'docs/guide', 'mode' => 'guide_audience'],
        ['path' => '../x2mobile/docs/guide/cu-dan', 'space' => 'cu-dan'],
        ['path' => '../x2mobile/docs/dev', 'space' => 'mobile-dev'],
    ],

];
