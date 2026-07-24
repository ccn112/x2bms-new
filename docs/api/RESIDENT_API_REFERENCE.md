# Resident API — Tài liệu tham chiếu (Reference)

_Backend: x2bms (Laravel) · App: x2mobile (Flutter) · Cập nhật: 2026-07-24_

> Tài liệu **API cho app cư dân**: mỗi endpoint kèm method/path, scope, tham số, và
> **shape response thật** (đã verify HTTP trên `https://x2bms.test`). Nguồn domain
> (ánh xạ cột/model) ở `docs/contracts/RESIDENT_API_DOMAIN.md`. Tài liệu vận hành
> (seed/ENV/deploy) ở `docs/api/RESIDENT_API_OPERATIONS.md`.

## Quy ước chung

- **Base:** `/api/v1` · **Site dev:** `https://x2bms.test`
- **Auth:** `Authorization: Bearer <token>` (Sanctum, ability `resident`). Lấy token qua `POST /api/v1/auth/login`.
- **Context:** header tuỳ chọn `X-Context-Id: apartment:{relationId}` để thu hẹp về 1 căn hộ.
- **Envelope thành công đơn:** `{ "data": {...}, "meta": { request_id, server_time } }`
- **Envelope danh sách (cursor):** `{ "data": [...], "meta": { next_cursor, request_id, server_time } }`
- **Envelope lỗi:** `{ "error": { code, message }, "meta": {...} }` + HTTP status.
- **Tiền:** chuỗi decimal (không float). **Ngày:** ISO-8601 / `YYYY-MM-DD`. **Field:** `snake_case`.
- **Cursor pagination:** `?cursor=<token>&per_page=<≤50>`; hết trang → `meta.next_cursor = null`.

---

## 1. Tài khoản & bootstrap

| Method | Path | Mô tả |
|---|---|---|
| POST | `/auth/login` | Đăng nhập → `data.tokens{access,refresh}` |
| POST | `/auth/otp/request`, `/auth/otp/verify`, `/auth/register`, `/auth/refresh` | Luồng OTP/đăng ký/refresh |
| GET | `/me/bootstrap` | Hồ sơ + module bật + contexts + `unread_notification_count` |
| PATCH | `/me/profile` | Cập nhật `name/phone/email/gender` (partial) |
| POST/DELETE | `/me/devices`, `/me/devices/{installationId}` | Đăng ký/huỷ thiết bị push |

## 2. Căn hộ (Hồ sơ)

**GET `/resident/apartment`** — căn đang chọn (theo `X-Context-Id`, mặc định `is_primary`) + thành viên hộ.
```json
{ "data": { "id": 11, "code": "...", "label": "...", "short_label": "DP-08.12",
  "role": "owner", "is_primary": true, "area_sqm": "...",
  "building": {"name": "..."}, "project": {"name": "..."},
  "members": [{"resident_id":1,"full_name":"...","role":"...","is_primary":true,"phone":"...","avatar_url":null,"is_me":true}] } }
```

## 3. Hoá đơn & công nợ (tab Hoá đơn / card Tiện ích)

**GET `/resident/statements`** (cursor) · **GET `/resident/statements/{id}`** — chi tiết + `lines[]`.
Scope: `apartment_id ∈` căn của user.
```json
{ "id": 1276, "code": "BK-202601-D011", "apartment_id": 11, "billing_period_id": 1,
  "period": {"label":"T1/2026","code":"2026-01","month":"2026-01-01","start":"2026-01-01","end":"2026-01-31","category":null},
  "status": "paid", "total_amount": "5000000.00", "paid_amount": "5000000.00",
  "currency": "VND", "due_date": "2026-01-20", "issued_at": null, "published_at": "...",
  "lines": [ {"id":1,"fee_type_id":1,"label":"Phí quản lý","category":"management",
    "description":"Phí quản lý","quantity":"...","unit_price":"...","amount":"1155000.00"} ] }
```
**GET `/resident/billing/summary`** → `{ current_debt, currency, due_date, unpaid_statement_count, as_of }`
**GET `/resident/billing/summary/trend?months=6`** → `{ data: { bars: [{label:"07/26", value:"22000000"}] } }`

## 4. Thông báo

**GET `/resident/notifications`** (cursor) · **POST `/resident/notifications/{id}/read`**.
Scope theo `notification_audiences` (all|building|apartment) + `status=published` + chưa hết hạn.

