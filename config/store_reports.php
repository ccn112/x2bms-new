<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Số lượt cài app từ hai store
    |--------------------------------------------------------------------------
    |
    | Chưa cấu hình thì mọi báo cáo phải hiện "chưa cấu hình", TUYỆT ĐỐI không
    | được bịa số hay lấy số thiết bị đã đăng nhập rồi gọi là "số lượt cài" —
    | hai con số đó khác nhau về bản chất (xem docblock của StoreInstallSyncer).
    */

    /*
    | GOOGLE PLAY
    |
    | LƯU Ý QUAN TRỌNG: **Play Developer Reporting API KHÔNG có số lượt cài.** API
    | đó chỉ phục vụ Android vitals (crash rate, ANR, wake-up…). Số cài/gỡ nằm
    | trong các file CSV mà Play Console tự đẩy vào một bucket Google Cloud
    | Storage của chính nhà phát triển:
    |
    |   gs://{bucket}/stats/installs/installs_{package}_{yyyyMM}_overview.csv
    |
    | `bucket` luôn bắt đầu bằng `pubsite_prod_rev_` — lấy bằng nút
    | "Copy Cloud Storage URI" ở Play Console › Download reports.
    |
    | Quyền cần có: service account được mời vào Play Console (User and
    | permissions), quyền "View app information" đặt ở mức Global mới đọc được
    | báo cáo tổng; OAuth scope `devstorage.read_only`.
    */
    'google' => [
        'bucket' => env('PLAY_REPORTS_BUCKET'),          // pubsite_prod_rev_...
        'package' => env('PLAY_PACKAGE_NAME', 'vn.x2bms.resident_mobile'),
        // Đường dẫn file JSON key của service account. Để ngoài repo.
        'credentials' => env('PLAY_SERVICE_ACCOUNT_JSON'),
    ],

    /*
    | APP STORE (iOS)
    |
    | App Store Connect API có endpoint báo cáo bán hàng; số lượt tải nằm ở cột
    | `Units` của báo cáo SALES/SUMMARY:
    |
    |   GET https://api.appstoreconnect.apple.com/v1/salesReports
    |       ?filter[reportType]=SALES&filter[reportSubType]=SUMMARY
    |       &filter[frequency]=DAILY&filter[vendorNumber]=...&filter[reportDate]=YYYY-MM-DD
    |
    | Trả về file **gzip chứa TSV** (không phải JSON). Auth là JWT ES256 tự ký từ
    | khoá .p8, sống tối đa 20 phút.
    */
    'apple' => [
        'issuer_id' => env('ASC_ISSUER_ID'),
        'key_id' => env('ASC_KEY_ID'),
        'private_key' => env('ASC_PRIVATE_KEY_PATH'),     // file .p8, để ngoài repo
        'vendor_number' => env('ASC_VENDOR_NUMBER'),
        // SKU của app trong App Store Connect — dùng để lọc đúng dòng trong TSV
        // (báo cáo gộp mọi app của cùng vendor).
        'sku' => env('ASC_APP_SKU'),
    ],

    /*
    | Số ngày lùi lại mỗi lần đồng bộ. Cả hai store đều chốt số liệu chậm và có
    | thể sửa lại số của những ngày trước, nên phải quét lại một khoảng chứ không
    | chỉ lấy ngày hôm qua.
    */
    'backfill_days' => (int) env('STORE_REPORTS_BACKFILL_DAYS', 7),

];
