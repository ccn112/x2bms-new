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
- `php -l` toàn bộ file mới sạch, không mojibake/BOM ✅.

## Việc còn lại / cần quyết
- Transport trên **Linux production** (curl OpenSSL có thể bị chặn) → chốt proxy / scraping API / curl-impersonate.
- Tải ảnh dự án về media library thay vì chỉ lưu URL nguồn trong `metadata_json`.
- (Tùy chọn) lịch cron `projects:fetch-more` + throttle để không gọi dồn.