## 5. Ưu đãi (tab Ưu đãi) ✅ 2026-07-24

**GET `/resident/loyalty`** — điểm & hạng thành viên. Scope: `loyalty_accounts.resident_id ∈` resident của user.
```json
{ "data": { "points": 3200, "status": "active",
  "tier": {"key":"silver","name":"Bạc"},
  "next_tier": {"key":"gold","name":"Vàng","target":5000,"points_to_next":1800},
  "benefits": [{"icon_key":"gift","title":"Tích điểm mọi giao dịch","subtitle":"Đổi quà & ưu đãi"}],
  "updated_at": null } }
```

**GET `/resident/loyalty/activities?cursor=`** — lịch sử điểm (mới nhất trước). `points` âm = đổi/redeem.
```json
{ "data": [{"id":"..","title":"Thanh toán phí quản lý T7","type":"earn","points":500,"occurred_at":"2026-07-05T00:00:00+00:00"}] }
```

**GET `/resident/offers?cursor=`** — ưu đãi (voucher **không cần đổi điểm**, `points_cost=0/null`).
Scope: voucher tenant của user **∪** voucher platform (SA) đã rollout tới tenant đó & đang trong kỳ. `status=active`, còn hạn.
```json
{ "data": [{"id":"..","code":"OF-GYM50","title":"Ưu đãi 50% vé gym nội khu",
  "badge":"discount","value":"50.00","expiry_date":"2026-12-31","image_url":null,"is_platform":false}] }
```

**GET `/resident/loyalty/gifts?cursor=`** — quà đổi điểm (voucher `points_cost > 0`). Cùng scope offers.
```json
{ "data": [{"id":"1","code":"GIAM10","title":"Giảm 10% dịch vụ","overline":"discount",
  "points_cost":200,"value":"10.00","expiry_date":"2026-12-31","image_url":null,"is_platform":false}] }
```
> **Ghi chú:** `image_url` chưa có cột trong `vouchers` → luôn `null`, app dùng placeholder.
> `is_platform=true` = voucher đối tác nền tảng (rollout xuống tenant) — app có thể gắn nhãn "Đối tác".

## 6. Cộng đồng (tab Cộng đồng) ✅ 2026-07-24

Scope: `project_id ∈ projectIds` của user. Cư dân tenant_id=NULL → không dựa tenant scope.

**GET `/resident/community/posts?cursor=`** — bài đăng (pinned trước, mới nhất trước; `status=published`).
```json
{ "data": [{"id":"3","author":{"name":"Nguyễn Văn Cường","role":"owner","avatar_url":"...","verified":false},
  "body":"...","likes":11,"comments":4,"pinned":false,"important":false,"image_urls":[],"created_at":"..."}] }
```
> `image_urls` từ cột `community_posts.image_paths` (json). `role` từ quan hệ căn của tác giả; `verified` = tác giả có tài khoản (`user_id`).

**GET `/resident/community/events?cursor=`** — sự kiện (`status=published`, sắp diễn ra trước).
```json
{ "data": [{"id":"..","title":"Đêm nhạc acoustic sân vườn","description":"...","location":"Sảnh block B",
  "starts_at":"...","ends_at":"...","capacity":120,"attendees":45,"registered":false,"image_url":null}] }
```
> `registered` = user (resident của user) đã đăng ký (`event_registrations`). `image_url` chưa có cột → null.

**GET `/resident/community/polls`** — khảo sát đang mở (`status=open`) + trạng thái vote của user.
```json
{ "data": [{"id":"..","question":"...","type":"single","status":"open","closes_at":"...",
  "total_participants":101,"voted":true,"voted_option_id":"4",
  "options":[{"id":"1","label":"Hồ bơi","votes":40,"percent":40}]}] }
```
**POST `/resident/community/polls/{poll}/vote`** body `{ "option_id": 4 }` — 1 vote / poll / resident.
- 200 → trả PollResource đã cập nhật (`voted=true`). 409 `already_voted` nếu đã vote. 422 `poll_closed`/`invalid_option`.

**GET `/resident/community/groups`** — nhóm cộng đồng của dự án (`status=active`).
```json
{ "data": [{"id":"1","name":"Hội cư dân block A","description":"...","category":null,
  "members":320,"joined":false,"icon_key":null,"image_url":null}] }
```
> `category`/`icon_key`/`image_url` chưa có cột → null. `joined` = cư dân đã tham gia (bảng `community_group_members`).

