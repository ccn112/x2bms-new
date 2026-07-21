# X2-BMS — Tài liệu VẬN HÀNH

> Dành cho quản trị hệ thống / DevOps. Cập nhật 2026-07-18.
> Tài liệu phát triển (dev) xem `docs/` gốc; hướng dẫn người dùng cuối xem `docs/user-guide/`.

## Mục lục
1. [Kiến trúc & môi trường](#1-kiến-trúc--môi-trường)
2. [Cấu hình `.env` trọng yếu](#2-cấu-hình-env-trọng-yếu)
3. [Chạy & build](#3-chạy--build)
4. [Cấu hình Mail (gửi thông báo)](#4-cấu-hình-mail-gửi-thông-báo)
5. [Kênh thông báo & nhật ký gửi](#5-kênh-thông-báo--nhật-ký-gửi)
6. [Kiểm tra sức khoẻ (verify)](#6-kiểm-tra-sức-khoẻ-verify)
7. [Sự cố thường gặp](#7-sự-cố-thường-gặp)
8. **Lưu trữ · Sao lưu · Vòng đời tenant (SA)** → [`SA_STORAGE_LIFECYCLE.md`](SA_STORAGE_LIFECYCLE.md)

---

## 1. Kiến trúc & môi trường
- **3 panel theo tầng quyền** (cùng 1 codebase Laravel + Filament v4):
  - `/sa` — **SuperAdmin** (nền tảng, đa công ty).
  - `/hq` — **Tenant** (công ty vận hành, đa dự án).
  - `/admin` — **Ban quản lý (BQL)** dự án. (+ `/fila` CRUD kỹ thuật.)
- Ngữ cảnh làm việc theo **dự án (project)**, không phải theo toà.
- Dev: PHP (Herd) + MySQL; serve `php artisan serve` (xem `.claude/launch.json`, cổng 8010) hoặc domain Herd `x2bms.test`.

## 2. Cấu hình `.env` trọng yếu
| Biến | Ý nghĩa |
|---|---|
| `APP_URL` | Base URL — dùng để sinh **link đặt lại mật khẩu**. |
| `DB_*` | Kết nối MySQL. |
| `MAIL_*` | Gửi email (xem §4). |
| `MAIL_TEST_TO_ADDRESS` | Có giá trị → **chế độ test**: mọi email nghiệp vụ về địa chỉ này. Production: **để trống**. |

Sau khi sửa `.env`: **`php artisan config:clear`**.

## 3. Chạy & build
```bash
php artisan serve --host=127.0.0.1 --port=8010   # hoặc domain Herd
npm run build                                     # build CSS/JS (Filament theme)
php artisan optimize:clear                        # xoá cache config/route/view
php artisan view:clear                            # xoá cache view (sau khi sửa blade)
```
> Sửa `.css`/`.js` phải `npm run build`. Sửa `.blade.php` nên `php artisan view:clear`.

## 4. Cấu hình Mail (gửi thông báo)
Chi tiết dev: `docs/PASSWORD_RESET_AND_MAIL.md`.
- `MAIL_MAILER=smtp` → gửi thật. `MAIL_MAILER=log` → ghi vào `storage/logs/laravel.log` (không gửi ra ngoài, tiện test).
- Hiện dùng **elasticemail** (`smtp.elasticemail.com:2525`).
- **Chế độ test:** đặt `MAIL_TEST_TO_ADDRESS` → mọi email OTP/link đặt lại mật khẩu về địa chỉ test. **Bỏ trống ở production** để gửi đúng cư dân.
- Kiểm tra nhanh: đổi `MAIL_MAILER=smtp` → `config:clear` → gửi thử; nếu lỗi handshake TLS thử `MAIL_SCHEME=smtp`.

## 5. Kênh thông báo & nhật ký gửi
- Kênh dự kiến: **Email · Push (app) · Web · Zalo OA · SMS**. Hiện **Email đã chạy (SMTP)**; **SMS/Zalo là stub** (chưa nối gateway).
- Backend log gửi: bảng `notification_delivery_logs` (theo `notification_id/user_id/resident_id/channel/status/error`), phân tầng theo `notifications.owner_level` (`platform`/`tenant`/`project` = SuperAdmin/Tenant/BQL).
- **Màn xem nhật ký gửi + retry theo 3 tầng: CHƯA dựng** (đang thiết kế) — xem `docs/COMMUNICATION_LOG_DESIGN_NOTE.md`.

## 6. Kiểm tra sức khoẻ (verify)
```bash
php _render_admin.php "residents,residents/1/detail,apartments"   # render 200 các màn /admin
php _render_hq.php "..."                                           # tương tự cho /hq
```
- Đăng nhập test /admin: `nv1@x2bms.vn` · cư dân: mật khẩu demo `Resident@2026!` · BQL/HQ/SA: `Bms@2026!`.

## 7. Sự cố thường gặp
| Hiện tượng | Nguyên nhân | Xử lý |
|---|---|---|
| Link đặt lại mật khẩu 404 | Sai `APP_URL` / môi trường không phục vụ domain đó | Kiểm tra `APP_URL`, dùng domain đang chạy |
| Email không tới | `MAIL_MAILER=log`, hoặc `MAIL_TEST_TO_ADDRESS` đang bật, hoặc sai SMTP | Xem §4, kiểm `storage/logs/laravel.log` |
| Sửa CSS không đổi | Chưa build | `npm run build` |
| Sửa blade không đổi | View cache | `php artisan view:clear` |
| Đổi `.env` không tác dụng | Config cache | `php artisan config:clear` |
