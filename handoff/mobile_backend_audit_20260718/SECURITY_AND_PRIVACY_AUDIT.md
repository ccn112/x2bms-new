# Audit Bảo mật & Quyền riêng tư — chuẩn bị App di động cư dân

> READ-ONLY. Phiên bản: 2026-07-18. Nguồn: mã nguồn tại `D:\Chinh\x2\x2bms`.
> Trọng tâm: rủi ro khi mở API token-based cho cư dân (khác biệt với mô hình phiên hiện tại).
> Mức độ: **CAO / TRUNG BÌNH / THẤP**.

---

## 1. Cô lập tenant (multi-tenancy isolation)

**Cơ chế hiện tại:** row-level, single DB, qua 2 trait global scope:
- `app/Models/Concerns/BelongsToTenant.php`: scope `tenant_id` lấy từ `auth()->user()?->tenant_id`.
- `app/Models/Concerns/BelongsToProject.php`: scope `project_id`/`building_id` từ
  `accessibleProjectIds()` của user.

### Phát hiện 1.1 — Scope phụ thuộc `auth()->user()`; no-op trong console/queue — **CAO** (đối với API tương lai)
- Cả hai trait `return null` (bỏ scope) khi `app()->runningInConsole()`. Queue jobs chạy trong
  console → **truy vấn trong job KHÔNG được scope tenant/project**. Nếu App mobile đẩy nghiệp vụ
  qua queue (gửi thông báo, sinh thống kê), phải tự áp `tenant_id` thủ công, nếu không sẽ rò
  dữ liệu chéo tenant.
- Với API token-based: scope chỉ đúng nếu request được resolve `auth()->user()` (qua
  `auth:sanctum`). Middleware API hiện tại (`EnsurePlatformAdmin`) đọc `$request->user()` nhưng
  **không có `auth:sanctum` phía trước** — nếu bê nguyên mẫu này cho API cư dân sẽ dễ tạo endpoint
  không có ngữ cảnh tenant → **rò dữ liệu**.
- File: `app/Models/Concerns/BelongsToTenant.php` (dòng `runningInConsole`),
  `app/Models/Concerns/BelongsToProject.php` (currentProjectIds).
- **Khuyến nghị:** khi xây API cư dân, bắt buộc `auth:sanctum` + thiết lập ngữ cảnh tenant/project
  từ token (ability/claims) TRƯỚC khi chạm model; viết test rò rỉ chéo-tenant; với queue job phải
  set ngữ cảnh tường minh (`withoutGlobalScope` chỉ khi cố ý).

### Phát hiện 1.2 — Một số model resident-facing thiếu scope project — **TRUNG BÌNH**
- `Payment`, `Statement`, `Resident`, `Apartment` dùng cả `BelongsToTenant` + `BelongsToProject`
  (tốt). Nhưng `PaymentRequest`, `QrPaymentToken`, `Receipt`, `PaymentGatewayConfig`,
  `LoginSession` chỉ có `BelongsToTenant` (không có `BelongsToProject`). Với BQL cấp dự án gọi API,
  cần kiểm lại xem có lộ dữ liệu giữa các dự án cùng tenant không.
- **Khuyến nghị:** rà từng model resident-facing sẽ mở qua API, xác nhận đúng tầng scope.

---

## 2. Lộ PII

Bảng `residents` chứa PII nặng: `id_no` (CCCD), `dob`, `phone`/`contact_phone`,
`contact_address`/`mailing_address`, `id_front_path`, `id_back_path`, `portrait_path`,
`face_match_status`, `documents` (json). Bảng `users` cũng có `id_no`, `dob`, `gender`, `avatar_path`.
(Migrations: `2026_06_30_000004_extend_residents_kyc_avatar.php`, `..._000005_global_identity...`.)

### Phát hiện 2.1 — Ảnh giấy tờ tùy thân (CCCD) lưu trên disk PUBLIC — **CAO**
- `app/Filament/Resources/Residents/Schemas/ResidentForm.php` dòng 104-106:
  `id_front_path`, `id_back_path`, `portrait_path` đều `->disk('public')->directory('residents/kyc')`.