**POST `/resident/community/groups/{group}/join`** · **DELETE `/resident/community/groups/{group}/join`** — tham gia / rời nhóm (1 resident/nhóm). Trả CommunityGroupResource cập nhật (`joined`, `members`).

## 7. Chợ nội khu + BĐS (tab Chợ) ✅ 2026-07-24

**GET `/resident/market/listings?cursor=&category=`** — sản phẩm (scope `project_id ∈ projectIds`, `status=active`).
```json
{ "data": [{"id":"3","title":"Bộ bàn ăn gỗ","description":"...","price":"3200000.00","category":"household",
  "condition":"used","seller":"Nguyễn Văn Cường","building":"Sunshine Garden - Tòa A",
  "image_url":null,"rating":null,"favorited":false,"created_at":"..."}] }
```
> `image_url` từ `image_path`; `rating`/`favorited` chưa có cột → null/false. `building` = toà nhà của người bán.

**GET `/resident/market/services?cursor=`** — nhà cung cấp dịch vụ (scope `tenant_id ∈ tenantIds` — bảng KHÔNG có project_id).
```json
{ "data": [{"id":"1","title":"Giặt là 5 sao","description":"laundry","category":"laundry",
  "phone":"0900000000","rating":"4.7","price":null,"image_url":null}] }
```

**GET `/resident/market/categories`** — danh mục sản phẩm (distinct) của dự án. → `[{"key":"household","label":"household"}]`

**GET `/resident/real-estate?cursor=&type=sale|rent`** — tin BĐS nội khu (TÁCH riêng khỏi market/*; scope project).
```json
{ "data": [{"id":"2","code":"RE-0002","type":"rent","title":"Cho thuê 1PN full nội thất",
  "price":"12000000.00","area":"45.00","bedrooms":1,"owner":"Nguyễn Văn Bình","apartment":"A-0102","published_at":"..."}] }
```

## 8. Home + SOS ✅ 2026-07-24

**GET `/resident/home`** — tổng hợp first-paint tab Home (metrics + tasks + notices_preview).
```json
{ "data": {
  "metrics": [{"key":"aqi","title":"Chất lượng không khí","value":95,"unit":"AQI","tone":"moderate","label":"Trung bình"}],
  "tasks": [
    {"key":"fee","title":"Công nợ","value":"13200000.00","count":1,"status":"due"},
    {"key":"guest","title":"Khách sắp đến","value":"0","count":0,"status":"none"},
    {"key":"feedback","title":"Phản ánh đang xử lý","value":"0","count":0,"status":"none"} ],
  "notices_preview": [ /* 2 NotificationResource mới nhất */ ] } }
```
> **AQI:** backend proxy Open-Meteo theo `projects.latitude/longitude` + cache theo project (TTL `AQI_CACHE_TTL`). `metrics=[]` nếu project không có toạ độ / API lỗi (app ẩn, không vỡ). `tone` ∈ good|moderate|poor|bad.
> **tasks:** fee←công nợ, guest←`visitor_registrations` sắp tới, feedback←`feedback_requests` đang mở.

**POST `/resident/sos`** — nút SOS an ninh. Body (tuỳ chọn): `{ "lat":10.787, "lng":106.751, "location":"...", "note":"..." }`.
- Tạo `sos_alerts` (`source=app`, `status=triggered`) scope theo căn đang chọn. `location` = "lat,lng" nếu gửi toạ độ, else mô tả, else mã căn.
- 201 → `{ "data": { "id", "status":"triggered", "triggered_at" } }`. 403 `no_apartment` nếu chưa gắn căn.

## 9. Thanh toán (tab Hoá đơn — CD-PAY-05) ✅ 2026-07-24 (history)

**GET `/resident/payments?cursor=`** — lịch sử thanh toán (scope căn/resident của user, mới nhất trước).
```json
{ "data": [{"id":"10","code":"PM-2026-06-11","amount":"5000000.00","status":"completed",
  "method":"Chuyển khoản","reference_no":"FT2606110001","paid_at":"...","note":"..."}] }
