<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disk lưu trữ dữ liệu tenant
    |--------------------------------------------------------------------------
    |
    | Tên disk (khai trong config/filesystems.php) dùng để lưu MỌI file do người
    | dùng đẩy lên, phân vùng theo từng tenant/dự án. Mặc định 'local' (self-host).
    |
    | Khi mua object storage (S3/MinIO): chỉ cần đổi ENV, KHÔNG sửa code:
    |   TENANT_STORAGE_DISK=s3
    |   AWS_ACCESS_KEY_ID=...  AWS_SECRET_ACCESS_KEY=...  AWS_BUCKET=...
    |   AWS_DEFAULT_REGION=...  (MinIO: thêm AWS_ENDPOINT + AWS_USE_PATH_STYLE_ENDPOINT=true)
    |
    */
    'disk' => env('TENANT_STORAGE_DISK', 'local'),

    /*
    | Tiền tố gốc cho toàn bộ dữ liệu tenant trên disk (đổi nếu muốn gom vào 1 nhánh).
    */
    'root_prefix' => env('TENANT_STORAGE_ROOT', 'tenants'),

];