- Disk `public` có `visibility=public` và URL cố định `APP_URL/storage/...`
  (`config/filesystems.php`). Ai biết/đoán được đường dẫn đều tải được ảnh CCCD, **không cần
  đăng nhập, không hết hạn**. Đây là dữ liệu định danh nhạy cảm nhất.
- **Khuyến nghị:** chuyển toàn bộ ảnh KYC sang disk `local` (private, đã có `serve=true`), truy
  cập qua route có kiểm quyền hoặc temporary signed URL; không bao giờ trả URL public cho ảnh CCCD.

### Phát hiện 2.2 — Avatar/URL công khai, không kiểm quyền — **THẤP/TRUNG BÌNH**
- `app/Models/Resident.php` (`avatarUrl`) dùng `Storage::disk('public')->url()`. Với avatar rủi ro
  thấp, nhưng nếu API resident trả thẳng URL public thì mọi ảnh đều truy cập được không cần token.
- **Khuyến nghị:** dùng signed URL/route có auth cho mọi media cá nhân trong API.

### Phát hiện 2.3 — Chưa có lớp che PII ở tầng trả dữ liệu — **TRUNG BÌNH** (khi mở API)
- Chưa có API Resource/Transformer nào cho cư dân (tầng API chưa tồn tại). Model dùng
  `$guarded = []` (mass-assignment mở) và không có `$hidden` cho `id_no`/`dob` trên `Resident`.
  Nếu serialize thẳng model ra API sẽ lộ CCCD/PII quá mức.
- `app/Models/User.php` có `#[Hidden(['password','remember_token'])]` nhưng KHÔNG ẩn `id_no`/`dob`.
- **Khuyến nghị:** bắt buộc tầng API Resource whitelisting; không trả `id_no` trừ khi thật cần,
  cân nhắc masking (ví dụ `****1234`).

---

## 3. Đồng thuận (consent)

### Phát hiện 3.1 — Không có theo dõi consent/quyền riêng tư — **TRUNG BÌNH**
- Tìm `consent|gdpr|pdpa|privacy_polic` → 0 kết quả. Không có bảng lưu chấp thuận điều khoản/
  xử lý dữ liệu, không có nhật ký đồng ý xử lý CCCD.
- Có `ContractAcceptance` (chấp thuận hợp đồng dịch vụ) nhưng không phải consent quyền riêng tư.
- **Khuyến nghị:** với App thu thập CCCD/ảnh chân dung, cần bảng consent (phiên bản điều khoản,
  timestamp, IP) để tuân thủ pháp lý (NĐ 13/2023 về bảo vệ dữ liệu cá nhân).

---

## 4. Thu hồi phiên / token

### Phát hiện 4.1 — Có thu hồi phiên WEB, chưa có thu hồi token API — **TRUNG BÌNH**
- `app/Filament/Pages/LoginSessions.php`: liệt kê/thu hồi phiên từ bảng `sessions`
  (`revoke`, `revokeOthers`), không cho thu hồi phiên hiện tại. Đây là **session-based (web)**.
- User có `HasApiTokens` (`app/Models/User.php`) nhưng **không có luồng phát hành/thu hồi token
  Sanctum nào** (grep `createToken`/`tokens()->delete`/`currentAccessToken` → không có ở tầng
  nghiệp vụ). Có model `LoginSession`, `TwoFactorSetting` nhưng `TwoFactorSetting` gần như trống
  (`app/Models/TwoFactorSetting.php` chỉ có cast), chưa có luồng 2FA thực.
- **Khuyến nghị:** khi mở API mobile, xây quản lý `personal_access_tokens` (liệt kê thiết bị, thu
  hồi từng token/tất cả token, gắn tên thiết bị), tích hợp vào màn "Phiên đăng nhập".

---

## 5. Phủ nhật ký kiểm toán (audit log)

### Phát hiện 5.1 — Audit tốt ở panel, nhưng phụ thuộc thao tác thủ công — **TRUNG BÌNH**
- `audit_logs` được ghi qua trait `WritesAudit` (`app/Filament/Concerns/WritesAudit.php`) và các
  biến thể `WritesBillingAudit`/`WritesIntegrationAudit`/`WritesSupportAudit`. Có ~137 callsite
  `->audit(...)`/`AuditLog::create` trong `app/`.
