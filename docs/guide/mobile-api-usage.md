# Hướng dẫn sử dụng Mobile API `/api/v1`

Dành cho lập trình viên app (Cư dân/BQL) và đối tác tích hợp. API **stateless**, xác thực bằng **Bearer token** (Sanctum). Chi tiết kiến trúc: `x2/docs/ARCHITECTURE_X2_PLATFORM_V1.md`.

> ⚠️ Trạng thái: Phase 0 đợt 1 — **đã verify end-to-end qua HTTP thật (2026-07-18)**: login → me/bootstrap → refresh (rotate) → logout, kèm 401 cho token sai/thiếu. Một số phần vẫn là skeleton (OTP chưa nối SMS thật, đăng ký tài khoản chưa có). Xem "Giới hạn hiện tại".

## 1. Base URL (theo môi trường) — CHỐT 2026-07-21
| Env | Base URL (`X2_API_BASE_URL`) |
|---|---|
| **PROD (internet)** | `https://x2.fino.vn/api/v1` |
| **Local (Herd)** | `https://x2bms.test/api/v1` |
| Local (artisan serve) | `http://127.0.0.1:8123/api/v1` |

- 1 codebase phục vụ nhiều panel theo **path**; API mobile ở path `/api/v1` cùng domain (không subdomain riêng).
- **Herd HTTPS (`x2bms.test`):** cert do Herd cấp (trusted trên máy). App/Dio chạy trên **thiết bị/emulator khác máy** có thể chưa tin cert → khi test qua `x2bms.test` dùng máy đã trust, hoặc dùng `http://127.0.0.1:8123` cho test nhanh.
- Flutter: truyền `--dart-define X2_API_BASE_URL=https://x2.fino.vn/api/v1` (prod) / `https://x2bms.test/api/v1` (local).

## 2. Headers chuẩn (mọi request)
| Header | Bắt buộc | Ghi chú |
|---|---|---|
| `Accept: application/json` | ✅ | |
| `Authorization: Bearer <access_token>` | với endpoint bảo vệ | |
| `X-Device-Id: <uuid>` | ✅ khi login/refresh | ID cài đặt cố định của thiết bị (token gắn theo device) |
| `X-Request-Id: <uuid>` | nên có | được echo lại trong `meta.request_id` để trace |
| `X-App-Version`, `X-Platform` (ios\|android) | nên có | |
| `X-Locale`, `X-Timezone` | tuỳ chọn | mặc định vi-VN / Asia/Ho_Chi_Minh |

## 3. Envelope (định dạng phản hồi)
**Thành công:**
```json
{ "data": { ... }, "meta": { "request_id": "...", "server_time": "2026-07-18T10:00:00+07:00" } }
```
**Lỗi:**
```json
{ "error": { "code": "AUTH_INVALID_CREDENTIALS", "message": "...", "retryable": false, "fields": {"identifier": ["..."]} },
  "meta": { "request_id": "..." } }
```
> `fields` chỉ có khi lỗi validation. `retryable=true` nghĩa là client có thể thử lại (timeout/5xx/429/OTP sai).

## 4. Luồng xác thực
### 4.1 Đăng nhập mật khẩu
```
POST /auth/login          Headers: X-Device-Id
Body: { "identifier": "<phone hoặc email>", "password": "..." }
→ 200 { data: { tokens: { access_token, refresh_token, access_expires_at, refresh_expires_at, abilities }, user } }
```
### 4.2 Đăng nhập/đăng ký bằng OTP
```
POST /auth/otp/request  Body: { channel: "phone|email", destination, purpose: "login|register|resident_activation" }
→ 200 { data: { sent, expires_in, dev_code? } }   # dev_code CHỈ có ở môi trường không phải production
POST /auth/otp/verify   Body: { channel, destination, purpose, code }
→ (purpose=login, user tồn tại) 200 { data: { verified: true, tokens: {...} } }
```
### 4.3 Làm mới token (rotate)
```
POST /auth/refresh   Authorization: Bearer <refresh_token>
→ 200 { data: { tokens: {...} } }    # refresh cũ + access cũ của device bị thu hồi, trả cặp mới
```
> App phải dùng **refresh mutex** (chỉ 1 request refresh cùng lúc). Refresh thất bại → về màn login nhưng giữ cache public.

