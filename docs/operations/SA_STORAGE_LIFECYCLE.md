# X2-BMS — Vận hành SA: Lưu trữ · Sao lưu · Vòng đời tenant

> Dành cho **SuperAdmin / DevOps**. Cập nhật 2026-07-21.
> Bao phủ: lưu trữ dữ liệu đa tenant, sao lưu/khôi phục, vòng đời off↔restore (churn), retention.
> Màn UI: `/sa/tenant-lifecycle` và `/sa/tenant-backups`. Lệnh CLI: `tenant:*`.

## Mục lục
1. [Mô hình lưu trữ đa tenant](#1-mô-hình-lưu-trữ-đa-tenant)
2. [Cấu hình `.env`](#2-cấu-hình-env)
3. [Sao lưu (backup)](#3-sao-lưu-backup)
4. [Off tenant (dormant) & Khôi phục (rehydrate)](#4-off-tenant-dormant--khôi-phục-rehydrate)
5. [Quản lý bản backup & retention](#5-quản-lý-bản-backup--retention)
6. [Tự động hoá vòng đời (sweep + scheduler)](#6-tự-động-hoá-vòng-đời-sweep--scheduler)
7. [Chuyển sang S3/MinIO](#7-chuyển-sang-s3minio)
8. [Sự cố thường gặp](#8-sự-cố-thường-gặp)

---

## 1. Mô hình lưu trữ đa tenant

- **Pool + prefix**: 1 disk (local now / S3 sau) + phân vùng **folder riêng từng tenant/dự án**. Shared DB + `tenant_id`.
- Quy ước khóa (mọi file người dùng upload đi qua `App\Support\Storage\TenantStorage`):
  ```
  {TENANT_STORAGE_ROOT}/{tenant_id}/projects/{building_id}/residents/import/{batch}/source.xlsx
  {root}/{tenant_id}/projects/{building_id}/residents/kyc/...        (PII, serve qua route ký + auth)
  {root}/{tenant_id}/kb-attachments/...                             (tài liệu cho X2AI)
  {root}/{tenant_id}/_backups/{yyyymmdd_His}/backup.zip             (bundle backup)
  ```
- Local: gốc thật ở `storage/app/private/{root}/…` (đĩa `local` = private).
- **Nguyên tắc**: mọi I/O file PHẢI qua `TenantStorage` (prefix lấy từ ngữ cảnh, không tin input) → chống rò dữ liệu chéo tenant.

## 2. Cấu hình `.env`

| Biến | Mặc định | Ý nghĩa |
|---|---|---|
| `TENANT_STORAGE_DISK` | `local` | Disk lưu dữ liệu tenant (đổi `s3` khi mua) |
| `TENANT_STORAGE_ROOT` | `tenants` | Tiền tố gốc |
| `TENANT_RETENTION_DAYS` | `1095` (~3 năm) | Giữ bundle sau khi off trước khi được purge |
| `TENANT_GRACE_DAYS` | `60` | Ân hạn sau khi thuê bao hết hạn trước khi tự off |
| `TENANT_BACKUP_CHUNK` | `1000` | Số dòng/lô khi dump DB |
| `QUEUE_CONNECTION` | `database` | Import chạy nền — cần `queue:work` |

Cấu hình chi tiết trong `config/tenant-storage.php`, `config/tenant-backup.php`.

## 3. Sao lưu (backup)

Bundle `.zip` gồm: `manifest.json` (tenant_id, `app_version`, số dòng mỗi bảng, file_count) · `db/<bảng>.ndjson` (lọc `tenant_id`) · `files/<key>` (toàn bộ file vùng tenant). Backup **logic**, độc lập DB engine.

- **UI**: `/sa/tenant-lifecycle` → hàng tenant → **Sao lưu**.
- **CLI**: `php artisan tenant:backup {tenantId}`
- Bundle lưu tại `.../{tenant}/_backups/{ts}/backup.zip` và ghi sổ vào bảng `tenant_backups`.
- Bảng backup xuất theo `tenant-backup.tables` (tự bỏ bảng không có `tenant_id`).

## 4. Off tenant (dormant) & Khôi phục (rehydrate)

**Vòng đời:** `active → dormant_archived → (khôi phục → active | quá hạn → purged)`.

**Off (churn — khách off nhưng vẫn giữ "gói storage"):**
- **UI**: `/sa/tenant-lifecycle` → **Off & lưu trữ**. **CLI**: `php artisan tenant:offboard {tenantId}` (`--force` bỏ xác nhận).
- Tác động: (1) tạo backup; (2) **XÓA dữ liệu sống** (DB rows + files) NHƯNG **giữ `_backups/`**; (3) `lifecycle_status=dormant_archived`, đặt `retention_until = now + TENANT_RETENTION_DAYS`.
- **Nhân sự tenant sẽ KHÔNG đăng nhập được** (mọi panel) cho tới khi khôi phục — SuperAdmin vẫn vào bình thường.

**Khôi phục (resume):**
- **UI**: **Khôi phục** (dùng bundle mới nhất). **CLI**: `php artisan tenant:restore {tenantId} [--bundle=<key>]`.
- Tác động: nạp lại DB (giữ nguyên `id`, trong transaction) + đẩy file về vùng tenant + `lifecycle_status=active`.
- **Bundle cũ hơn version app** vẫn khôi phục được: chỉ nạp cột còn tồn tại ở schema hiện tại (cột mới nhận default). Sau khôi phục nên chạy `php artisan migrate` nếu có migration mới.

> Kịch bản điển hình: năm 1 dùng → off (giữ gói storage) → năm 3 resume = **Khôi phục** → dữ liệu nguyên vẹn.

## 5. Quản lý bản backup & retention

- **UI**: `/sa/tenant-backups` — liệt kê mọi bản backup (tenant, thời điểm, dung lượng, số file, tổng dòng DB, version, nguồn). Actions: **Tải về**, **Xóa**.
- **Retention**: bản backup của tenant dormant được giữ tới `retention_until`; sau đó `tenant:lifecycle-sweep` sẽ purge.
- Xóa thủ công 1 bản = xóa file + bản ghi sổ (không hoàn tác).

## 6. Tự động hoá vòng đời (sweep + scheduler)

`php artisan tenant:lifecycle-sweep` — **mặc định dry-run** (chỉ báo cáo). Thêm `--commit` để thực thi:
- Tenant `active` + thuê bao hết hạn quá `grace_days` (không còn subscription active) → **OFF**.
- Tenant `dormant_archived` quá `retention_until` → **PURGE** (xóa `_backups` + folder + đánh dấu `purged`).

Lên lịch (scheduler / cron) chạy hằng ngày, ví dụ:
```php
// app/Console/Kernel.php hoặc routes/console.php
Schedule::command('tenant:lifecycle-sweep --commit')->dailyAt('02:00');
```
> Luôn chạy dry-run trước khi bật `--commit` trên production để xem danh sách bị tác động.

## 7. Chuyển sang S3/MinIO

Khi mua object storage — **chỉ đổi ENV, không sửa code**:
```
TENANT_STORAGE_DISK=s3
AWS_ACCESS_KEY_ID=...   AWS_SECRET_ACCESS_KEY=...   AWS_BUCKET=...   AWS_DEFAULT_REGION=...
# MinIO: thêm
AWS_ENDPOINT=https://minio.example.com   AWS_USE_PATH_STYLE_ENDPOINT=true
```
- Bucket phải **private** (KYC/PII serve qua route ký + auth, không public URL).
- **Tiering nóng→lạnh** = đặt **lifecycle policy của bucket** cho tiền tố `.../_backups/` (chuyển sang Standard-IA/Glacier). Đây là cấu hình hạ tầng, không phải code.
- Dữ liệu cũ đang ở `local`: cần migrate sang bucket (đồng bộ thủ công) — file cũ vẫn đọc được nhờ fallback nhưng nên hợp nhất.

## 8. Sự cố thường gặp

| Triệu chứng | Nguyên nhân & xử lý |
|---|---|
| Import "đứng" ở trạng thái *Đang ghi (nền)* | Chưa chạy worker → `php artisan queue:work`. Xong bấm **Retry** ở màn Nhật ký Import. |
| Nhân sự tenant không đăng nhập được | Tenant đang `dormant_archived`/`purged` → khôi phục ở `/sa/tenant-lifecycle`. |
| Khôi phục báo lỗi cột/không khớp | Bundle quá cũ so với schema — kiểm `manifest.app_version`; đã lọc cột tự động, nếu vẫn lỗi FK: chạy sau `migrate`, hoặc khôi phục vào môi trường staging trước. |
| `Class "Laravel\Octane\Octane" not found` (CLI local) | Thiếu vendor (Windows) → `composer install --ignore-platform-reqs`. |
| Tải file nguồn/backup 404 | File chưa sync (đa máy) hoặc `TENANT_STORAGE_DISK` khác lúc tạo — kiểm ENV + đường dẫn trong `tenant_backups.path`. |

---

**Lệnh nhanh:**
```bash
php artisan tenant:backup {id}                 # tạo bản backup
php artisan tenant:offboard {id} [--force]      # off (backup + purge, giữ bundle)
php artisan tenant:restore {id} [--bundle=key]  # khôi phục
php artisan tenant:lifecycle-sweep [--commit]   # sweep vòng đời (dry-run mặc định)
```
