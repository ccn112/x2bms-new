# PUBLIC-PROJECT-IMPORT — Nhập dữ liệu dự án từ batdongsan.com.vn

> 🔒 Đối tượng: dev nội bộ. Track 2. Thu thập metadata dự án PUBLIC vào thư viện dùng chung.
> Liên quan: WEB-UX-22-03 (Thư viện dự án public, panel `/sa`).

## Tổng quan
Bổ sung khả năng **thu thập tiếp** metadata dự án bất động sản từ batdongsan.com.vn NGAY TRONG APP,
qua nút **"Lấy tiếp"** trên màn `Sa/Pages/PublicProjectLibrary` (SuperAdmin) và command CLI/cron.
Chỉ lấy metadata thư mục công khai (tên, CĐT, địa chỉ, tỉnh/TP, loại hình, trạng thái, số căn/block,
diện tích, ảnh, mô tả) → upsert vào `public_projects` theo `code`.

Logic **chuẩn hoá** dùng CHUNG với `PublicProjectBdsSeeder` (đã refactor: các hàm private của seeder
chuyển thành `public static` trong service, seeder gọi lại — không lặp code).

## Thành phần
- **Service** `App\Services\Projects\BdsProjectImporter`
  - `fetchMore(array $cityKeys, int $pages): array` — với mỗi khu vực: đọc con trỏ trang
    (`bds_import_states`), lấy `pages` trang KẾ TIẾP, parse card, upsert, cập nhật con trỏ.
    Trả `{city: {label, added, updated, pagesFetched, stoppedReason}}`.
  - `fetchHtml()` — tầng vận chuyển (xem §Chống bot). `parseCards()` — bóc card bằng DOMDocument/DOMXPath
    (built-in, KHÔNG thêm package). `upsertCard()` — map card → `public_projects`.
  - Static chuẩn hoá: `codeFrom` (pj<id> → `BDS-PJ<id>`), `parseConfigs` ([apartments, blocks, area]),
    `developer` (regex từ summary: `do (.+?) làm chủ đầu tư`…), `province`, `projectType`, `status`.
- **Config** `config/bds.php` — `cities` (ha-noi / tp-hcm / da-nang / phu-quoc → label + slug + province,
  phu-quoc có `slug_fallback` = kien-giang), `pages_per_run` (3), `delay_ms` (400), `user_agent`,
  `transport`, `curl_binary`.
- **Command** `php artisan projects:fetch-more {--pages=3} {--city=*}` — cùng service (cho cron/CLI).
  Bỏ trống `--city` = tất cả khu vực.

## Nút "Lấy tiếp" (Filament header action, `/sa`)
- Chỉ hiện với SuperAdmin (`->visible(fn () => Auth::user()?->isPlatformAdmin())`).
- Icon `heroicon-o-arrow-down-tray`, màu primary. Mở modal: **CheckboxList khu vực** (4 TP, mặc định chọn hết)
  + **số trang/lần** (mặc định 3). Submit → gọi `fetchMore()` ĐỒNG BỘ →
  `Notification` tổng `+added / ~updated` theo từng khu vực (persistent). Ghi audit `public_project.fetch`.
- Bị chặn → notification **warning** "batdongsan chặn request server (Cloudflare)" + gợi ý
  đặt `BDS_TRANSPORT=curl` / chạy lại sau / dùng proxy. Nút vẫn hoạt động.

## Làm giàu metadata từ TRANG CHI TIẾT (`enrichDetail`)
Sau khi upsert card, với dự án MỚI hoặc chưa có detail, service fetch trang chi tiết theo
`metadata_json.source_url` và bóc:
- **Bảng "Thông tin dự án"** — selector thật: `table > tbody.re__project-attr > tr >
  td.re__attr-item-label (h4)` + `td.re__attr-item-value`. Lưu NGUYÊN nhãn tiếng Việt vào
  `metadata_json['detail'] = { nhãn: giá trị }` (vd `{"Số căn hộ":"1.281 căn","Số tòa":"3 tòa",
  "Diện tích":"...","Chủ đầu tư":"...","Pháp lý":"..."}`) + `detail_fetched_at`.
- **FAQ** (`re__collapse-box`: `re__collapse-label` hỏi + `re__collapse-content` đáp) →
  `metadata_json['detail_faq']`; suy ra CĐT/giá nếu bảng thiếu.
- **Mô tả tổng quan** (`re__detail-content`) → regex CĐT dự phòng.