```
**GET `/resident/payments/{id}`** — chi tiết + phân bổ vào hoá đơn.
```json
{ "data": { /* như trên */ "allocations":[{"statement_id":1271,"statement_line_id":null,"amount":"5000000.00"}] } }
```
### Cổng thanh toán ✅ 2026-07-24

Cổng bật theo **tenant** + áp dụng theo **dự án** (bảng `payment_channels`; owner enable qua backend).

**GET `/resident/payment-methods`** — cổng đang bật cho ngữ cảnh cư dân.
```json
{ "data": [
  {"channel":"vietqr","display_name":"Chuyển khoản VietQR","sort":1,
   "bank":{"code":"VCB","account_name":"BAN QUAN LY SUNSHINE GARDEN"}},
  {"channel":"vnpay","display_name":"VNPay","sort":2} ] }
```

**POST `/resident/payments/intent`** body `{ "statement_id": 11, "channel": "vietqr"|"vnpay"|"momo" }`.
Số tiền = công nợ còn lại của hoá đơn; nội dung = `TT <mã hoá đơn>` (fallback `TT HD<id>`).

- **vietqr** → QR chuẩn EMVCo (napas) + ảnh + list app ngân hàng:
```json
{ "data": {
  "channel":"vietqr","statement_id":11,"statement_code":null,
  "amount":"13200000.00","content":"TT HD11",
  "qr_string":"00020101021238540010A0000007270124...6304XXXX",   // app render QR (qr_flutter), CRC16 hợp lệ
  "qr_image_url":"https://img.vietqr.io/image/VCB-1234567890-compact2.png?amount=13200000&addInfo=...",
  "bank":{"bin":"970436","code":"VCB","account_no":"1234567890","account_name":"..."},
  "bank_apps":[{"code":"VCB","bin":"970436","name":"Vietcombank","logo":"...","android_package":"com.VCB","ios_scheme":"vietcombank://"}, ...] } }
```
  → App hiển thị QR để quét bằng bất kỳ app ngân hàng, và nút "Mở app" theo `bank_apps` (deeplink Android package / iOS scheme, best-effort).
- **vnpay/momo** → nếu backend đã cấu hình credential (ENV): trả `redirect_url` (đang scaffold). Chưa cấu hình → `{ "status":"not_configured", "message":... }` (app hiện mờ/ẩn). Owner bật qua `payment_channels` + set ENV `VNPAY_*`/`MOMO_*`.

Lỗi: 404 `not_found` (hoá đơn không thuộc user) · 422 `already_paid`/`channel_unavailable`/`channel_not_configured`.

---

## Trạng thái triển khai

| Tab / nhóm | Endpoint | Backend | App wired |
|---|---|---|---|
| Auth/Me | auth/*, me/* | ✅ | ✅ |
| Hồ sơ | apartment | ✅ | ✅ |
| Hoá đơn | statements(+detail), billing/summary(+trend) | ✅ | ✅ |
| Thông báo | notifications(+read) | ✅ | ✅ |
| Ưu đãi | loyalty, loyalty/activities, **offers**, **loyalty/gifts** | ✅ | ⏳ đang wire |
| Cộng đồng | **community/posts,events,polls(+vote),groups** | ✅ | ⏳ đang wire |
| Chợ + BĐS | **market/listings,services,categories + real-estate** | ✅ | ⏳ đang wire |
| Home | **home** (AQI live + tasks + notices) | ✅ | ⏳ đang wire |
| SOS | **sos** | ✅ | ⏳ đang wire |
| Payments | **payments** (history+detail) · **payment-methods** · **payments/intent** (VietQR ✅, VNPay/MoMo chờ creds) | ✅ | ⏳ đang wire |
| Cộng đồng+ | **community/groups/{id}/join** (POST/DELETE) | ✅ | ⏳ đang wire |

## Điểm chờ owner chốt (chặn shape cuối)

- **VNPay/MoMo:** VietQR đã chạy (không cần credential). VNPay/MoMo cần owner set ENV `VNPAY_*`/`MOMO_*` + bật cổng trong `payment_channels` → khi đó `intent` trả `redirect_url` (signer đang scaffold, hoàn thiện khi có sandbox creds).
- **Offers/Gifts:** rollout voucher platform có giới hạn số lượng theo tenant không (hiện chỉ theo kỳ starts_at/ends_at)?
- **AQI:** Open-Meteo free = phi thương mại → chốt gói/nguồn khi lên prod (ENV AQI_* đã sẵn).
- **Community groups:** `category/icon/image` cần thêm cột nếu muốn hiển thị (membership `joined` đã có).
- **eKYC/household invite:** contract chưa chốt (app còn stub).
