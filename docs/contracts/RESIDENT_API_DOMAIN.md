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
`GET billing/summary` · `GET billing/summary/trend?months=` (xu hướng phí N tháng, CD-PAY-01; trả `{data:{bars:[{label,value}]}}` — app đọc đúng shape này) · `GET notifications` + `POST notifications/{id}/read` · `unread_notification_count` trong `/me/bootstrap` · `GET statements`(+`/{id}` enrich `code`/`period{}`/line `label`/`category`) · `GET apartment`.
**Tab Ưu đãi (2026-07-24):** `GET loyalty` + `loyalty/activities` + **`GET offers`** (voucher points_cost=0/null) + **`GET loyalty/gifts`** (voucher points_cost>0). Scope voucher qua `VoucherVisibilityService` (tenant ∪ platform rollout). Shape đầy đủ: `docs/api/RESIDENT_API_REFERENCE.md`. Code mẫu: `BillingSummaryController`, `NotificationController`, `LoyaltyController`, `OfferController`, `VoucherVisibilityService`.

> **Statement enrichment (agent x2mobile, branch `feat/resident-statements-enrich` — CHỜ verify HTTP; máy app KHÔNG có php):** `StatementResource` +`code` +`period{label,code,month,start,end,category}` (từ `billingPeriod`, whenLoaded — index eager-load `billingPeriod`, show load `['lines.feeType','billingPeriod']`). `StatementLineResource` +`label`←`fee_type` +`category`←`feeType.category` (fix bug `description` luôn null; giữ alias). Chỉ Resource/Controller/route — không migration. App đã map (period→title/kỳ, line label+icon, trend).

