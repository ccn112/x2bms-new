# Audit Thanh toán · Thông báo · Upload — chuẩn bị App di động cư dân

> READ-ONLY. Phiên bản: 2026-07-18. Nguồn: mã nguồn tại `D:\Chinh\x2\x2bms`.
> Laravel 13 · Filament 5 · Sanctum 4 · spatie/permission 7.
> Không có package fcm/firebase/twilio/zalo/pusher/horizon trong `composer.json`.

---

## 0. Bối cảnh xác thực API (ảnh hưởng toàn bộ 3 phần)

- Toàn bộ API hiện có nằm ở `routes/api.php`, chia 3 nhóm: `platform/billing` (Batch 07),
  `platform/integrations` (Batch 08), `platform/support` (Batch 10).
- Cả 3 nhóm đều gác bằng middleware **`platform.admin`** (`app/Http/Middleware/EnsurePlatformAdmin.php`),
  chỉ cho SuperAdmin/Billing admin (`$user->isPlatformAdmin()`).
- Xác thực **dựa trên phiên Filament** (session cookie), KHÔNG dùng token Sanctum. Comment
  trong `routes/api.php` ghi rõ: *"Xác thực qua phiên Filament (actingAs trong test)"*.
  `EnsurePlatformAdmin` chỉ đọc `$request->user()` — không có middleware `auth:sanctum` phía trước.
- `bootstrap/app.php` chỉ đăng ký alias `platform.admin`; không có nhóm throttle, không có
  `apiPrefix` tùy biến, không có middleware token cho cư dân.

**Kết luận nền tảng:** hiện tại **không tồn tại API hướng cư dân (resident-facing) nào**.
Tất cả nghiệp vụ chạy trong panel Filament (SSR). Model dữ liệu đầy đủ nhưng tầng API/tokens
để App di động gọi vào **chưa được xây**.

---

## 1. THANH TOÁN (Payment)

### 1.1 Có khái niệm "payment intent" không?
**KHÔNG.** Không có bảng/model payment intent, không có checkout session, không có tích hợp
cổng thanh toán thực (VNPay/Momo/QR động thời gian thực).

Có 2 nhánh thanh toán tách biệt, cả hai đều là ghi nhận thủ công/hậu kỳ:

**A. Billing SaaS (tenant ↔ nền tảng) — Batch 07, đã có API (chỉ platform admin):**
- Model: `BillingInvoice`, `BillingInvoiceLine`, `BillingPayment`, `BillingReconciliation`,
  `BillingAdjustment`, `CreditNote`, `BillingRun`, `BillingPeriod`, `PassThroughWallet`,
  `PassThroughTransaction`, `TenantSubscription`.
- Controller: `app/Http/Controllers/Platform/Billing/BillingInvoiceController.php`.
- Ghi nhận thanh toán: `recordPayment()` (dòng 105-121). Chỉ tạo `BillingPayment` với
  `status = 'confirmed'` rồi cộng dồn `paid_amount`. **Đây là ghi nhận nội bộ, không phải
  giao dịch thật với ngân hàng/cổng.**

**B. Phí cư dân (resident fee) — CÓ MODEL, KHÔNG CÓ API:**
- Model tồn tại: `Payment`, `PaymentAllocation`, `PaymentMethod`, `PaymentRequest`,
  `PaymentGatewayConfig`, `QrPaymentToken`, `Statement`/`StatementLine`, `Debt`,
  `Receipt`, `Wallet`/`WalletTransaction`/`WalletTopupRequest`, `Fund`/`FundTransaction`,
  `BankAccount`, `BankTransaction`, `BankStatementImport`, `ReconciliationMatch`.
- `app/Models/QrPaymentToken.php` ("Mã QR thanh toán cho bảng kê/công nợ") liên kết
  `Statement`, `Debt`, `Payment` — hạ tầng cho QR thanh toán ĐÃ có ở mức bảng.
- `app/Models/PaymentGatewayConfig.php` ("Cấu hình cổng thanh toán theo tenant") — bảng cấu
  hình cổng theo tenant tồn tại nhưng **không có mã tích hợp cổng thực tế** (không có
  controller callback/return-url, không có client SDK).
- **Không có endpoint nào để cư dân tạo/xem thanh toán phí.** Đây là mảng resident-facing
  bị thiếu hoàn toàn ở tầng API.

### 1.2 Webhook & retry
- Model: `WebhookEndpoint`, `WebhookDeliveryAttempt`, `WebhookEventGroup`, `IntegrationRetryJob`.
- Controller: `app/Http/Controllers/Platform/Integration/WebhookEndpointController.php`.
- **QUAN TRỌNG — webhook là MÔ PHỎNG, không phải gửi thật:** `test()` (dòng 47-73) sinh
  `duration_ms = random_int(60,700)`, `http_status` cứng 200/500 theo trạng thái, body giả
  `{"received":true}`. Không có HTTP client gọi ra ngoài, không có ký HMAC payload thực khi gửi.
