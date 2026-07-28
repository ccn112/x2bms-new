# PUBLIC-PROJECT-IMPORT — DB & Kiến trúc

> 🔒 Track 3. Bảng con trỏ thu thập + tái dùng logic chuẩn hoá.

## Bảng `bds_import_states` (migration `2026_07_27_000010`)
Con trỏ phân trang cho việc "Lấy tiếp" — mỗi khu vực nhớ trang cuối đã lấy.

| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| city | string **unique** | key trong `config('bds.cities')`: ha-noi, tp-hcm, da-nang, phu-quoc |
| last_page | unsignedInt default 0 | trang cuối đã lấy; lần sau lấy từ `last_page+1` |
| last_status | string nullable | `ok` / `empty` / `blocked` / `http_<code>` / `error` |
| last_run_at | timestamp nullable | |
| timestamps | | |

Model `App\Models\BdsImportState` (`$guarded=[]`, cast `last_run_at=>datetime`).

## Đích upsert: `public_projects` (đã có)
Upsert theo `code` (`updateOrCreate`). `code = BDS-PJ<id>` (từ href chứa `pj<id>`) hoặc `BDS-<slug tên>`.
Gán `metadata_json`: `source`, `city`, `source_url`, `image`, `area`, `configs_raw`, `status_raw`, `imported_at`.
`status` map về enum planning|selling|handover (mặc định planning). `is_public=true`.

### `metadata_json` bổ sung từ trang chi tiết (`enrichDetail`)
- `detail` = `{nhãn tiếng Việt: giá trị}` từ bảng `re__project-attr` (Số căn hộ, Diện tích, Số tòa,
  Chủ đầu tư, Loại hình, Pháp lý, Mức giá…). `detail_fetched_at` (ISO8601). `detail_error` (blocked/http_x) khi lỗi.
- `detail_faq` = `{hỏi: đáp}` từ `re__collapse-box`. `price`, `legal`, `developer_unit` (khi có).
- `images` = mảng URL ảnh dự án (full-size, bỏ `/crop/NxN/`); `cover_image` = ảnh bìa (ảnh đầu, hoặc ảnh card
  làm baseline khi chưa enrich); `images_watermarked` = bool.
- Map cột: `apartments` ← "Số căn hộ", `blocks` ← "Số tòa", `project_type` ← "Loại hình",
  `developer_name` ← "Chủ đầu tư" (nếu trống). `upsertCard` GIỮ các khoá này khi upsert lại card.

### Ảnh dự án (watermark)
- Nguồn: gallery `re__project-album__media` (quy về full-size) + các ảnh full-size cùng "lô upload"
  (cùng path `YYYY/MM/DD`) để không vơ nhầm ảnh dự án liên quan. Tối đa 20 URL.
- **KIỂM CHỨNG (2026-07-27): ẢNH CÓ WATERMARK.** Mọi ảnh có hậu tố `_wm` (vd `...-fb78_wm.jpg`).
  Bản KHÔNG `_wm` KHÔNG truy cập được (HTTP **530**). Bản `_wm` HTTP 200. → lưu URL `_wm` +
  `images_watermarked=true` để sau thay bằng ảnh chính thống. CHƯA tải file về (chỉ lưu URL cho ImageColumn).

## Địa chỉ có cấu trúc + toạ độ (migration `2026_07_27_000011`)
Thêm cột `public_projects`: `ward` (phường/xã/thị trấn), `district` (quận/huyện/thành phố/thị xã),
`latitude`/`longitude` decimal(10,7). `province` (đã có) giữ nguyên. Idempotent (hasColumn guard).
- **`BdsProjectImporter::parseAddress(string): {ward,district,province,street}`** — tách theo dấu phẩy,
  phân loại theo TIỀN TỐ (Phường/Xã/Thị trấn → ward; Quận/Huyện/Thành phố/Thị xã → district; đoạn cuối →
  province; phần đầu → street). Quận/huyện có thể KHÔNG tiền tố (bare "Bình Tân", "Sơn Trà", "Đông Anh") —
  chỉ nhận khi có phường đứng trước (tránh nhầm số nhà/đường). Lưu VERBATIM (không đổi tên hành chính cũ/mới).