### 🔨 P2 — Loyalty & Ưu đãi
**`GET /resident/loyalty`** — model `LoyaltyAccount` (scope `resident_id ∈` user's residents).
- `points` ← `points_balance` · `tier` ← `tier` (enum: `silver` default; xác nhận gold/platinum trong model) · `status`.
- `next_tier{name,target,points_to_next}` + `benefits[]`: ✅ **CHỐT — bảng mới.** `loyalty_tiers` (key, name, min_points, sort) + `loyalty_tier_benefits` (loyalty_tier_id, icon_key, title, subtitle). Service tính next_tier từ points_balance so bảng tiers; benefits lấy theo tier hiện tại. (Backend seed ngưỡng + quyền lợi.)

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
**`GET community/posts?cursor=`** — `CommunityPost` (`status='published'`, scope `project_id ∈ projectIds`; sort `pinned desc, created_at desc`).
- `author{name,role,avatar_url,verified}` ← quan hệ `author_resident_id`→Resident (name; role/verified suy từ resident) · `body` · `likes`←`like_count` · `comments`←`comment_count` · `created_at`.
- ✅ **CHỐT — thêm cột** vào `community_posts`: `is_pinned bool`, `is_important bool`, `image_paths json` (ADD-ONLY, guard MySQL nếu cần). API trả `pinned/important/image_urls[]` từ các cột này.

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
- ✅ **CHỐT — BĐS tách riêng:** `GET /resident/real-estate?cursor=&type=` ← `real_estate_listings` (`status='active'`, scope `project_id ∈ projectIds`): `type(sale|rent)/title/price/area/bedrooms/owner(owner_resident_id→name)/apartment(apartment_id→code)/published_at`. KHÔNG gộp vào `market/*`.

### 🔨 P2 — `GET /resident/home` (aggregate, scope hỗn hợp)
Compose nhẹ cho first-paint:
- `metrics[]`:
  - **AQI ✅ CHỐT — nguồn Open-Meteo Air Quality (free, không key), theo `projects.latitude/longitude`** (cột đã có).
    Backend gọi `https://air-quality-api.open-meteo.com/v1/air-quality?latitude=..&longitude=..&current=european_aqi`
    → **cache theo project (TTL ~1h)** rồi trả `{key:'aqi', title, value, tone}` (tone theo ngưỡng AQI). KHÔNG gọi trực tiếp từ app (giấu vị trí + đỡ rate-limit). ⚠️ Open-Meteo free = **phi thương mại** → cần chốt gói commercial/nguồn khác (WAQI/IQAir) khi lên prod — xem §4.
  - **An ninh = KHÔNG phải metric.** ✅ CHỐT — là **nút SOS** (action tile) → app render nút; backend nhận qua endpoint SOS (P3, đặc tả sau). Bỏ khỏi `metrics[]`.
- `tasks[]`: compose — `fee` ← billing/summary (unpaid), `guest` ← `visitor_registrations` sắp tới của user, `feedback` ← `feedback_requests` đang mở của user. Trả `{key,title,value,status}`.
- `notices_preview` ← 2 item đầu của `notifications` (tái dùng service).

### 🔨 P3 — Thứ cấp
- **`GET /resident/payments?cursor=`** ← `Payment` (scope `resident_id`/`apartment_id`): `code/amount/paid_at/reference_no/status/method`. Chi tiết phân bổ ← `payment_allocations`; biên lai ← `receipts`.
- **`PATCH /me/profile`** ← cập nhật `User` (name…) + `Resident` (contact) — chốt field cho sửa; audit.
- **`GET /resident/apartment`** ← thành viên căn (household) — dùng quan hệ đã có.
- Action tiles (amenities booking `amenity_bookings`, guest `visitor_registrations`, feedback `feedback_requests`): đặc tả khi tới lượt; đều có bảng sẵn với `resident_id/apartment_id`.

## 4. Nền dùng chung — ✅ ĐÃ DỰNG (2026-07-23, commit foundation)
- ✅ **`ResidentContextService::projectIds()` + `tenantIds()`** — đã thêm (buildings.project_id / apartments.tenant_id).
- ✅ **Voucher platform:** `vouchers` + `owner_level`(platform|tenant, default tenant) + `tenant_id` **nullable**; pivot **`voucher_tenant`**(voucher_id, tenant_id, `starts_at`, `ends_at`, status). Cư dân thấy voucher platform CHỈ khi tenant mình có rollout đang trong kỳ (`now BETWEEN starts_at AND ends_at`). _Màn SA quản lý đối tác + triển khai = việc backend riêng; app chỉ đọc `/resident/offers` hợp nhất._
- ✅ **Loyalty:** bảng `loyalty_tiers`(key,name,min_points,sort) + `loyalty_tier_benefits`(loyalty_tier_id,icon_key,title,subtitle), **đã seed** silver(0)/gold(5000)/platinum(20000) + benefit mẫu. Model `LoyaltyTier`/`LoyaltyTierBenefit`. Service tính `next_tier`/`points_to_next` từ `points_balance`.
- ✅ **community_posts:** đã thêm `is_pinned`, `is_important`, `image_paths(json)`.
- ✅ **AQI config:** `config('services.aqi')` + ENV (`AQI_PROVIDER/AQI_BASE_URL/AQI_API_KEY/AQI_CACHE_TTL`), `.env.example` đã có. **Còn lại (agent build endpoint):** viết AqiService gọi HTTP + cache theo project, dùng trong `/resident/home`.
- ✅ **SOS — dùng bảng có sẵn `sos_alerts`** (migration `2026_07_01_000010`, model `App\Models\SosAlert`). **KHÔNG tạo bảng mới.** Cột thật: tenant_id, project_id, building_id, apartment_id, resident_id, `source`(app|panic_button|intercom), `status`(**triggered**|acknowledged|responding|resolved|false_alarm), `location`(string), `triggered_at`, acknowledged_by_id, resolved_at, note. Endpoint `POST /resident/sos`: tạo với `source='app'`, `status='triggered'`, scope resident/apartment, `location`= "lat,lng" hoặc mô tả; + audit + (sau) notify BQL.

## 5. ⛳ Điểm cần OWNER chốt (đang chặn shape cuối)
1. ✅ **CHỐT:** Offers/Gifts = `vouchers`, **scope toàn tenant** (bỏ cash_vouchers). + Voucher **hợp tác cấp từ SA (platform)** rollout xuống tenant → cần schema §4 (owner_level + pivot `voucher_tenant`). _Còn cần chốt: bản ghi rollout có kỳ hạn/giới hạn số lượng theo tenant không?_
2. ✅ **CHỐT:** Market = products (`marketplace_products`) + services (`service_providers`) + categories; **BĐS tách riêng** `/resident/real-estate` (`real_estate_listings`).
3. ✅ **CHỐT:** AQI ← Open-Meteo theo `projects.latitude/longitude` (backend proxy + cache; license commercial cần chốt). An ninh = **nút SOS** (không phải metric).
4. ✅ **CHỐT:** Loyalty tier/benefits = **bảng mới** (`loyalty_tiers` + `loyalty_tier_benefits`, seed).
5. ✅ **CHỐT:** `community_posts` **thêm cột** `is_pinned/is_important/image_paths`.

**Đã chốt nốt:** (a) rollout voucher platform **có kỳ hạn** (`starts_at/ends_at`; chưa giới hạn số lượng). (b) AQI **dùng Open-Meteo free tạm, ENV-ready** để owner gắn key/gói khi lên prod. (c) SOS → bảng mới **`sos_alerts`** + `POST /resident/sos`.

## 6. Vòng đồng bộ
1. Agent x2mobile code API theo file này; field nào "chưa có cột" → theo hướng đã ghi (null/placeholder) hoặc mở mục §5.
2. Backend-coordinator (phiên này) **review commit API** ở góc domain: envelope · scope tenant-null · naming snake_case · money string · dùng service/resource chuẩn.
3. Phát sinh lệch/model mới → cập nhật file này + `docs/CANONICAL_ENTITY_MAP.md`, và đẩy điểm cần quyết lên §5.
4. Khi 1 cụm xong: verify HTTP thật trên DB local + ghi `DEV_JOURNAL`.