- `IntegrationRetryJob` (`app/Models/IntegrationRetryJob.php`) chỉ là bảng hàng đợi; không có
  job worker gửi lại webhook ra hệ thống ngoài. Replay (`IntegrationEventController::replay`)
  chỉ enqueue bản ghi, không thực thi gửi.
- **Không có endpoint nhận webhook vào (inbound) từ cổng thanh toán** → không thể tự động đối
  soát giao dịch cổng.

### 1.3 Idempotency
- **Không có idempotency key cho thanh toán.** `recordPayment()` không nhận/kiểm khóa idempotency;
  `transaction_ref` là `nullable`, không unique, không kiểm trùng → **gọi 2 lần = ghi 2 payment**,
  cộng dồn `paid_amount` sai.
- Từ khóa `idempoten*` chỉ xuất hiện ở module Integration (Batch 08): cột
  `integration_connections.idempotency_enabled` (chỉ là cờ cấu hình, không thực thi ở tầng
  thanh toán) và tại `IntegrationEventController::replay` (chống enqueue trùng replay). Không áp
  cho billing/payment.

### 1.4 Hóa đơn / biên lai
- `BillingInvoice`/`BillingInvoiceLine` (SaaS), `Statement`/`StatementLine` (bảng kê phí cư dân),
  `Receipt` (`app/Models/Receipt.php` — biên lai gắn `Payment`), `CreditNote`.
- Không có sinh PDF hóa đơn/biên lai cho cư dân qua API (chỉ có `smalot/pdfparser` để ĐỌC PDF,
  dùng cho trích xuất knowledge base — `app/Support/Knowledge/DocumentTextExtractor.php`).

---

## 2. THÔNG BÁO (Notification)

### 2.1 Kênh đã nối dây
- Bảng: `notifications`, `notification_channels`, `notification_delivery_logs`,
  `notification_audiences`, `notification_reads` (model tương ứng đã có).
- `app/Models/NotificationChannel.php` khai báo kênh **`app|email|sms|zalo|push`** — nhưng đây
  chỉ là enum/nhãn dữ liệu, KHÔNG có engine gửi.
- **KHÔNG có engine phát thông báo thực.** `NotificationDeliveryLog::create` chỉ xuất hiện trong
  `database/seeders/DemoDataSeeder.php` (dữ liệu demo). Không có service dispatch, không có job,
  không có notification class Laravel nào ghi delivery log ở runtime.
- Các lời gọi `Notification::make()->...->send()` khắp `app/Filament/**` là **toast UI của
  Filament**, không phải thông báo tới cư dân.
- **Email THẬT chỉ tồn tại ở đúng 1 luồng:** đặt lại mật khẩu cư dân
  (`app/Filament/Concerns/ResetsResidentPassword.php` → `deliverResidentMail()` dùng
  `Mail::html(...)`). SMS/Zalo là **stub**: nếu kênh ≠ email và không đặt `mail.test_to` thì
  `return null` (không gửi gì) — xem `deliverResidentMail()` dòng ~137-155.

### 2.2 FCM / device token / push
- **KHÔNG có gì.** Tìm `device_token|fcm|firebase|apns|push_token|expo` trên toàn bộ
  `app/ config/ database/ routes/` → 0 kết quả thực (chỉ trùng chuỗi "export"/"ReportExport").
- Không có bảng device token, không có package push, `config/services.php` không có khối FCM/APNs.
- Kênh `push` trong `NotificationChannel` là **nhãn suông, không có hạ tầng**.

### 2.3 Cấu trúc `notification_delivery_logs`
- Model `app/Models/NotificationDeliveryLog.php`: cast `sent_at:datetime`, quan hệ
  `belongsTo(Notification)` và `belongsTo(User)`. Đủ để làm log gửi per-user, nhưng hiện chưa
  có tiến trình nào ghi vào (ngoài seeder).

### 2.4 Tùy chọn nhận thông báo (per-user preference)
- **KHÔNG có bảng preference per-user.** Không tìm thấy notification_preferences hay tương tự.
  Đối tượng nhận được mô tả ở mức chiến dịch qua `notification_audiences` (owner_level
  platform/tenant/project — xem `scopeVisibleTo` trong `app/Models/Notification.php`), không phải
  theo lựa chọn của từng cư dân.

---

## 3. UPLOAD / TỆP