- Ghi audit là **thủ công theo từng action** (mỗi Page tự gọi `$this->audit(...)`), không phải
  observer tự động ở tầng model. → Nghiệp vụ mới (đặc biệt API mobile) sẽ **không tự có audit** trừ
  khi lập trình viên nhớ gọi. Các action nhạy cảm như đọc ảnh CCCD, đổi PII qua API sẽ dễ bị bỏ sót.
- `AuditLog` model không dùng `BelongsToTenant` (guarded rỗng), `tenant_id` set thủ công từ user.
- **Khuyến nghị:** với API mobile, chuẩn hóa audit ở middleware/observer; log các sự kiện: đăng
  nhập/OTP, đọc/tải ảnh KYC, thay đổi PII, thanh toán, thu hồi token.

### Phát hiện 5.2 — Billing thanh toán CÓ audit; nhưng thao tác đọc PII thì không — **THẤP**
- `recordPayment`/`reconcile` có `billingAudit(...)` (tốt). Nhưng không có audit khi xem/export
  ảnh giấy tờ, không có audit truy cập media private (vì media đang là public — mục 2.1).

---

## 6. Giới hạn tần suất (rate limiting)

### Phát hiện 6.1 — Không có throttle trên API — **CAO** (khi mở API)
- `bootstrap/app.php` KHÔNG đăng ký nhóm throttle cho API; `routes/api.php` không có middleware
  `throttle:*`. Grep `throttle|RateLimiter|rateLimit` trên `app/routes/bootstrap/config` chỉ ra:
  `config/auth.php` (throttle của password reset broker = 60s) và RateLimiter core (không cấu hình).
- Không có `RateLimiter::for(...)` tùy biến trong `AppServiceProvider`.
- **Khuyến nghị:** trước khi mở API cư dân, thêm `throttle` cho login/OTP/thanh toán/upload.

### Phát hiện 6.2 — Login panel dựa mặc định Filament — **THẤP**
- Không thấy cấu hình throttle đăng nhập riêng ở `app/Providers`. Filament có throttle mặc định
  cho panel login, nhưng chưa được tùy biến/kiểm chứng cho ngữ cảnh cư dân.

---

## 7. Chống lạm dụng OTP

### Phát hiện 7.1 — OTP reset mật khẩu không giới hạn số lần thử/sinh — **CAO**
- `app/Filament/Concerns/ResetsResidentPassword.php`, nhánh `otp`:
  `Cache::put('resident_pwd_otp_'.$resident->id, $otp, now()->addMinutes(10))`.
- **Không có bộ đếm số lần nhập sai, không có khóa sau N lần, không có rate-limit số lần sinh OTP.**
  Mỗi lần sinh ghi đè key cache → OTP cũ vô hiệu, nhưng attacker vẫn có thể brute-force trong 10
  phút (10^6 khả năng) nếu tầng xác thực OTP không giới hạn thử. Hiện luồng NHẬP/xác thực OTP phía
  cư dân chưa tồn tại (chưa có API) — cần cài đặt giới hạn khi xây.
- OTP hiện chỉ do **BQL kích hoạt trong panel** (không phải cư dân tự yêu cầu), nên bề mặt tấn công
  hiện tại hẹp; nhưng khi App mobile cho cư dân tự "gửi OTP", rủi ro trở thành CAO.
- **Khuyến nghị:** khi xây xác thực OTP cho App: giới hạn số lần thử (ví dụ 5), khóa tạm, throttle
  số lần yêu cầu OTP/giờ, và ghi audit mỗi lần thất bại.

---

## 8. Bảo mật tệp

### Phát hiện 8.1 — Ảnh giấy tờ trên disk public (trùng mục 2.1) — **CAO**
- Xem 2.1. Đây vừa là lộ PII vừa là lỗ hổng kiểm soát truy cập tệp.

### Phát hiện 8.2 — Upload ảnh KYC thiếu ràng buộc kích thước/loại — **TRUNG BÌNH**
- `id_front_path`/`id_back_path`/`portrait_path` chỉ `->image()`, **không `maxSize`, không
  `acceptedFileTypes`** (`ResidentForm.php` dòng 104-106). Rủi ro DoS/lưu file lớn; với API upload
  tương lai càng cần validate chặt (mime thật, kích thước, quét nội dung).
