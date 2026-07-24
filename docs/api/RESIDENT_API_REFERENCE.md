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

---

## Trạng thái triển khai

| Tab / nhóm | Endpoint | Backend | App wired |
|---|---|---|---|
| Auth/Me | auth/*, me/* | ✅ | ✅ |
| Hồ sơ | apartment | ✅ | ✅ |
| Hoá đơn | statements(+detail), billing/summary(+trend) | ✅ | ✅ |
| Thông báo | notifications(+read) | ✅ | ✅ |
| Ưu đãi | loyalty, loyalty/activities, **offers**, **loyalty/gifts** | ✅ | ⏳ đang wire |
| Cộng đồng | community/* | ⏳ | ⏳ |
| Chợ + BĐS | market/*, real-estate | ⏳ | ⏳ |
| Home | home | ⏳ | ⏳ |
| Payments/SOS | payments, sos | ⏳ | ⏳ |