### 3.1 Disk (`config/filesystems.php`)
- `local` → `storage/app/private`, `serve=true` (riêng tư, không public URL trực tiếp).
- `public` → `storage/app/public`, có URL `APP_URL/storage`, **`visibility=public`**.
- `s3` → cấu hình sẵn theo biến môi trường (tên biến: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`,
  `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`, `AWS_ENDPOINT`) — chưa dùng làm mặc định.
- Mặc định: biến `FILESYSTEM_DISK` (fallback `local`).

### 3.2 Cách xử lý upload
- Chỉ qua **Filament `FileUpload`** trong panel (không có endpoint upload API cho cư dân).
- Ảnh KYC cư dân — `app/Filament/Resources/Residents/Schemas/ResidentForm.php`:
  - `avatar_path` (dòng 28-32): `->image()->disk('public')`.
  - `id_front_path`, `id_back_path`, `portrait_path` (dòng 104-106):
    `->image()->disk('public')->directory('residents/kyc')` — **ảnh giấy tờ tùy thân lưu trên
    disk PUBLIC** (xem chi tiết rủi ro ở SECURITY_AND_PRIVACY_AUDIT.md).
  - `documents` (dòng 125-131): `->disk('public')`,
    `->acceptedFileTypes(['application/pdf','image/jpeg','image/png'])`, `->maxSize(10240)` (10MB).
- Các upload khác cũng dùng disk `public`: `AiKnowledgeBase` (kb-attachments),
  `MarketplaceProductForm` (image), `PlatformContentForm`/`PlatformContentCms` (cover),
  `DesignSystemSet1` (ds-demo).

### 3.3 Validation (mime/size)
- Ảnh KYC (`id_front_path`/`id_back_path`/`portrait_path`) chỉ có `->image()`;
  **không giới hạn `maxSize`, không giới hạn `acceptedFileTypes` chi tiết** → có thể upload ảnh
  rất lớn.
- Chỉ `documents` có `maxSize(10240)` + `acceptedFileTypes` rõ ràng.

### 3.4 Signed URL / truy cập tệp riêng tư
- **Không dùng signed URL, không có gateway tải tệp riêng tư.** Avatar dùng
  `Storage::disk('public')->url(...)` (`app/Models/Resident.php` — `avatarUrl`), tức URL công khai
  cố định, không hết hạn, không kiểm quyền.
- Disk `local` (private) có `serve=true` nhưng KYC lại đặt trên `public` → không tận dụng.

### 3.5 Trường JSON/ảnh trên residents/apartments
- `residents.documents` (json, cast `array` trong `app/Models/Resident.php`),
  `avatar_path`, `id_front_path`, `id_back_path`, `portrait_path`, `face_match_status`
  (migration `2026_06_30_000004_extend_residents_kyc_avatar.php`).
- `users` cũng có `avatar_path`, `id_no`, `dob`, `gender` (migration `..._000005_global_identity...`).

---

## 4. Điểm CHẶN cho App di động (mobile-blocking) — tổng hợp

| # | Hạng mục thiếu | Mức chặn | Bằng chứng |
|---|---|---|---|
| 1 | **Không có API hướng cư dân** (mọi route chỉ `platform.admin`) | CHẶN CỨNG | `routes/api.php`, `EnsurePlatformAdmin.php` |
| 2 | **Không có phát hành/thu hồi token Sanctum cho cư dân** (dù User có `HasApiTokens`) | CHẶN CỨNG | `app/Models/User.php`; không có `createToken` ngoài Password broker |
| 3 | **Không có hạ tầng push** (FCM/APNs/device token) | CHẶN (push) | tìm 0 kết quả |
| 4 | **Không có engine gửi thông báo thực** (delivery log chỉ seeder) | CHẶN (in-app/notify) | `NotificationDeliveryLog::create` chỉ ở seeder |
| 5 | **Không có API thanh toán phí cư dân + không tích hợp cổng thực** | CHẶN (thanh toán) | `PaymentGatewayConfig` chỉ là bảng; không có callback |
| 6 | **Không có idempotency cho thanh toán** | CAO (rủi ro ghi trùng) | `BillingInvoiceController::recordPayment` |
| 7 | **Webhook là mô phỏng** (không gửi/nhận thật) → không auto đối soát | TRUNG BÌNH | `WebhookEndpointController::test` random |
| 8 | **Không có endpoint upload cho cư dân + không signed URL** | CHẶN (KYC/tệp) | chỉ Filament `FileUpload`; `Storage::url` public |
| 9 | **SMS/Zalo là stub** (chỉ email thật ở luồng reset mật khẩu) | TRUNG BÌNH | `ResetsResidentPassword::deliverResidentMail` |
| 10 | **Không có preference nhận thông báo per-user** | THẤP | không có bảng |

**Điểm sáng để tái sử dụng:** model dữ liệu cho gần như mọi nghiệp vụ mobile ĐÃ đầy đủ
(Payment/Statement/Debt/QrPaymentToken, Notification*, Resident/Apartment/Relation,
Feedback*, Amenity*/Booking, Visitor*/AccessCard, EmergencyContact...). Việc còn lại chủ yếu là
xây **tầng API + tokens + policies + resource** trên nền model sẵn có.