### 4.4 Đăng xuất
```
POST /auth/logout   Authorization: Bearer <access_token>   → thu hồi token của device.
```

## 5. Bootstrap (khởi tạo app)
```
GET /public/bootstrap    (no auth)  → branding, enabled_modules, minimum_app_version
GET /me/bootstrap        (Bearer)   → experience_mode, user, available_contexts, enabled_modules, unread_notification_count
```
`experience_mode` ∈ `public | member | resident_applicant | verified_resident` — quyết định app render trải nghiệm nào.
`available_contexts` liệt kê các "context" người dùng có thể chọn: căn hộ (cư dân) và scope (BQL).

## 6. Thiết bị & push
```
POST   /me/devices                    Body: { installation_id(uuid), platform, provider?, push_token?, app_version?, locale?, timezone?, notification_permission? }
DELETE /me/devices/{installationId}   → gỡ user khỏi device (giữ subscription public)
```

## 6b. Nghiệp vụ cư dân (yêu cầu ability `resident`)
```
GET /resident/statements?per_page=20        → { data:[...], meta:{ next_cursor, has_more } }   # hóa đơn, cursor paginate
GET /resident/statements/{id}                → { data:{ ...statement, lines:[...] } }
```
- Chỉ trả hóa đơn thuộc **căn hộ của chính người dùng** (scope theo quan hệ cư dân–căn hộ).
- Dùng header `X-Context-Id: apartment:<relationId>` để giới hạn theo 1 căn cụ thể.
- Token có ability `staff` (BQL) gọi nhóm này sẽ nhận **403** — đây là ranh giới cư dân/BQL.
- Tiền trả dạng **chuỗi** (`"1500000.00"`), không phải số thực; ngày ISO-8601.

## 6c. File riêng tư (KYC / tài liệu)
Ảnh CCCD/chân dung và tài liệu pháp lý **không có URL công khai**. Chúng được phục vụ qua URL **ký (signed) + đăng nhập + kiểm quyền** (`media/residents/...`). Backend sinh URL ký ngắn hạn khi cần hiển thị; app không tự đoán đường dẫn file.

## 6d. X2AI chat (SSE — auth tuỳ chọn)
```
POST /ai/chat                      Body: { message, session_id?, surface? }   Header: X-Device-Id (nếu ẩn danh)
  → text/event-stream: data:{"type":"delta","text":"…"} … data:{"type":"done","session_id":N}
  → lỗi tiền điều kiện trả JSON+status: 400 empty/device_required · 413 input_too_long · 429 rate_limited/daily_limit/register_for_more
GET  /ai/chat/sessions             → danh sách phiên (theo user hoặc device)
GET  /ai/chat/sessions/{id}        → tin nhắn để resume (kiểm quyền theo user/device)
```
- **Ẩn danh** (app public): gửi `X-Device-Id`, chỉ dùng tác vụ cơ bản; khi cần tác vụ nâng cao → mã `register_for_more` → app mở **Action Gate** đăng nhập.
- **Đã đăng nhập / web**: gửi `Authorization: Bearer` (web Filament tự định danh). Cap ngày cao hơn.
- Provider mặc định **Anthropic Haiku 4.5**; local dùng `CHAT_PROVIDER=fake` để thử luồng không tốn phí.

## 7. Mã lỗi thường gặp
| HTTP | code | Ý nghĩa |
|---|---|---|
| 401 | `AUTH_INVALID_CREDENTIALS` / `AUTH_UNAUTHENTICATED` / `AUTH_REFRESH_INVALID` | sai đăng nhập / thiếu-hết token / refresh sai |
| 403 | `FORBIDDEN` | không đủ quyền |
| 404 | `NOT_FOUND` / `AUTH_USER_NOT_FOUND` | không tìm thấy |
| 422 | `VALIDATION` / `OTP_MISMATCH` / `OTP_EXPIRED` / `OTP_TOO_MANY_ATTEMPTS` | dữ liệu/OTP sai |
| 429 | `RATE_LIMITED` | quá nhiều yêu cầu (retryable) |
| 500 | `SERVER_ERROR` | lỗi hệ thống (retryable) |

