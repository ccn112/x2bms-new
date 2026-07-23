# Brief cho agent x2mobile — build API resident bên x2bms

_Đọc kèm hợp đồng đầy đủ: `docs/contracts/RESIDENT_API_DOMAIN.md` (nguồn chân lý)._

Bạn build code API resident trong repo **x2bms**. Bám 5 điều dưới đây là không lệch.

## 1. Quy ước (bắt buộc, đừng tự chế)
- Envelope: **luôn** `App\Support\Api\ApiResponse` — `success()` / `paginated()` / `error()`. Đừng tự `response()->json()`.
- Route: `/api/v1/resident/*`, middleware `['auth:sanctum','ability:resident','throttle:api']`.
- Cursor: `cursorPaginate($perPage)` (`per_page` ≤ 50) → `ApiResponse::paginated($items, $p->nextCursor()?->encode())`.
- Money = **chuỗi decimal** (bcadd/bcsub, KHÔNG float). Date = ISO-8601 / `YYYY-MM-DD`. Field = `snake_case`.
- Mỗi entity 1 `*Resource` (mirror `StatementResource` / `NotificationResource`).

## 2. ⚠️ Scope cư dân — điểm sai chết người
Cư dân **`tenant_id = NULL`** → global scope tenant **vô hiệu**. KHÔNG BAO GIỜ lọc theo tenant scope.
Scope tường minh qua `ResidentContextService`:
- theo căn: `apartmentIds()` · theo toà: `buildingIds()` · **theo dự án: `projectIds()`** · theo tenant: `tenantIds()` (2 cái sau **cần bổ sung** — làm trước).
- dữ liệu của chính user (loyalty/payments/bookings): scope theo `resident_id`/`apartment_id`.

## 3. 🕳️ Bẫy đã trả giá
1. `statements` **KHÔNG có cột `currency`** (Resource default 'VND').
2. **DB dev sync tay** → **verify = HTTP thật trên DB local**, KHÔNG dựa Feature test sqlite.
3. Notification cư dân theo `notification_audiences` (all/building/apartment) — dùng `ResidentNotificationService`, KHÔNG `scopeVisibleTo` (đó là staff).
4. Hầu hết bảng có SoftDeletes.

## 4. Nền phải dựng TRƯỚC (nhiều endpoint phụ thuộc)
- `ResidentContextService::projectIds()` + `tenantIds()`.
- Voucher platform: `vouchers` thêm `owner_level`+`tenant_id` nullable · pivot `voucher_tenant(starts_at,ends_at,status)`.
- Loyalty: bảng `loyalty_tiers` + `loyalty_tier_benefits` (+seed).
- `community_posts`: thêm `is_pinned/is_important/image_paths`.
- AQI service: HTTP Open-Meteo theo `projects.latitude/longitude`, **cache 1h**, cấu hình ENV (`AQI_BASE_URL/AQI_API_KEY/AQI_PROVIDER`).
- SOS: bảng `sos_alerts` + `POST /resident/sos`.

## 5. Xong 1 endpoint thì
1. Verify **HTTP thật** trên DB local (resident seed user_id=6 có căn/hoá đơn/thông báo).
2. Ghi `DEV_JOURNAL`. 3. Nếu phát sinh model mới / field mới → **báo để cập nhật DOMAIN contract** (đừng đổi shape âm thầm).

## Thứ tự đề xuất (theo giá trị first-paint)
`projectIds/tenantIds` (nền) → **Home aggregate** → **Loyalty + Offers(vouchers)** → **Community** → **Market + BĐS** → P3 (payments/profile/SOS/action tiles).

_Đã trả sẵn (tham chiếu shape): `billing/summary`(+`/trend`), `notifications`(+read), `unread` trong `me/bootstrap`._