**Map lên cột khi có giá trị rõ hơn:** `apartments` ← "Số căn hộ/Số căn", `blocks` ←
"Số tòa/Số block/Số tháp", `project_type` ← "Loại hình" (nếu chi tiết rõ hơn slug),
`developer_name` ← "Chủ đầu tư" (nếu đang trống). Thêm `metadata_json['price']` (Mức giá/Giá),
`['legal']` (Pháp lý), `['developer_unit']` (Đơn vị phát triển).

- Cờ `config('bds.enrich_detail')` (default true, ENV `BDS_ENRICH_DETAIL`); command có `--no-detail`.
- `delay_ms` giữa mỗi request chi tiết. Bị chặn/empty → bỏ qua êm, ghi `metadata_json['detail_error']`.
- `upsertCard` GIỮ các khoá làm giàu (`detail`, `price`, `legal`…) khi upsert lại card (không xoá mất).
- ⚠️ Trang chi tiết KHÔNG có card → `looksBlocked()` đã bỏ phụ thuộc số card; chỉ coi là bị chặn khi
  body < 20KB + chứa token challenge thật (`_cf_chl_opt`/`challenge-error-text`/`cf-chl-`/`Just a moment`).
  (Chuỗi `challenge-platform` xuất hiện cả trên trang hợp lệ — KHÔNG dùng làm dấu hiệu.)

## Địa chỉ có cấu trúc + lọc theo địa bàn (bảng /sa)
- `address` (chuỗi đầy đủ) được tách thành `ward` (phường/xã), `district` (quận/huyện), `province` qua
  `parseAddress()`. Trang chi tiết cho địa chỉ chính xác hơn (số nhà/đường) + toạ độ `latitude`/`longitude`.
- **Bảng dự án** (`PublicProjectLibrary` /sa + `PublicProjectsTable` resource): cột "Địa điểm" hiển thị
  **Phường · Quận** (dòng chính) + **Tỉnh/TP** (description), searchable theo ward/district/province.
  Thêm **SelectFilter** `province` (Tỉnh/TP) và `district` (Quận/Huyện) — options distinct từ DB, searchable.

## Chủ đầu tư (developers) — entity riêng, quản lý ở /sa
- CĐT tách khỏi chuỗi thành bảng `developers` (dedup theo slug: nhiều dự án cùng CĐT → 1 record).
  `public_projects.developer_id` link, giữ `developer_name` gốc.
- **`DeveloperResource`** (/sa, nhóm "Dự án"): CRUD (name, website, logo upload, mô tả) + cột **"Số dự án"**
  (đếm quan hệ) + **RelationManager** liệt kê dự án của CĐT. Bảng dự án hiển thị cột "Chủ đầu tư" = `developer.name`.
- "Đơn vị phát triển" (nếu detail có) lưu riêng `metadata_json.developer_unit`, KHÔNG nhầm chủ đầu tư.

## Đồng bộ local → server (export → seed, KHÔNG gọi batdongsan trên server)
Vì server (Linux) có thể bị Cloudflare chặn, thu thập chạy ở **local**, rồi commit JSON, server chỉ seed.
- **`php artisan projects:export-json [--path=...]`** — dump TẤT CẢ rows nguồn batdongsan
  (`metadata_json->source='batdongsan.com.vn'`) ra `database/seeders/data/public_projects_export.json`
  (đủ cột: code, name, developer_name, address, province, project_type, status, blocks, apartments,
  amenities_json, description, is_public, metadata_json). UTF-8, không BOM.
- **`PublicProjectImportSeeder`** — đọc file đó, `updateOrCreate(['code'=>...], [...])` theo cột (idempotent).
- **Quy trình:** `[local]` fetch-more (+enrich) → `projects:export-json` → commit. `[server]`
  `git pull` → `php artisan db:seed --class=PublicProjectImportSeeder`. KHÔNG chạm batdongsan.
- `PublicProjectBdsSeeder` cũ vẫn giữ (cho file thu thập trình duyệt `bds_projects.json`); export mới là nguồn chính.

## Chống bot (KIỂM CHỨNG THẬT — 2026-07-27, DB thật)
batdongsan.com.vn đứng sau **Cloudflare managed challenge** (lọc theo TLS/JA3 fingerprint):
- **PHP Guzzle / ext-curl (OpenSSL 3.0.18)** → **403 + trang challenge** (bị chặn chắc chắn, kể cả gắn đủ header trình duyệt).
- **Binary `curl.exe` Schannel** (Windows System32 8.21.0 hoặc Git 8.16.0) → **200 OK + đủ 10 card/trang**, lặp lại ổn định.

