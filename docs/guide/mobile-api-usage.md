# Hướng dẫn sử dụng Mobile API `/api/v1`

Dành cho lập trình viên app (Cư dân/BQL) và đối tác tích hợp. API **stateless**, xác thực bằng **Bearer token** (Sanctum). Chi tiết kiến trúc: `x2/docs/ARCHITECTURE_X2_PLATFORM_V1.md`.

> ⚠️ Trạng thái: Phase 0 đợt 1 — **đã verify end-to-end qua HTTP thật (2026-07-18)**: login → me/bootstrap → refresh (rotate) → logout, kèm 401 cho token sai/thiếu. Một số phần vẫn là skeleton (OTP chưa nối SMS thật, đăng ký tài khoản chưa có). Xem "Giới hạn hiện tại".

## 1. Base URL (theo môi trường)
| Env | Base URL |
|---|---|
| DEV | `https://api-dev.x2bms.vn/api/v1` |
| STAGING | `https://api-staging.x2bms.vn/api/v1` |
| PROD | `https://api.x2bms.vn/api/v1` |

Cục bộ: `http://127.0.0.1:8000/api/v1` (sau `php artisan serve`).

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
