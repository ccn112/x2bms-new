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
