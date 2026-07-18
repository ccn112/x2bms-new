# Thứ tự triển khai backend đề xuất — phục vụ App di động cư dân

> READ-ONLY audit. Phiên bản: 2026-07-18. Nguồn: `D:\Chinh\x2\x2bms`.
> Nguyên tắc nền: **model dữ liệu đã có gần đủ; tầng API chưa tồn tại.** Toàn bộ API hiện tại
> chỉ phục vụ SuperAdmin (`platform.admin`, session-based). Vì vậy mọi slice đều phụ thuộc vào
> **Phase 0** dưới đây.

---

## PHASE 0 — Nền tảng API (BẮT BUỘC, chặn mọi slice)

Không slice nào chạy được nếu thiếu Phase 0. Đây là điều kiện tiên quyết.

**0.1 Phát hành token Sanctum cho cư dân**
- Đã có: `User` dùng `HasApiTokens` (`app/Models/User.php`), Sanctum 4 trong `composer.json`,
  bảng `personal_access_tokens` (migration Sanctum mặc định).
- Phải xây: endpoint đăng nhập cư dân (email/phone + mật khẩu, hoặc OTP) → `createToken()` gắn
  tên thiết bị + abilities; endpoint đăng xuất/thu hồi token; danh sách thiết bị.
- Cấu hình `config/auth.php` guard `sanctum` cho nhóm route mobile; nhóm route mới
  `routes/api.php` với `auth:sanctum` (KHÔNG dùng lại `platform.admin`).
- Blocker: hiện chưa có bất kỳ luồng `createToken` nào (chỉ có Password broker trong
  `ResetsResidentPassword.php`).

**0.2 Thiết lập ngữ cảnh tenant/project từ token**
- Đã có: scope `BelongsToTenant`/`BelongsToProject` chạy theo `auth()->user()`.
- Phải xây: middleware set ngữ cảnh sau `auth:sanctum` để scope hoạt động đúng cho request API
  (xem SECURITY §1.1 — rủi ro rò chéo-tenant nếu thiếu). Với cư dân đa-membership
  (`User::residentMemberships()` bỏ scope tenant có chủ đích), cần chọn membership/tenant hiện hành.

**0.3 Chuẩn phản hồi API (response standard)**
- Đã có: `bootstrap/app.php` bật `shouldRenderJsonWhen(api/*)` (lỗi trả JSON).
- Phải xây: envelope thống nhất (data/meta/errors), mã lỗi, phân trang chuẩn, versioning
  (`/api/v1/...`). Cân nhắc dùng `dedoc/scramble` (đã có) để sinh OpenAPI cho mobile team.

**0.4 Tầng API Resource + Policy cho phạm vi cư dân**
- Đã có: `spatie/permission` (RBAC 3 tầng), `UserRoleScope`, các Policy trong `app/Policies`.
- Phải xây: `JsonResource` cho từng model mở ra (che PII — SECURITY §2.3), Policy/ability riêng
  cho cư dân (chỉ đọc/ghi dữ liệu của chính mình + căn hộ liên kết qua
  `ResidentApartmentRelation`).

**0.5 Rào bảo mật tối thiểu (kèm Phase 0)**
- `throttle` cho login/OTP/upload/payment (SECURITY §6.1).
- Chuyển ảnh KYC sang disk private + signed URL (SECURITY §2.1/§8.1).
- Giới hạn thử/sinh OTP (SECURITY §7.1).

---

## SLICE 1 — Kích hoạt & định danh (Activation & Identity)
**Mục tiêu:** cư dân đăng nhập, lấy token, đăng ký thiết bị.

- **Đã có để tái sử dụng:**
  - `User` (account_type='resident', `HasApiTokens`, `isResident()`), `GlobalUserAccount`,
    `Resident` + `ResidentApartmentRelation` + `linkedUser()` (`app/Models/Resident.php`).
  - Liên kết cư dân ↔ tài khoản qua CCCD (`id_no`) — migration `..._000005_global_identity...`;
    các model `ResidentBindingRequest`, `ResidentUnitBinding`, `ResidentApprovalRequest`.
  - Hạ tầng OTP/reset (cache OTP + Password broker) trong
    `app/Filament/Concerns/ResetsResidentPassword.php` (logic có thể trích ra service dùng chung).
  - `LoginSession`, `TwoFactorSetting` (bảng đã có).
