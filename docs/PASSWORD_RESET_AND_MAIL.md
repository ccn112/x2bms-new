# Đặt lại mật khẩu cư dân & Cấu hình Mail (Dev)

> Tài liệu phát triển. Cập nhật 2026-07-18. Liên quan: `LISTING_PAGE_STANDARD.md`, `COMMUNICATION_LOG_DESIGN_NOTE.md`.

## 1. Tổng quan
Luồng "Đặt lại mật khẩu cư dân" dùng chung cho **màn Danh sách** (`ResidentDirectory`) và **màn Chi tiết** (`ResidentDetail`) qua trait `App\Filament\Concerns\ResetsResidentPassword`. BQL chọn 1 trong 4 phương thức, hệ thống sinh dữ liệu và mở modal kết quả có nút Copy; kênh email gửi thật qua SMTP.

Điều kiện: cư dân **đã có tài khoản đăng nhập liên kết** (`residents.user_id` → `users`). Chưa có → chặn, báo kích hoạt tài khoản trước.

## 2. Bốn phương thức
| Key | Nhãn | Hành vi | Hiển thị lại |
|---|---|---|---|
| `temp` | Cấp mật khẩu tạm (1 lần) | `Str::password(10)` → set `user.password` (cast `hashed` tự băm) | Mật khẩu trong modal (đọc cho cư dân) |
| `otp` | Gửi mã OTP | 6 số, `Cache::put('resident_pwd_otp_{id}', 10')` + gửi email | Mã OTP + trạng thái gửi |
| `link_send` | Gửi link đặt lại | `Password::broker()->createToken()` + gửi email | Link + trạng thái gửi |
| `link_copy` | Tạo link copy (Zalo) | Sinh link, KHÔNG gửi | Link + nút Copy |

Kênh gửi (`otp`/`link_send`): `sms` · `zalo` · `email`. SMS/Zalo **chưa nối gateway** — chỉ gửi thật khi kênh=email hoặc đang ở chế độ test (xem §4).

## 3. Trang tiêu thụ token (guest)
- Route: `GET /reset-password/{token}` (`password.reset`), `POST /reset-password` (`password.store`) — `routes/web.php`.
- Controller: `App\Http\Controllers\Auth\ResidentPasswordResetController` — dùng `Password::reset()` chuẩn Laravel (validate token+hạn, cập nhật mật khẩu, tự xóa token).
- View: `resources/views/auth/reset-password.blade.php` — **tự chứa** (CSS inline, branded navy/gold), không phụ thuộc Vite → luôn render dù asset chưa build.
- Link sinh ra: `config('app.url') . '/reset-password/{token}?email=...'`. Nếu sau này có **portal cư dân riêng**, tách base bằng biến `RESIDENT_URL` và sửa `makeResetLink()`.
- Hạn token: `config/auth.php > passwords.users.expire` (mặc định 60').

## 4. Cấu hình Mail
`.env`:
```
MAIL_MAILER=smtp                 # 'log' = ghi vào storage/logs/laravel.log (không gửi thật)
MAIL_HOST=smtp.elasticemail.com
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="hello@xhub.com.vn"
MAIL_TEST_TO_ADDRESS="chtchinh@gmail.com"   # BẬT chế độ test (xem dưới)
```
- `config/mail.php` thêm `'test_to' => env('MAIL_TEST_TO_ADDRESS')`.
- **Chế độ test:** khi `mail.test_to` có giá trị → `deliverResidentMail()` gửi MỌI email nghiệp vụ về địa chỉ test (mọi kênh), tiện kiểm thử OTP/link. **Production: để TRỐNG** `MAIL_TEST_TO_ADDRESS` để gửi đúng cư dân.
- Sau khi đổi `.env` phải `php artisan config:clear`.

## 5. Điểm mã nguồn
- `app/Filament/Concerns/ResetsResidentPassword.php` — `resetPasswordSchema()`, `handleResidentPasswordReset()`, `makeResetLink()`, `deliverResidentMail()`, template email, `residentResetResultAction()` (modal kết quả, auto-discover qua tên `*Action`).
- Gắn nút: `ResidentDirectory` (row action), `ResidentDetail` (header action) — cùng gọi `handleResidentPasswordReset()`.

## 6. Bẫy đã trả giá
- **Header/row action KHÔNG chạy qua `Livewire::test()->callAction()` headless** (Filament v4) — verify action phải bằng browser thật (đã xác minh).
- **`assert(... Configuration)`** khi gọi `assertHasNoActionErrors()` ngoài PHPUnit runner — không phải lỗi code, bỏ assert đó khi test bằng script.
- Link reset **404 nếu chưa đăng ký route** — luôn kèm route+controller+view khi phát hành link.
- `password` cast `hashed`: gán **plaintext**, model tự băm khi save (đừng `Hash::make` 2 lần).

## 7. Verify
- `php _render_admin.php "residents,residents/{id}/detail"` → 200.
- Browser: mở popup reset → chọn phương thức → modal kết quả + Copy; link_copy → mở link → đặt mật khẩu → `Hash::check` mới PASS + token xóa.
- SMTP: đặt `MAIL_MAILER=smtp`, gửi thử; hoặc `log` rồi đọc `storage/logs/laravel.log`.