- Áp trong `upsertCard` + 2 seeder. `province()` cũ giữ nguyên (tương thích).
- **Toạ độ**: `enrichDetail`/`parseDetail` lấy `latitude`/`longitude` từ URL Google Maps `?q=<lat>,<lng>`
  (hoặc `LatLng(lat,lng)`), lọc trong khung VN (lat 7–24, lng 100–115).
- **Địa chỉ chi tiết hơn**: `parseDetail` bóc `div.re__project-address` (gỡ link "Xem bản đồ"); `enrichDetail`
  thay `address` nếu bản chi tiết "tốt hơn" (nhiều đoạn phẩy hơn / dài hơn) rồi re-parse ward/district/province.

## Bảng `developers` (migration `2026_07_27_000012`) — CHỦ ĐẦU TƯ là entity riêng
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| name | string | tên CĐT (chuẩn hoá gọn) |
| slug | string **unique** | định danh dedup (Str::slug) |
| code, website, logo_path | string nullable | |
| description | text nullable | |
| source, metadata_json | | nguồn + meta |
| timestamps + softDeletes | | |

- `public_projects.developer_id` (nullable FK → developers, nullOnDelete). GIỮ `developer_name` (chuỗi gốc đối chiếu).
- Model `App\Models\Developer` (`hasMany PublicProject`) có `upsertByName(name, extra)` — dedup theo slug
  (nhiều dự án cùng CĐT → 1 record). `PublicProject::developer()` belongsTo.
- Importer + 2 seeder: sau khi có developer_name → `Developer::upsertByName()` → set `developer_id`.
  "Đơn vị phát triển" lưu riêng ở `metadata_json.developer_unit` (KHÔNG nhầm với chủ đầu tư).
- Filament `DeveloperResource` (/sa, nhóm "Dự án"): CRUD + cột "Số dự án" (withCount) + RelationManager dự án.

## Đồng bộ local → server (export/seed)
- `projects:export-json` dump rows `metadata_json->source='batdongsan.com.vn'` → JSON đủ cột (thêm
  `ward`,`district`,`latitude`,`longitude` + object `developer` name/slug/website…)
  (`database/seeders/data/public_projects_export.json`, UTF-8 no BOM).
- `PublicProjectImportSeeder` đọc file → tạo lại `developers` (`upsertByName` dedup) + link `developer_id`,
  rồi `updateOrCreate` PublicProject theo `code` (idempotent, ghi cả ward/district/lat/lng/metadata_json) —
  server KHÔNG gọi batdongsan. Giữ `PublicProjectBdsSeeder` cũ cho `bds_projects.json`.

## Tái dùng logic chuẩn hoá (DRY)
Các hàm chuẩn hoá chuyển từ `PublicProjectBdsSeeder` (private) → `BdsProjectImporter` (**public static**):
`codeFrom`, `parseConfigs`, `developer`, `tidy`, `province`, `projectType`, `status`.
Seeder GIỜ gọi lại các static này (đọc JSON `database/seeders/data/bds_projects.json`) → 1 nguồn logic duy nhất.

## Kiến trúc fetch (chống Cloudflare)
`BdsProjectImporter::fetchHtml()` — 3 transport (`config('bds.transport')`, mặc định `curl`):
- `curl`: `Process::run([curl_binary, '-sS','-L','--compressed','-A',ua,'-H',lang,'-w','__HTTP_STATUS__%{http_code}', url])`,
  parse status từ marker. Cần binary curl **Schannel** (Windows) — OpenSSL bị Cloudflare chặn.
- `auto`: Guzzle (`Http::withHeaders`) trước, fallback `curl` khi `looksBlocked()`.
- `http`: chỉ Guzzle.

Parse HTML: `DOMDocument + DOMXPath` (built-in PHP, không thêm composer package). Selector card:
`.js__project-card` → `.re__prj-card-title`, `a[href]` (pj<id>), `img[data-src]`, `.re__prj-tag-info`,
`.re__prj-card-config-value` (nhiều), `.re__prj-card-location`, `.re__prj-card-summary`.

## Config `config/bds.php`
`base_url`, `cities` (label/slug/province, phu-quoc kèm `slug_fallback=du-an-bat-dong-san-kien-giang`),
`pages_per_run=3`, `delay_ms=400`, `user_agent`, `accept_language`, `timeout=30`,
`transport=env(BDS_TRANSPORT,curl)`, `curl_binary=env(BDS_CURL_BINARY,curl)`.