- **Phải xây:**
  - Service OTP dùng chung (rời khỏi trait Filament) + endpoint gửi/verify OTP có rate-limit.
  - Endpoint login/refresh/logout token; đăng ký device token (bảng device token — CHƯA CÓ, xem
    PAYMENT §2.2).
  - Luồng kích hoạt: cư dân nhập CCCD/mã căn → khớp `Resident` → tạo/liên kết `User`.
- **Phụ thuộc:** Phase 0 (0.1, 0.2, 0.5).
- **Blocker:** chưa có bảng device token; SMS/Zalo là stub (chỉ email thật) — nếu kích hoạt qua
  SMS phải nối gateway trước (PAYMENT §2.1).

---

## SLICE 2 — Hóa đơn & thanh toán (Invoice & Payment)
**Mục tiêu:** cư dân xem bảng kê/công nợ và thanh toán.

- **Đã có để tái sử dụng:**
  - `Statement`/`StatementLine`, `Debt`, `Payment`/`PaymentAllocation`, `Receipt`,
    `QrPaymentToken` (đã liên kết Statement/Debt/Payment — `app/Models/QrPaymentToken.php`),
    `PaymentMethod`, `PaymentGatewayConfig`, `Wallet`/`WalletTransaction`.
  - Đối soát: `BankTransaction`, `BankStatementImport`, `ReconciliationMatch`.
- **Phải xây:**
  - API đọc bảng kê/công nợ theo căn hộ của cư dân (Policy chỉ trong phạm vi
    `ResidentApartmentRelation`).
  - **Tích hợp cổng thanh toán thật** (VNPay/Momo...): `PaymentGatewayConfig` mới là bảng cấu
    hình, chưa có client/callback (PAYMENT §1.1B).
  - **Khóa idempotency** cho ghi nhận thanh toán (PAYMENT §1.3 — `recordPayment` hiện có thể ghi
    trùng).
  - Endpoint nhận webhook/callback INBOUND từ cổng (hiện webhook chỉ mô phỏng — PAYMENT §1.2) để
    tự đối soát.
  - Sinh QR động thực tế từ `QrPaymentToken`.
- **Phụ thuộc:** Phase 0; Slice 1 (định danh để biết căn hộ của cư dân).
- **Blocker:** không có tích hợp cổng thực; không có idempotency; webhook mô phỏng → chưa auto
  đối soát. Đây là slice nặng nhất về phần "phải xây mới".

---

## SLICE 3 — Phản ánh (Feedback)
**Mục tiêu:** cư dân gửi phản ánh, đính kèm ảnh, theo dõi trạng thái.

- **Đã có để tái sử dụng:**
  - `FeedbackRequest`, `FeedbackCategory`, `FeedbackComment`, `FeedbackAttachment`,
    `FeedbackAssignment`, `FeedbackStatusHistory`, `SlaPolicy`/`SlaEvent`.
  - Chuyển tiếp sang thi công: `WorkOrder` + `WorkOrderAssignment`/`Attachment`/`Checklist`.
- **Phải xây:**
  - API tạo/list/xem feedback của chính cư dân + timeline trạng thái.
  - Endpoint upload đính kèm (dùng disk private + validate mime/size — SECURITY §8.2; PAYMENT §3.3).
  - Thông báo cập nhật trạng thái tới cư dân (phụ thuộc engine notify — xem Blocker chung).
- **Phụ thuộc:** Phase 0; Slice 1.
- **Blocker:** chưa có endpoint upload cho cư dân; chưa có engine gửi thông báo trạng thái
  (PAYMENT §2.1). Slice này **nhẹ nhất**, tốt để làm slice mẫu chứng minh Phase 0.

---

## SLICE 4 — Đặt tiện ích (Amenity Booking)
**Mục tiêu:** cư dân xem lịch, đặt chỗ, nhận QR vào cửa.

- **Đã có để tái sử dụng:**
  - `Amenity`, `AmenitySlot`, `AmenityBooking`, `BookingQrPass`.
- **Phải xây:**
  - API xem tiện ích + slot còn trống, tạo booking (chống double-booking: khóa slot/transaction).
  - Sinh `BookingQrPass` + xác thực QR tại cổng.
  - Quy tắc nghiệp vụ (giới hạn số booking, hủy, phí nếu có → nối Slice 2).
