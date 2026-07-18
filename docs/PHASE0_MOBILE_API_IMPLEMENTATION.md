# Backend Phase 0 — Mobile API (đợt 1) — Implementation Notes

> Kèm theo: `../../docs/ARCHITECTURE_X2_PLATFORM_V1.md` (kiến trúc tổng).
> Trạng thái: đã code slice 1–4 (identity, Sanctum, auth, bootstrap/device) và **đã verify end-to-end qua HTTP thật** (2026-07-18, DB local, 135 users).

## ✅ Kết quả verify (2026-07-18)
`composer install` OK · `migrate` OK (tạo `mobile_devices`) · `route:list` = 9 route `/api/v1`. Smoke test HTTP (`php artisan serve`):

| Test | Kết quả |
|---|---|
| `GET /public/bootstrap` | 200, mode=public |
| `POST /auth/login` (X-Device-Id) | 200, abilities=`resident` |
| `GET /me/bootstrap` (Bearer access) | 200, mode=member, contexts=0 |
| `POST /auth/refresh` (Bearer refresh) | 200, cấp cặp token mới (rotate) |
| `me/bootstrap` không token | 401 `AUTH_UNAUTHENTICATED` |
| login sai mật khẩu | 401 `AUTH_INVALID_CREDENTIALS` |

> ⚠️ **Gotcha khi test:** KHÔNG test nhiều request auth trong **cùng một tiến trình PHP** (in-process HTTP kernel). Sanctum guard bind vào request đầu tiên và memoize user → refresh/no-token cho kết quả sai lệch. Luôn test auth bằng HTTP thật (`php artisan serve` + client tách biệt) hoặc Feature test (mỗi test một request).

## Quyết định đã áp dụng
- **Hợp nhất identity về `User`** — `User` là chủ thể auth/mobile duy nhất. Vai trò suy ra từ quan hệ (`hasResidentMembership()` / `isStaffOperator()`), không dựa `account_type`. PII (`id_no`, `dob`) đã `#[Hidden]` khỏi serialize API.
- **Chưa xóa** stack `GlobalUserAccount` + `ResidentUnitBinding` + `ResidentBindingRequest` (24 file Filament SA phụ thuộc). Đây là bước **deprecation có data-migration**, cần xác nhận riêng — xem "Việc còn lại".

## Đã tạo / sửa
| File | Nội dung |
|---|---|
| `app/Models/User.php` | ẩn PII; `hasResidentMembership()`, `isStaffOperator()`, `tokenAbilities()` |
| `app/Models/MobileDevice.php` + migration `2026_07_18_000001_create_mobile_devices_table` | device/push registry, `push_token` encrypted |
| `config/sanctum.php`, `config/mobile.php` | cấu hình Sanctum + TTL token/OTP/min version (qua env) |
| `app/Support/Api/ApiResponse.php` | envelope success/error/paginated chuẩn |
| `app/Services/Auth/TokenService.php` | cặp access+refresh, refresh **rotate**, device-bound |
| `app/Services/Auth/OtpService.php` | OTP cache + attempt limit (chưa nối SMS) |
| `app/Http/Controllers/Api/V1/*` | ApiController, AuthController, OtpController, BootstrapController, DeviceController |
| `app/Providers/AppServiceProvider.php` | rate limiter `otp`/`auth-login`/`public-read`/`api` |
| `routes/api.php` | nhóm `/api/v1` (auth, otp, refresh, me/bootstrap, me/devices) |
| `bootstrap/app.php` | map exception → envelope cho `api/*` |

## Endpoint đợt 1 (`/api/v1`)
- `GET  public/bootstrap` — branding, module, min version (no auth).
- `POST auth/login` — identifier(phone|email)+password → cặp token. Yêu cầu header `X-Device-Id`.
- `POST auth/otp/request` / `auth/otp/verify` — OTP (dev trả `dev_code` khi không phải production).
- `POST auth/refresh` — Bearer refresh token (ability `token:refresh`) → cặp token mới (rotate).
- `POST auth/logout` — thu hồi token của device.
- `GET  me/bootstrap` — experience_mode + available_contexts (resident relations + staff scopes).
- `POST me/devices` / `DELETE me/devices/{installationId}` — device/push token.

## Cách chạy & verify (local)
```powershell
cd E:\App\code\x2\x2web
composer install
copy .env.example .env   # nếu chưa có
php artisan key:generate
# DB nhanh để test: đặt trong .env -> DB_CONNECTION=sqlite ; và tạo database/database.sqlite
php artisan migrate
php artisan route:list --path=api/v1
php artisan serve
# smoke test:
#   POST /api/v1/auth/otp/request {channel:phone,destination:...,purpose:login}
#   POST /api/v1/auth/login (header X-Device-Id) {identifier,password}
#   GET  /api/v1/me/bootstrap (Bearer <access_token>)
```

## Đợt 2 — đã thêm & verify (2026-07-18)
- **Scale layer** (sẵn sàng production, bật bằng env): read/write DB split trong `config/database.php` (mặc định trỏ primary); cài **Horizon** (3 hàng đợi `emergency/default/bulk`, gate `viewHorizon` = platform admin), **Octane** (config frankenphp; binary chỉ chạy Linux/Docker), **predis**. Local giữ driver `database`. Chi tiết: `x2/docs/guide/scale-ops.md`, `.env.production.example`, checklist `ARCHITECTURE §13b`.
- **Bảo mật — private KYC**: `ResidentForm` chuyển `id_front/id_back/portrait_path` + `documents` sang disk `local` (private) + validate mime/size. Phục vụ qua route **signed + auth + ResidentPolicy@view**: `media/residents/{resident}/{field}` và `.../documents/{index}` (`PrivateMediaController`).
- **Data-migration KYC cũ (public → private)**: command `php artisan kyc:migrate-private` (`--dry-run` báo cáo, `--delete` xoá bản public sau copy). Giữ nguyên path nên record cũ hoạt động. Đã verify chạy sạch trên DB local (chưa có file thật để chuyển → moved=0). **Chạy trên production sau khi deploy** để dời file KYC cũ khỏi public.
- **Nghiệp vụ — hóa đơn cư dân**: `GET /api/v1/resident/statements` (cursor paginate) + `/{statement}` (kèm lines). Ability `resident`, **scope tường minh theo apartment_id** (không dựa tenant scope vì cư dân tenant_id=NULL). Resource ẩn PII, money = string. Verify: resident→200, admin(staff)→403.

## Việc còn lại (Phase 0 tiếp theo)
1. **Deprecate `GlobalUserAccount` stack** — data-migration gộp binding vào `Resident`/`ResidentApartmentRelation`, gỡ 24 file Filament SA (cần xác nhận, thao tác có tính phá huỷ).
2. **Đăng ký tài khoản** — `POST auth/register` tiêu thụ OTP marker → tạo `User` account_type=resident.
3. **Private KYC** — chuyển disk KYC `public`→`local` + signed URL + validate mime/size (đang là security blocker).
4. **Context header stateless** — middleware đọc `X-Context-Id`, xác thực relation active, thay `CurrentContext` session.
5. **Policy + API Resources** cho từng domain cư dân (billing/feedback/amenity) + tách service khỏi Livewire.
6. **Push fan-out** qua queue + kênh push trong Notification.
7. **Tests** — feature test cho auth/refresh/bootstrap + rate limit.