Do đó `fetchHtml()` có 3 chế độ (`config('bds.transport')`):
- `curl` (**mặc định**) — shell thẳng ra binary curl (Schannel) qua `Process` facade. Nhanh + ổn định.
- `auto` — thử Guzzle trước, phát hiện bị chặn thì fallback curl.
- `http` — chỉ Guzzle (sẽ bị chặn — để test/chẩn đoán).

`looksBlocked()` nhận diện chặn: status 403/429/503, body rỗng, hoặc 0 card kèm dấu hiệu
`challenge-platform`/`_cf_chl_opt`/`Just a moment` (hoặc body < 20KB).

> ⚠️ **Phụ thuộc môi trường:** transport `curl` cần có binary curl Schannel trong PATH của server.
> Windows 10+ có sẵn `C:\Windows\System32\curl.exe`. Trên **Linux production** curl thường build OpenSSL →
> có thể lại bị chặn giống Guzzle. **Điểm cần chủ dự án quyết:** chạy import từ máy Windows, hoặc dùng
> proxy/scraping API (ScraperAPI, ZenRows…), hoặc curl-impersonate trên server Linux. Xem §Việc còn lại.

## Verify (2026-07-27, DB thật `x2bms`)
- migrate `bds_import_states` ✅.
- `projects:fetch-more --pages=1 --city=ha-noi` (transport curl): `public_projects` 5 → 15 (+10 mới) ✅;
  chạy tiếp trang 2 → +10 nữa, `bds_import_states.ha-noi` `last_page=2 status=ok` (con trỏ tiến đúng) ✅.
- Data mẫu upsert đúng: `BDS-PJ6746 | JSC 34 | dev="Công ty Cổ phần Đầu tư và Xây dựng Số 34" | Hà Nội | handover | area=3.341,8 m²` ✅.
- Guzzle trực tiếp = 403 challenge; curl binary = 200/10 card (đã kiểm chứng cả hai) ✅.
- **Enrich detail:** `fetch-more --pages=1 --city=da-nang` → 9/10 dự án mới có `metadata_json.detail`
  (1 lỗi transient blocked). Mẫu `BDS-PJ6639 FourS Tower`:
  `detail={Pháp lý:"Sở hữu lâu dài", Số tòa:"3 tòa", Số căn hộ:"1.281 căn", Chủ đầu tư:"Tập đoàn Sun Group"}`
  → map cột `apartments=1281, blocks=3, project_type="Căn hộ chung cư"` ✅.
- **Export/seed:** `projects:export-json` → 30 dự án (9 có detail) ra JSON (no BOM, UTF-8, tiếng Việt đúng
  "Ecohome Hòa Hiệp/Đà Nẵng") ✅; `db:seed --class=PublicProjectImportSeeder` upsert 30 (idempotent) ✅.
- **Địa chỉ/CĐT (backfill 715 dự án):** parse "Phường Đại Kim, Quận Hoàng Mai, Hà Nội" →
  ward/district/province đúng ✅; ward 697/district 696; **452 CĐT** (dedup từ 606 dự án có tên),
  Masterise Homes 18 / Vingroup 18 / Sun Group 15 dự án gom đúng 1 record (56 CĐT có >1 dự án) ✅.
- **Địa chỉ chi tiết + toạ độ:** enrich `The Keisho` → `address` nâng cấp thành
  "Ngõ 17 Đường Cổ Linh, Phường Long Biên, Quận Long Biên, Hà Nội" (chi tiết hơn card),
  `lat=21.0310955 lng=105.8933182` ✅. `DeveloperResource` render (route `sa/developers`) ✅.
- **Export/import mở rộng:** export 710 dự án kèm ward/district/lat + object developer; import tạo lại
  developers + link, idempotent ✅.
- `php -l` toàn bộ file mới sạch, không mojibake/BOM ✅.

## Việc còn lại / cần quyết
- Transport trên **Linux production** (curl OpenSSL có thể bị chặn) → chốt proxy / scraping API / curl-impersonate.
- Tải ảnh dự án về media library thay vì chỉ lưu URL nguồn trong `metadata_json`.
- (Tùy chọn) lịch cron `projects:fetch-more` + throttle để không gọi dồn.