- **Phụ thuộc:** Phase 0; Slice 1; (tùy chọn) Slice 2 nếu tiện ích thu phí.
- **Blocker:** không có blocker hạ tầng lớn; chủ yếu cần logic chống trùng slot + xác thực QR.

---

## SLICE 5 — Khách & ra vào (Guest & Access)
**Mục tiêu:** cư dân đăng ký khách, cấp QR/thẻ ra vào tạm.

- **Đã có để tái sử dụng:**
  - `VisitorRegistration`, `VisitorPass`, `AccessCard`, `AccessDevice`, `AccessLog`,
    `IntercomEvent`, `Vehicle`; cảnh báo: `SosAlert`, `EmergencyAlert`.
- **Phải xây:**
  - API đăng ký khách của cư dân + sinh QR/pass tạm thời (hết hạn).
  - Endpoint cho thiết bị cổng xác thực pass (có thể là API riêng cho `AccessDevice`, khác token
    cư dân).
  - Ghi `AccessLog` khi qua cổng.
- **Phụ thuộc:** Phase 0; Slice 1; (chia sẻ hạ tầng QR/signed token với Slice 4).
- **Blocker:** cần chuẩn xác thực cho thiết bị cổng (machine-to-machine) — khác với token cư dân;
  hạ tầng device auth chưa có.

---

## SLICE 6 — Hồ sơ & hộ khẩu (Profile & Household)
**Mục tiêu:** cư dân xem/cập nhật hồ sơ, thành viên hộ, liên hệ khẩn, KYC.

- **Đã có để tái sử dụng:**
  - `Resident`, `ResidentApartmentRelation`, `ResidentEmergencyContact`, `Apartment`;
    trường KYC `id_front_path`/`id_back_path`/`portrait_path`/`face_match_status`/`documents`.
  - Liên kết đa-membership: `User::residentMemberships()`.
- **Phải xây:**
  - API đọc/cập nhật hồ sơ của chính cư dân (che PII — SECURITY §2.3; whitelist trường sửa được).
  - Upload KYC an toàn (private disk + signed URL + validate — SECURITY §2.1/§8.2).
  - Quản lý thành viên hộ (thêm/xóa liên hệ khẩn, đề nghị liên kết thành viên qua
    `ResidentBindingRequest`/duyệt).
  - Consent quyền riêng tư khi thu thập CCCD (SECURITY §3.1 — bảng consent CHƯA CÓ).
- **Phụ thuộc:** Phase 0; Slice 1.
- **Blocker:** ảnh KYC hiện ở disk public (phải sửa trước khi mở API — SECURITY §2.1); chưa có
  theo dõi consent.

---

## Blocker dùng chung (ảnh hưởng nhiều slice)

| Blocker | Ảnh hưởng slice | Tham chiếu |
|---|---|---|
| Chưa có tầng API + token cư dân | TẤT CẢ | Phase 0 |
| Chưa có engine gửi thông báo (in-app/push) + bảng device token | 1,3,4,5 (mọi thông báo đẩy) | PAYMENT §2.1-2.3 |
| SMS/Zalo là stub (chỉ email thật) | 1 (OTP qua SMS), 2/3 (nhắc nợ/trạng thái) | PAYMENT §2.1 |
| Chưa tích hợp cổng thanh toán thật + không idempotency + webhook mô phỏng | 2 (và 4 nếu thu phí) | PAYMENT §1.1-1.3 |
| Ảnh KYC ở disk public, upload thiếu validate | 6 (và mọi slice có upload: 3) | SECURITY §2.1/§8 |
| Không rate-limit + OTP không giới hạn | 1 (và toàn API) | SECURITY §6/§7 |

## Thứ tự đề xuất tổng
**Phase 0 → Slice 1 → Slice 3 (mẫu nhẹ) → Slice 6 → Slice 4 → Slice 5 → Slice 2 (nặng nhất, để
sau khi hạ tầng đủ chín).**
Lý do đảo Slice 2 về cuối: phụ thuộc tích hợp cổng thật + idempotency + webhook inbound — khối
lượng và rủi ro cao nhất, nên làm khi Phase 0 và các slice nhẹ đã ổn định.
