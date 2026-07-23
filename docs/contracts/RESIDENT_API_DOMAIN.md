# Resident API — Domain Contract (nguồn chân lý đồng bộ đối tượng)

_Ngày: 2026-07-23 · Repo: x2bms (backend) ↔ x2mobile (app + build API resident)_

> **Vai trò tài liệu:** Agent x2mobile **viết code API resident** bên x2bms. Tài liệu này do
> phía backend giữ, là **hợp đồng domain** — ánh xạ mỗi endpoint (theo
> `x2mobile/docs/API_REQUIREMENTS_RESIDENT_TABS_20260723.md`) sang **model/bảng/cột/enum
> THẬT** + **quy tắc scope** + điểm tái dùng. Mục tiêu: API build ra khớp schema thật ngay,
> không bịa shape, không lệch quy ước. Mọi thay đổi shape API phải cập nhật file này trước.

---

## 0. Quy ước bắt buộc (không tự đổi)

- **Envelope:** luôn dùng `App\Support\Api\ApiResponse` — `success()` / `paginated()` / `error()`.
  Không tự `response()->json()` shape khác.
- **Base + auth:** `/api/v1/resident/*`, middleware `['auth:sanctum','ability:resident','throttle:api']`.
- **Money:** chuỗi decimal (dùng bcmath cho tổng), KHÔNG float. **Date:** ISO-8601 / `YYYY-MM-DD`. **Field:** `snake_case`.
- **Cursor:** `cursorPaginate($perPage)`, `per_page` ≤ 50; trả qua `ApiResponse::paginated($items, $paginator->nextCursor()?->encode())`.
- **Resource:** mỗi entity 1 `App\Http\Resources\Api\V1\*Resource` (mirror `StatementResource`).

## 1. ⚠️ Quy tắc SCOPE cho cư dân (điểm sai nguy hiểm nhất)

Cư dân có **`tenant_id = NULL`** → **BelongsToTenant global scope là no-op** → **KHÔNG bao giờ**
dựa scope tenant để lọc dữ liệu cư dân. Luôn scope tường minh theo ngữ cảnh của người dùng:

| Cần scope theo | Lấy từ | Helper |
|---|---|---|
| Căn hộ | quan hệ cư dân ↔ căn | `ResidentContextService::apartmentIds($user, $ctxHeader)` |
| Toà nhà | apartments.building_id | `ResidentContextService::buildingIds(...)` |
| **Dự án** | buildings.project_id | **CẦN THÊM `projectIds()`** (chưa có — xem §4) |
| Chính resident | `residents.user_id = $user->id` | `$user->residentMemberships()` (đã `withoutGlobalScope('tenant')`) |

- `X-Context-Id: apartment:{relationId}` → thu hẹp về 1 căn (service đã xử lý).
- Dữ liệu **theo dự án** (community/events/polls/market/loyalty…) scope bằng `project_id ∈ projectIds`.
- Dữ liệu **của chính người dùng** (loyalty account, payments, bookings…) scope bằng `resident_id ∈` (các resident của user) hoặc `apartment_id ∈ apartmentIds`.

## 2. 🕳️ Sổ bẫy đã biết (đọc trước khi code)

1. **`statements` KHÔNG có cột `currency`** — Resource default `'VND'`. Đừng `select('currency')`.
2. **DB dev sync tay**, có thể lệch migration (thiếu cột). **Verify = HTTP thật trên DB local**, KHÔNG dựa Feature test sqlite. (Muốn test sqlite phải dựng đủ FK graph + tránh `ALTER…MODIFY ENUM` MySQL-only — đã guard 2 migration import_batches.)
3. **Notification hiển thị cho cư dân** theo `notification_audiences` (`all|building|apartment`) + `status='published'` + chưa hết hạn — dùng `ResidentNotificationService`, KHÔNG dùng `Notification::scopeVisibleTo` (đó là cho staff).
4. Hầu hết bảng có **SoftDeletes** (`deleted_at`) → sqlite/mysql dễ lệch; query bình thường đã loại soft-deleted.
5. **Tiền là string**: cộng bằng `bcadd/bcsub/bccomp` (xem `BillingSummaryController`).

## 3. Ánh xạ endpoint → domain thật

Ký hiệu cột: `→` = field API ⟵ cột DB. Enum ghi giá trị **mặc định + quan sát**; xác nhận full set trong model/seeder khi code.

### ✅ Đã trả (tham chiếu shape chuẩn — không làm lại)
`GET billing/summary` · `GET notifications` + `POST notifications/{id}/read` · `unread_notification_count` trong `/me/bootstrap`. Code mẫu: `BillingSummaryController`, `NotificationController`, `ResidentNotificationService`.