- Đối chiếu: `documents` có `maxSize(10240)` + mime whitelist (tốt) — nên áp cùng chuẩn cho KYC.

---

## 9. Rủi ro ghi log

### Phát hiện 9.1 — Không thấy log OTP/mật khẩu/PII — **THẤP (tốt)**
- Grep `Log::(info|debug|warning|error)` chỉ ra 4 callsite, đều ở module Knowledge/X2AI
  (`DocumentTextExtractor.php`, `X2aiClient.php`, `X2aiDataConnector.php`) — log path/status/message
  lỗi, KHÔNG log OTP/mật khẩu/CCCD. `ResetsResidentPassword` không ghi OTP ra log.
- **Lưu ý cấu hình:** `config/mail.php` mặc định `MAIL_MAILER=log` và mailer `log`. Nếu môi trường
  chạy với `log` mailer, **toàn bộ nội dung email OTP/link reset sẽ nằm trong
  `storage/logs/laravel.log`** (`config/logging.php` → channel `single`) dưới dạng plaintext.
  Ở production phải đặt SMTP thật, không để `log`. — **TRUNG BÌNH (phụ thuộc cấu hình)**.
- `mail.test_to` (biến `MAIL_TEST_TO_ADDRESS`): khi đặt, MỌI email nghiệp vụ (OTP/link) chuyển về
  địa chỉ test — hữu ích khi dev, nhưng **phải bỏ trống ở production** kẻo OTP cư dân bị chuyển
  hướng. — **TRUNG BÌNH (phụ thuộc cấu hình)**.

---

## 10. Bảng tổng hợp mức độ

| # | Phát hiện | Mức | File chính |
|---|---|---|---|
| 2.1 / 8.1 | Ảnh CCCD/chân dung lưu disk public, URL cố định không auth | **CAO** | `ResidentForm.php:104-106`, `config/filesystems.php` |
| 6.1 | Không có rate-limit trên API | **CAO** | `bootstrap/app.php`, `routes/api.php` |
| 7.1 | OTP không giới hạn thử/sinh | **CAO** | `ResetsResidentPassword.php` |
| 1.1 | Scope tenant/project phụ thuộc `auth()`; no-op ở console/queue; API thiếu `auth:sanctum` | **CAO** | `BelongsToTenant.php`, `BelongsToProject.php`, `EnsurePlatformAdmin.php` |
| 1.2 | Vài model resident-facing thiếu `BelongsToProject` | TRUNG BÌNH | `PaymentRequest`, `QrPaymentToken`, `Receipt`... |
| 2.3 | Chưa có lớp che PII khi serialize (model guarded rỗng, không hidden id_no) | TRUNG BÌNH | `Resident.php`, `User.php` |
| 3.1 | Không theo dõi consent quyền riêng tư | TRUNG BÌNH | (không có bảng) |
| 4.1 | Chưa có phát hành/thu hồi token API | TRUNG BÌNH | `User.php`, `LoginSessions.php` |
| 5.1 | Audit thủ công theo action, dễ sót ở nghiệp vụ mới | TRUNG BÌNH | `WritesAudit.php` |
| 8.2 | Upload KYC thiếu maxSize/mime | TRUNG BÌNH | `ResidentForm.php:104-106` |
| 9.1 | Mailer `log` / `mail.test_to` có thể lộ OTP nếu cấu hình sai production | TRUNG BÌNH | `config/mail.php`, `config/logging.php` |
| 6.2 | Login panel dùng throttle mặc định (chưa tùy biến) | THẤP | `app/Providers` |
| 5.2 | Không audit truy cập/đọc ảnh KYC | THẤP | — |

**Ưu tiên xử lý trước khi mở App:** (1) chuyển ảnh KYC sang private + signed URL,
(2) rate-limit API, (3) giới hạn OTP, (4) chuẩn `auth:sanctum` + ngữ cảnh tenant cho mọi API cư dân,
(5) tầng API Resource che PII.