## 8. Rate limit
| Nhóm | Giới hạn |
|---|---|
| OTP | 5 / 10 phút / destination |
| Login | 10 / phút / (ip + device) |
| Đọc public | 120 / phút / ip |
| Có token | 300 / phút / user |

## 9. Giới hạn hiện tại (Phase 0 đợt 1)
- OTP **chưa gửi SMS/Zalo thật** — môi trường dev trả `dev_code` để test.
- **Chưa có** `POST /auth/register` (tạo tài khoản) — sẽ bổ sung đợt sau.
- **Chưa có** context header stateless (`X-Context-Id`), private KYC, policy per-domain, push fan-out.
- Danh tính: **`User` là chủ thể đăng nhập duy nhất**; stack `GlobalUserAccount` không dùng cho mobile.

---

## 10. Slice 0 — Auth (CHỐT + verify E2E 2026-07-21)

> Hợp đồng đóng băng cho Slice 0. Cả 2 repo bám mục này. Nguồn: `x2bms` (server) ↔ `x2mobile` (`RemoteAuthRepository`).

**Đã verify E2E thật** (flutter test → backend live, tài khoản cư dân `0900000555`): login mật khẩu → tokens ✅ · sai mật khẩu → `Err` ✅ · `otp/request` → challenge ✅ · `me/bootstrap` (Bearer access) → 200 ✅.

**Mẫu login đã xác nhận:**
```
POST /api/v1/auth/login   Headers: Accept: application/json · X-Device-Id: <uuid>
Body: { "identifier": "0900000555", "password": "…" }
→ 200 { "data": { "tokens": { "access_token","refresh_token","access_expires_at","refresh_expires_at","abilities":["resident"] }, "user": {…} }, "meta": {…} }
```
**OTP (Flutter map từ `identifier`):** `channel` = có `@` → `email`, ngược lại `phone`; `destination` = identifier; `purpose` = `login`|`register`. `otp/verify` (purpose=login, user tồn tại) trả `data.tokens`.

**⚠️ BẮT BUỘC (bug đã trả giá):** `X2_API_BASE_URL` / baseUrl **phải để path tương đối không có `/` đầu** (`auth/login`) và client tự thêm `/` cuối baseUrl — nếu baseUrl `.../api/v1` (thiếu `/`) + path `auth/login`, Dio **rớt `/v1`** → 404 mọi endpoint. (Đã fix ở `ApiClient` x_core: tự chuẩn hóa `/` cuối.)

**Trạng thái wiring mobile (x2mobile) — Slice 0 nền auth ĐÓNG (verify E2E):**
- ✅ `RemoteAuthRepository`: login / requestOtp / verifyOtp / register (register client tạm gọi `otp/request?purpose=register`).
- ✅ `ApiClient` baseUrl trailing-slash fix.
- ✅ `onRefresh` (auto-refresh khi 401): dùng Dio trần gọi `POST auth/refresh` với **refresh-token** làm Bearer → lưu token mới; ApiClient bọc mutex 1-lần. **E2E verify:** refresh xoay token + access cũ bị thu hồi.
- ⏸️ **Backend `POST /auth/register` (server-side) — HOÃN, chờ quyết định chủ dự án.** `AUTH_AND_RESIDENT_IDENTITY` KHÔNG định nghĩa luồng self-registration. Câu hỏi cần chốt: đăng ký tạo `public_user` rồi nâng lên `resident`? có bước BQL duyệt (dùng `resident_approval_requests`)? định danh bằng CCCD/SĐT match? → làm ở **Slice 1 (Auth + Kích hoạt)** cùng ngữ cảnh duyệt/kích hoạt.

**Tài khoản test (dev seed):** cư dân `0900000555` / `Resident@2026!` (email `nguyenvananh@gmail.com`).