### 🔨 P2 — Loyalty & Ưu đãi
**`GET /resident/loyalty`** — model `LoyaltyAccount` (scope `resident_id ∈` user's residents).
- `points` ← `points_balance` · `tier` ← `tier` (enum: `silver` default; xác nhận gold/platinum trong model) · `status`.
- `next_tier{name,target,points_to_next}`: **KHÔNG có cột** → tính ở service theo bảng ngưỡng tier (hằng số trong 1 class `LoyaltyTiers`, chốt ngưỡng với owner). `benefits[]`: chưa có bảng → tạm hằng số theo tier hoặc bảng mới (cần quyết).

**`GET /resident/loyalty/activities?cursor=`** — `LoyaltyTransaction` join `loyalty_accounts` theo resident.
- `title` ← `description` · `occurred_at` ← `transacted_at` · `points` ← `points` (âm nếu `type` là redeem/`points<0`). enum `type`: `earn` default (xác nhận redeem/adjust).

**`GET /resident/offers?cursor=` + `GET /resident/loyalty/gifts`** — map cả 2 → `vouchers`.
- **Scope (CHỐT): voucher toàn tenant.** Cư dân `tenant_id=null` → lấy tenant qua `apartments.tenant_id`
  của các căn user (thêm `ResidentContextService::tenantIds()`). Hiển thị voucher `tenant_id ∈ tenantIds`.
- **+ Voucher hợp tác cấp từ SA (platform):** nền tảng đi hợp tác đơn vị ngoài → SA tạo voucher
  **owner_level=platform**, rồi **triển khai (rollout)** xuống 1 số tenant. Cư dân thấy = voucher tenant mình
  **∪** voucher platform đã rollout tới tenant đó. → **cần nền schema mới, xem §4.**
- offers: `status='active'` & `valid_to >= today`. `id/badge(type)/title(name)/expiry_date(valid_to)`; `image_url` **chưa có cột** → null/placeholder.
- gifts: `vouchers` có `points_cost > 0`. `points_cost`, `title(name)`, `overline(type)`.
- **KHÔNG dùng `cash_vouchers`** (đó là chứng từ quỹ tài chính: fund_id/payment_request_id — không phải ưu đãi tiêu dùng).

### 🔨 P2 — Cộng đồng (scope `project_id ∈ projectIds`)
**`GET community/posts?cursor=`** — `CommunityPost` (`status='published'`, sort ghim? **không có cột pinned** → bỏ hoặc thêm cột; cần quyết).
- `author{name,role,avatar_url,verified}` ← quan hệ `author_resident_id`→Resident (name; role/verified suy từ resident) · `body` · `likes`←`like_count` · `comments`←`comment_count` · `created_at`. `image_urls[]`, `important`, `pinned`: **chưa có cột** → null/false (hoặc thêm cột, chốt).

**`GET community/events?cursor=`** — `Event`.
- `title/location/starts_at/attendees(registered_count)/image_url(chưa có→null)`. `registered` (user đã đăng ký?) ← tồn tại `event_registrations{event_id,resident_id}`.

**`GET community/polls` + `POST polls/{id}/vote {option_id}`** — `Poll`+`PollOption`(+`PollVote`).
- `question` · `options[{label,percent}]` percent = `poll_options.vote_count / polls.vote_count` · `total_participants`←`vote_count` · `voted` ← có `poll_votes{poll_id,resident_id}`. Vote: tạo `PollVote` (guard 1 vote/poll/resident; tăng `vote_count`).

**`GET community/groups`** — `CommunityGroup`.
- `name/category(chưa có→null hoặc description)/members(member_count)/joined(chưa có bảng membership→false)/image_url,icon_key(chưa có→null)`.

### 🔨 P2 — Chợ nội khu (scope `project_id ∈ projectIds`)
**ĐỀ XUẤT map 3 nguồn khác nhau:**
- **`GET market/listings?cursor=&category=`** ← `marketplace_products` (`status='active'`): `title(name)/price/seller(seller_resident_id→name)/image_url(image_path)/category`. `building`←qua seller's apartment; `rating/favorited`: chưa có → null/false.
- **`GET market/services?cursor=`** ← `service_providers` (`status='active'`): `title(name)/desc(category)/rating/price(chưa có ở provider → từ service_orders? tạm null)`. `image_url` chưa có.
- **`GET market/categories`** ← `SELECT DISTINCT category FROM marketplace_products` (hoặc danh sách hằng số) → `[{key,label}]`.
- `real_estate_listings` = tính năng BĐS riêng (sale/rent), **KHÔNG gộp vào market chung** ở P2 (để sau nếu app cần).

### 🔨 P2 — `GET /resident/home` (aggregate, scope hỗn hợp)
Compose nhẹ cho first-paint:
- `metrics[]` (AQI/an ninh): **KHÔNG có nguồn dữ liệu backend** → **ĐỀ XUẤT tạm bỏ** (app ẩn) hoặc trả từ config tĩnh; đừng bịa số. (Chốt với owner nếu có IoT/`sensor_events`.)
- `tasks[]`: compose — `fee` ← billing/summary (unpaid), `guest` ← `visitor_registrations` sắp tới của user, `feedback` ← `feedback_requests` đang mở của user. Trả `{key,title,value,status}`.
- `notices_preview` ← 2 item đầu của `notifications` (tái dùng service).

### 🔨 P3 — Thứ cấp
- **`GET /resident/payments?cursor=`** ← `Payment` (scope `resident_id`/`apartment_id`): `code/amount/paid_at/reference_no/status/method`. Chi tiết phân bổ ← `payment_allocations`; biên lai ← `receipts`.
- **`PATCH /me/profile`** ← cập nhật `User` (name…) + `Resident` (contact) — chốt field cho sửa; audit.
- **`GET /resident/apartment`** ← thành viên căn (household) — dùng quan hệ đã có.
- Action tiles (amenities booking `amenity_bookings`, guest `visitor_registrations`, feedback `feedback_requests`): đặc tả khi tới lượt; đều có bảng sẵn với `resident_id/apartment_id`.

## 4. Việc backend cần bổ sung (nền dùng chung — nên làm sớm)
- **`ResidentContextService::projectIds($user, $ctx)`** — từ `buildingIds` → `buildings.project_id`. Gần như mọi endpoint community/market/loyalty cần.
- **`ResidentContextService::tenantIds($user, $ctx)`** — từ `apartmentIds` → `apartments.tenant_id`. Cho offers/voucher toàn tenant.
- **Voucher platform (hợp tác SA) — schema mới (mirror pattern Notification 3 lớp):**
  - `vouchers`: thêm `owner_level` (`platform|tenant`, default `tenant`) + đổi `tenant_id` **nullable** (platform voucher tenant_id null). ADD-ONLY, guard MySQL nếu ALTER enum.
  - **Rollout:** bảng pivot `voucher_tenant` (voucher_id, tenant_id, +trạng thái/kỳ triển khai) — SA chọn tenant nào được nhận. Cư dân thấy voucher platform CHỈ khi tenant mình có bản ghi rollout.
  - Màn SA quản lý đối tác/voucher + "triển khai xuống tenant" = việc backend (ngoài phạm vi app); app chỉ đọc `/resident/offers` đã hợp nhất.
- Lớp `LoyaltyTiers` (ngưỡng tier + benefits) nếu chốt tính `next_tier`.
- Cân nhắc thêm cột `pinned/important/image` cho `community_posts`, `image` cho events/products (tùy quyết định §5).

## 5. ⛳ Điểm cần OWNER chốt (đang chặn shape cuối)
1. ✅ **CHỐT:** Offers/Gifts = `vouchers`, **scope toàn tenant** (bỏ cash_vouchers). + Voucher **hợp tác cấp từ SA (platform)** rollout xuống tenant → cần schema §4 (owner_level + pivot `voucher_tenant`). _Còn cần chốt: bản ghi rollout có kỳ hạn/giới hạn số lượng theo tenant không?_
2. **Market** = 3 nguồn (products/services + categories từ products), **BĐS tách riêng**? (đề xuất: có.)
3. **Home metrics (AQI/an ninh):** tạm ẩn (đề xuất) hay có nguồn (IoT)?
4. **Loyalty tier/benefits:** ngưỡng nâng hạng + quyền lợi lấy đâu (hằng số vs bảng mới)?
5. **community_posts pinned/important + ảnh:** thêm cột hay bỏ field ở app?

## 6. Vòng đồng bộ
1. Agent x2mobile code API theo file này; field nào "chưa có cột" → theo hướng đã ghi (null/placeholder) hoặc mở mục §5.
2. Backend-coordinator (phiên này) **review commit API** ở góc domain: envelope · scope tenant-null · naming snake_case · money string · dùng service/resource chuẩn.
3. Phát sinh lệch/model mới → cập nhật file này + `docs/CANONICAL_ENTITY_MAP.md`, và đẩy điểm cần quyết lên §5.
4. Khi 1 cụm xong: verify HTTP thật trên DB local + ghi `DEV_JOURNAL`.
