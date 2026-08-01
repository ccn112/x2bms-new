# X2-BMS — Nhật ký phát triển (Dev Journal)

Mỗi lần cập nhật code, ghi một entry vào đầu danh sách (mới nhất ở trên).
Định dạng: ngày · phạm vi · file đổi · tóm tắt · cách verify.

---

## 2026-07-28 — Danh mục dự án PUBLIC: import batdongsan 6.000 dự án + thư viện ảnh + CĐT + địa chỉ 2025 + enrichment

**Phạm vi:** dựng trọn pipeline danh mục dự án công khai (`public_projects`) cho SuperAdmin `/sa`, phục vụ trang public cư dân.

**Import batdongsan (chống Cloudflare):** Guzzle bị 403 → dùng transport `curl.exe` Schannel (chạy ở LOCAL Windows; Linux server có thể bị chặn nên chốt hướng fetch local → export → seed server). `BdsProjectImporter` + nút "Lấy tiếp" `/sa` + command `projects:fetch-more` (cursor `bds_import_states`). URL khu vực `/du-an-bat-dong-san-{ha-noi|tp-hcm|da-nang|phu-quoc}` + 10 tỉnh + `toan-quoc` (national ~6026). `delay_ms` 800 chống rate-limit; backoff khi bị chặn.

**Dữ liệu (6.005 → export 6.000):** name, address + tách `ward/district/province` (parseAddress), `latitude/longitude` (từ Google Maps link trang chi tiết), `blocks/apartments/project_type`, `metadata_json.detail` (Loại hình/Số tòa/Số căn/Pháp lý/Giá), `metadata_json.images` (URL, ⚠️ watermark `_wm`, chỉ lưu URL + cờ). enrichDetail đọc trang chi tiết. `projects:enrich-missing` (kèm `--only=developer` LOCAL, không network).

**Entity Chủ đầu tư (`developers`):** dedup theo slug (3.097 CĐT), `public_projects.developer_id`, `DeveloperResource` /sa. Backfill từ `detail['Chủ đầu tư']` → 4.733/6.000 dự án có CĐT (~79%); 1.272 nguồn không có.

**Chuẩn hoá tỉnh:** `canonicalProvince()` + `projects:normalize-province` → gộp 111→62 nhãn (TP.HCM→Hồ Chí Minh...).

**Địa chỉ mới 2025:** bảng `admin_2025` + `Admin2025Seeder` + `AddressResolver` + `projects:resolve-new-address` → `metadata_json.address_new` (high/medium/low). Nguồn truongqv12 + tuongnguyen913 + NQ202.

**Thư viện ảnh (`project_media`):** +cột source/is_cover/is_watermarked; `ProjectMediaSync` + `projects:sync-media` materialize ảnh từ metadata (KHÔNG tải file — chỉ bản ghi URL hotlink); RelationManager "Thư viện ảnh" (đặt bìa, reorder, thêm tay). ~64.7k bản ghi ảnh, ~10.8 ảnh/dự án.

**Tìm ảnh & thông tin chính thống:** `ProjectEnrichmentService` + provider mock|google_cse|serpapi (`config/enrichment.php`, ENV `ENRICH_PROVIDER`); Action "Tìm ảnh & thông tin" /sa (admin duyệt ảnh+info kèm nguồn → `metadata_json.official_*` + `enrichment_log`). User đã có SerpAPI key.

**API public + onboarding:** `GET /api/v1/public/projects` (+`/{slug}`) đọc `public_projects` cho tab Dự án màn public cư dân (ảnh bìa eager-load, ưu tiên official > watermark; search name/CĐT/địa chỉ). Bảng `user_public_projects` (dự án quan tâm chọn khi đăng ký) + AuthController.register nhận `project_codes`. Fix `projects.sales_status` (chip màn public) + DemoImage đúng chủ đề.

**Export/đồng bộ:** `projects:export-json` (STREAM chunkById 500, bỏ orderBy phá phân trang, tránh hết RAM) → `public_projects_export.json` (15MB, 6.000 dự án) đã commit. Server: `PublicProjectImportSeeder` + `Admin2025Seeder` + `sync-media` (dựng lại media từ metadata, không gọi batdongsan).

**Verify:** DB thật x2bms — 6.005 dự án · 64.7k ảnh · 3.097 CĐT · ~6k toạ độ · 62 tỉnh. Tất cả command lint sạch, không mojibake/BOM. Đã commit + push toàn bộ (nhánh main).

**Còn lại:** 1.272 dự án thiếu CĐT (bổ sung tay/SerpAPI); ảnh watermark (chờ SerpAPI/tải ảnh về local — làm sau); bật `ENRICH_PROVIDER=serpapi` + key trong .env để có ảnh sạch; command `projects:download-covers` (tải ảnh về kho) chưa làm.

## 2026-07-27 — Reader: restyle code block (card xám + header + số dòng + syntax highlight)

**Phạm vi:** đổi khối code trong reader `/docs` từ "nền tối tràn rộng" sang CARD đẹp giống card JSON. Chỉ đụng code block + assets liên quan; không phá layout 3 cột/version/TOC.

**Highlighter:** dùng `scrivo/highlight.php` (`Highlight\Highlighter`) — ĐÃ CÓ SẴN trong vendor (transitive dep), server-side, PHP port highlight.js, 185 ngôn ngữ. **KHÔNG thêm composer package, KHÔNG CDN, KHÔNG JS highlight** → hoạt động cả cho guest.

**`DocsMarkdown` (`styleCodeBlocks` + `highlightCode`):** post-process `<pre><code class="language-xxx">` do commonmark sinh → CARD: `.docs-code[data-code]` > `.docs-code-head` (tên ngôn ngữ + nút Copy) + `.docs-code-body` (`.docs-code-gutter` số dòng + `.docs-code-scroll > code.hljs`). Giải mã entity lấy source thật để highlight + nhét `data-code` (copy lấy đúng source, KHÔNG kèm số dòng). Alias sh/shell→bash, js→javascript, yml→yaml, md→markdown, html→xml. Ngôn ngữ trống/lạ → escape thường, nhãn "CODE", vẫn có card + số dòng (vd cây thư mục ASCII).

**`layout.blade.php`:** bỏ CSS `.docs-content pre` nền navy + nút copy nổi cũ. Thêm CSS card nền xám nhạt (#f6f8fa, viền #e5e7eb, bo góc, chỉ rộng theo cột nội dung) + gutter số dòng (`user-select:none`) + theme hljs github-ish (light) + biến thể `@media (prefers-color-scheme: dark)` (xám đậm dịu, không đen tuyền). `.docs-code-scroll` `overflow-x:auto` + `white-space:pre` (cuộn ngang khi dài, gutter cố định). Copy JS đổi sang đọc `data-code` từ `.docs-code`.

**Verify:** lint `DocsMarkdown` sạch · render block ```json/```bash/không-ngôn-ngữ → card xám, header JSON/BASH/CODE + Copy, gutter số dòng, có `hljs-attr/string/...` · `data-code` = source đúng, KHÔNG chứa số dòng · full page (mobile-api-usage) render OK, không còn rule navy `pre`, có dark-mode block · không mojibake/BOM. KHÔNG cần cài thêm gì trên server (highlight.php đã có trong vendor). CHƯA commit.

## 2026-07-27 — Docs CMS = nơi xuất bản chính thức (đa nguồn x2bms + x2mobile) + fix H1 tiêu đề

**Phạm vi:** biến Docs CMS thành nơi chính thức publish tài liệu dev + hướng dẫn của CẢ 2 dự án; import đa nguồn an toàn khi thiếu repo; cập nhật quy trình (skill + CLAUDE.md); fix cỡ chữ H1 tiêu đề trang reader.

**Config-driven import (`config/docs.php`):** thêm `spaces` (7 space: dev, mobile-dev, ops, cu-dan, bql, hq, sa — title/audience/is_public/sort) + `import_paths` (danh sách nguồn: `docs/dev`→dev, `docs/guide`→`guide_audience`, `../x2mobile/docs/guide/cu-dan`→cu-dan, `../x2mobile/docs/dev`→mobile-dev).

**`DocsImport` viết lại theo config:** `ensureDefaultVersion()` + `ensureSpaces()` (tạo mọi space khai báo dù nguồn trống) + lặp `import_paths`: `is_dir()` false → `warn("skip (không tồn tại)")` KHÔNG lỗi; entry `space` → gom 1 space; entry `mode=guide_audience` → map thư mục con (bql/hq/sa/ops). `resolvePath()` dùng `base_path()` + `realpath` (trỏ được repo cạnh `../x2mobile`). Bỏ SUMMARY.md. Vẫn gán `version_id=v1.0`, idempotent.

**Fix UI H1 tiêu đề trang** (`show.blade.php` + `layout.blade.php`): tiêu đề trang trước đây `<h1>` không có font-size → nhỏ (nhất là khi bật X2AI kéo theo preflight app.css). Thêm class `.docs-pagetitle` (font 2.1rem, weight 700, line-height 1.25). Tiêu đề chứa `` `code` `` → escape rồi biến `` `...` `` thành `<code>` (`.docs-pagetitle code` cỡ .9em, không bị nhỏ).

**Skill `cap-nhat-tai-lieu/SKILL.md`:** thêm bước 8 "XUẤT BẢN vào Docs CMS" (chạy `docs:import` hoặc soạn `/sa` → gán space+version → thêm 1 mục backlog vào DocVersion hiện hành). **`CLAUDE.md`:** thêm dòng Docs CMS là nơi tài liệu chính thức + quy trình chốt.

**Verify:** lint `DocsImport` sạch · `docs:import` (có x2mobile): 16 trang, dev/ops/mobile-dev có nội dung, cu-dan skip êm · giả lập server KHÔNG có x2mobile (path bịa `../KHONG_TON_TAI/...`): exit 0, skip 2 nguồn, không lỗi · 2 space mới cu-dan (resident/public) + mobile-dev (dev/nội bộ) tạo OK, mobile-dev=4 trang · H1: class `docs-pagetitle` 2.1rem, `/api/v1`→`<code>` · không mojibake/BOM (Edit/Write UTF-8). CHƯA commit.

## 2026-07-27 — Module Tài liệu Phase 5: PHIÊN BẢN SẢN PHẨM (v1.0/v2.0) + Backlog

**Phạm vi:** thêm khái niệm "phiên bản sản phẩm" toàn site (v1.0/v2.0) + backlog hạng mục, gắn version cho từng trang. TÁCH BẠCH với "revision từng trang" (Phase 3/4): reader đổi nhãn control revision thành **"Lịch sử sửa trang"**, control mới là **"Phiên bản"** (`?ver=`).

**DB (3 migration):** `..._000006_create_doc_versions_table` (label unique, name, released_at, status enum planned/in_progress/released, is_current bool, sort, summary) · `..._000007_create_doc_version_items_table` (doc_version_id cascade, category enum feature/improvement/fix/change, title, detail, status enum done/in_progress/planned, ref_page_id nullOnDelete, sort) · `..._000008_add_version_id_to_doc_pages` (nullable FK nullOnDelete; null = trang chung).

**Models:** `DocVersion` (hasMany items/pages, routeKey label, `current()`), `DocVersionItem` (belongsTo version/refPage), `DocPage`+`version()`.

**Filament (/sa, nav "Tài liệu"):** `DocVersionResource` (CRUD + `ItemsRelationManager` backlog reorderable) — `is_current` độc nhất qua hook `afterCreate/afterSave` (bỏ current các version khác). `DocPageForm` +Select `version_id` (trống = chung).

**Reader (`DocsController`):** helpers `visibleVersions` (guest chỉ released), `activeVersion` (`?ver=` hợp lệ else current), `applyVersionScope` (version_id = active OR null), `shareVersionContext` (share `docVersions`+`activeVersion`). `pageTree`/`firstPage`/`search` lọc theo version. `show()` set `versionMismatch` khi trang thuộc version khác. Action `versions()` + route `/docs/versions` (loại khỏi catch-all bằng `versions$` trong regex). Blade: `layout` (bộ chọn phiên bản sidebar + JS `docsSetVersion`/`docsSetRevision`), `show` (nhãn "Lịch sử sửa trang" + banner mismatch), `versions.blade.php` (timeline + backlog nhóm category).

**Import:** `docs:import` tạo `v1.0` (released, is_current nếu chưa có) idempotent + gán trang import `version_id=v1.0`.

**Verify (DB thật, controller + reflection):** migrate 3 bảng ✅ · import v1.0 gán 12 trang ✅ · activeVersion: guest→v1.0, admin `?ver=v2.0`→v2.0, guest `?ver=v2.0`(planned)→v1.0 ✅ · lọc cây guest ẩn trang v2.0 / admin `?ver=v2.0` hiện ✅ · banner mismatch ✅ · `/docs/versions` guest chỉ v1.0(released) / admin v2.0+2 backlog item, render OK (đã bổ sung `$spaces` cho sidebar) ✅ · is_current độc nhất ✅ · lint 13 file sạch, không mojibake/BOM. Dọn data test, DB về v1.0.

**Điểm chờ chủ dự án:** (1) chính sách gán version cho trang khi ra v2.0 — clone trang hay chuyển; (2) có cần diff nội dung giữa 2 phiên bản sản phẩm không.

## 2026-07-27 — Module Tài liệu Phase 4: search full-text + X2AI + copy code + sửa nhanh (+3 chỉnh UI)

**Phạm vi:** 4 tính năng reader + 3 tinh chỉnh giao diện. Không đổi backend phân quyền/public của Phase 1–3.

**1. Tìm kiếm full-text.** Migration `..._000005_add_fulltext_to_doc_pages` (FULLTEXT `(title, body)`, InnoDB; guard mysql, có `down`). `DocsController@search`: `MATCH…AGAINST … IN BOOLEAN MODE` (`+từ*` mỗi từ) + order relevance; fallback LIKE (driver ≠ mysql hoặc rỗng kết quả). Tôn trọng quyền (space được xem + published). Thêm `buildSnippet()` (bỏ markdown, ~40 từ quanh match, escape rồi `<mark>` — chống XSS) + `matchHeadingAnchor()` (khớp heading → `#slug`). `search.blade.php` render thẻ kết quả (snippet, badge "khớp tiêu đề mục", link + anchor).

**2. X2AI trong reader.** Tái dùng NGUYÊN `<x-x2.ai-fab>` + Livewire `x2ai-chat` (không dựng AI mới). Ngữ cảnh: `view()->share('x2aiContext', ['title'=>...])` trong `show()`; nội dung trang bắt qua `window.x2aiCaptureScreen()` (đọc `<main>` — reader có `<main class="docs-main">`). **Chỉ nạp cho `@auth` + `X2aiPolicyGate::canUse()`** (`ai.use`); guest KHÔNG có chat/asset/endpoint AI. Asset trong nhánh điều kiện: `@livewireStyles` + `@vite('resources/css/app.css')` + `@livewireScripts` (Alpine đi kèm Livewire 3). Hardened `.docs-content ul/ol` list-style tường minh để preflight của app.css không xoá bullet.

**3. Copy code.** JS thuần trong `layout.blade.php`: gắn nút "Copy"→"Đã sao chép" vào mọi `.docs-content pre`; `navigator.clipboard` + fallback `execCommand`. CSS `.docs-copy-btn` (hiện khi hover pre).

**4. Sửa nhanh từ reader.** `@auth @can('docs.manage')` → nút "✎ Sửa trang" deep-link `/sa/doc-pages/{id}/edit` (tab mới). Ẩn hoàn toàn với guest/không quyền.

**Chỉnh UI (theo chủ dự án):** (1) `DocsMarkdown::render($md, stripLeadingH1:true)` bỏ H1 đầu body trùng tiêu đề (h2/h3 giữ) — `show()` gọi với cờ này. (2) Dòng version thành `<select>` LUÔN hiện (disabled khi 1 version), giữ banner bản cũ — ghi chú "version = revision từng trang" chờ chủ dự án xác nhận nếu muốn version toàn bộ. (3) Bỏ `max-width` `.docs-article`/`.docs-main` → content full-width giữa sidebar và TOC.

**Files:** `database/migrations/..._000005…php` · `app/Support/Docs/DocsMarkdown.php` (render `$stripLeadingH1` + `stripLeadingH1()`) · `app/Http/Controllers/Docs/DocsController.php` (search full-text + snippet/anchor + share x2aiContext + stripLeadingH1) · `resources/views/docs/{layout,show,search}.blade.php`.

**Verify (DB thật, HttpKernel + controller render):** lint sạch 3 file PHP · FULLTEXT migrate OK · guest 'Coolify'→ops + snippet + `<mark>`; 'Seeding' guest 0 rows (không rò dev), admin 2 rows · guest page KHÔNG AI/livewire/nút sửa; admin có AI fab + livewireScripts + @vite + deep-link đúng id; technician (ai.use, không docs.manage): AI có, nút sửa ẩn · content `<h1>`=0 (đã strip) + `<h2 id>` còn (TOC) · dropdown version luôn hiện · copy-code script có · full-width (không cap 860 ngoài media-query). Không mojibake/BOM (Edit/Write UTF-8). CHƯA commit.

**⚠️ Điểm chờ chủ dự án quyết:** X2AI cho GUEST — hiện **tắt** (chỉ user login + `ai.use`) để tránh chi phí token/abuse trên site public. Nếu muốn bật cho guest phải cân nhắc rate-limit/chi phí + tách endpoint an toàn.

## 2026-07-27 — Module Tài liệu Phase 3: polish UI reader (3 cột + TOC + version)

**Phạm vi:** nâng cấp giao diện reader `/docs` (Blade) — bố cục 3 cột, mục lục "Trong trang này", hiển thị phiên bản rõ ràng. Không đổi backend/phân quyền; giữ nguyên chế độ public/guest của Phase 2.

**`app/Support/Docs/DocsMarkdown.php`:** thêm `render()` trả `['html','headings']`; gán `id` (slug) cho `h2`/`h3` bằng regex trên HTML đã render (an toàn vì `html_input=strip`), slug qua `Str::slug` (hỗ trợ tiếng Việt) + dedupe trùng. Giữ `toHtml()` (delegate `render()['html']`) để chỗ gọi cũ không vỡ.

**`DocsController@show`:** truyền thêm `headings`, `latestVersion` (max version), `updatedAt` cho view.

**`resources/views/docs/layout.blade.php`:** CSS mới — `.docs-layout` (grid `minmax(0,1fr) 240px`), `.docs-toc` (sticky, scrollspy `.active`), `.docs-verline`/`.docs-verpill`, `.docs-toc-inline` (khối `<details>` cho màn nhỏ). `< 1100px` ẩn cột phải; `.docs-main` max-width 900→1180px, `.docs-article` cap 860px.

**`resources/views/docs/show.blade.php`:** dựng 3 cột; head trang = tiêu đề + dòng "Phiên bản N · cập nhật dd/mm/yyyy" + dropdown chọn version; banner khi xem bản cũ; cột phải "Trong trang này" + inline `<details>`; script scrollspy (IntersectionObserver, `rootMargin -70%`) + smooth-scroll khi bấm mục lục.

**Verify (DB thật, render qua controller):** lint sạch (renderer + controller) · slug tiếng Việt đúng ("Cài đặt & Cấu hình"→`cai-dat-cau-hinh`), dedupe `van-hanh`/`van-hanh-2` · trang dev: có `docs-layout`, `#docs-toc`, pill "Phiên bản N", script `IntersectionObserver`, `<h2 id=...>` · xem `?v=` bản cũ → banner "phiên bản cũ" hiện · `docs:import --fresh` khôi phục dữ liệu test (12 trang/5 space) · không BOM/mojibake (Edit/Write UTF-8). CHƯA commit theo yêu cầu.

## 2026-07-27 — Module Tài liệu Phase 2: site công khai qua subdomain (doc.x2.fino.vn)

**Phạm vi:** cho phép guest xem tài liệu công khai qua subdomain phục vụ từ chính app x2bms; space nội bộ vẫn yêu cầu đăng nhập + quyền.

**Migration:** `2026_07_27_000004_add_is_public_to_doc_spaces` — thêm `is_public` (bool, default false) sau `is_published`. Model `DocSpace`: thêm cast `is_public=>boolean` (đã `$guarded=[]` nên mass-assignable).

**Filament:** `DocSpaceForm` +Toggle `is_public` ("Công khai (cho xem không cần đăng nhập)", helper trỏ doc.x2.fino.vn). `DocSpacesTable` +IconColumn `is_public`.

**Config:** `config/docs.php` mới — key `host` = `env('DOCS_HOST', 'doc.x2.fino.vn')`.

**Định tuyến theo host (`routes/web.php`):**
- Root `/` (named `docs.home`): host == `config('docs.host')` → `DocsController@index` (landing); host khác → redirect `/admin`.
- Nhóm `/docs` (`docs.index/search/show`) **BỎ middleware `auth`** (controller tự phân quyền), domain-agnostic → chạy trên cả host chính lẫn subdomain. Giữ ràng buộc negative-lookahead loại `api`/`api.json` (Scramble).

**Phân quyền (`DocsController::canView`):** (1) chưa published → ẩn; (2) `is_public` → guest xem được (chỉ trang published); (3) nội bộ → cần login + `docs.view.{audience}`. Guest gặp space nội bộ → redirect `filament.admin.auth.login`; login mà thiếu quyền → 403. `visibleSpaces()` lọc sidebar/landing/search theo cùng luật; guest không thấy draft.

**Reader (Blade):** link nội bộ chuyển sang **URL tương đối** (`route(..., absolute:false)`) để giữ nguyên host đang duyệt (không nhảy chéo domain). `index` thêm badge "công khai" + text/CTA đăng nhập cho guest; layout thêm khối "Đăng nhập để xem tài liệu nội bộ".

**Import (`docs:import`):** `spaceDefs` thêm `public` (ops=true, dev/bql/hq/sa=false). Set is_public khi TẠO MỚI hoặc `--fresh` (không ghi đè chỉnh tay ở import thường). Đổi `updateOrCreate` → `firstOrNew`+`fill`.

**Verify (DB thật, giả lập HTTP qua HttpKernel):** migrate ✅ · `docs:import --fresh` (ops public, dev private, 11 trang/5 space) ✅ · guest `/docs`=200 chỉ ops (ẩn dev) · guest `/docs/ops`=200 · guest `/docs/dev`=302→`/admin/login` · host `doc.x2.fino.vn` `/`=landing 200 chỉ ops · host chính `/`=302→`/admin` · guest search 'Seeding' rò 0 trang dev · admin thấy cả 5 space · lint 8 file sạch.

**Hạ tầng (chủ dự án làm — CloudPanel):** `docs/guide/deploy-cloudpanel-docs-subdomain.md` (DNS A → thêm domain vào site x2bms dùng chung `public/` → Let's Encrypt → `DOCS_HOST` + `config:cache`; phương án 2 nếu không add được nhiều domain/1 site). CHƯA commit theo yêu cầu.

## 2026-07-27 (chiều) — Cộng đồng: lớp GHI + kiểm duyệt (9 route, verify HTTP thật)

**Bối cảnh:** app cư dân đã dựng xong UI tab Cộng đồng kiểu FB/Zalo nhưng backend chỉ có route ĐỌC, app phải giữ bài trong RAM. Slice này khép luồng. Chủ dự án chốt: **KHÔNG duyệt trước**, đăng là hiện ngay, hậu kiểm; BQL có thể khóa/ẩn/xóa cả trên web lẫn ngay trên app.

**⚠️ Máy dev CHẠY ĐƯỢC PHP** — Herd có sẵn `C:\Users\ADMIN\.config\herd\bin\php84\php.exe` (8.4.15). Ghi chú cũ "máy này không chạy được PHP" đã lỗi thời. Nhân tiện chạy luôn 2 migration treo từ phiên trước (`apartment_wallets`, `attachments`) — sạch, hết nợ verify.

**1. Ba hành động kiểm duyệt TÁCH BẠCH** (đừng gộp — đây là chỗ dễ sai nhất):
`locked_at` = bài CÒN hiện, cấm tương tác (423) · `status=hidden` = gỡ khỏi feed nhưng **tác giả vẫn thấy kèm lý do** (không thì họ tưởng app lỗi rồi đăng lại) · `deleted_at` = xóa mềm, tác giả tự xóa bài mình được.

**2. Migration `2026_07_27_100001`:** `community_posts` +`author_user_id`/`author_kind`/`locked_at`/`locked_by_user_id`/`moderated_at`/`moderated_by_user_id`/`moderation_reason`/`report_count`; bảng mới `community_post_reactions` (unique post+user → đổi emoji là UPDATE, không cộng dồn) và `community_post_reports` (unique post+user, idempotent).
**BẪY:** tên unique tự sinh của `community_post_reports` dài **66 ký tự**, vượt giới hạn 64 của MySQL → phải đặt tên tay (`cp_reports_post_user_unique`).

**3. Lưu MÃ cảm xúc, không lưu ký tự emoji** (`like|love|haha|wow|sad|angry`) — đổi bộ icon ở app không phải migrate data.

**4. `can{}` do SERVER tính** (`CommunityModerationService`): app không suy vai trò từ `abilities` để bật/tắt nút. Kèm `tallyMany()` gộp cảm xúc cả trang feed — query từng bài là N+1.

**5. BẪY MIDDLEWARE:** nhóm resident dùng `ability:resident` sẽ **chặn chính nhân sự BQL** khỏi route kiểm duyệt. Alias `ability` = `CheckForAnyAbility` (**OR**, không phải AND) → tách nhóm riêng `ability:resident,staff`. Verify bằng `nv1@x2bms.vn` (ability chỉ có `staff`, `hasResidentMembership=false`) — gọi được, và bị chặn 403 khi bài ngoài `accessibleProjectIds`.

**6. BẪY RESOURCE:** `Resource::collection(...)->additional([...])` **KHÔNG xuống tới từng item con** — feed trả `can:{}` cho mọi bài. Chuyển sang gắn meta thẳng lên model (`$post->post_meta = [...]`), đúng lối `is_mine` module bình luận đang dùng.

**7. Sửa bug ảnh:** `CommunityPostResource` fallback ảnh demo cho MỌI bài không ảnh → bài cư dân đăng chay bằng chữ tự mọc ảnh lạ. Nay chỉ fallback cho bài seeder (`author_user_id === null`); bài thật đọc từ attachment, không có thì trả `[]`.

**8. Siết `report`:** trả `can.report=false` cho bài của chính mình nhưng endpoint vẫn nhận → cờ kia thành trang trí. Nay chặn 403.

**9. Seeder `CommunityFeedDemoSeeder`:** +14 bài **nhiều tác giả** + 52 cảm xúc trộn emoji + bình luận mẫu. Feed cũ 10 bài cùng một người nên vừa thưa vừa đơn điệu. Idempotent (`title = FEED-<key>`), chạy 2 lần vẫn 27 bài.

**Verify (HTTP thật `https://x2bms.test`, token Sanctum của cư dân/staff/cư-dân-khác):**
201 đăng bài (UTF-8 OK) · 200 upsert cảm xúc (đổi emoji → total vẫn 1) · 422 emoji ngoài whitelist · 201/200 bình luận · 403 báo cáo bài mình · 403 kiểm duyệt khi không phải BQL · 422 kiểm duyệt thiếu lý do · **423** tương tác vào bài đang khóa · bài ẩn: tác giả 200 (kèm lý do) / người khác **404** / không lọt vào feed · xóa mềm 200. Đã dọn dữ liệu test + thu hồi toàn bộ token CLI.

**Còn lại:** màn kiểm duyệt trên Web BQL (BQL-07-08) — Resource `CommunityPosts` vẫn là scaffold sinh tự động.

---

## 2026-07-27 — Module Tài liệu (docs CMS kiểu GitBook, tự code)

**Phạm vi:** trung tâm tài liệu nội bộ có soạn thảo (Filament) + reader (web) + phân quyền theo đối tượng + quản lý version.

**Migrations mới:** `2026_07_27_000001_create_doc_spaces_table` (key unique, title, description, audience enum[dev/ops/bql/hq/sa/resident], icon, sort, is_published) · `000002_create_doc_pages_table` (space_id, parent_id tự tham chiếu cây, slug, title, sort, body longText markdown, status enum, updated_by, timestamps+softDeletes, unique[space_id,parent_id,slug]) · `000003_create_doc_page_revisions_table` (page_id, version, title, body, note, editor_id, created_at; unique[page_id,version]).

**Models mới:** `DocSpace` (hasMany pages/rootPages, routeKey=key) · `DocPage` (belongsTo space/parent, hasMany children/revisions, `#[ObservedBy(DocPageObserver)]`, `pathSegments()`) · `DocPageRevision`. **Observer** `DocPageObserver`: `created`+`updated` (khi `wasChanged('title'|'body')`) → tạo revision version tăng dần.

**Filament (panel `/sa` SuperAdmin, nav group "Tài liệu"):** `DocSpaceResource` (CRUD, reorderable sort, auto-slug từ title) · `DocPageResource` (Select space/parent lọc theo space, auto-slug, `MarkdownEditor` body có `fileAttachmentsDisk('public')` để chèn ảnh, status) + `RevisionsRelationManager` (bảng version chỉ đọc + action **Xem** modal + **Khôi phục** → ghi lại body cũ, sinh version mới). `updated_by` set ở `mutateFormDataBeforeCreate/Save`.

**Phân quyền:** `DocsPermissionSeeder` tạo `docs.view.{dev,ops,bql,hq,sa,resident}` + `docs.manage`, gán cho 14 role theo 3-tier (super_admin/platform_support = tất cả; company_admin/operations_director = hq+ops+bql; building_manager = bql+ops+resident; …). Reader lọc space bằng `$user->can("docs.view.{audience}")`.

**Reader (web `/docs`):** `Http/Controllers/Docs/DocsController` (index/show/search) + `Support/Docs/DocsMarkdown` (commonmark GFM, `html_input=strip` + `allow_unsafe_links=false` chống XSS). Route `/docs`, `/docs/search`, `/docs/{space:key}/{path?}` (where path `.*`, where space negative-lookahead loại `api`/`api.json` để KHÔNG nuốt route Scramble `/docs/api`). Blade tự chứa CSS (2 cột sidebar navy + content, breadcrumb, chọn version `?v=`, tìm kiếm LIKE, responsive): `views/docs/{layout,index,show,search,_spaces,_tree}.blade.php`.

**Command:** `docs:import` (`Console/Commands/DocsImport.php`, idempotent, `--fresh`): nạp `docs/dev/**` → space dev; `docs/guide/**` → audience theo thư mục (bql/hq/sa, còn lại ops); bỏ SUMMARY.md; slug phẳng từ relative path; title từ heading `#` đầu.

**Verify (DB thật x2bms, PHP 8.4 Herd):** `migrate` OK (3 bảng) · `db:seed DocsPermissionSeeder` OK (7 quyền / 14 role) · `docs:import` OK (5 space, 9 trang từ dev+ops — guide/bql|hq|sa chưa có file nên trống) · observer test: sửa body → revisions 1→2 · markdown sanitize: `<script>` bị strip, bảng GFM render · reader show/search render 200 (len ~11–12KB) · `route:list` đủ 3 route docs.* · `/docs/api` vẫn về Scramble (không bị nuốt).

**Panel — CHỐT `/sa` (SuperAdmin):** chủ dự án quyết định 2026-07-27 đưa module vào `/sa` thay vì `/fila`. Đã dời `app/Filament/Resources/{DocSpaces,DocPages}` → `app/Filament/Sa/Resources/...`, đổi namespace `App\Filament\Resources\Doc*` → `App\Filament\Sa\Resources\Doc*` (13 file: Resource + Schemas + Tables + Pages + RelationManager), đổi `navigationGroup` 'Hệ thống' → 'Tài liệu'. `SaPanelProvider`: thêm `->discoverResources(App\Filament\Sa\Resources)` + `NavigationGroup::make('Tài liệu')->icon('heroicon-o-book-open')`. Verify: `route:list --path=sa` thấy 6 route `sa/doc-spaces|doc-pages`; `/fila` không còn doc-; lint 13 file sạch; reader `/docs` không đổi.

## 2026-07-25 — Bình luận thông báo (cư dân comment) + comment_count

**Phạm vi:** cho cư dân bình luận dưới một thông báo (app hiển thị số + list + input).

**File mới:** migration `notification_comments` (notification_id cascade, user_id nullOnDelete, author_name, body, timestamps+softDeletes); `Models/NotificationComment`; `Resources/NotificationCommentResource` (id, body, author{name,avatar_url}, is_mine, created_at).

**File đổi:** `Notification` model +`comments()` HasMany. `NotificationController` +`comments()` (GET cursor) +`storeComment()` (POST, validate body≤2000, author=user hiện tại, scope visibleQuery→404 nếu không thấy); `index/show` +`withCount('comments')`. `NotificationResource`+`NotificationDetailResource` +`comment_count`. `routes/api.php` +GET/POST `notifications/{id}/comments`.

**Verify HTTP (Herd, user #6, x2bms.test):** list trả `comment_count`; POST `{body}`→201 (author name+avatar_url person-level, is_mine:true); GET comments count=1 is_mine=true; comment_count list+detail =1. `php -l` sạch. Docs RESIDENT_API_REFERENCE §4.

**Deploy live:** cần chạy `php artisan migrate` (tạo bảng notification_comments) trên x2.fino.vn — `deploy.sh` tự làm.

## 2026-07-25 — Avatar upload (person-level) cho app cư dân: POST/DELETE /me/avatar

**Phạm vi:** dựng luồng ảnh đại diện còn thiếu (trước đó ProfileController ghi rõ "avatar upload multipart CHƯA làm"). Avatar là **person-level** (tài khoản global dùng chung nhiều tenant → cùng khuôn mặt), lưu disk `public` tại `avatars/users/{user_id}/…` — **KHÔNG qua TenantStorage** (đúng khuôn `Resident::avatarUrl` sẵn có; không đụng invariant cô lập tenant vì đây không phải dữ liệu tenant). Cư dân `tenant_id=NULL` + API stateless nên context tenant không khả dụng — càng khẳng định person-scope là đúng.

**File đổi:**
- `app/Models/User.php` — thêm accessor `avatarUrl` (mirror Resident): có `avatar_path` → `Storage::disk('public')->url(...)`, else `ui-avatars` theo `name`. +import `Attribute`/`Storage`.
- `app/Http/Controllers/Api/V1/ProfileController.php` — `avatar()` (POST, validate `image|mimes:jpeg,jpg,png,webp|max:4096`, `store('avatars/users/{id}','public')`, xoá file cũ, ghi `user.avatar_path` + **đồng bộ `residentMemberships()->update(avatar_path)`** trong transaction) + `removeAvatar()` (DELETE → null + xoá file) + `userPayload()` dùng chung (thêm `avatar_url`). `update()` cũng trả `avatar_url`.
- `routes/api.php` — `POST/DELETE me/avatar` trong nhóm `auth:sanctum`.
- `app/Http/Controllers/Api/V1/BootstrapController.php` — `me().user` thêm `avatar_url`.
- `docs/api/RESIDENT_API_REFERENCE.md` — mục §1 + ghi chú person-level.

**Đồng bộ hiển thị:** vì propagate sang `resident` liên kết, avatar hiện nhất quán ở list thành viên hộ (`members[].avatar_url` khi `is_me`) và tác giả bài cộng đồng.

**Verify HTTP (Herd, token user #6, `x2bms.test`):** `me/bootstrap.user.avatar_url` = ui-avatars fallback → POST multipart PNG → trả `.../storage/avatars/users/6/xxxx.png` (GET file 200); bootstrap + `resident/apartment` member is_me phản ánh URL mới; DELETE → về ui-avatars + file cũ bị gỡ (không rác). `php -l` sạch 4 file.

## 2026-07-24 — Demo "giàu hình ảnh": ảnh thật cho mọi Resource + tăng volume seed 2-3x

**Phạm vi:** làm app cư dân demo giàu hình ảnh + nhiều dữ liệu (chỉ backend). Verify HTTP thật Herd token user #6 trên `x2bms.test` — mọi item đều có `image_url`/`image_urls`/`cover_url` bắt đầu `https://`.

**1. Helper ảnh** — thêm `app/Support/DemoImage.php`: `DemoImage::url($keywords, $id, $w, $h)` trả URL ảnh thật loremflickr theo chủ đề, ổn định theo id (`lock=crc32(id)`), không cần API key.

**2. Resource dùng ảnh thật** (ưu tiên cột ảnh thật nếu có, else DemoImage theo chủ đề — GIỮ NGUYÊN shape/scope khác):
- `OfferResource`/`GiftResource`.image_url · `EventResource`.image_url · `CommunityGroupResource`.image_url.
- `CommunityPostResource`.image_urls → nếu `image_paths` rỗng trả 1 ảnh demo.
- `MarketProductResource`.image_url → theo `category` (household→furniture, electronics→gadget, fashion→clothes, khác→product).
- `ServiceProviderResource`.image_url → `service,repair,<category>`.
- `AmenityResource`.image_url → theo type/name (pool→swimming, gym→fitness, bbq→grill, khác→facility).
- `RealEstateListingResource` → **THÊM** field `image_url` (`apartment,interior,realestate`).
- `NotificationResource` (list) → **THÊM** `cover_url`; `NotificationDetailResource`.cover_url → fallback ảnh demo 1200×500.
- `BootstrapController@public` `featured_projects[].image` → ảnh demo 1200×700.

**3. Enrich `ResidentDemoContentSeeder`** (idempotent, scope tenant1/project1/resident1305): vouchers 8 offers + 6 gifts; loyalty 4200đ (gold) + 10 hoạt động; community 10 posts + 5 events + 3 polls + 5 groups; **mới** `seedMarketplace` (10 SP đa category), `seedServices` (6 NCC), `seedRealEstate` (5 tin sale+rent), `seedNotifications` (4 tb published audience `all` có body); visitors 2→5, amenity bookings 2→4, feedback 2→4 (status/priority đa dạng).

**Verify HTTP (bằng chứng):** offers 9 · gifts 8 · posts 13 · events 5 · groups 6 · market listings 13 · services 6 · real-estate 7 · amenities 4 · notifications 6 — **tất cả items bad=0** (mọi ảnh `https://loremflickr.com/...`). Notification detail `cover_url` + `body` OK; `public/bootstrap` 12 featured có `image`.

**Docs:** cập nhật `RESIDENT_API_REFERENCE.md` (image_url/cover_url nay trả URL ảnh demo) + `RESIDENT_API_OPERATIONS.md` §3 (volume seed mới).

---

## 2026-07-24 — Resident API Phase 1: khách/tiện ích/phản ánh/thông báo-detail/biên lai

**Phạm vi:** 5 nghiệp vụ cư dân còn thiếu (bảng/model đã có sẵn — chỉ dựng Resource/Controller/route + seed + verify HTTP Herd token user #6). Scope tường minh qua `ResidentContextService` (cư dân tenant_id=NULL → global scope no-op). Tất cả verify HTTP thật OK trên `x2bms.test`.

**1. Đăng ký khách** (`visitor_registrations`): `VisitorController` (index/store/cancel) + `VisitorRegistrationResource`. GET `/resident/visitors`, POST `/resident/visitors` (code `KH`+random, status `pending`, apartment/building/project/resident/host_user từ context), POST `/resident/visitors/{visitor}/cancel` (chủ căn → `cancelled`). Scope `apartment_id ∈ apartmentIds`.

**2. Đặt tiện ích** (`amenities`/`amenity_slots`/`amenity_bookings`): `AmenityController` (index/show/bookings/book/cancelBooking) + `AmenityResource`/`AmenitySlotResource`/`AmenityBookingResource`. Amenities scope theo dự án (`status=active`); detail kèm `slots`. Booking scope resident/căn; status `confirmed` (mặc định) hoặc `pending` nếu `requires_approval`. DELETE → `cancelled`.

**3. Phản ánh** (`feedback_requests`/`feedback_categories`): `FeedbackController` (categories/index/store/show) + `FeedbackCategoryResource`/`FeedbackRequestResource`. Categories theo tenant; feedback scope `resident_id ∈ residentIds OR apartment_id ∈ apartmentIds`. POST tạo `status=new`, `channel=app`, code `PA`+random. Detail trả `timeline[]` (comments công khai + status histories). Lưu ý `feedback_requests.building_id` NOT NULL → set từ căn.

**4. Chi tiết thông báo** (`notifications`): thêm `NotificationController@show` + `NotificationDetailResource` (kèm `body`, `cover_url`). Dùng `ResidentNotificationService::visibleQuery` (không thấy=404) + `markRead` (idempotent). GET `/resident/notifications/{notification}`.

**5. Biên lai** (`receipts`): thêm quan hệ `Payment::receipt()` hasOne + `PaymentController@show` load `receipt` + `PaymentResource` trả `{code,amount,issued_at}` khi có (chỉ ở detail).

**Routes:** thêm 12 route vào nhóm `resident` (`routes/api.php`), đặt `notifications/{notification}` sau route literal.

**Seed:** `ResidentDemoContentSeeder` thêm `seedVisitors` (2), `seedAmenityBookings` (2), `seedFeedback` (2), `seedReceipts` (1 cho payment apt 11) — idempotent theo `code`/`payment_id`.

**Enum status thực tế dùng:** visitor `pending`; amenity booking `confirmed`/`pending`; feedback `new` (enum `FeedbackStatus`). Amenities có sẵn 4 (project 1, mỗi cái 2 slots).

---

## 2026-07-24 — Payment channels: mô hình MỖI DỰ ÁN 1 TÀI KHOẢN + admin Filament

**Chốt (owner):** VietQR cấu hình **per dự án** (mỗi project 1 tài khoản nhận). Seed demo `seedPaymentChannels` chuyển sang `project_id` cụ thể (dọn bản ghi tenant-wide cũ). Verify HTTP: `payment-methods` + `intent vietqr` resolve đúng từ bản ghi theo dự án (bank VCB, amount 13.2M, bank_apps 18).

**Admin Filament** (`fila/payment-channels`): `PaymentChannelResource` + Schemas/Tables/Pages — bật/tắt cổng, chọn tenant + dự án (null=tất cả), nhập TK nhận VietQR (config.bank_* dot-notation vào JSON, verify round-trip OK), VNPay/MoMo ghi chú khoá ở ENV. Owner tự nhập TK thật per dự án tại đây → VietQR golive.

---

## 2026-07-24 — Cổng thanh toán VietQR/VNPay/MoMo (per tenant/dự án) + community membership

**Phạm vi:** thanh toán cư dân (VietQR sinh QR từ hoá đơn + deeplink app ngân hàng; VNPay/MoMo scaffold) + `joined` nhóm cộng đồng. Verify HTTP Herd token user #6.

**File mới/đổi:**
- Migration `payment_channels` (tenant_id, project_id nullable, channel, is_enabled, config json, unique scope) + model `PaymentChannel`.
- Migration `community_group_members` (group_id, resident_id, role, joined_at, unique) + model `CommunityGroupMember`.
- `config/vietnam_banks.php` — 18 ngân hàng VN (BIN napas + android package + ios scheme) cho deeplink.
- `app/Services/Resident/VietQrService` — sinh chuỗi EMVCo napas (TLV + **CRC16-CCITT**) + `img.vietqr.io` URL + `bank_apps`. Bỏ dấu nội dung, số tiền nguyên đồng.
- `app/Http/Controllers/Api/V1/Resident/PaymentChannelController` — `index` (GET `/resident/payment-methods`, cổng bật theo project ∪ tenant, ưu tiên bản ghi riêng dự án) + `intent` (POST `/resident/payments/intent {statement_id, channel}`: VietQR đầy đủ; VNPay/MoMo → not_configured/pending theo ENV).
- `CommunityController` — `groups` tính `joined`; `joinGroup`/`leaveGroup` (POST/DELETE, tx cập nhật member_count). `CommunityGroupResource.joined` đọc cờ.
- `config/services.php` + `.env.example` — block `vnpay`/`momo` (ENV cho owner enable).
- Routes: `payment-methods`, `payments/intent` (đặt TRƯỚC `payments/{payment}`), `community/groups/{group}/join` (POST+DELETE).
- Seeder: `seedPaymentChannels` (VietQR VCB tenant 1 + VNPay chờ cấu hình) + membership resident #1305 vào 1 nhóm.

**Verify HTTP (token user #6):** `payment-methods` → vietqr+vnpay. `intent vietqr` (statement 11, công nợ 13.2M) → **CRC16 hợp lệ (9F0B)**, field 54=13200000, 53=704(VND), addInfo="TT HD11", bank_apps=18, img url OK. Community groups `joined=true` (nhóm đã seed); `join` 320→321; `leave` →320.

**Lưu ý:** statement 11 `code=null` → nội dung fallback `TT HD<id>`. Khoá bí mật VNPay/MoMo KHÔNG lưu DB (chỉ ENV).

---

## 2026-07-24 — Resident Payments (lịch sử + chi tiết); POST khởi tạo chờ owner chốt cổng

**Phạm vi:** vùng resident `/api/v1` (tab Hoá đơn — CD-PAY-05, lịch sử thanh toán). Verify HTTP Herd token user #6.

**File mới/đổi:**
- `app/Http/Controllers/Api/V1/Resident/PaymentController` — `index` (scope apartment_id ∈ apartmentIds OR resident_id ∈ residentIds, cursor) + `show` (guard sở hữu, load allocations + method).
- `app/Http/Resources/Api/V1/PaymentResource` — code/amount/status/method(name)/reference_no/paid_at/note + allocations khi loaded.
- `routes/api.php` — `payments`, `payments/{payment}`.
- `ResidentDemoContentSeeder::seedPayments` — 2 thanh toán (apt 11/resident 1305) + allocation vào statement đã trả.
- Docs: `RESIDENT_API_REFERENCE` (+ mục "Điểm chờ owner") + `DEV_JOURNAL`.

**Verify HTTP (token user #6):** `/payments` → 2 (method "Tiền mặt", status completed); `/payments/{id}` → allocations `[{statement_id:1271, amount}]`.

**CHƯA làm (chờ owner):** `POST /resident/payments` khởi tạo giao dịch cần **cổng thanh toán** (VietQR/VNPay/MoMo…) + credentials → nút Thanh toán app còn no-op.

---

## 2026-07-24 — Resident Home aggregate + AQI (Open-Meteo) + SOS

**Phạm vi:** vùng resident `/api/v1` (tab Home CD-HOME + nút SOS). Verify HTTP Herd token user #6.

**File mới/đổi:**
- `app/Services/Resident/AqiService` — proxy Open-Meteo Air Quality theo `projects.latitude/longitude`, cache theo project (`config('services.aqi.cache_ttl')`), dùng `us_aqi`; trả null (Home ẩn metric) khi thiếu toạ độ / lỗi. ENV `AQI_*` sẵn (phi thương mại → gắn gói khi prod).
- `app/Http/Controllers/Api/V1/Resident/HomeController@index` — `metrics` (AQI project đầu có toạ độ), `tasks` (fee←công nợ bcmath, guest←visitor_registrations sắp tới, feedback←feedback_requests đang mở), `notices_preview` (2 thông báo mới nhất, tái dùng `ResidentNotificationService`).
- `app/Http/Controllers/Api/V1/Resident/SosController@store` — `POST /resident/sos` tạo `sos_alerts` (source=app, status=triggered) scope căn đang chọn; location = "lat,lng"/mô tả/mã căn. KHÔNG tạo bảng mới.
- `routes/api.php` — `home`, `sos`.
- Docs: `RESIDENT_API_REFERENCE` + `DEV_JOURNAL`.

**Verify HTTP (token user #6):** `/home` → AQI **live** value 95 tone moderate ("Trung bình") — gọi Open-Meteo thật OK; tasks fee 13.2M (due), guest/feedback 0; notices_preview 2. `POST /sos` → 201, row `source=app/status=triggered/apt=11/project=1/resident=1305`, location "10.787,106.751", note lưu đúng (dùng `--data-raw`). Đã dọn 2 SOS test.

---

## 2026-07-24 — Resident tab Chợ + BĐS: market listings/services/categories + real-estate

**Phạm vi:** vùng resident `/api/v1` (tab Chợ — CD-MK-*). Verify HTTP Herd token user #6 (data có sẵn project 1/tenant 1).

**File mới/đổi:**
- `app/Http/Controllers/Api/V1/Resident/MarketController` — `listings` (marketplace_products, scope projectIds, filter `category`), `services` (service_providers, scope **tenantIds** — bảng không có project_id), `categories` (distinct category products), `realEstate` (real_estate_listings, scope projectIds, filter `type`).
- Resources: `MarketProductResource` (title/price/seller/building/image_url; rating/favorited null/false), `ServiceProviderResource` (rating string; price/image null), `RealEstateListingResource` (type/price/area/bedrooms/owner/apartment/published_at).
- `routes/api.php` — market/listings, market/services, market/categories, real-estate.
- Docs: `RESIDENT_API_REFERENCE` + `DEV_JOURNAL`.

**Verify HTTP (token user #6):** listings=3 (seller + building "Sunshine Garden - Tòa A"), services=3 (rating 4.7), categories=1 (household), real-estate=2 (rent/owner/apartment). Không cần seed — data sẵn có đủ scope.

**Lưu ý domain:** `service_providers` chỉ có `tenant_id` (KHÔNG project_id) → services scope theo tenant; listings/real-estate scope theo project.

---

## 2026-07-24 — Resident tab Cộng đồng: posts/events/polls(+vote)/groups

**Phạm vi:** vùng resident `/api/v1` (tab Cộng đồng — CD-CM-*). Scope `project_id ∈ projectIds`. Verify HTTP Herd token user #6.

**File mới/đổi:**
- `app/Http/Controllers/Api/V1/Resident/CommunityController` — `posts` (pinned trước, status=published), `events` (status=published, +cờ `registered` theo `event_registrations`), `polls` (status=open, +`voted`/`voted_option_id`), `vote` (guard 1/poll/resident, transaction tăng vote_count option+poll), `groups` (status=active).
- Resources: `CommunityPostResource` (author{name,role,avatar_url,verified}, likes/comments, pinned/important, image_urls từ image_paths), `EventResource`, `PollResource` (options+percent), `CommunityGroupResource`.
- `app/Models/CommunityPost` — thêm `$casts` `is_pinned/is_important`=bool, `image_paths`=array (fix bug bindValue([]) khi seed + để Resource đọc mảng ảnh đúng).
- `routes/api.php` — 5 route community.
- `ResidentDemoContentSeeder::seedCommunity` — 3 posts + 2 events + 1 poll(4 options) + 3 groups (project 1).
- Docs: cập nhật `RESIDENT_API_REFERENCE` + `DEV_JOURNAL`.

**Verify HTTP (token user #6):** posts=3, events=2 (registered=false), polls có options+percent, groups OK. **Vote:** POST option → `voted=true`, total 100→101; vote lại → **409 `already_voted`**. Endpoint chạy đúng cả trên dữ liệu community có sẵn (project 1) lẫn seed mới.

---

## 2026-07-24 — Resident tab Ưu đãi: `offers` + `loyalty/gifts` (voucher) + seed demo

**Phạm vi:** vùng resident `/api/v1` (tab Ưu đãi — CD-OF-01/CD-LY-01). Verify HTTP thật qua Herd `https://x2bms.test` với token resident user #6.

**File mới/đổi:**
- `app/Services/Resident/VoucherVisibilityService` — query voucher hiển thị cho cư dân: voucher tenant mình (`owner_level=tenant`, `tenant_id ∈ tenantIds`) **∪** voucher platform (`owner_level=platform`) đã rollout tới tenant mình & đang trong kỳ (pivot `voucher_tenant`: status active, now ∈ [starts_at, ends_at]); chỉ `status=active` + còn hạn (`valid_to >= today` hoặc null). tenantIds rỗng → `1=0`.
- `app/Http/Resources/Api/V1/OfferResource` — `id/code/title(name)/badge(type)/value/expiry_date(valid_to)/image_url(null)/is_platform`.
- `app/Http/Resources/Api/V1/GiftResource` — `id/code/title/overline(type)/points_cost/value/expiry_date/image_url(null)/is_platform`.
- `app/Http/Controllers/Api/V1/Resident/OfferController@index` — `GET /resident/offers` (voucher `points_cost=0/null`).
- `LoyaltyController@gifts` — `GET /resident/loyalty/gifts` (voucher `points_cost>0`).
- `routes/api.php` — thêm `offers`, `loyalty/gifts`.
- `database/seeders/ResidentDemoContentSeeder` (MỚI, idempotent) — 3 offers + 1 platform rollout (tenant 1) + loyalty account 3200đ (Bạc) + 4 hoạt động cho resident #1305.
- Docs: `docs/api/RESIDENT_API_REFERENCE.md` + `RESIDENT_API_OPERATIONS.md` (mới); cập nhật `docs/contracts/RESIDENT_API_DOMAIN.md`.

**Verify HTTP (Herd, token user #6):** `offers` → 4 (3 tenant + 1 platform `is_platform=true`); `loyalty/gifts` → 2; `loyalty` → points 3200 / hạng Bạc / còn 1800 lên Vàng; `loyalty/activities` → 4. Scope platform rollout hoạt động đúng (voucher Highlands chỉ hiện vì đã rollout tenant 1).

**Bẫy gặp:** cột `vouchers.value` NOT NULL → offer "quà" đặt `value='0.00'` thay vì null.

---

## 2026-07-23 — Resident P3: `GET /resident/apartment` + PHÁT HIỆN scope leak `project` cho cư dân

**Phạm vi:** vùng resident `/api/v1` (Hồ sơ cư dân — P3). Verify HTTP thật qua Herd `https://x2bms.test`.

**File mới/đổi:**
- `app/Http/Controllers/Api/V1/Resident/ApartmentController@show` — căn hộ đang chọn (X-Context-Id `apartment:{relationId}`, mặc định `is_primary` → quan hệ đầu) + thành viên hộ. Giải quyết qua `residentMemberships()`.
- `app/Http/Resources/Api/V1/ApartmentResource` — `id/code/label(project · building · code)/short_label(building · code)/area_sqm(string)/role/is_primary/building{}/project{}/members[]`. Cần eager-load `building.project` + `apartmentRelations.resident`. `avatar_url` từ accessor Resident, `is_me` khớp `resident.user_id`.
- `routes/api.php` — `GET resident/apartment` trong nhóm `auth:sanctum|ability:resident`.

**Verify HTTP (Herd):** route mới **401** khi chưa auth (như `resident/loyalty`) → route+class load OK, không lỗi PHP. Login resident seed `nguyenvananh@gmail.com`/`Resident@2026!` (user_id=6) OK → token.

**🚨→✅ BUG SCOPE `project` cho cư dân (ĐÃ FIX trong phiên này):** ban đầu user 6 → `/me/bootstrap` `member`/`available_contexts:[]`, apartment 404, billing 0, statements [], chỉ `notifications` audience `all` lọt. **Nguyên nhân (bug, không phải thiếu data):** `Resident`/`Apartment`/`Statement` dùng trait `BelongsToProject`. Cư dân (account_type=resident, KHÔNG role scope, `users.project_id=null`) → `accessibleProjectIds()=[]` → `currentProjectIds()=[]` → global scope `project` áp `whereIn(<t>.building_id, buildings WHERE project_id IN ())` = **0 dòng LUÔN**. `residentMemberships()` chỉ drop `tenant`, KHÔNG drop `project`.
- **FIX (`app/Models/Concerns/BelongsToProject.php@currentProjectIds`):** trả `null` (scope no-op) khi `! $user->isStaffOperator()` — cư dân/member thuần không mang mũ staff; project scope là khái niệm staff-workspace, an ninh dòng cư dân đến từ `apartmentIds` tường minh. Staff (kể cả dual-hat) giữ nguyên hành vi.
- **Verify HTTP (Herd) SAU fix — user 6:** bootstrap `verified_resident` + 2 contexts (primary `apartment:1306`→căn Đại Phúc, phụ `apartment:1305`→căn Sunshine) + unread 2 · `/resident/apartment` 200 shape đủ (label/short_label/members `is_me:true`) · `X-Context-Id: apartment:1305` → đổi sang căn Sunshine `A-0205` đúng · `billing/summary` = **13.200.000đ / hạn 20-07 / 1 chưa trả** (KHỚP ghi nhận P1 cũ → xác nhận trước đây verify qua console-scope-noop; giờ HTTP cũng đúng).
- **Tác động rộng:** fix này unblock TOÀN BỘ API cư dân (bootstrap contexts, apartment, billing, statements, notifications scope-căn). Đề nghị coordinator review kỹ vì chạm trait chung.

**`PATCH /me/profile` (P3, ĐÃ dựng + verify):** `ProfileController@update` (auth:sanctum) — sửa field AN TOÀN của `users` (name/phone/email(unique ignore self)/gender/dob/nationality), partial (`sometimes`), KHÔNG đụng KYC. Route `Route::patch('me/profile', ...)` trong nhóm auth. **Bootstrap enrichment:** `BootstrapController.me().user` thêm `email/phone/gender/nationality` (additive) để app prefill form edit. Verify Herd (user 6): PATCH echo user cập nhật (200), body rỗng → 422 `no_changes`, UTF-8 tên tiếng Việt OK; bootstrap.user có đủ field mới. Avatar upload multipart CHƯA làm. Audit API-level CHƯA có (chỉ Filament) — follow-up.

**Còn tồn resident P3:** avatar upload; submit KYC/đăng ký cư dân. CHƯA commit (nhánh `feat/resident-statements-enrich`, để coordinator review — có scope-trait chung + routes đan xen loyalty).

---

## 2026-07-23 — BQL-02 màn 07: Yêu cầu đổi thông tin (tái dùng data_fix_requests)

**Phạm vi:** `AccountChangeRequests` (`/admin/account-change-requests`) — before/after + duyệt/từ chối/**áp dụng** cho yêu cầu đổi thông tin cư dân. **KHÔNG bảng mới**: tái dùng `data_fix_requests` (entity='residents') + model `DataFixRequest` (mới) + cột `before_snapshot` (migration `000005`).

**Áp dụng an toàn:** whitelist field cư dân (full_name/phone/email/dob/id_no/contact_*); apply chụp `before_snapshot` giá trị cũ rồi ghi giá trị mới vào `residents`, set status=applied + applied_at + audit. Scope: target_id ∈ cư dân của tòa BQL.

**Drift DB:** bảng `data_fix_requests` vắng trên DB dev (migration gốc 000012 marked-run nhưng bảng không có) → migration 000005 **tạo bảng nếu thiếu** (khớp schema gốc + before_snapshot), idempotent.

**Verify:** `php -l` sạch; migrate DONE; luồng thật (tạo yêu cầu → áp dụng → phone cư dân đổi 0910000000→0912345678, snapshot lưu giá trị cũ, status=applied → hoàn tác sạch). **CÒN LẠI:** browser-click; nguồn tạo yêu cầu từ app (`PATCH /me/profile` → sinh request) do agent x2mobile nối sau.

**=> BQL-02 khép các màn 🆕** (05/06 kích hoạt, 07 đổi thông tin, 09 xung đột, 10 trung tâm rule); 01/02/03 đã có + cắm rule.

---

## 2026-07-23 — BQL-02 màn 09: Workbench xung đột (cách A — thuần tính toán)

**Phạm vi:** `ApprovalConflictWorkbench` (`/admin/approval-conflicts`) — phát hiện xung đột **live** từ dữ liệu, KHÔNG bảng trạng thái (owner chốt cách A). 2 loại: (1) trùng danh tính (tài khoản gắn căn cùng `duplicate_group_id`, nhóm >1); (2) căn tranh chấp (>1 `resident_unit_bindings` owner active/căn). "Ghi nhận xử lý" → AuditLog; xung đột tự mất khi sửa dữ liệu gốc.

**File mới:** `ApprovalConflictWorkbench.php` + view. KPI (tổng/trùng/tranh chấp); mỗi case: badge loại + các bên liên quan (link về chi tiết TK) + gợi ý hành động + nút ghi nhận.

**Verify:** `php -l`+`view:cache` sạch; query trên DB thật (tòa 1: 1 TK gắn căn, 0 trùng, 0 tranh chấp → empty state). Logic detect đúng, chạy không lỗi. **CÒN LẠI:** browser-click; nâng cấp cách B (bảng `approval_conflict_cases` theo dõi SLA/assignee) khi cần.

---

## 2026-07-23 — BQL-02 màn 10: Trung tâm rule/AI duyệt (gom 4 luồng Module 0)

**Phạm vi:** dashboard `ApprovalRuleCenter` (`/admin/approval-ai-copilot`) gom cảnh báo rule-based từ **4 luồng**: duyệt cư dân (ApprovalRiskRules), chất lượng dữ liệu (DataQualityRules), kích hoạt TK (AccountActivationRules), gắn căn (BindingRiskRules). Human-gate, KHÔNG LLM.

**File mới:**
- `app/Filament/Pages/ApprovalRuleCenter.php` — scan mỗi nguồn (giới hạn 100 bản ghi), cộng dồn tally theo mức (policy_block/high_risk/warning/info), trả top 8 mục/nguồn (nặng trước) + link về màn xử lý. Scope theo `CurrentContext::buildingIds`.
- `resources/views/filament/pages/approval-rule-center.blade.php` — 4 tile tổng theo mức + 4 card nguồn (mỗi card: đếm cần chú ý + link mở màn + top mục kèm chip cảnh báo → click về đúng bản ghi). Dark + responsive.

**Verify:** `php -l`+route+`view:cache` sạch; aggregation trên DB thật (tòa 1): duyệt 4 · data-quality 100 · kích hoạt 1 · gắn căn 3 → tally 107 warning + 1 info. **CÒN LẠI:** browser-click.

**=> Module 0 khép vòng:** 1 rule engine → 4 màn xử lý + 1 trung tâm tổng hợp. Còn lại BQL-02: màn 07 (đổi thông tin, cần bảng mới) · 09 (workbench xung đột).

---

## 2026-07-23 — BQL-02 cụm Gắn căn: cắm BindingRiskRules vào màn 03 (03+04 gộp)

**Phạm vi:** cụm "Gắn căn hộ". Màn 03 `ResidentBindingQueue` vốn đã đầy đủ (HasTable + duyệt/từ chối/bổ sung/phân công + modal chi tiết `binding-detail` + cảnh báo trùng). Nhu cầu "chi tiết" của màn 04 đã được modal đáp ứng → **KHÔNG dựng trang trùng lặp**, thay vào đó gom cảnh báo vào [[Module 0]] cho nhất quán.

**File mới:** `app/Support/Rules/BindingRiskRules.php` — `forRequest($r, $duplicateCount, $unitTaken)`: `identity_not_verified`(warning) · `duplicate_identity`(high_risk) · `unit_owner_taken`(high_risk, khi xin vai trò owner mà căn đã có chủ) · `no_evidence`(info).

**File sửa:**
- `ResidentBindingQueue`: + helper `unitTaken()` / `riskFor()`; **cột "Rủi ro"** (badge mức cao nhất + số cảnh báo, màu theo tone→Filament color); đưa rule report vào modal detail.
- `binding-detail.blade.php`: + panel "Đánh giá rủi ro" (tone + checklist) đầu modal.

**Verify:** `php -l`+`view:cache` sạch; chạy rule trên 10 yêu cầu thật (BIND-0001 verified/không trùng → green, 0 finding). **CÒN LẠI:** browser-click; nếu sau này cần trang chi tiết riêng (reassign nâng cao) tách từ modal.

**Nhất quán:** giờ 4 luồng dùng chung Module 0 — duyệt cư dân · data-quality · kích hoạt TK · gắn căn.

---

## 2026-07-23 — BQL-02 màn 06: Chi tiết tài khoản & quyền (trọn cụm Kích hoạt)

**Phạm vi:** dựng `AccountDetail` (`/admin/resident-accounts/{record}/detail`) nối từ màn 05 → **trọn 1 luồng test được**: danh sách → click tên → chi tiết.

**File mới:**
- `app/Filament/Pages/AccountDetail.php` — mount kiểm scope (account phải gắn căn trong tòa BQL, ngoài → 404). getViewData: profile (định danh/risk/last_login/trạng thái), `resident_unit_bindings` (căn + tòa + vai trò + trạng thái), thiết bị `MobileDevice` (platform/version/last_seen/revoked), rule `AccountActivationRules`. Actions invite/lock/unlock (audit, subject=GlobalUserAccount).
- `resources/views/filament/pages/account-detail.blade.php` — 3 cột: profile+actions / căn+thiết bị / panel rủi ro (tone+checklist). Back về màn 05.

**Sửa:** màn 05 — tên tài khoản link sang chi tiết. **Route:** đổi slug detail thành `{record}/detail` để KHÔNG đụng `resident-accounts/activations` (đã verify route:list tách bạch).

**Verify:** `php -l`+`view:cache` sạch; render trên account thật (Nguyễn Văn An, 2 căn Sunshine Garden Tòa A, rule "chưa có thiết bị"). **CÒN LẠI:** browser-click.

---

## 2026-07-23 — BQL-02 màn 05: Hàng đợi kích hoạt tài khoản (mới)

**Phạm vi:** dựng mới `AccountActivationQueue` (`/admin/resident-accounts/activations`) theo chuẩn listing. Activation dựa `global_user_accounts` + track thiết bị (`MobileDevice`) — đúng quyết định dự án. BQL-01 đã đóng (3 màn timeline/move/households vốn đã hoàn chỉnh — không sửa thừa).

**File mới:**
- `app/Support/Rules/AccountActivationRules.php` ([[Module 0]]) — `forAccount($account, $deviceCount)`: cảnh báo `identity_not_verified`(warning) · `duplicate_identity`(high_risk, theo `duplicate_group_id`) · `high_risk_score`(≥50) · `account_suspended`(info) · `no_device`(info).
- `app/Filament/Pages/AccountActivationQueue.php` — scope BQL qua `resident_unit_bindings.building_id ∈ CurrentContext::buildingIds()`; KPI (tổng/chưa xác thực/nghi trùng/đang khóa); filter search+status; phân trang; actions `invite`(mời/gửi lại — ghi metadata+audit, hạ tầng gửi SMS/Zalo nối sau), `lock`/`unlock`(account_status + audit). Device count = match user theo email → MobileDevice chưa revoke (heuristic v1).
- `resources/views/filament/pages/account-activation-queue.blade.php` — bảng tài khoản/định danh/thiết bị/cảnh báo(tone)/thao tác + dark + mobile scroll.

**Verify:** `php -l` + `view:cache` sạch; chạy scope+rule trên DB thật (binding→account building 1, rule sinh "chưa có thiết bị"). **CÒN LẠI:** browser-click actions; nối gửi lời mời thật (SMS/Zalo/email) khi hạ tầng OTP sẵn sàng.

---

## 2026-07-23 — BQL-01 màn 10: cắm DataQualityRules vào Chất lượng dữ liệu

**Phạm vi:** đóng nốt BQL-01 — `ResidentDataQuality` (`/admin/residents/data-quality`). Thay logic tag mỗi dòng (tính tay, thiếu `face_mismatch`) bằng [[Module 0]] `DataQualityRules::forResident()` — cùng nguồn với màn chi tiết/duyệt.

**File đổi:**
- `app/Filament/Pages/ResidentDataQuality.php`: mỗi dòng issue build từ `DataQualityRules` (tag = `{label, tone}` theo mức RiskLevel) + giữ phát hiện trùng SĐT/email (cross-record, rule không có) → tone đỏ. Sắp **nặng trước** (`sortByDesc('severity')`, dòng có trùng nâng lên high_risk).
- `resources/views/.../resident-data-quality.blade.php`: tag tô theo `tone` (green/amber/red/slate) thay bảng màu hard-code; hỗ trợ dark.

**Verify:** `php -l` + `view:cache` sạch; chạy DataQualityRules trên 200 cư dân thật → tag + tone + sort đúng (KYC chưa xác thực = amber…). KPI/breakdown giữ nguyên.

**Coordination:** agent x2mobile đã thêm `LoyaltyController` (`GET resident/loyalty` + `/activities`) dùng nền `loyalty_tiers` vừa dựng — nền phát huy tác dụng.

---

## 2026-07-23 — Nền dùng chung Resident API (dọn đường cho agent x2mobile)

**Phạm vi:** dựng phần nền theo `docs/contracts/RESIDENT_API_DOMAIN.md §4` để agent x2mobile build endpoint không phải chờ. KHÔNG build endpoint (agent kia làm).

**Đã dựng + verify (migrate trên DB local, seed OK):**
- `ResidentContextService`: + `projectIds()` (buildings.project_id) + `tenantIds()` (apartments.tenant_id).
- Migration `2026_07_23_000001` voucher platform: `vouchers`+`owner_level`(platform|tenant) + `tenant_id` nullable; pivot `voucher_tenant`(starts_at/ends_at/status) — rollout SA có kỳ hạn.
- Migration `000002` loyalty: bảng `loyalty_tiers`+`loyalty_tier_benefits` + seed silver/gold/platinum(0/5000/20000). Model `LoyaltyTier`/`LoyaltyTierBenefit`.
- Migration `000003` community_posts: +`is_pinned/is_important/image_paths`.
- AQI: `config/services.php` `aqi` + `.env.example` (`AQI_PROVIDER/BASE_URL/API_KEY/CACHE_TTL`) — Open-Meteo free mặc định, ENV-ready cho prod.

**Coordination catch:** contract từng đề xuất tạo `sos_alerts` mới — nhưng **bảng đã tồn tại** từ `2026_07_01_000010` (model `SosAlert`, cột `source/status=triggered/location/triggered_at`). Đã **xóa migration trùng** + sửa contract trỏ vào bảng có sẵn (KHÔNG dựng lại). (Cũng đã gộp `billing/summary/trend` mà agent x2mobile thêm ở commit `3e1de1f` vào contract.)

**CÒN LẠI (agent x2mobile):** AqiService (HTTP+cache) + toàn bộ endpoint P2/P3 theo contract.

---

## 2026-07-23 — Mobile API P1: billing/summary + notifications + unread (5 tab App Cư dân)

**Phạm vi:** trả API theo yêu cầu x2mobile `docs/API_REQUIREMENTS_RESIDENT_TABS_20260723.md` — mức P1 (cần cho ghép dần sớm). Đều dưới `/api/v1/resident` (ability `resident`), envelope chuẩn, cursor.

**File mới:**
- `Http/Controllers/Api/V1/Resident/BillingSummaryController` — `GET billing/summary`: công nợ = Σ max(total-paid,0) các statement status != paid, due_date gần nhất, count. Tiền chuỗi decimal (bcmath). Không có căn → summary 0.
- `Http/Controllers/Api/V1/Resident/NotificationController` — `GET notifications` (cursor, pin trước) + `POST notifications/{id}/read`. is_read lấy 1-query theo trang.
- `Services/Resident/ResidentNotificationService` — visibleQuery (published + chưa hết hạn + audience all/building/apartment của user), unreadCount, markRead (idempotent, 404 nếu ngoài phạm vi). Dùng chung cho bootstrap.
- `Http/Resources/Api/V1/NotificationResource` — {id, kind(type), title, summary, priority, is_pinned, is_read, created_at}.

**File sửa:**
- `ResidentContextService`: + `buildingIds()` (từ apartments của user).
- `BootstrapController::me`: `unread_notification_count` thật (0 nếu không phải cư dân).
- `routes/api.php`: +3 route resident.
- **Fix bug parity:** 2 migration `2026_07_20_00000{1,2}` dùng `ALTER … MODIFY … ENUM` (MySQL-only) làm **vỡ mọi Feature test trên sqlite** → guard `if driver === 'mysql'`.

**Verify (HTTP thật trên DB local seed, user_id=6):** billing/summary → công nợ "13200000", due 2026-07-20, count 1 ✅; notifications → 2 item (system 'all' pin đầu + maintenance theo tòa), is_read false ✅; markRead(1) → true, unread 2→1; id lạ → false(404); bootstrap unread khớp ✅. (Bỏ Feature test sqlite: DB dev sync tay lệch schema migration — thiếu cột `currency`/`deleted_at`; theo tiền lệ "Verify HTTP thật".)

**CÒN LẠI (P2/P3 theo doc):** `/resident/home`, `/loyalty` + `/offers`, `/community/*`, `/market/*`; rồi action tiles + `/me/profile`.

---

## 2026-07-23 — BQL-02 màn 01: cắm Rule Engine vào Hàng đợi duyệt cư dân

**Phạm vi:** nâng cấp `ResidentApprovalQueue` (`/admin/resident-approvals`) — chip rủi ro mỗi dòng + gate duyệt nhanh. Tái dùng [[Module 0]] `ApprovalRiskRules`.

**File đổi:**
- `app/Filament/Pages/ResidentApprovalQueue.php`: `getViewData` build map `riskById` (tone/count/blocked/top per request). `approve()` gate: nếu `isBlocked` → chặn duyệt nhanh + notify "mở Chi tiết để override" (override kèm lý do chỉ làm ở màn 02, queue không có ô lý do).
- `resources/views/filament/pages/resident-approval-queue.blade.php`: badge rủi ro (mức cao nhất + số cảnh báo, theo tone); viền đỏ dòng bị block; nút "Duyệt" → thay bằng link đỏ "Cần override" (mở Chi tiết) khi block.

**Verify:** `php -l` sạch; `view:cache` OK. Gate server-side ở cả `approve()` (không chỉ ẩn nút). **CÒN LẠI:** browser-click.

**Quyết định activation (cho màn 05 sau):** owner chốt — dựa `GlobalUserAccount` + track thiết bị (`MobileDevice`). Lưu memory `x2bms-account-activation-decision`.

---

## 2026-07-22 — BQL-02 màn 02: cắm Rule Engine vào Chi tiết duyệt cư dân

**Phạm vi:** nâng cấp màn đã có `AccountApprovalDetail` (`/admin/residents/approvals/{record}`) — thêm **panel Đánh giá rủi ro** + **gate policy_block** ở nút Phê duyệt. Tái dùng [[Module 0]] `ApprovalRiskRules`, KHÔNG dựng lại màn (đúng nguyên tắc BQL master plan). Chọn màn này trước màn 05 (Hàng đợi kích hoạt TK 🆕) vì màn 05 cần thiết kế schema activation mới — hoãn tới khi owner xác nhận.

**File đổi:**
- `app/Filament/Pages/AccountApprovalDetail.php`: `getViewData` build `ApprovalRiskRules::forRequest()` → `risk/riskTone/isBlocked/canOverride`; helper `canOverridePolicyBlock()` = `isPlatformAdmin() || isTenantOperator()` (HQ/SA). `decide('approve')` gate: nếu `isBlocked` và không quyền → chặn + notify; nếu có quyền → bắt buộc note + audit `account.approve.override` (mô tả `[OVERRIDE policy_block]`).
- `resources/views/filament/pages/account-approval-detail.blade.php`: panel risk (badge tổng theo tone, list finding theo mức + checklist); nút Phê duyệt: disable (khóa) nếu blocked & không quyền, hoặc chuyển "Duyệt (override)" màu đỏ + confirm riêng nếu có quyền.

**Verify:** `php -l` sạch; `view:cache` compile toàn bộ blade OK; `RiskEngineTest` 7/7 (28 assertion) vẫn pass. Gate cốt lõi (đk block) do rule unit test phủ. **CÒN LẠI:** browser-click override + Livewire::test cho page (chưa dựng khuôn page-test trong repo).

---

## 2026-07-22 — Module 0: Rule Engine (rủi ro/data-quality thuần, tái dùng web+AI)

**Phạm vi:** lớp nghiệp vụ thuần PHP (không phụ thuộc Filament/UI) đánh giá rủi ro để chặn/nhắc ở nút quyết định và đổ context cho FAB AI. Nền cho cụm cư dân (data-quality 10) + duyệt hồ sơ.

**File mới (`app/Support/Rules/`):**
- `RiskLevel` — thang mức `INFO < WARNING < HIGH_RISK < POLICY_BLOCK` + `severity()/tone()/label()` (tone badge: green/amber/red).
- `RiskFinding` — value object 1 phát hiện (level/code/message/checklist) + factory `info()/warning()/highRisk()/block()`.
- `RiskReport` — tập findings: `isBlocked()` (có `policy_block` → chặn nút, chỉ HQ/SA override + audit), `highestLevel()/tone()/countFrom()`, `toArray()`, `toAiContext()` (đổ vào `ProvidesAiContext::shareAiContext`).
- `DataQualityRules` — luật chất lượng dữ liệu (thiếu CCCD/SĐT…).
- `ApprovalRiskRules::forRequest()` — chấm rủi ro `ResidentApprovalRequest` (match_score, document_count, apartment_id, trùng phone/email khi có).

**Test:** `tests/Unit/RiskEngineTest.php` — **7 test / 28 assertion PASS**. Phủ: thứ tự severity/tone/label; factory set level; report highest/blocked/countFrom; report rỗng → green; shape `toAiContext`; nhánh thuần ApprovalRules (phone/email null → bỏ truy vấn trùng, không cần DB); high-score không sinh cảnh báo.

**Verify:** `php -l` sạch 5 file; `php artisan test --filter=RiskEngineTest` → 7/7 pass (148ms).

> Ghi chú: phiên 22/07 bị **mất điện lúc ~21:35**, file kịp lưu 21:20; kiểm tra lại sau sự cố — không mất dữ liệu, dọn 1 file rác `forceDelete()` (output tinker redirect nhầm). Commit này là bước đóng module sau khi khôi phục.

---

## 2026-07-21 — [Con trỏ] Mobile Slice 0 + đăng nhập nhanh (biometric) hoàn tất ở x2mobile

Phần Flutter của Slice 0 + đăng nhập nhanh làm ở repo **x2mobile** (`ccn112/x2_mobile`): RemoteAuthRepository, fix baseUrl, onRefresh, register A (màn premium), biometric (port từ x1mobile, không lưu mật khẩu). Nhật ký chi tiết: `x2mobile/DEV_JOURNAL.md`; handoff: `x2mobile/SESSION_HANDOFF_20260721.md`. Contract chung: `docs/guide/mobile-api-usage.md §10/§11` (+ `handoff/x2bms/_contracts/`). Backend liên quan (`/auth/register`, `/auth/refresh`, otp) đã journaled bên dưới.

---

## 2026-07-21 — Slice 0 (mobile auth) verify E2E thật + chốt contract

**E2E thật (flutter test → backend live 8123, tài khoản cư dân `0900000555`):** login→tokens ✅ · sai mật khẩu→Err ✅ · otp/request→challenge ✅ · me/bootstrap(Bearer)→200 ✅. **Chạy code mobile thật** (`RemoteAuthRepository` + `ApiClient` x2mobile), không mock.

**Bug đã bắt & fix (x2mobile):** Dio nối `baseUrl` thiếu `/` cuối + path `auth/login` → **rớt `/v1`** → 404 mọi API. Fix trung tâm `ApiClient` (tự thêm `/` cuối baseUrl). Chỉ lộ khi chạy remote thật lần đầu.

**Contract Slice 0 (auth):** đóng băng ở `docs/guide/mobile-api-usage.md §10` (mẫu login đã verify, map OTP channel/destination/purpose, cảnh báo baseUrl, trạng thái wiring: RemoteAuthRepository ✅ / onRefresh ⬜ / register ⬜, tài khoản test). **Copy sang handoff dùng chung:** `handoff/x2bms/_contracts/mobile-api-usage.md`.

**x2mobile (repo riêng) — đổi:** `RemoteAuthRepository` (nối thật), `ApiClient` (baseUrl fix), `test/live_auth_test.dart` (E2E). Chưa commit — chờ chốt điều phối agent.

---

## 2026-07-21 — Track A: đóng cụm cư dân Đợt B (4 màn) + vá hiệu năng Hộ gia đình

**Đánh giá thực tế (render Livewire):** cả 4 màn ĐÃ hoạt động (không phải stub rỗng) — có getViewData + blade + KPI + filter:
- `ResidentTimeline` (Dòng thời gian) ✓ · `MoveInOutHistory` (Chuyển đến/đi) ✓ (hiển thị cap 120) · `ResidentDataQuality` (Chất lượng DL) ✓ (dùng aggregate COUNT — chuẩn).
- `HouseholdRelationships` (Hộ gia đình) ✗ **nạp toàn bộ ~1300 quan hệ, không phân trang → HTML 3.66MB**.

**Vá HouseholdRelationships:** `WithPagination` — phân trang **theo căn hộ** (24/trang, scope tòa, filter role/search qua whereHas), chỉ nạp quan hệ của căn ở trang hiện tại; KPI chuyển sang **aggregate COUNT** (distinct apartment_id / is_primary) thay vì nạp hết. Blade: hiện tổng số hộ + `->links()`. Reset page khi đổi search/role. **Kết quả: 3.66MB → 98KB (×37).**

**Nav:** 4 màn nest dưới cha "Cư dân" (navigationParentItem).

**Verify:** `php -l` sạch; render lại 4 màn OK, kích thước bounded (Household 98KB, Timeline 120KB, Move 178KB, DataQuality 78KB).

**Còn lại (tính năng nâng cao, tùy chọn — không chặn):** MoveInOutHistory state machine (confirm/cancel/correct); ResidentDataQuality action fix/merge/gửi yêu cầu cập nhật per BQL plan. Hiện cả hai là màn tra cứu/dashboard read-only chạy tốt.

---

## 2026-07-21 — Fix nav: `->icon()` trên NavigationGroup LÀM PHẲNG sub-nav (bỏ icon → nesting trở lại)

**Triệu chứng:** "Cây căn hộ"/"Duyệt gắn căn hộ" hiện phẳng thay vì thụt dưới cha "Hồ sơ căn hộ". **Nguyên nhân:** thêm `->icon()` cho `NavigationGroup` khiến Filament render nhóm ở layout khác, **mất `.fi-sidebar-sub-group`** (kiểm chứng qua DOM: subGroupCount 0→1 sau khi bỏ icon). **Fix:** bỏ icon group ở AdminPanelProvider (trả về `NavigationGroup::make('...')` trần) → nesting hiện lại đúng "như cũ". Hq cũng trả về nguyên bản (chỉ giữ icon gốc X2 AI Engine). SA giữ icon gốc (không dùng navigationParentItem nên không ảnh hưởng). Verify browser thật: "Cây căn hộ" thụt lề dưới "Hồ sơ căn hộ". (Lưu ý: "Duyệt gắn căn hộ" ẩn với tài khoản nv1 do phân quyền — hiện đủ với tài khoản có quyền/platform admin, không phải lỗi nav.)

**Bẫy ghi nhớ:** KHÔNG đặt `->icon()` cho NavigationGroup nếu nhóm đó chứa mục có `navigationParentItem` (sẽ phá nesting).

---

## 2026-07-21 — Nav: bỏ collapsed (giữ sub-nav nested như cũ) + icon nhóm + nhóm SA "Lưu trữ & Sao lưu"

**Đính chính (owner):** "sub-nav" = **menu lồng** đã có sẵn qua `navigationParentItem` (vd cha **"Hồ sơ căn hộ"** → con **Cây căn hộ**, **Duyệt gắn căn hộ**; cha "Cư dân" → ResidentDetail). Lần trước tôi đặt `collapsed()` → che mất mục con. Đã **bỏ `collapsed()`** cả 3 panel để mục con hiện lại "như cũ"; **giữ nguyên 1 nhóm "Cư dân & Căn hộ"** (không tách). Thêm icon cho group header (mỹ thuật). Giữ nhóm SA mới **"Lưu trữ & Sao lưu"** (2 màn tenant). Verify: dump nav /admin thấy đúng cây cha→con, `php -l` sạch.

---

## 2026-07-21 — Fix 500 SA backup/lifecycle: closure column phải nhận `$state`

`/sa/tenant-backups` báo `BindingResolutionException: [$s] unresolvable` — column closure Filament resolve theo TÊN tham số. Đổi `formatStateUsing/color/state` từ `$s`/`$b` → **`$state`**, record closure → **`$record`** (cả TenantBackupManager + TenantLifecycleManager). Verify: render thật 2 page (có dữ liệu) dưới platform admin → OK. (Bẫy đã ghi ở BQL_MASTER_BUILD_PLAN §"bẫy đã trả giá".)

---

## 2026-07-21 — SA UI vòng đời tenant + registry backup + chặn login dormant + sweep + schema-drift (Increment 10)

**Vòng đời tenant (state machine):** migration `tenants` += `lifecycle_status` (active|dormant_archived|purged) + `dormant_at` + `retention_until`. Offboard → dormant_archived + retention (config `retention_days`, mặc định ~3 năm). Restore → active.

**Chặn đăng nhập:** `User::canAccessPanel` — platform admin luôn vào; nhân sự tenant `dormant_archived`/`purged` bị chặn.

**Registry backup:** bảng `tenant_backups` + model + `TenantBackupService` ghi sổ mỗi lần tạo (path/size/counts/file_count/app_version/trigger/created_by). `latestBundle` ưu tiên registry.

**SA UI (2 page, panel /sa):**
- `TenantLifecycleManager` (`/sa/tenant-lifecycle`): bảng tenant + trạng thái, actions **Sao lưu / Off & lưu trữ / Khôi phục** (có confirm + cảnh báo).
- `TenantBackupManager` (`/sa/tenant-backups`): bảng bản backup + **Tải về / Xóa** (retention).

**Sweep tự động** (`tenant:lifecycle-sweep`, mặc định dry-run, `--commit` để chạy): active + thuê bao hết hạn quá `grace_days` → OFF; dormant quá `retention_until` → PURGE (xóa bundle + đánh dấu purged). Tiering nóng→lạnh = **lifecycle policy của bucket S3** (cấu hình hạ tầng khi mua, ghi chú trong command) — local không có tier.

**Schema-drift khi rehydrate (bundle cũ):** `TenantRestoreService` chỉ chèn cột CÒN tồn tại ở schema hiện tại (`array_intersect_key` với `getColumnListing`); cột mới nhận default. Manifest có `app_version` làm mốc.

**Verify (E2E tenant tạm, xóa sạch sau):** setup active → backup (registry=1) → ACCESS active(admin=true) → OFF (dormant, retention 2029, purged data, bundle còn) → ACCESS dormant(non-admin=false, admin=true) → RESTORE (active, data+file đúng nội dung) → registry download/delete OK → sweep dry-run OK. migrate DONE, `php -l` sạch 8 file.

---

## 2026-07-21 — Rehydrate/restore + offboard (churn off↔restore) (Increment 9)

**Đối xứng backup:** hoàn thiện vòng đời dữ liệu tenant.
- `app/Support/Backup/TenantRestoreService.php`: rehydrate từ bundle .zip — nạp DB (NDJSON, **giữ nguyên id**; xoá dòng tenant hiện có reverse-order rồi chèn forward-order, bọc transaction + `SET FOREIGN_KEY_CHECKS=0/1`) + đẩy file `files/<key>` về vùng tenant. `latestBundle()` chọn bundle mới nhất. Kiểm manifest khớp tenant.
- `app/Support/Backup/TenantOffboardService.php`: **off** (churn) = backup trước → purge DB rows (reverse, FK off) + purge files **NHƯNG giữ `_backups/`** (đúng mô hình "gói storage": off vẫn giữ hộ bundle, resume thì rehydrate).
- Command: `php artisan tenant:offboard {tenant}` (có xác nhận/`--force`), `php artisan tenant:restore {tenant} [--bundle=]`.

**Verify (E2E trên TENANT TẠM cô lập, không đụng tenant demo, xóa sạch sau test):**
TRƯỚC residents=2/buildings=1/projects=1/file=có → OFF: purge 4 rows + 1 file, mọi thứ=0, file mất, **bundle còn** → RESTORE: khôi phục 4 rows + 1 file, counts về đúng, **file đúng nội dung** → ✅ PASS. `php -l` sạch.

**Còn lại:** UI trigger backup/restore + màn quản lý bản backup (retention/tải/xóa); subscription state machine + tiering nóng→lạnh tự động; schema-migration khi rehydrate bundle cũ hơn version hiện tại (manifest đã có `app_version`).

---

## 2026-07-21 — Backup bundle theo tenant + route KYC/kb qua TenantStorage (Increment 8)

**(item 3) Route KYC + kb-attachments qua TenantStorage (per-tenant folder, tương thích ngược):**
- KYC (`ResidentForm`): 3 FileUpload id_front/id_back/portrait → disk theo ENV + `tenants/{t}/residents/kyc` (giữ visibility private). `PrivateMediaController` đọc qua `TenantStorage::disk()` (S3-ready; disk local nên file cũ vẫn serve).
- kb (`AiKnowledgeBase`): FileUpload attachments → tenant disk + `tenants/{t}/kb-attachments` (rời disk 'public' → riêng tư hơn). `DocumentTextExtractor::fromStoredFile` đọc qua `TenantStorage::localReadablePath` + **fallback disk 'public'** cho file cũ.

**(item 2) Backup/export bundle theo tenant:**
- `config/tenant-backup.php`: danh sách bảng tenant-scoped (tự bỏ bảng không có `tenant_id`) + chunk.
- `app/Support/Backup/TenantBackupService.php`: dump DB **NDJSON** (lọc `tenant_id`, cursor chunk) + gom toàn bộ file vùng tenant → **.zip** (`manifest.json` có `app_version` để rehydrate bản cũ · `db/*.ndjson` · `files/<key>`), lưu tại `tenants/{t}/_backups/{ts}/backup.zip` (chính trong vùng tenant → hợp mô hình "gói storage"). Backup LOGIC, độc lập DB engine.
- `php artisan tenant:backup {tenant}` (dùng tay/lên lịch).

**Verify:** `php -l` sạch; KYC/kb lint OK; `tenant:backup 1` → zip 95KB, 13 entry (manifest + 12 NDJSON, counts đúng theo tenant), section files/ hoạt động; dọn sạch.

**Còn lại (đợt sau):** UI trigger backup + màn quản lý bản backup (tải/xóa/retention); **rehydrate/restore** từ bundle; subscription state machine + tiering nóng→lạnh (kịch bản churn); route nốt các điểm upload khác (marketplace/platform content) nếu cần theo tenant.

---

## 2026-07-20 — Nền lưu trữ đa tenant (TenantStorage) — folder riêng từng tenant, ENV-ready S3 (Increment 7)

**Bối cảnh (owner):** SaaS, dữ liệu upload lưu **folder riêng từng tenant/dự án** để sau backup được; tạm local, **cấu hình S3/SA đặt ở ENV** (mua sau chỉ điền ENV, không sửa code); giữ **shared DB + tenant_id** (silo tương lai).

**File mới:**
- `config/tenant-storage.php`: `disk` = `env('TENANT_STORAGE_DISK','local')`, `root_prefix` = `env('TENANT_STORAGE_ROOT','tenants')`.
- `app/Support/Storage/TenantStorage.php`: cổng I/O DUY NHẤT cho file tenant. Prefix lấy từ `CurrentContext` → `tenants/{tenant_id}/projects/{building_id}/<relative>` (chống rò chéo). Driver-agnostic: `prefix()/key()/move()/exists()/download()` + `localReadablePath()` (local→path thật; remote→tải file tạm, để lib đọc Excel theo path vẫn chạy khi lên S3).
- `.env.example`: thêm `TENANT_STORAGE_DISK`/`TENANT_STORAGE_ROOT` + `AWS_URL`/`AWS_ENDPOINT` (MinIO), có chú thích "mua thì điền".

**Đổi:** import cư dân — FileUpload nay đẩy vào `tenants/{t}/_incoming/residents` (disk theo ENV); sau stage **move** file nguồn về `tenants/{t}/projects/{b}/residents/import/{batch}/` + cập nhật `storage_path`. `ImportHistory` tải file nguồn qua `TenantStorage` (mọi driver).

**Verify (DB thật, dọn sạch):** disk=local/root=tenants; upload→incoming→stage đọc OK→move ra `tenants/1/projects/1/residents/import/{id}/…` (file thật trên đĩa), incoming đã xóa; commit tạo cư dân. `php -l` sạch.

**Chưa làm (đã bàn, owner OK — đợt sau):** backup/export bundle theo tenant; subscription state machine + tiering nóng→lạnh + rehydrate (kịch bản 1 năm on → 2 năm off gói storage → year 3 on); route nốt KYC/kb-attachments qua TenantStorage.

---

## 2026-07-20 — Import async (queue) + màn Nhật ký Import/Export + retry (Increment 6 / Đợt 2)

**Async:** ghi (commit) chuyển sang **hàng đợi nền**:
- `app/Jobs/CommitImportBatchJob.php` (ShouldQueue): set `committing` → `StagingImporter::commit()` (tự set committed/failed). **Idempotent** (chỉ xử lý dòng valid|warning, dòng 'imported' bỏ qua) → **retry an toàn, không trùng**.
- `app/Support/Import/ImportProfileRegistry.php`: map `import_type` → ImportProfile (để Job dựng lại profile).
- Migration `2026_07_20_000002`: `import_batches.status` += `committing`.
- Popup preview "Ghi các dòng hợp lệ" nay **dispatch Job** + set `committing` + báo "đưa vào hàng đợi, theo dõi ở Nhật ký". (Cần chạy `php artisan queue:work` — QUEUE_CONNECTION=database.)

**Màn Nhật ký Import/Export** — `app/Filament/Pages/ImportHistory.php` (+ blade), nav "Cư dân & Căn hộ", slug `/admin/import-history`:
- Bảng `import_batches` scope theo tòa: thời gian, file nguồn, loại, **badge trạng thái**, tổng/hợp lệ/lỗi, người tạo, ghi lúc.
- Row actions: **Chi tiết** (bảng từng dòng + lỗi), **Tải file nguồn** (kiểm tra file người dùng upload), **Nhập lại (retry)** dòng còn lại (re-dispatch Job), **Export kết quả** (CSV cư dân đã tạo bởi batch — đối chiếu dữ liệu đã lưu, dùng trait ExportsCsv).

**Verify (DB thật, dọn sạch):** stage valid=2 → `dispatchSync` Job → `committed` + committed_at + 2 cư dân (SĐT tự chuẩn hóa); chạy lại Job → vẫn 2 (idempotent). `ImportHistory` mount OK (Livewire::test assertOk). `php -l` sạch; migrate DONE.

**Ghi chú:** modal action (upload/preview/history) render qua **table/preview** → hoạt động trên trình duyệt thật; queue cần worker chạy nền.

---

## 2026-07-20 — Làm sạch dữ liệu nhập (normalizer mạnh) + cảnh báo chất lượng (Increment 5)

**`RowNormalizers` nâng cấp** (nền dùng chung mọi profile/tầng):
- `stripInvisible()`: quy đổi nbsp/zero-width/BOM/tab/newline → space.
- `string()`: gộp mọi khoảng trắng (kể cả ẩn) → 1 space (sửa "2 dấu cách giữa họ tên").
- `name()` (mới): whitespace + **Title Case unicode** ("nguyễn  văn AN"/"TRẦN THỊ BÌNH" → "Nguyễn Văn An"/"Trần Thị Bình").
- `email()`: bỏ **mọi** khoảng trắng kể cả ở giữa ("a b c@x.vn"→"abc@x.vn") + lowercase.
- `phone()`: bỏ ký tự thừa; `+84`/`84`/`0084`→`0`; **mất số 0 đầu do Excel** (9 số bắt đầu 3/5/7/8/9) → thêm `0` ("090 1234 567"/"901234567"/"+84 901 234 567" → "0901234567").
- `idNo()` (mới): chỉ giữ chữ số; **CCCD mất số 0 đầu** (11 số) → pad về 12.

**`ResidentImportProfile`:** full_name→`name`, id_no→`idNo`; **bỏ rule email cứng** (không chặn dòng); thêm **cảnh báo chất lượng** (rule-based) sau chuẩn hóa: CCCD ≠ 9/12 số, SĐT ≠ dạng `0#########`, email sai định dạng (vẫn lưu, gợi ý bổ sung).

**Verify:** `php -l` sạch; test 12 case dữ liệu bẩn (nbsp, mất số 0, dấu cách giữa email/tên/CCCD, +84) → ra đúng kỳ vọng.

---

## 2026-07-20 — Import gộp căn+cư dân+quan hệ · nới lỏng required + gợi ý AI · mẫu trong popup (Increment 4)

**Quyết định owner (2026-07-20):** (1) chỉ **Họ tên** bắt buộc cứng; thiếu CCCD/SĐT/Email vẫn nhập, tự đặt `profile_status='cho_bo_sung'` + cảnh báo + gợi ý AI (rule-based). (2) **Gộp 1 file** tạo căn hộ + cư dân + quan hệ.

**`ResidentImportProfile` (rework):**
- Cột: bỏ `required` ở id_no/phone (chỉ full_name required); **thêm `Mã căn hộ` + `Vai trò`** (normalizeRole: Chủ sở hữu/Người thuê/Thành viên → owner/tenant/member).
- `validateRow`: gợi ý AI theo loại thiếu (CCCD → chờ bổ sung; thiếu SĐT+Email → chưa kích hoạt; trùng CCCD trong tòa → gộp; căn chưa có → sẽ tạo mới; không mã căn → chưa gắn). Tất cả **warning** (không chặn), chỉ thiếu Họ tên = error.
- `commitRow`: `profile_status` = `cho_bo_sung` nếu thiếu định danh, ngược lại `hoat_dong`; **resolve-or-create Apartment theo mã trong tòa** + tạo `ResidentApartmentRelation` (role/is_primary/start_date); tự liên kết tài khoản; audit ghi cả căn + vai trò.

**UI (item 1):** link **"Tải file mẫu (.xlsx)"** chuyển VÀO footer popup import (`extraModalFooterActions`), **gỡ nút header** ngoài. Mô tả modal cập nhật (required nới lỏng + cột gộp). File mẫu nay 13 cột (thêm Mã căn hộ, Vai trò).

**Verify (E2E DB thật, có dọn sạch):** CSV 3 dòng → stage total=3 valid=2 error=1. row đủ→`hoat_dong`+tạo căn+quan hệ owner; row chỉ-tên+căn→`cho_bo_sung`+tạo căn+quan hệ tenant + đủ gợi ý AI; row thiếu tên→error (bỏ qua, KHÔNG tạo căn). commit created=2. `php -l` sạch.

**Còn lại (đợt sau — item 3+4 owner yêu cầu):** async queue (đưa `commit` vào Job) + màn **Nhật ký Import/Export** (status/counts/retry dòng lỗi/xem-tải file nguồn) + export dữ liệu đối chiếu.

---

## 2026-07-20 — UI wizard nhập cư dân (Increment 3)

**Phạm vi:** nối engine staging vào giao diện BQL — nút "Nhập dữ liệu" ở `ResidentDirectory` (trước là stub) nay chạy thật qua modal 2 bước.

**File mới:** `app/Filament/Concerns/ImportsResidentsFromExcel.php` (trait):
- `residentImportAction()` (bước 1): modal chọn **Tòa/dự án** (Select scope theo `CurrentContext::buildings`) + **FileUpload** .xlsx/.csv (disk `local`, thư mục `imports/residents`) → `StagingImporter::stage()` → `replaceMountedAction('residentImportPreview')`.
- `residentImportPreviewAction()` (bước 2, auto-discover): modal 4xl hiện **đếm tổng/hợp lệ/lỗi** + **bảng từng dòng** (Họ tên/CCCD/SĐT/trạng thái/ghi chú lỗi, tối đa 200 dòng) → nút "Ghi các dòng hợp lệ" gọi `StagingImporter::commit()` + audit `resident.import` + notification + `refreshTable()`. Chặn khi `valid_rows=0`.
- Context ghi theo `tenant_id` (user) + `building_id` (chọn ở form) → scope đúng, không rò cross-tenant.

**Sửa:** `ResidentDirectory` — `use ImportsResidentsFromExcel`, thay action stub bằng `$this->residentImportAction()`.

**Verify:** `php -l` sạch 2 file; `php artisan view:cache` compile OK (blade/HtmlString hợp lệ); boot app dựng được cả 2 action (`residentImport`/`residentImportPreview`), header actions = `residentImport, export, create` (stub đã thay). **Còn lại — verify browser thật:** click upload→preview→commit trên `/admin/residents` với phiên BQL (chưa chạy trong phiên này do cần đăng nhập panel). Pipeline lõi đã verify E2E ở Increment 2.

---

## 2026-07-20 — Engine staging import + profile cư dân (Increment 2)

**Phạm vi:** engine import staging DÙNG CHUNG 3 tầng trên bảng `import_batches`/`import_batch_rows` sẵn có + profile import cư dân BQL. Verify end-to-end thật.

**Schema (delta ADD-ONLY):** `database/migrations/2026_07_20_000001_extend_import_batches_for_residents.php` — `import_batches.import_type` += `residents`, `import_batch_rows.row_type` += `resident`, thêm `import_batches.building_id` nullable FK. An toàn trên bảng đã seed. `migrate` DONE.

**File mới:**
- `app/Support/Import/ImportColumnSpec.php` — VO mô tả cột (key/label/aliases/required/normalizer/rules/example) + `extract($row)`; tương đương Filament `ImportColumn` nhưng độc lập UI/package.
- `app/Support/Import/ImportProfile.php` — interface nghiệp vụ (importType/rowType/columns/validateRow/commitRow).
- `app/Support/Import/StagingImporter.php` — engine: `stage()` (đọc file bằng spatie/simple-excel → tạo batch + rows raw+normalized, validate field bằng Validator + rule nghiệp vụ, đếm, status=validated) và `commit()` (ghi dòng valid|warning qua profile, set committed_entity, batch=committed/failed). Không tự biết tenant/building — nhận qua `$context`.
- `app/Support/Import/Profiles/ResidentImportProfile.php` — 11 cột thật của `residents`; `validateRow` cảnh báo trùng CCCD trong tòa + đã có tài khoản X2BMS; `commitRow` set scope tenant/building + code unique + `source='import'`/`profile_status='cho_bo_sung'` (giá trị đã ghi trong migration, không tự chế) + tự liên kết `user_id` qua `ResidentIdentityMatcher` + ghi `AuditLog`.

**Verify (end-to-end, DB thật, có dọn sạch):** CSV 3 dòng → STAGE `total=3 valid=2 error=1` (dòng thiếu CCCD bị bắt đúng "id no required"); normalizer áp đúng (`0901-234 567`→`0901234567`, `15/01/1990`→`1990-01-15`); COMMIT `created=2`, batch→`committed`, 2 resident có mã unique; cleanup OK.

**Môi trường:** máy D: thiếu `vendor/laravel/octane` (composer install chưa chạy đủ) → artisan không boot. Đã `composer install --ignore-platform-reqs` (Windows thiếu ext pcntl/posix cho horizon/octane — chỉ dùng khi chạy queue/octane, không ảnh hưởng dev). Sau đó artisan/migrate/tinker chạy bình thường.

**Còn lại:** (a) UI wizard 6 bước cho BQL (upload→map→preview bảng validate→commit) nối nút "Nhập dữ liệu" đang stub ở `ResidentDirectory`; (b) file mẫu tải về (sinh từ `columns()` example); (c) i18n message validate (hiện English "id no required"). (d) áp profile cho HQ/SA khi cần.

---

## 2026-07-20 — Nền Import/Export dùng chung 3 tầng (Increment 1)

**Phạm vi:** dựng lớp nền import/export panel-agnostic (dùng chung SA/HQ/BQL), port pattern production từ x1web (Filament v5), độc lập gói Excel (x2bms dùng `spatie/simple-excel`, KHÔNG kéo `maatwebsite/excel`). Chưa đụng schema.

**File mới:**
- `app/Support/Import/RowNormalizers.php` — chuẩn hóa `string/email/phone/date` + `header()` (normalize whitespace) + `value($row,$expected,$aliases)` (guess-match cột như Filament `ImportColumn::guess`).
- `app/Support/Import/RowIssue.php` — DTO cảnh báo/lỗi theo dòng (`warning()/error()/toArray()`), khớp `import_batch_rows.validation_errors`.
- `app/Support/Import/ImportSummary.php` — bộ đếm processed/created/updated/skipped/warnings/errors + issues; `counters()` ánh xạ `import_batches`.
- `app/Support/Export/ExportsCsv.php` — trait `streamCsv(rows, headers, mapRow, filenameBase)`: CSV streaming + BOM UTF-8. Không tự audit/scope (caller giữ trách nhiệm scope theo context + audit → trait độc lập tầng, không giấu side-effect).

**Áp dụng đầu tiên (bằng chứng):** `ResidentDirectory::export()` (BQL `/admin`) refactor dùng `ExportsCsv` — bỏ `fputcsv` thủ công, giữ nguyên audit + filter scope building.

**Verify:** `php -l` sạch 5 file; autoload OK; chạy thật: `phone(' 0901-234 567 ')→0901234567`, `date('15/01/2024')→2024-01-15`, `email(' Test@X.VN ')→test@x.vn`, `value` match cột qua alias + header 2 space; trait áp đúng (`class_uses`), `RowIssue::isError`/`ImportSummary::validRows` đúng. (Không boot full app được ở local do config Octane production-only → test qua autoload thuần.)

**Còn lại (Increment 2):** import staging cư dân BQL cần mở rộng `import_batches` (`import_type` +`residents`, `row_type` +`resident`) + cân nhắc `building_id` nullable — là delta schema, làm ở bước sau. Tham chiếu: memory `x1web-reusable-filament-for-x2bms`.

---

## 2026-07-18 — Merge PR#3 vào main · Handoff dự án · Hướng dẫn deploy (server/domain/CI-CD)

**Git:** PR #3 (`feat/bql01-04-resident-detail-password-reset`) đã **merge vào `main`** (merge commit `f2876e4`), nhánh feature đã xóa. Toàn bộ phiên 07-18 nay ở main.

**Handoff dự án:** `docs/SESSION_HANDOFF_20260718.md` — nguồn chân lý khi chuyển máy: tổng quan, trạng thái git, setup máy mới, biến `.env`, kế hoạch domain `xbuilding.vn`, điểm khởi đầu Flutter (cần API Phase 0 trước), bản đồ tài liệu. Kèm gói audit `handoff/mobile_backend_audit_20260718/` (15 file).

**Hướng dẫn deploy:** `docs/DEPLOYMENT_GUIDE.md` (11 mục) — mô hình 1 app phục vụ 5 subdomain (sa/hq/bql/web/api.xbuilding.vn), cài server Ubuntu (PHP 8.4-fpm/Nginx/MySQL/Redis/Supervisor/Node/Certbot), DNS, các bước deploy, Nginx 1 server block, HTTPS, queue+scheduler, **CI/CD GitHub Actions** (push main → SSH deploy), checklist. **2 việc CODE cần làm trước khi chạy subdomain thật:** (1) `->domain()` trong `*PanelProvider` (đọc từ env, ảnh hưởng `APP_URL`→link reset MK); (2) chuyển ảnh CCCD/chân dung từ disk public sang **private** + signed URL (theo SECURITY audit).

**Lưu ý:** Filament thực tế là **v5** (composer `filament/filament 5.*`) — sửa ghi nhớ cũ "v4".

---

## 2026-07-18 — BQL-01-04 Chi tiết cư dân 360 · Reset mật khẩu đa kênh · Mail SMTP · chuẩn action UX

**Phạm vi:** hoàn thiện cụm cư dân màn 04 (chi tiết 360), luồng đặt lại mật khẩu (list+detail), gửi mail thật, và chốt chuẩn UX action.

**1. Màn Chi tiết cư dân 360 (BQL-01-04)** — dựng lại bản GIÀU theo format `ApartmentProfile`: title = tên cư dân ở topbar · breadcrumb + action ở header Filament · KPI strip 7 ô · 6 section-tab (Hồ sơ tổng quan · Căn hộ · Phương tiện & thẻ · Công nợ · Phản ánh · Nhật ký). Tab tổng quan: hồ sơ (avatar `avatar_url`) + thông tin cá nhân + căn hộ liên kết + snapshot phí/công nợ + thành viên hộ + gợi ý AI rule-based. File: `app/Filament/Pages/ResidentDetail.php` (thay stub cũ), `resources/views/filament/pages/resident-detail.blade.php`. Tái dùng partial `apartment-residents-table`/`apartment-assets`/`apartment-feedback`/`apartment-timeline`.

**2. Fix z-index popup + avatar list.** Bảng listing freeze cột → popup ActionGroup (Filament render TRONG ô sticky z-index:3, KHÔNG teleport dù có `.teleport`) bị ô sticky hàng dưới đè. Fix (CSS scoped `.x2-bql-page`): `tr:has(.fi-dropdown-panel[style*="display: block"]) td { z-index:25 }`. Thêm cột avatar (`ImageColumn avatar_url ->circular()`) + avatar trong mobile card. File: `theme.css`, `ResidentDirectory.php`, `resident-directory.blade.php`.

**3. Đặt lại mật khẩu cư dân (dùng nhiều)** — trait chung `app/Filament/Concerns/ResetsResidentPassword.php`, nút ở CẢ màn list (row action 🔑) + detail (header). Popup 4 phương thức: **mật khẩu tạm** (Str::password 10, cast hashed) · **OTP** (6 số, cache 10') · **gửi link** · **tạo link copy (Zalo)**. Sau khi tạo → mở modal kết quả (`replaceMountedAction('residentResetResult')`) có ô + nút Copy (Alpine clipboard). Yêu cầu cư dân đã có tài khoản liên kết. Token sinh qua **Password broker** chuẩn Laravel.
- **Trang tiêu thụ token (guest):** route `GET/POST /reset-password/{token}` (`password.reset`/`password.store`) + `ResidentPasswordResetController` (dùng `Password::reset`) + view tự chứa `resources/views/auth/reset-password.blade.php` (branded, không phụ thuộc Vite). File: `routes/web.php`. **Bẫy đã fix:** trước đó link 404 vì CHƯA có route — nay đã có, verify E2E: set mật khẩu mới → Hash::check PASS + token tự xóa sau dùng.

**4. Gửi email thật (SMTP).** `config/mail.php` thêm `'test_to' => env('MAIL_TEST_TO_ADDRESS')`: khi có → MỌI email nghiệp vụ route về địa chỉ test (tiện kiểm thử); production để trống. Trait có `deliverResidentMail()` (gửi qua `Mail::html`, try/catch) + template branded `otpEmailHtml`/`resetLinkEmailHtml`. `.env`: `MAIL_MAILER=log`→`smtp` (elasticemail smtp.elasticemail.com:2525). **Verify:** gửi thật tới `chtchinh@gmail.com` OK (không exception); log driver trước đó ghi đúng OTP vào `storage/logs/laravel.log`.

**5. Chuẩn ACTION UX (chốt owner).** Màn detail/list nhiều action → **tối đa ~3 nút chính** + gom còn lại vào `ActionGroup` "Thao tác khác" (icon ellipsis, `->button()`); hành động hủy diệt/nhạy cảm nằm trong dropdown. **Màu nút theo ý nghĩa:** gold=tạo mới · success=duyệt/mở khóa · danger=xóa/khóa · warning=bảo mật(reset MK/OTP) · gray=trung tính · primary=nhấn mạnh. Áp mẫu: reset MK=warning, mở khóa=success, khóa=danger. Ghi vào `docs/LISTING_PAGE_STANDARD.md §5b` + memory.
- **Breadcrumb:** mục click được (thẻ `<a>`) tô màu link `x2-primary`; mục hiện tại (`<span>`) giữ xám. CSS trong theme admin (chỉ /admin).

**6. Rà notification/log (cho feature sau).** Backend ĐÃ có `notifications` (`owner_level` = platform/tenant/project = **3 tầng SuperAdmin/Tenant/BQL**), `notification_channels`, `notification_delivery_logs` (notification_id·user_id·resident_id·channel·status·error·sent_at), `notification_audiences`. Handoff CHƯA có màn "nhật ký gửi đa kênh + retry theo 3 tầng" (chỉ có BQL-07 trung tâm gửi + HQ-05-08 nhắc nợ + các audit log). → Màn log + retry là MỚI (owner tự thiết kế). Xem `docs/COMMUNICATION_LOG_DESIGN_NOTE.md`.

**Verify tổng:** `php -l` sạch mọi file; `npm run build` OK; render 200 (`/admin/residents`, `/admin/residents/{id}/detail`, `/reset-password/{token}`); Livewire::test tab/action; browser thật: reset flow (temp/otp/link) + copy + trang reset E2E + SMTP thật + màu nút/breadcrumb + z-index popup.

**CHƯA COMMIT** (đang trên working tree, HEAD vẫn `3d34216`). Tài liệu: cập nhật `LISTING_PAGE_STANDARD.md`, tạo `PASSWORD_RESET_AND_MAIL.md`, `COMMUNICATION_LOG_DESIGN_NOTE.md`, bộ `docs/operations/` + `docs/user-guide/`.

---

## 2026-07-03 — DS-03 đủ 10 màn (Button/Action/Badge/Status) + vá tab tiêu đề DS-02

**Yêu cầu:** làm bộ 3 DS-03, bám sát thiết kế + nội dung từng màn nhiều nhất; trước đó tab "Phân cấp tiêu đề" DS-02 bị chê sơ sài (thiếu ví dụ minh hoạ trên màn thật).

**DS-03 (bộ 3):** `DesignSystemSet3` (`/sa/design-system/ds03`) — 1 nav menu, **10 tab đủ 10 màn**: Button Hierarchy (kiểu nút/icon/size + ví dụ ngữ cảnh + hướng dẫn + quy tắc thứ bậc) · Page Action Bar (là gì + thứ tự ưu tiên + 4 nhóm quy tắc + 3 ví dụ list/detail/tabbed) · Compact Action Group · Header Quick Create vs Page Create · Row Actions (nguyên tắc + bảng row-action + thứ tự + menu More) · Bulk Action Bar (bulk bar + bảng chọn + vị trí/trạng thái) · Split Button (overview + khi dùng + ví dụ phê duyệt/xuất/thanh toán/footer) · Badge Count (vị trí + biến thể + màu + KPI thật) · Status Pill (icon+màu+text pills + bảng + chi tiết + ngữ cảnh) · Action Decision Matrix (bảng 10 dòng + quy tắc nhanh). Ảnh DS-03 **lệch nhãn** (tên file ≠ nội dung) → map theo tiêu đề thật.

**Vá DS-02 tab "Phân cấp tiêu đề":** thêm card "Ứng dụng phân cấp tiêu đề trên màn thật" — mock màn cư dân có **đánh số ①–⑦** đúng vị trí Header/Page-tab/Section/Card/Form/Drawer/Modal title (bám ảnh DS-02-02).

**Verify:** `php -l` sạch; `view:cache` compile toàn bộ OK; render `/sa/design-system` + `/ds02` + `/ds03` = **200** (đủ marker 10 tab; pill icon dùng Blade::render chạy tốt); `npm run build` OK. Status pill = icon + màu + text (đúng rule DS-03).

---

## 2026-07-03 — DS-02 đủ 10 màn + restructure "mỗi bộ 1 menu, trang → tab" + spacing DS-02

**Yêu cầu:** margin/padding đúng DS-02; mỗi bộ handoff = 1 nav menu, các trang gộp vào tab; DS-02 làm ĐỦ 10 màn với đầy đủ nội dung. Guide ở /sa; chuẩn áp chung /sa /hq /admin (theme.css + component x2.*).

**Spacing DS-02:** `theme.css` token `--x2-card-radius 16` / `--x2-input-radius 12` / `--x2-section-gap 24` / `--x2-card-padding 20` + `--color-x2-info #06b6d4`. `x2.card.info` → radius rounded-2xl, padding px-5 py-4, title 15px. 6 partial bộ-1 → nhịp 24px (gap-6/mt-6).

**Restructure (1 bộ = 1 menu, trang → tab):** xoá 6 page class rời + `_nav`; strip wrapper 6 blade thành partial.
- **Bộ 1** `DesignSystemSet1` (`/sa/design-system`, HasForms) — 6 tab: Nền tảng · KPI & Bảng · Nút · Form & Lọc · Modal & AI · Tabs & Chi tiết.
- **Bộ 2** `DesignSystemSet2` (`/sa/design-system/ds02`) — **10 tab bám sát 10 màn DS-02**: Typography (thang chữ + ứng dụng) · Phân cấp tiêu đề (7 cấp + rule) · Token màu (Navy/Gold/Blue/Neutrals/Semantic + live preview) · Màu ngữ nghĩa (overview + banner + notice + bảng spec bg/text/border hex + debt/maintenance severity) · Icon (8 nhóm + preview panel) · Spacing (thang 4–48 + áp dụng) · Mật độ (Comfortable/Default/Compact) · Radius & Shadow (thang xs–xl + nơi dùng + elevation 0–5 rgba + radius component) · Accessibility (focus/hover/disabled/readonly/permission/empty + contrast AA/AAA + checklist) · Showcase (6 KPI token + tổng quan + màn Chất lượng dữ liệu thật).

**Verify:** `php -l` sạch; `view:cache` compile toàn bộ OK; render `/sa/design-system` + `/sa/design-system/ds02` = **200**; `npm run build` OK.

**Chốt token:** DS-02 xác nhận Plus Jakarta + Inter (Manrope trong Design/*.png cũ bỏ). Màu trong guide bám ảnh DS-02 (Navy 900 #0B1533…, Semantic Success #22C55E, Info #0EA5E9, AI #8B5CF6).

---

## 2026-07-03 — DS guide Forms: dùng component Filament THẬT (đối chiếu UI)

**Yêu cầu chủ dự án:** đối chiếu guide với bộ component Filament, nhất là dropdown/select & input — làm đúng nhất với UI.

**Vấn đề:** trang `DesignSystemForms` trước tự viết `<input>`/`<select>` thô bằng Tailwind → KHÔNG khớp UI Filament thật.

**Sửa:** `DesignSystemForms` giờ `implements HasForms` + `InteractsWithForms`, định nghĩa `form(Schema)` render **field Filament thật** trong Grid(3) × 3 Section: (1) TextInput (text/search prefixIcon/phone tel prefix +84/amount numeric prefix VND) + Textarea maxLength + FileUpload multiple + DatePicker native(false) range; (2) Select native(false) + Select multiple + CheckboxList + Radio + Toggle; (5-6) TextInput required + helperText (chỉ dẫn) + disabled + Placeholder. `mount()` fill mặc định. Blade chỉ còn `{{ $this->form }}` + Filter Bar (x2) + Drawer mock (pattern tổ hợp, không phải field đơn). Namespace v5: `Filament\Schemas\{Schema,Components\Section,Components\Grid}` + `Filament\Forms\Components\*` (copy từ ResidentForm).

**Verify:** `php -l` sạch; `view:cache` OK; render `/sa/design-system/forms` = **200** (form Filament resolve + render, dropdown/select/checkbox/radio/toggle/date/file là component thật); 6/6 route DS vẫn 200; `npm run build` OK.

**Còn:** các trang khác chủ yếu là component X2 tùy biến (buttons/card/table/badge — đúng DS của mình) + mock pattern (modal/drawer/notification/timeline) — hợp lệ. Dropdown/kebab ở trang Buttons vẫn là mock minh hoạ pattern ActionGroup.

---

## 2026-07-03 — Design System: menu + 6 trang hướng dẫn trên /sa (living style guide)

**Bối cảnh:** chủ dự án đưa 6 ảnh `handoff/0307/Design/*.png` (các trang tài liệu Design System) và yêu cầu tạo 1 menu trên /sa để làm trang hướng dẫn cho bộ này.

**Menu:** thêm nav group `Design System` (icon swatch) vào `SaPanelProvider`.

**6 page class** (`app/Filament/Sa/Pages/DesignSystem*.php`, trait `PlatformScreen` → chỉ SuperAdmin, slug `design-system[/…]`):
1. `DesignSystemFoundations` (`/sa/design-system`) — Typography (Plus Jakarta/Inter), Màu, Spacing, Bo góc, Điều hướng, Bố cục, Nguyên tắc.
2. `DesignSystemDataDisplay` (`/data-display`) — KPI (dogfood `x-x2.card.kpi`), loại card, bảng (`x-x2.table.data`), trạng thái bảng.
3. `DesignSystemButtons` (`/buttons`) — thứ bậc nút (`x-x2.btn`), split/group, topbar, dropdown/kebab, badges/status pills.
4. `DesignSystemForms` (`/forms`) — input, controls, filter bar (`x-x2.filter.bar/chip`), drawer lọc, validation states.
5. `DesignSystemOverlays` (`/overlays`) — modal/drawer, wizard, thông báo, approval, AI, system states.
6. `DesignSystemRecords` (`/records`) — kiểu tab, record detail, info blocks, related lists, timeline, AI side panel.

**Views:** `resources/views/filament/sa/ds/*.blade.php` + partial `_nav.blade.php` (pill sub-nav 6 trang). Dùng **token thật + component x2 thật** → guide vừa là tài liệu vừa là bản test component.

**Verify:** `php -l` 6 class sạch; `view:cache` compile toàn bộ blade OK; render headless 6 route `/sa/design-system*` = **200** (login platform admin x2bms@x2bms.vn); `npm run build` OK (theme 676KB, class x2-ai tint mới compiled). CHƯA screenshot pixel (preview cần chạy ở repo có deps).

**Lưu ý cần chốt:** 6 ảnh Design hiển thị **Manrope** + Navy `#0D1B2A`/Gold `#D4A017`; guide tôi dựng theo **giá trị ĐANG hiện thực** (DS-01: Plus Jakarta + Navy `#0B2146`/Gold `#D5A331`). Cần chủ dự án xác nhận bộ token canonical (DS-01 mới hay ảnh cũ) để đồng bộ.

---

## 2026-07-03 — DS-01 Phase 1 (đợt 1): bộ component list/dashboard + áp màn Danh sách cư dân (05)

**Component mới** (`resources/views/components/x2/`, dotted namespace, Blade thuần):
- `btn` (`x-x2.btn`): variant primary(blue)/gold(CTA)/outline/danger/ghost + size + icon @svg + loading/disabled state.
- `card/kpi` (`x-x2.card.kpi`): KPI DS-01 — icon tròn tint, số dùng `.font-title` (Plus Jakarta), trend ▲/▼ + "so với tháng trước", link "Xem chi tiết →", state loading (skeleton).
- `card/info` (`x-x2.card.info`): card có tiêu đề + slot `actions` + body.
- `page/tabs` (`x-x2.page.tabs`): **hàng tab trái + action page-level phải cùng hàng** (chữ ký DS-01), tab active gạch chân xanh + đậm, badge count; hỗ trợ `wire` (wire:click) hoặc `url`.
- `page/action-group` (`x-x2.page.action-group`): cụm action phải cho trang không tab.
- `filter/bar` + `filter/chip`: toolbar trên bảng (slot savedView/search/trailing + nút "Bộ lọc nâng cao" badge) + chip filter có nút xoá.
- `table/data` (`x-x2.table.data`): shell bảng bespoke (slot head/body/footer, state empty+loading skeleton, sticky, row ~56px) + `table/bulk-actions` (chỉ hiện khi có chọn, mobile → sticky đáy).
- Giữ nguyên component flat cũ (kpi-card/data-table/action-bar…) → trang đã build không vỡ.

**Áp màn Danh sách cư dân (DS-01-05)** — `ResidentDirectory` + blade:
- Bỏ `x-x2.action-bar subtitle=...` (subtitle bị cấm) → `x-x2.page.tabs` 5 tab (Tất cả/Chủ sở hữu/Người thuê/Chờ duyệt/Đã khóa) + count, action inline (Nhập/Xuất/+Thêm mới gold).
- Thêm `public $activeTab` + `setTab()` + `scopeByTab()`; tab wire vào query Filament (owner/tenant qua whereHas relations, pending/locked qua status).
- KPI nâng lên `x-x2.card.kpi` 5 thẻ (thêm "Cập nhật gần đây"); **KPI = tổng theo context, KHÔNG đổi theo tab/filter** (đúng rule DS-01). Table Filament giữ nguyên.

**Verify:** `php -l` sạch; `view:cache` compile toàn bộ blade OK; render headless `/admin/residents` = **200**, đủ marker (tab labels, `font-title`, "Thêm mới", "Cập nhật gần đây"). CHƯA screenshot pixel (preview thiếu deps — verify bằng render headless repo chính). Toolbar filter tùy biến + đối chiếu pixel để đợt sau.

**Tiếp:** component `record.*`/`approval.*`/`ai.*` khi làm màn 06/07/09; refactor tiếp các màn list khác.

---

## 2026-07-03 — DS-01 Phase 0: font Plus Jakarta Sans + design tokens (nền design-system)

**Bối cảnh:** khởi động track DS-01 (`docs/DS01_EXECUTION_PLAN.md`) — bộ Design System chính thức. Chủ dự án chốt: /admin·/hq·/sa bespoke đúng thiết kế, /fila giữ UI mặc định Filament, **font Plus Jakarta Sans áp cho tất cả panel**.

**File đổi:**
- `resources/css/filament/admin/theme.css`: `--font-title` Manrope → **'Plus Jakarta Sans'**; selector `.fi-header-heading/h1-4/.font-title` dùng PJS; palette chỉnh theo DS-01 tokens (`--color-x2-navy #0b2146` navy-900, `--color-x2-navy-950 #071a3a`, `--color-x2-gold #d5a331` gold-600, `--color-x2-ai #7c3aed`, canvas #f8fafc); thêm `:root` layout tokens (`--x2-sidebar-width 20rem`, `--x2-sidebar-collapsed-width 5rem`, `--x2-topbar-height 4.25rem`, `--x2-content-padding 1.5rem`, `--x2-card-radius 12px`, `--x2-button-height 40px`, `--x2-table-row-height 56px`).
- `AdminPanelProvider`/`HqPanelProvider`/`SaPanelProvider`: link bunny.net `manrope` → `plus-jakarta-sans:400,500,600,700,800`. /admin thêm `->sidebarWidth('20rem')->collapsedSidebarWidth('5rem')`.
- `FilaPanelProvider`: thêm `->font('Plus Jakarta Sans')` (giữ chrome mặc định, chỉ đổi typeface).
- `resources/views/filament/hooks/header-cluster.blade.php`: hardcode `font-family:'Manrope'` → 'Plus Jakarta Sans'.

**Verify:** `php -l` 4 provider sạch; không còn `manrope` trong code (chỉ docs). **CHƯA build/render** — worktree thiếu `node_modules`/`vendor`. Cần chạy ở repo có deps: `npm run build && php artisan optimize:clear`, rồi kiểm topbar/sidebar/KPI đổi sang Plus Jakarta + sidebar 20rem đối chiếu ảnh DS-01-01. Font-link (renderHook) hiệu lực ngay; thay đổi theme.css cần build.

**Tiếp theo:** Phase 1 — bộ component dotted namespace `x2.shell.*/nav.*/header.*/page.*/card.*/filter.*/table.*/record.*/approval.*/ai.*` (Blade, 13 state), giữ alias flat cũ.

---

## 2026-07-02 — BQL-03-02 Chu kỳ phí & đợt thu + drawer "Thiết lập kỳ phí" (dựng đúng UI)

**Migration `2026_07_02_000008_fee_cycles_bql0302`** (add-only): `billing_periods` += `name`, `fee_category`, `scope_label`, `expected_units`, `expected_amount`. Seed `seedBql0302Cycles`: 10 kỳ phí CP-YYYY-MM-XX (một kỳ/loại phí/tháng) khớp ảnh — **6 đang mở / 3 chờ chốt / 1 đã phát hành** (status open/pending_close/published). Tách khỏi 7 billing_periods theo tháng của backbone (03-02 lọc code LIKE 'CP-%').

**Page `FeeCycleList`** (`/admin/fees/cycles`, ẩn nav, vào từ pill "Chu kỳ phí" trên màn Khoản thu) + view: KPI 4 (đang mở 6/chờ chốt 3/đã phát hành 1/tổng 10), bảng kỳ phí (Mã/Tên/Loại phí/Phạm vi/Kỳ thu/Trạng thái) + bulk (Chốt kỳ/Phát hành) + **drawer "Thiết lập kỳ phí"** (Alpine slide-over): step indicator 5 bước, form trái (①Thông tin ②Phạm vi ③Nguồn dữ liệu&quy tắc ④Lịch chạy ⑤Xem trước 4 card), panel phải "Tóm tắt kỳ phí" + Hướng dẫn + "Kiểm tra trước khi tạo" checklist, footer Hủy/Lưu nháp/Chạy thử/Tạo kỳ phí. `createDraftCycle()` tạo kỳ nháp thật + notification. Thêm sub-nav pill trên FeeCatalog (Biểu phí/Chu kỳ phí).

**Fix path 2 máy:** `.claude/launch.json` `runtimeExecutable` đổi `C:\Users\ADMIN\...php.bat` → **`php`** (portable, mỗi máy PATH tự trỏ Herd). Preview MCP chạy được.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000008 OK, ~49s); `npm run build` (Node 22) OK; `_render_admin.php "fees/cycles,fees/catalog"` → 200/200; **preview thật:** đăng nhập x2bms → `/admin/fees/cycles` render đúng (KPI 6/3/1/10, rows CP-*), mở drawer "Thiết lập kỳ phí" khớp ảnh (step indicator, §1-§2 field đúng, Số căn 1.248, footer 4 nút). Screenshot xác nhận.

**Lưu ý side-effect:** backbone 1.248 căn làm dashboard WEB-01-01 đổi số (Tỷ lệ thu 95.9%, Đã thu 3,21 tỷ, Công nợ đến hạn 2.220 tr) — dữ liệu thật nhưng lệch ảnh gốc 96.2%/2.45 tỷ. Cần chốt với chủ dự án: giữ (ưu tiên BQL-03) hay cô lập số dashboard.

**Slice 3 đã xong:** 03-01, 03-02, 03-04, 03-05, 03-06, 03-09 (6/10). **Còn:** 03-03, 03-07, 03-08, 03-10.

---

## 2026-07-02 — BQL-03-04 Bảng kê phí cư dân + BQL-03-06 Sổ công nợ cư dân

**Chung:** thêm trait `App\Filament\Concerns\FinanceScope` (financeBuildingId = toà chính dự án, currentPeriod, money/moneyCompact) dùng cho các màn tài chính. Thêm relation `Apartment::residents()` (belongsToMany qua resident_apartment_relations) + `Apartment::statements()`. Cast `statements.viewed_at/due_date`. Seed backbone bổ sung: **dòng phí** (statement_lines 5-7 dòng/bảng kê mới, exact-sum → cột "Số khoản phí" + chi tiết 03-09) và **lịch sử kỳ trước** cho 24 debtor (6 kỳ đã thanh toán → ledger 03-06).

**BQL-03-04** (`/admin/statements`, nav 'Hóa đơn & thanh toán'): Page `StatementList` (WithPagination) + view. 5 KPI khớp ảnh: **Chờ phát hành 124 · Đã phát hành 1.086 · Đã xem 732 · Quá hạn 148 · Tổng phải thu 8,42 tỷ**. Bảng 11 cột (Mã/Căn hộ+Cư dân/Kỳ/Số khoản phí/Phải thu/Đã TT/Còn nợ/Ngày PH/Hạn TT/Trạng thái/Thao tác), phân trang thật 10 dòng, sort hash-shuffle để mỗi trang mix trạng thái. Ẩn `StatementApprovalQueue` cũ khỏi nav (giữ 4 mục tài chính đúng ảnh).

**BQL-03-06** (`/admin/debts/{record}`, ẩn nav, link từ 03-05): Page `DebtLedger` + view. Header cư dân + 4 KPI (Nợ hiện tại=bucket 0-30 / Nợ quá hạn=31-90+ / Số kỳ còn nợ / Tổng đã TT năm nay) + bảng công nợ theo kỳ (phát sinh/đã thu/còn nợ/trạng thái + tổng) + biểu đồ tuổi nợ (4 bucket) + thao tác nhanh.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (~46s); `_render_admin.php "debts,debts/1,statements,fees/catalog"` → **200×4**; grep HTML: 03-04 KPI 1.086/124/732/148/8,42 tỷ đúng, trang 1 mix trạng thái (đã PH/đã TT/chờ PH/quá hạn).

**BQL-03-09** (`/admin/statements/{record}`, ẩn nav, link từ 03-04): Page `StatementDetail` + view. Cột trái (căn hộ+cư dân / kỳ phí / trạng thái TT) + giữa (chi tiết dòng phí từ statement_lines + Tổng trước VAT/VAT 8%/Tổng cộng + timeline phát hành-xem-thanh toán-hạn) + phải (thao tác Phát hành/Điều chỉnh/Gửi lại/In PDF + checklist). Render `/admin/statements/13` → 200.

**Đã xong Slice 3:** 03-01, 03-04, 03-05, 03-06, 03-09 (+backbone). **Còn:** 03-02 Chu kỳ phí+wizard · 03-03 Chi tiết kỳ phí · 03-07 Duyệt điều chỉnh (cần seed adjustment) · 03-08 Nhắc nợ/chiến dịch (cần seed campaign cho tenant demo) · 03-10 Nhật ký thao tác (cần seed audit tài chính).

---

## 2026-07-02 — BQL-03 backbone dữ liệu (1.248 căn, số khớp ảnh 100% thật) + BQL-03-05 Công nợ & tuổi nợ

**Quyết định chủ dự án:** dữ liệu tài chính seed theo hướng **"phình lên ~1.248 căn, mọi số khớp ảnh, 100% bản ghi thật"** (không dùng snapshot). Vì 6 màn (03-02..09) phụ thuộc, dựng **nền seed hợp nhất trước**, verify từng con số, rồi mới dựng UI.

**Migration `2026_07_02_000007_bql03_receivables_columns`** (add-only): `statements` += `viewed_at`, `due_date`, `assignee_name`, `sent_channel`; `debts` += `code`, `resident_name`, `last_period_code`, `bucket_0_30/31_60/61_90/over_90`, `risk_level`, `recovery_status`, `assignee_name`.

**Seed `seedBql03Receivables`** (gọi trong run() sau seedBillingAndPayments; bulk-insert):
- Scale Tòa A (SG-A) lên **1.248 căn** (+1.128 căn code A/B/C dạng `A12.06`, mỗi căn 1 cư dân `CDX-*` + relation owner).
- **Bảng kê kỳ T7/2026:** tổng 1.210 = **published 1.086 / pending 124**; trong published: **viewed 732 / overdue 148**; **tổng phải thu = 8.420.000.000 (8,42 tỷ)** (phân bổ exact-sum). Mỗi bảng kê có mã `BK-2026-07-####`, kênh gửi, người phụ trách.
- **Sổ công nợ:** **24 dòng** (mã `AR-2026-####`), aging **1,02 tỷ / 650tr / 320tr / 210tr** (tổng 2,20 tỷ — lưu ý ảnh ghi "2,18 tỷ" là số lệch trong ảnh; ta hiển thị đúng tổng buckets). Risk theo thứ hạng nợ quá hạn: **critical 4 / high 6 / medium 8 / low 6**; recovery_status + assignee.
- Helper `distribute()` chia tổng thành N phần exact-sum (không âm).

**Scope tài chính:** các màn 03 scope theo **toà chính của dự án (SG-A)** qua `financeBuildingId()` (= building nhỏ nhất trong project) — khớp topbar 1 toà; toà phụ SG-B (6 bảng kê/4 nợ) không lẫn vào KPI.

**BQL-03-05 as-built** (`/admin/debts`, nav 'Tài chính – Phí' > 'Công nợ'): Page `DebtAgingList` + view: 5 KPI aging + bảng 12 cột (Mã/Căn hộ+Cư dân/Kỳ gần nhất/Tổng nợ/4 bucket/Mức rủi ro/Người phụ trách/Trạng thái thu hồi/Thao tác) + filter row + bulk bar (Gửi nhắc nợ/Giao xử lý/Đề nghị khóa tiện ích/Xuất). Helper `money()`/`compact()` format VND (tỷ/triệu). Đọc DB thật, scope SG-A.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000007 OK, ~43s); `_chk.php` (bootstrap script) xác nhận **apartments=1248, published=1086, pending=124, viewed=732, overdue=148, receivable=8.42 tỷ, debts=24, buckets 1,02/0,65/0,32/0,21 tỷ, không âm, risk 4/6/8/6**; `_render_admin.php "debts,fees/catalog"` → **200/200**; grep HTML render `/admin/debts`: 24 mã AR-2026, aging tỷ/triệu, badge rủi ro/thu hồi hiện đúng.

**Tiếp (cùng data đã sẵn):** 03-02 Chu kỳ phí+wizard · 03-03 Chi tiết kỳ phí · 03-04 Bảng kê phí cư dân (KPI 124/1.086/732/148/8,42 tỷ) · 03-06 Sổ công nợ cư dân · 03-07 Duyệt điều chỉnh · 03-08 Nhắc nợ/chiến dịch · 03-09 Chi tiết bảng kê · 03-10 Nhật ký thao tác.

---

## 2026-07-02 — CHỐT: 3 tầng scope của hệ thống (ghi lại cho rõ ràng)

Hệ thống có **3 phạm vi (scope) lớn**, ánh xạ theo `tenant_id` / `project_id`:

| Panel | Tầng | Scope dữ liệu | Ai vào |
|---|---|---|---|
| **`/sa`** | SuperAdmin / Nền tảng | **Toàn nền tảng** — xuyên mọi tenant (không giới hạn tenant_id) | Platform admin |
| **`/hq`** | Công ty quản lý toà nhà | **`tenant_id`** — 1 công ty, **đa dự án** (CurrentContext::hqProjectIds / hqAllProjectsSelected, session `hq_tenant_id`); platform admin có thể "as a company" | Tenant operator (company_admin) + platform admin |
| **`/admin`** | Ban Quản lý (BQL) vận hành | **`tenant_id` + `project_id`** — vận hành **MỘT dự án** (CurrentContext::projectId, workspace `bql`); `building_id` chỉ là filter | BQL staff + cấp trên |

- Nói gọn: **/sa = platform · /hq = tenant_id (đa dự án) · /admin = tenant_id + project_id (một dự án)**.
- Nhiều bảng nghiệp vụ scope theo `building_id` (BelongsToProject suy ra project qua building) — ví dụ statements/debts/billing_periods không có cột project_id riêng, scope bằng building_id thuộc dự án.
- `$scope` trong seeder = `['tenant_id', 'building_id']`. Global scope `BelongsToTenant` + `CurrentContext` áp scope theo tầng đang đăng nhập.

---

## 2026-07-02 — Slice 3 (BQL-03 Tài chính) bắt đầu: BQL-03-01 Biểu phí & quy tắc tính phí

**Bối cảnh:** Bắt đầu Slice 3 theo `WEB_BQL_EXECUTION_PLAN.md`. Handoff bộ riêng BQL-03 ở **`C:\app\x2-bms\handoff\WEB-BQL-03_FEE_CYCLE_STATEMENT_DEBT_BILLING_HANDOFF_20260702\`** (máy này; docs cũ trỏ `D:\` là máy kia). Đã đọc trọn contract + xem 10 ảnh. **Tên file ảnh lệch nội dung** → bám tiêu đề trên ảnh: 03-01=Biểu phí (không phải fee cycle như tên file), 03-02=Chu kỳ phí+wizard, 03-09=Chi tiết bảng kê (không phải report), 03-10=Nhật ký thao tác.

**Map handoff→model có sẵn:** fee_categories→FeeType · fee_rules/versions→FeeRate/FeeFormula(+Version) · fee_cycles→BillingPeriod · fee_cycle_runs(+items)→BillingRun(+Item) · statements(+lines)→Statement(+Line/Approval/PublishLog) · debt_ledgers→Debt · debt_adjustments→BillingAdjustment · collection_campaigns(+msgs)→DebtReminderCampaign(+Log). Reuse tối đa, không dựng lại schema.

**BQL-03-01 as-built** (`/admin/fees/catalog`, nav 'Tài chính – Phí' > 'Khoản thu'):
- Migration `2026_07_02_000006_extend_fee_catalog_bql03` — thêm cột hiển thị cho `fee_types`: `applies_to`, `frequency`, `vat_percent`, `formula_text`, `effective_from`, `is_complex` (add-only). FeeType casts + $guarded=[].
- Page `App\Filament\Pages\FeeCatalog` + view `fee-catalog.blade.php`: 5 KPI (đang áp dụng/sắp hiệu lực/tạm ngưng/công thức phức tạp/cập nhật tháng này), bảng catalogue 10 cột (Mã/Tên/Nhóm/Đối tượng/Công thức/Chu kỳ/VAT/Hiệu lực/Trạng thái/Thao tác) + filter row + "Quy tắc tính nổi bật" 5 card. Đọc FeeType tenant-scoped, không hardcode.
- Seed `seedFeeCatalog` + `seedBql03CatalogExtra`: enrich 5 fee type chức năng (giữ code QL/RAC… cho billing) + sinh 33 catalogue rows BF-* để **KPI khớp ảnh chính xác: 28 active / 6 pending / 4 inactive / 9 complex / 12 cập nhật tháng này** (backdate updated_at 26 dòng còn lại).
- Trạng thái dùng string 'active'/'pending'/'inactive' (fee_types.status là string, mở rộng thêm 'pending').

**Công cụ verify mới:** `_render_admin.php` (gitignore) — login user, render `/admin/<slug>` in HTTP status (mặc định platform admin). `_chk.php` để đếm nhanh qua DB.

**Verify:** `php -l` sạch 4 file; `migrate:fresh --seed` sạch (000006 OK, seed ~44s); đếm fee_types = 38 (28/6/4/9/12 khớp ảnh); `_render_admin.php "fees/catalog,my-work,access"` → **200/200/200**.

**Tiếp:** 03-02 Chu kỳ phí & wizard (BillingPeriod), 03-03 Chi tiết kỳ phí (BillingRun) — cùng nhóm 'Khoản thu'; rồi 03-04..03-10.

---

## 2026-07-02 — Tách 3 panel + shell + Web BQL Slice 0/1/2 + mobile/search/profile/context-switcher

Phiên dài, nhiều hạng mục (tất cả trên `/admin` = workspace BQL trừ khi ghi khác). Chưa commit.

**1. Tách 3 panel theo 3 tầng.** Tạo `SaPanelProvider` (`/sa`, `EnsurePlatformAdmin`); chuyển **35 page platform** từ `app/Filament/Pages` → `app/Filament/Sa/Pages` (SaaS Billing/Integration/Support/Nền tảng+WEB-UX-22), **4 page AI** → `app/Filament/Hq/Pages`. `/admin` còn **13 page thuần BQL**. Sửa ~5 tham chiếu blade AI-class, rewire workspace switch redirect (bql→/admin, hq→/hq, superadmin→/sa). 3 panel boot sạch (route:list).

**2. Shell dùng chung (quyết định chủ dự án).** Tiêu đề lên header (`topbar-start`), **search căn giữa** (flex-1 gap), **bỏ subtitle** (drop trong `x-x2.action-bar`), **giữ số cột KPI theo thiết kế** — thêm `<x-x2.kpi-row :cols>` (6 card/hàng không tự co về 2/3).

**3. Slice 0 — BQL-00 Foundation (4 màn):** MyWork (`/admin/my-work`, inbox đa nguồn ApprovalRequest/Statement/ResidentApproval/PaymentRequest/WorkOrder/Feedback/Sla/Ioc/Audit + duyệt/từ chối ghi audit), AuditLogViewer (`audit-logs`), PermissionState (`access-denied`), ProjectSettingsPreview (`project-settings`). Thêm quan hệ `user()/building()` cho AuditLog, `creator()` cho WorkOrder.

**4. Slice 1 — BQL-01 (5 màn):** ApartmentTree (`apartments/tree`), HouseholdRelationships (`households`), MoveInOutHistory (`move-history`), ResidentTimeline (`resident-timeline`), ResidentDataQuality (`residents/data-quality`).

**5. Slice 2 — BQL-02 Access (5 màn, nav group mới "An ninh & Kiểm soát"):** AccessControlDashboard (`access`), VehicleRequests (`access/vehicle-requests`), AccessCards (`access/cards`), ResidentAccessProfile (`access/resident-profile`), AccountApprovalDetail (`residents/approvals/{id}`, link từ ResidentApprovalQueue).

**6. Đối chiếu ảnh handoff (quét 99 ảnh bằng 10 subagent).** Phát hiện `UI_IMAGE_INVENTORY.md` **sai tên↔nội dung ở batch 00–04** (01/02/04 xáo hoàn toàn, 03 lệch một phần; 05–09 chuẩn). Lập `UI_IMAGE_INVENTORY_CORRECTED.md` (bộ gốc) + verify bộ chủ dự án re-map `D:\Chinh\x2\handoff\01-04\` (`REMAP_VERIFICATION_20260702.md`, còn vài slot lệch). Memory: `x2bms-handoff-image-mislabels`.

**7. Mobile responsive header (WEB-UX-MOBILE).** `<x-x2.mobile-shell>` inject BODY_START (<lg), ẩn `.fi-topbar` mobile; hamburger dùng lại sidebar Filament làm drawer; header gọn + context row + bottom sheet. Bật cho **cả 3 panel**.

**8. Global search (WEB-UX-10).** `App\Livewire\GlobalSearch` — command palette dùng chung (desktop dropdown / mobile full), query Resident/Apartment/Feedback/WorkOrder scope context, recent + điều hướng nhanh + kết quả nhóm. Mở bằng nút search + Ctrl/K, render BODY_END 3 panel. (Filament global search dựa Resource không hợp panel Pages-only.)

**9. Profile (WEB-UX-02):** MyProfile (`my-profile`), SecuritySettings (`security`, đổi mật khẩu thật + 2FA + cảnh báo), LoginSessions (`sessions`, đọc bảng `sessions` thật, revoke). Nối avatar userMenuItems (trước `#`).

**10. Context switcher gộp 1 popup (WEB-UX-03).** `App\Livewire\ContextSwitcher` — Công ty→Dự án→Workspace/Vai trò, **gate quyền** (ẩn cột Công ty nếu không platform admin; ẩn workspace HQ/SA nếu không quyền; dự án chỉ cái được cấp). Thay 2 dropdown workspace+project bằng 1 chip header (admin+sa) + trigger mobile. Width **2/3 content** desktop / full mobile.

**Bẫy đã gặp:** (1) `transition()` là method reserved của Livewire — đừng đặt tên page method. (2) Nhiều cột model là **BackedEnum cast** (Vehicle/AccessCard/Resident status+type) → chuẩn hoá `enumVal()` trước khi dùng làm key/so sánh. (3) `@php use ... @endphp` bên trong `@auth` = fatal → dùng FQN. (4) **`theme.css` `@source` thiếu `resources/views/livewire`** → Tailwind không sinh class chỉ dùng ở component Livewire (z-[100], lg:pl-64, calc width) → modal lỗi vị trí; đã thêm source. (5) Tailwind arbitrary `w-[calc(...*2/3)]` vỡ (opacity) → dùng `*0.667`. (6) Bottom-sheet slide cần `x-transition:enter/enter-start/enter-end` tường minh.

**Verify:** build + `optimize:clear` sạch; route:list 3 panel OK (/admin 22 page BQL + profile/access); browser (preview) verify: dashboard/my-work/audit/project-settings/tree/households/move/timeline/data-quality/vehicle-requests/access-cards/access-dashboard/resident-profile/account-approval + mobile shell (drawer/search/bottom-sheet) + global search (kết quả thật "Nguyễn") + context switcher (3 cột platform admin, 2/3 width, mobile full) + profile pages. Memory cập nhật: `x2bms-web-admin-architecture`, `x2bms-build-roadmap`, `x2bms-handoff-image-mislabels`.

**CÒN LẠI:** Slice 3+ (BQL-03 Tài chính → 09); nối avatar menu /hq /sa; HQ context-row đa-dự-án trong mobile-shell; global search kết quả wiring sâu hơn; guard EnsureProjectContext→/access-denied.

---

## 2026-07-02 — HQ-03 + HQ-04: Tài liệu/Biểu mẫu/AI KB + Phân quyền/Hỗ trợ (20 màn /hq) — HOÀN TẤT HQ PORTAL

**Phạm vi:** 2 batch cuối. HQ Portal đủ **50/50 màn** (Phase 0 + HQ-01/02/03/04/05).

**HQ-03** (migration `2026_07_02_000004`, 12 bảng): `document_libraries`, `documents`, `document_versions`, `sop_templates`, `checklist_templates`, `checklist_items`, `template_assignments`, `config_inheritance_rules`, `ai_knowledge_sources`, `ai_knowledge_sync_logs`, `ai_test_questions`, `ai_test_runs`. Tái sử dụng `dynamic_forms` (form builder) + `knowledge_*` (KB). 10 màn nav 'Biểu mẫu & Tri thức': KnowledgeHub (03-01), SharedDocuments (03-02, folder tree + tab loại), SharedForms (03-03), FormBuilder (03-04, Alpine kéo trường), SopChecklists (03-05), TemplateAssignments (03-06), InheritanceRules (03-07), KnowledgeBaseHq (03-08), AiKnowledgeSources (03-09), AiKnowledgeTest (03-10). Seed khớp ảnh: docs 1842/SOP 356, forms 218/156/38/24, hub AI index 1256 (778/226/151/101), 6 nguồn tri thức (SharePoint 128.4GB…).

**HQ-04** (migration `2026_07_02_000005`, 4 bảng): `permission_groups`, `permission_group_items`, `two_factor_settings`, `login_sessions`. Tái sử dụng spatie roles/permissions, `user_role_scopes`, `audit_logs`, `support_tickets`/`support_kb_articles` (Batch 10). 10 màn nav 'Hỗ trợ & Phân quyền': AccessSupportOverview (04-01), UserManagement (04-02), RoleManagement (04-03), PermissionGroupsPage (04-04), PermissionMatrix (04-05, ma trận module×vai trò×5 hành động), HqActivityLog (04-06), SupportTickets (04-07), TicketDetail (04-08, route-model-binding), SlaReport (04-09), SupportKnowledgeBase (04-10). Seed khớp ảnh: users 1248 (1062/96/60/30), roles 18, tickets 386 (132/146/68/24/16), CSAT 4.62, SLA 88.4%, 8 nhóm quyền, 8 ticket T-SSG + messages.

**Bẫy:** (1) `dynamic_forms.current_version` là INTEGER → parse 'v2.3'→2. (2) Heredoc inline dễ vỡ khi có ký tự đặc biệt → dùng file generator `_gen_hq3.sh`/`_gen_hq4.sh` (Write literal) rồi `bash`, xoá sau.

**Verify:** `migrate:fresh --seed` sạch (000004/000005 OK); **51/51 route `/hq` render HTTP 200** (login HQ operator) — toàn bộ HQ Portal không hồi quy.

**HQ PORTAL DONE:** Phase 0 + 5 batch × 10 màn = 50 màn. 5 migration HQ (000001–000005). Tiếp (tùy chọn): action ghi thật cho các form/wizard còn ở mức UI, Sanctum cho API, Playwright screenshot.

---

## 2026-07-02 — HQ-05: Báo cáo công nợ, tài chính, thu chi đa dự án (10 màn /hq)

**Phạm vi:** Trọn batch HQ-05. Dashboard/aggregate dùng `metric_snapshots` (đã có từ HQ-02) theo đúng khuyến nghị handoff (không tạo bảng report riêng từng màn) + delta nghiệp vụ mới + seed + 10 Page.

**DB delta** (`2026_07_02_000003_create_hq05_finance`, 8 bảng): `debt_reminder_campaigns`, `debt_reminder_logs`, `cash_funds`, `cash_transactions`, `expenses`, `report_schedules`, `report_export_jobs`, `ai_insights`. 8 model.

**Seed** (`seedHq05`) cho T-SSG — chủ yếu `metric_snapshots` (89 dòng): aging 5 nhóm (tổng **1.024 tỷ**, nợ xấu >90 ngày 213.91 tỷ 20.7%), per-project aging (5 dự án, 4065 căn), debt_by_fee (5 loại), collection_rate (6 kỳ + 5 dự án), finance_kpi, project_cashflow (7 dự án, doanh thu **28.62 tỷ**), cashflow_kpi, top_debtor (10 hồ sơ + debt_kpi 1236/8.2465 tỷ/268/156), reminder_kpi (12 chạy/128.456 gửi/12.68 tỷ cam kết), ai_risk_kpi (68/100, dự báo 28.45 tỷ, 63.2%), ai_forecast (3 tháng). + 6 chiến dịch nhắc nợ + logs, quỹ + thu chi + 3 đề nghị chi, 4 lịch báo cáo + 4 job xuất, 10 ai_insights (xếp hạng rủi ro Top-10).

**Pages** (`app/Filament/Hq/Pages`, nav 'Báo cáo'):
- 05-01 `FinanceOverview` `/hq/finance/overview` · 05-02 `DebtByProject` `/hq/debts/by-project` · 05-03 `DebtAging` `/hq/debts/aging` (KPI 5 nhóm + stacked bar + donut + bảng chi tiết) · 05-04 `TopDebtors` `/hq/debts/top-debtors` (KPI + bảng + panel chi tiết Alpine) · 05-05 `CollectionRate` `/hq/collection-rate` · 05-06 `DebtByFeeType` `/hq/finance/debt-by-fee` · 05-07 `Cashflow` `/hq/finance/cashflow` (KPI + bảng hiệu quả tài chính + đề nghị chi) · 05-08 `DebtReminders` `/hq/debt-reminders` (KPI + bảng chiến dịch) · 05-09 `ReportExports` `/hq/finance/reports` · 05-10 `FinanceAiRisk` `/hq/finance/ai-risk` (KPI + dự báo + Top-10 rủi ro AI).

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000003 OK, seed ~36s); đếm seed đúng ảnh (aging 1024 tỷ, top-debtor 1236/8.25 tỷ/268, 6 campaign, 10 insight, 89 snapshot); **10/10 route render HTTP 200** (login HQ operator); HQ-01/02 không hồi quy.

**Tiếp:** HQ-03 (Biểu mẫu/tài liệu/AI KB — reuse document_templates/form builder/knowledge_*) hoặc HQ-04 (Phân quyền/hỗ trợ — reuse spatie/user_role_scopes/support_* Batch10).

---

## 2026-07-02 — HQ-02: Billing, ví công ty & tương tác Platform (10 màn /hq)

**Phạm vi:** Trọn batch HQ-02. Tái sử dụng Batch 07 (billing_invoices/lines, billing_payments, usage_records/periods, quota_alerts, billing_adjustments, billing_reconciliations, pass_through_*) + delta mới + seed cho tenant Sunshine Group + 10 Page.

**DB delta** (`2026_07_02_000002_create_hq02_billing`, 7 bảng): `wallets` (ví prepaid cấp công ty — khác pass-through theo kênh), `wallet_transactions` (sổ cái: top_up/deduct/allocation/refund/adjustment), `wallet_topup_requests`, `billing_rate_cards` (đơn giá/markup theo kênh), `plan_change_requests` + `plan_change_request_items`, `metric_snapshots` (read-model dashboard/dự báo). 7 model.

**Seed** (`seedHq02`): cho tenant T-SSG-HQ — ví (số dư **352.680.000** / hạn mức **1.000.000.000**, auto-topup 200M, Vietcombank ****8888), 12 wallet_tx (nạp **6 lần = 745.000.000**) + 4 phân bổ dự án (210/160/120/80 = 570M), 2 topup request; usage_records (SMS 174k/300k, Zalo 92k/120k, Email 78k/150k) + quota alert Zalo 76.7%; 5 rate card; metric_snapshots (cơ cấu chi phí **128.45M** = phí nền tảng 80.75M + pass-through 47.7M; xu hướng 6 tháng 96.8→128.45; top 4 dự án; dự báo T8 +6.3%); 6 hóa đơn platform + lines + payments; 2 reconciliation (matched/mismatch) + 1 adjustment; **128 plan_change_requests** (processing 18 / pending 27 / completed 78 / rejected 5).

**Pages** (`app/Filament/Hq/Pages`, nav 'Billing & Gói dịch vụ'):
- 02-01 `SaasCostOverview` `/hq/billing/overview` (KPI + xu hướng bar + donut cơ cấu + top dự án + hạn mức).
- 02-02 `BillingByProject` `/hq/billing/by-project`.
- 02-03 `CompanyWallet` `/hq/billing/wallet` (số dư/hạn mức + biểu đồ + phân bổ dự án + actions).
- 02-04 `WalletHistory` `/hq/billing/wallet-history` (filter loại GD).
- 02-05 `UsageMetering` `/hq/billing/usage`.
- 02-06 `PassThrough` `/hq/billing/pass-through`.
- 02-07 `PlatformInvoices` `/hq/billing/invoices`.
- 02-08 `BillingReconciliation` `/hq/billing/reconciliation`.
- 02-09 `CostForecast` `/hq/billing/forecast`.
- 02-10 `PlanChangeRequests` `/hq/billing/plan-changes` (KPI 128/18/27/78 + tab loại + search).

**Bẫy đã trả giá:** (1) Page class `BillingReconciliation` trùng tên model import ⇒ "Cannot redeclare class" → alias `BillingReconciliation as BillingReconciliationModel`. (2) Div-by-zero ở forecast khi tenant rỗng data → `max(array_merge([1], ...))`. (3) Render headless nên đăng nhập **HQ operator** `hq@sunshinegroup.vn` (có tenant_id) thay vì platform admin (chưa chọn công ty ⇒ tenant context sai) → `_render_hq.php` mặc định user này (arg 2 để override).

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000002 OK, seed ~36s); đếm seed đúng ảnh (ví 352.68M, topup 6×745M, plan-change 128=18/27/78/5, chi phí 128.45M); **11/11 route render HTTP 200** (overview + 10 màn HQ-02) qua `_render_hq.php`; HQ-01 không hồi quy.

**Tiếp:** HQ-05 (tài chính công nợ đa dự án) hoặc HQ-03 (docs/forms/AI KB), HQ-04 (IAM/support).

---

## 2026-07-02 — HQ-01: Danh mục dự án, BQL, nhân sự & gói dịch vụ (10 màn /hq)

**Phạm vi:** Trọn batch HQ-01 (10 screen) trên panel `/hq`. DB delta + models + seed khớp ảnh + 10 Page bespoke.

**DB delta** (`2026_07_02_000001_create_hq01_project_org`, ADD-ONLY, 7 bảng): `bql_teams`, `employee_project_assignments`, `employee_assignment_histories`, `project_subscription_periods`, `project_module_overrides`, `import_batches`, `import_batch_rows`. Tái sử dụng projects/staff_profiles(≈employees)/departments/plans/modules. Models tương ứng (BelongsToTenant + SoftDeletes; histories/rows là log/child → không soft delete). `employee_id` → `staff_profiles`.

**Seed** (`DemoDataSeeder::seedHq01`): tenant "Sunshine Group" (T-SSG-HQ) + HQ operator `hq@sunshinegroup.vn` (company_admin, scope tenant). 24 dự án khớp ảnh HQ-01-01 (Tổng 24 · active 18 · trial 3 · suspended 3 · gia hạn≤30d **6** · BQL thiếu **4**; donut Đầy đủ 8/Phổ biến 7/Thông minh 3/Trial 3/Tạm ngừng 3; Tòa nhà **32** · Căn hộ **12.540** · Diện tích **238.500**). 128 nhân sự khớp HQ-01-05 (đang làm 112 / chờ 16; phòng ban Ban giám đốc 18/Kỹ thuật 58/Kế toán 12/CSKH 22/Bảo vệ 18; **Đa dự án 36**). + bql_teams(24), assignments(148), histories(6), module overrides(8), import batch(1)+rows(8).

**Pages** (`app/Filament/Hq/Pages/*` + view `resources/views/filament/hq/pages/*`):
- HQ-01-01 `ProjectDirectory` `/hq/projects` (KPI + tab/search reactive + bảng + donut + tổng quan nhanh).
- HQ-01-02 `ProjectCreate` `/hq/projects/create` (wizard 5 bước Alpine + tóm tắt live + **save thật**: project+period+bql_team+audit).
- HQ-01-03 `ProjectDetail` `/hq/projects/{project}` (header+lifecycle+info+BQL+tab nhân sự+KPI/gói/module) — route-model-binding.
- HQ-01-04 `BqlSetup` `/hq/projects/{project}/bql` (định biên phòng ban + bảng BQL + liên hệ).
- HQ-01-05 `EmployeeDirectory` `/hq/employees` (KPI + tab phòng ban + donut + dự án thiếu nhân sự).
- HQ-01-06 `ProjectAssignment` `/hq/project-assignments` (chọn dự án + nhân sự khả dụng + **assign() thật**).
- HQ-01-07 `AssignmentHistory` `/hq/assignment-histories`.
- HQ-01-08 `ProjectPackage` `/hq/projects/{project}/package` (thẻ gói + ma trận tính năng + cấu hình).
- HQ-01-09 `ProjectModules` `/hq/projects/{project}/modules` (metrics + bảng entitlement).
- HQ-01-10 `ProjectEmployeeImport` `/hq/imports/projects-employees` (wizard + preview + file info).

**Bẫy đã trả giá:** (1) Record sub-page slug `{project}` vẫn đăng ký nav ⇒ Filament dựng link thiếu param ⇒ 500 mọi màn. Fix: override `shouldRegisterNavigation(): bool { return false; }` (property bị method của trait `HqScreen` ghi đè). (2) Filament route-model-binding tự resolve `{project}` → `Project`; `mount()` phải nhận `Project $project` (không `int`). (3) Guard tenant phải bypass cho platform admin (xem mọi dự án).

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (000001 OK, seed ~35s); đếm seed đúng ảnh (script tạm); **11/11 route render HTTP 200** (overview + 10 màn HQ-01) qua `_render_hq.php` (platform admin).

**Tiếp:** HQ-02 (Billing/ví/platform — reuse Batch 07) hoặc HQ-05 (tài chính đa dự án).

---

## 2026-07-02 — HQ Portal Phase 0: hạ tầng panel /hq (Cổng Công ty / Tenant HQ)

**Phạm vi:** Dựng tầng GIỮA của mô hình 3 tầng (Platform → **HQ** → BQL). Panel `/hq` riêng cho công ty vận hành đa dự án, theo handoff `X2_BMS_HQ_FULL_CLAUDE_CODE_HANDOFF_20260702`.

**Files mới:**
- `app/Providers/Filament/HqPanelProvider.php` — panel id/path `hq`, theme navy/gold dùng lại `admin/theme.css`, 7 nav group (Tổng quan/Quản lý dự án/Nhân sự & BQL/Billing & Gói dịch vụ/Biểu mẫu & Tri thức/Hỗ trợ & Phân quyền/Báo cáo), discover `App\Filament\Hq\{Pages,Widgets}`, X2AI fab, shell hooks.
- `app/Http/Middleware/EnsureHqAccess.php` — chặn panel: chỉ `isPlatformAdmin()` hoặc `isTenantOperator()` (403 với BQL/cư dân).
- `app/Filament/Concerns/HqScreen.php` — gate màn HQ (platform admin | tenant operator; optional feature-gate qua `FeatureGateService`, không hardcode gói).
- `resources/views/filament/hq/brand.blade.php` (label "HQ Portal"), `header-cluster.blade.php` (company selector cho platform admin + multi-project scope dropdown + notif/help).
- `app/Filament/Hq/Pages/HqOverview.php` + `resources/views/filament/hq/pages/hq-overview.blade.php` — landing "Tổng quan HQ", KPI tính từ DB theo tenant + tập project đang chọn.

**Files sửa:**
- `app/Support/Context/CurrentContext.php` — thêm HQ multi-project: `hqProjectIds()` (∅ = tất cả), `hqAllProjectsSelected()`, `setHqProjects()`; `tenantId()`/`availableProjects()` honor `session('hq_tenant_id')` để platform admin thao tác "as a company".
- `routes/web.php` — `POST /context/hq-projects` (đặt phạm vi đa dự án), `GET /context/hq-tenant/{tenant}` (platform admin đổi công ty).
- `bootstrap/providers.php` — đăng ký `HqPanelProvider`.

**Verify:** `php -l` sạch; `optimize:clear` OK; render headless `/hq/overview` → **HTTP 200** (68KB) với platform admin (script tạm `_render_hq.php`, đã gitignore).

**Tiếp:** HQ-01 — DB delta (bql_teams, employee_project_assignments, employee_assignment_histories, project_subscription_periods, project_module_overrides, import_batches, import_batch_rows) + models + seed khớp ảnh + 10 màn.

---

## 2026-07-02 — Fix migrate:fresh FAIL: getTableListing() trả bảng của MỌI database trên server

**Phạm vi:** Migration soft-delete `2026_07_01_000025` FAIL trên máy dev có nhiều DB dùng chung MySQL server.

**Triệu chứng:** `SQLSTATE[42S02] Table 'x2bms._action_logs' doesn't exist` khi `alter table _action_logs add deleted_at`.

**Nguyên nhân gốc:** `Schema::getTableListing()` (Laravel 13/MySQL) trả về schema-qualified names của **TẤT CẢ database** trên server (`appsale._action_logs`, `tuart.app_log_action`, `x1.b_o_transactions`…), không chỉ `x2bms`. Fix cũ (strip dấu `.` cuối) biến `appsale._action_logs` → `_action_logs` rồi cố ALTER trong `x2bms` → không tồn tại → FAIL (bảng index 0 nên vỡ ngay đầu).

**Fix:** `includeTables()` trong migration 000025 — lấy `DB::connection()->getDatabaseName()`, **bỏ qua** bảng có prefix schema khác DB hiện tại; chỉ strip `db.` khi schema == database hiện tại.

**File:** `database/migrations/2026_07_01_000025_add_soft_deletes_and_archive.php` (chỉ sửa method `includeTables()`).

**Verify:** `php -l` OK; `migrate:fresh --seed` sạch — 000025 chạy 10s DONE, 000026/000027 DONE, seed 11.4s DONE.

---

## 2026-07-01 — Batch 10: Support Center, Ticket & Data Correction (10 màn, platform-level)

**Phạm vi:** Trọn gói Batch 10 (WEB-UX-30): 27 bảng, 27 model, 11 resource `/fila`, 8 màn bespoke `/admin` (phủ 10 screen), API `platform/support` + 10 test. Reconcile drop `support_tickets`/`support_ticket_comments`/`data_fix_requests` cũ.

**Yêu cầu UI của chủ dự án (đã áp):**
1. **Số thống kê đúng ảnh** — dashboard priority Critical 12 / High 46 / Medium 132 / Low 120 (tổng 310, phân bố ticket có kiểm soát) + 28 escalated + 37 near-breach; % (SLA 88.4 / breach 11.6 / CSAT 4.6) đọc từ `support_reports` snapshot `DASH-CURRENT`; report tháng 06 (1248 / 14h36m / 96.8% / 312 / 24 / 4.7) đọc từ `support_reports` type=resolution. Đều từ DB, không hardcode view.
2. **Listing luôn có title click → chi tiết** — mọi màn listing đặt `->action($this->detailAction())` trên cột tiêu đề (mở modal chi tiết).
3. **Textarea → HTML editor mặc định** — mọi field mô tả/lý do/nội dung/rollback trong form thêm mới dùng `RichEditor` (cả /admin lẫn /fila qua codemod).

**Files chính:**
- Migration `..._000027_create_support_center_batch10.php` (27 bảng + drop bảng cũ + archive clone audit/status_log/sla_event; FK ngắn `dcar_request_fk` tránh vượt 64 ký tự).
- 27 model `app/Models/Support*.php` + `Data*.php` + `TenantSupport*.php`; rewrite `SupportTicket` (bỏ BelongsToTenant), xoá `SupportTicketComment`/`DataFixRequest` cũ.
- Trait `app/Filament/Concerns/WritesSupportAudit.php` (ghi `support_audit_logs`).
- Seed `DemoDataSeeder::seedBatch10Support` (SLA policies, 4 team = 29 member, 2 tenant profile + contacts + entitlements, 310 ticket + 4 named có timeline, 2 escalation, 3 DCR + snapshot/diff/approval, 4 KB article, 2 report snapshot).
- 11 resource `/fila` (nav group 'Support Center', RichEditor, soft-delete UX).
- 8 Page bespoke `/admin` (nav 'Support Center', PlatformScreen): `SupportDashboard` (30-01), `SupportTicketQueue` (30-02/03/04 — queue + detail modal timeline + create + bulk assign + escalate/close/reopen + reply), `TenantSupportProfile` (30-05), `DataCorrectionRequests` (30-06 — approve 2 người cho high/critical), `ControlledDataFixWizard` (30-07 — snapshot→execute [gate bắt buộc snapshot]→rollback), `SupportKnowledgeBase` (30-08), `SupportEscalationAssignment` (30-09 — workload + auto-assign/balance), `SupportAuditResolutionReport` (30-10 — số từ support_reports + export).
- API: `routes/api.php` prefix `platform/support` (middleware `platform.admin`), 3 controller `App\Http\Controllers\Platform\Support\*`.
- Test `tests/Feature/Batch10SupportApiTest.php`.

**Bẫy đã trả giá:** (1) Cột Filament closure `fn (string $s)` → 500 "unresolvable [$s]" (bug #1 quen thuộc) — sửa hết sang `$state` ở 5 page. (2) FK auto-name `data_correction_affected_records_data_correction_request_id_foreign` = 66 ký tự > giới hạn 64 của MySQL — đặt tên FK ngắn thủ công. (3) archive clone dùng driver-aware (MySQL LIKE / khác AS SELECT) như Batch 08.

**Verify:** `php -l` toàn bộ = 0 lỗi; `migrate:fresh --seed` sạch (000027 OK, seed ~7s); đếm seed đúng ảnh (priority 12/46/132/120, escalated 28, near-breach 37, snapshot 88.4/11.6/4.6, report 1248/14h36m/96.8/312/24/4.7, 4 team/29 member); **10/10 màn render HTTP 200** (8 bespoke `/admin` + `/fila`); **Batch10 API test 10/10 PASS (37 assertion)**; Batch07 10/10 + Batch08 11/11 PASS (không hồi quy).

**Còn lại (tùy chọn):** Escalation dạng bảng + workload cards (chưa dựng Kanban kéo-thả); AI suggest KB theo ngữ cảnh ticket (chưa nối X2AI); wizard là action theo bước (chưa stepper trang riêng đầy đủ); API auth qua phiên Filament/actingAs (chưa Sanctum stateless).

**Tiếp:** BQL-4 Tài chính (WEB-FORM-08) hoặc gắn Sanctum cho API platform (Batch 07/08/10).

---

## 2026-07-01 — Batch 08: Integration Center, API Key & Webhook (10 màn, platform-level)

**Phạm vi:** Trọn gói Batch 08 (M1→M6): 18 bảng, 18 model, 17 resource `/fila`, 7 màn bespoke `/admin` (phủ 10 screen WEB-UX-28), API `platform/integrations` + 11 test. Reconcile drop `integration_connections` per-tenant cũ.

**Files chính:**
- Migration `database/migrations/2026_07_01_000026_create_integration_center_batch08.php` (18 bảng + archive clone; drop bảng cũ + recreate ở down()).
- Models: `app/Models/Integration*.php` + `Webhook*.php` (18). `IntegrationConnection` viết lại thành platform-level (bỏ BelongsToTenant).
- Service `app/Support/Integration/IntegrationSecret.php` (Crypt encrypt/decrypt · sha256 hash · mask · generate). Trait `app/Filament/Concerns/WritesIntegrationAudit.php`.
- Seed `DemoDataSeeder::seedBatch08Integration` (7 category, 12 connection + credential + 36 check, 4 API key + 8 scope, 12 event group, 5 webhook + 12 delivery, 10 event, 3 retry job, 2 incident, 8 security policy, 4 IP allowlist, rate limit).
- `/fila`: 17 resource sinh bằng `make:filament-resource --generate`; codemod set nav group 'Integration Center' + **strip secret fields** (secret_hash/encrypted_payload/signing_secret_hash) khỏi form/table; soft-delete UX cho 4 resource soft-deletable.
- Bespoke `/admin` (nav group 'Integration Center', gate `PlatformScreen`): `IntegrationOverviewDashboard` (28-01), `ExternalConnectionManagement` (28-02/03, detail modal), `ApiKeyManagement` (28-04/05), `WebhookEndpointManagement` (28-06/07, test + delivery history), `EventLogMonitor` (28-08, filter/replay/export), `IntegrationHealthRetryQueue` (28-09, retry/skip/dead-letter + incident timeline), `IntegrationSecuritySettings` (28-10, save/enforce-hmac/rotate-expiring/emergency-disable/IP allowlist).
- API: `routes/api.php` prefix `platform/integrations` (middleware `platform.admin`), 7 controller `App\Http\Controllers\Platform\Integration\*`. Secret trả về MỘT LẦN khi create/rotate.
- Test `tests/Feature/Batch08IntegrationApiTest.php`.

**Nguyên tắc bảo mật (đạt):** secret không lưu plain-text — credential dùng `Crypt::encryptString` (payload) + masked_summary; API key/webhook lưu `sha256` hash + masked; secret chỉ hiện 1 lần (Notification persistent / API response create/rotate). Mọi hành động đổi trạng thái ghi `integration_audit_logs`. Emergency disable cần lý do + `isPlatformAdmin`. Replay idempotent (không tạo retry job trùng theo event_id).

**Bẫy đã trả giá:** `CREATE TABLE … LIKE` (archive clone) chỉ đúng MySQL → vỡ trên sqlite test (RefreshDatabase chạy mọi migration). Fix: branch theo `DB::getDriverName()` — MySQL `LIKE`, driver khác `AS SELECT … WHERE 1=0`. Áp cho cả migration 000025 lẫn 000026. (Ngoài ra: `use` import phải ở đầu routes/api.php, không append cuối file.)

**Verify:** `php -l` toàn bộ model/resource/page/controller/test = 0 lỗi; `migrate:fresh --seed` sạch (000026 ≈ 1s, seed 12s); seed đếm đúng (12 connection, 4 key, 5 webhook…), credential `encrypted_payload` là Crypt blob + api key hash dài 64; **10/10 màn render HTTP 200** (7 bespoke `/admin` + `/fila`); **Batch08 API test 11/11 PASS (49 assertion)**; Batch07 test **10/10 PASS** (không hồi quy).

**Còn lại (tùy chọn):** ConnectionDetail/ApiKeyCreate hiện là modal trong trang quản lý (không tách page riêng); StaffProfilesTable (batch trước) chưa gắn trashed UX; API xác thực qua phiên Filament/actingAs (chưa gắn Sanctum stateless — như Batch 07); provider connector thật (hiện test/health là mô phỏng).

**Tiếp:** BQL-4 Tài chính (công nợ WEB-FORM-08) hoặc gắn Sanctum cho API platform.

---

## 2026-07-01 — Soft Delete toàn hệ + Global scope tầng Project + Archive log

**Phạm vi:** Nền tảng CSDL/ORM — thêm soft delete cho toàn bộ bảng nghiệp vụ (trừ log/pivot), global scope tầng dự án `BelongsToProject`, xử lý unique index, UX khôi phục ở `/fila`, và cơ chế archive log.

**Files:**
- Migration ADD-ONLY `database/migrations/2026_07_01_000025_add_soft_deletes_and_archive.php`.
- Trait `app/Models/Concerns/BelongsToProject.php` (mới); `app/Filament/Concerns/SoftDeletableResource.php` (mới).
- `config/archive.php` (mới); `app/Console/Commands/ArchiveStaleLogs.php` (mới, lệnh `logs:archive`); `routes/console.php` (đăng ký schedule dailyAt 02:30).
- **156 model** `app/Models/*` +`use SoftDeletes`; **17 model** thuộc set vận hành + `use BelongsToProject` (Apartment, Resident, Vehicle, AccessCard, ResidentApprovalRequest, FeedbackRequest, WorkOrder, Statement, BillingRun, BillingPeriod, Payment, Debt, IocAlert, Department, Area, Floor, Team).
- **82 Resource** `/fila` +`use SoftDeletableResource`; **81 Table** +TrashedFilter+Restore/ForceDelete (record & bulk). (StaffProfilesTable non-standard → chưa gắn, làm tay sau.)

**Quyết định (chốt với chủ dự án):**
1. Soft delete cho **tất cả trừ log/pivot** (DENY set trong migration + codemod: framework, log/append-only/ledger, pivot thuần).
2. `BelongsToProject` **opt-in, đa dự án**: auto-detect cột — `project_id` nếu có, else `building_id ∈ (buildings của các dự án user được phép)`. No-op ở console + platform admin + tenant operator (HQ thấy mọi dự án trong tenant); BQL scope theo `accessibleProjectIds()`. Bypass `withoutGlobalScope('project')`. (Đa số bảng scope theo `building_id` chứ không có `project_id` — chỉ blocks/buildings/teams/users/ai_* có cột project_id thật.)
3. Archive `*_archive` (CREATE TABLE LIKE) + lệnh `logs:archive` dọn định kỳ theo `config/archive.php` (retention/ table).

**Unique index:** rebuild 4 unique nghiệp vụ có nguy cơ đụng khi soft delete → composite `[col, deleted_at]`: `buildings.code`, `projects.code`, `tenants.code`, `users.email` (NULL distinct ⇒ 1 bản live/khoá, N bản trashed).

**Bẫy đã trả giá:** `Schema::getTableListing()` (Laravel 13/MySQL) trả tên **schema-qualified** (`db.table`) ⇒ `in_array($t, $deny)` không khớp ⇒ lần chạy đầu thêm `deleted_at` vào MỌI bảng (kể cả cache/jobs/permissions/_archive). Fix: strip prefix trước khi so deny.

**Verify:** `php -l` toàn bộ model + resource + table = **0 lỗi**; `migrate:fresh --seed` sạch (000025 ≈ 4s, seed OK). Smoke `scratchpad/smoke.php` **14/14 PASS** (deleted_at đúng chỗ, deny sạch, archive tồn tại, delete/restore/withTrashed, composite unique tái tạo email trashed, 'project' scope wired đúng set — FeeType KHÔNG có). `scratchpad/archive_test.php`: deny+archive sạch, `logs:archive` chuyển đúng dòng cũ (2020) sang archive, dòng mới nguyên vẹn. Render HTTP: `/fila/apartments|statements|work-orders|residents` = **200** (login platform admin).

**Còn lại:** StaffProfilesTable gắn tay; cân nhắc soft delete cho thêm bảng con nếu cần; project-scope mới phủ set vận hành lõi — mở rộng khi có nhu cầu. Chưa chạy full test suite (`php artisan test`) sau đổi model — nên chạy ở phiên sau.

**Tiếp:** BQL-4 Tài chính (công nợ WEB-FORM-08 + duyệt chi + biên lai).

---

## 2026-07-01 — BQL-3: Trung tâm thông báo (soạn + phạm vi 3 lớp + hiệu quả)

**Files:** `app/Filament/Pages/NotificationCenter.php`; blades `notification-center.blade.php` + `notification-detail.blade.php`.

**Tóm tắt:** Page HasTable trên `notifications` **theo quyền xem** (`Notification::scopeVisibleTo`, 3 lớp). KPI (đã phát hành/hẹn giờ/nháp/tỉ lệ đọc). Header action **Soạn thông báo** (RichEditor + loại/ưu tiên + **phạm vi 3 lớp**: scope options theo cấp user [platform: all/tenant/project/building · công ty: project/building/apartment · BQL: building/apartment] + target select động qua `Get` + kênh app/email/sms/zalo + phát hành ngay / hẹn giờ). owner gán theo cấp (`creatorOwner`), tạo `notification_audiences` + `notification_channels`. Row actions: **Chi tiết** (modal: nội dung + phạm vi + kênh + người nhận/đã đọc/đã gửi), **Phát hành** (`applyPublish` ước tính người nhận theo scope: building/apartment/project/tenant/all → residents count), **Lưu trữ** — gate `canManageBy`. Audit đầy đủ.

**Bẫy (lặp lại lần 3):** cột Filament closure `fn (string $s)` → 500 "unresolvable [$s]"; đổi hết sang `$state`.

**Verify:** `php -l` sạch; `view:cache` OK; render `/admin/notifications/center` → **HTTP 200**; script: composeSchema dựng 10 field; tạo NHÁP (audiences=1/channels=2), applyPublish → published + recipient_count=121, publish-now → 178, detail modal render, audit ghi nhận.

**Tiếp:** BQL-4 Tài chính (công nợ WEB-FORM-08 + duyệt chi/đề nghị thanh toán + biên lai).

---

## 2026-07-01 — BQL-2: Bảng công việc Kanban (kéo-thả + checklist + nghiệm thu)

**Files:** `app/Filament/Pages/WorkOrderKanban.php`; blades `work-order-kanban.blade.php` + `work-order-detail.blade.php`.

**Tóm tắt:** Page bespoke (không HasTable) — 4 cột theo `WorkOrderStatus` (Chờ/Đang/Hoàn thành/Quá hạn), thẻ công việc **kéo-thả bằng HTML5 draggable + Alpine** (`dragId`), thả gọi `moveCard($id,$status)` (→ set started_at/completed_at, ghi audit). Scope theo dự án (`CurrentContext::buildingIds`). Thẻ hiện code/tiêu đề/ưu tiên/người xử lý/tiến độ checklist. Action theo thẻ qua `mountAction(name,{id})` (render ẩn 4 action): **Chi tiết** (modal checklist/đính kèm/chữ ký/giao việc), **Giao việc**, **Checklist** (CheckboxList tick mục → cập nhật is_done/done_by/done_at), **Nghiệm thu** (tạo `work_order_signatures` + set done). Không cần thư viện ngoài (native DnD).

**Bẫy:** eager-load `'category'` trên WorkOrder lỗi — `category` là CỘT string, không phải quan hệ (WorkOrder chỉ có `department()`); đã bỏ khỏi `with()`.

**Verify:** `php -l` sạch; `view:cache` OK; render `/admin/work-orders/kanban` → **HTTP 200**; script: buildingIds=[1,2], `moveCard`→in_progress (started_at set) →done (completed_at set), status bogus bị chặn (no-op), detail modal render (có checklist), audit ghi nhận.

**Tiếp:** BQL-3 Thông báo (center + soạn + audiences 3 lớp + hiệu quả đã đọc/gửi).

---

## 2026-07-01 — BQL-1: Hàng đợi & xử lý phản ánh (bespoke /admin)

**Phạm vi:** Màn vận hành BQL đầu tiên (QL-FB-01..03) — luồng phản ánh end-to-end.

**Files:** `app/Filament/Pages/FeedbackQueue.php`; blades `feedback-queue.blade.php` + `feedback-detail.blade.php`; nav group 'Vận hành' vào AdminPanelProvider.

**Tóm tắt:** Page HasTable trên `feedback_requests` **scope theo dự án** (`CurrentContext::buildingIds`). KPI (chờ/quá hạn SLA/đã xử lý/đã đóng) + phân bố theo danh mục (bar). Row actions: **Chi tiết** (modal timeline gộp comment/assignment/status_history + tệp + đánh giá), **Trao đổi** (comment nội bộ), **Giao việc** (→ `feedback_assignments` + status Assigned + history), **Tạo công việc** (→ `work_orders` link `feedback_request_id`), **Bắt đầu/Đã xử lý/Đóng** (chuyển trạng thái + `feedback_status_histories`; đóng kèm rating), bulk Giao việc. Mọi hành động ghi `audit_logs` (WritesAudit) + đẩy ngữ cảnh X2AI.

**Bẫy:** `Livewire\Component` đã có method public `transition()` → đặt tên private `transition()` bị Fatal "must be public". Đổi thành `changeStatus()`. (Ghi nhớ: tránh trùng tên method Livewire: mount/render/transition/dispatch...)

**Verify:** `php -l` sạch; `view:cache` OK; render `/admin/feedback/queue` → **HTTP 200**; script logic: assign→Assigned (+assignment+history), changeStatus start→resolved (resolved_at set, history tăng), createWorkOrder → `WO-FB-1` link đúng, detail modal render (có timeline), audit ghi nhận.

**Tiếp:** BQL-2 Công việc (Kanban) — tái dùng detail/timeline pane.

---

## 2026-07-01 — Addendum SuperAdmin / P2–P6: Platform Library + AI Governance (HOÀN TẤT addendum)

**Files:** migrations `..._000019..000023`; ~25 models mới + resource /fila; các seed method `seedPlatformContent/seedGlobalAccounts/seedSharedPartners/seedDocumentTemplates/seedKbAiGovernance`.

**Tóm tắt theo slice:**
- **P2 Platform content:** `platform_content_categories`, `platform_contents` (CMS tin/banner/guide, publish_scope), `public_projects` (+`project_media`), `tenant_project_links`.
- **P3 Global account & binding:** `global_user_accounts` (registry public→verified→resident), `resident_binding_requests`, `resident_unit_bindings` (bổ trợ users/residents; 1 user ↔ N căn).
- **P4 Shared partner library (platform):** `shared_partner_categories`, `shared_partners` (+`certifications`,+`products`), `tenant_partner_assignments` (approved/contracted/blacklist/favorite) — khác `contractors`/`service_providers` per-tenant.
- **P5 Document template library:** `document_template_categories`, `document_templates` (+`shares` view_only|use_as_template|clone_allowed|force_apply, +`clones`), owner_scope 3 cấp.
- **P6 KB/AI governance:** `knowledge_documents` (+`knowledge_scopes`, sensitivity+ai_index_status), `ai_guardrail_policies`, `ai_retrieval_logs`; **mở rộng** `ai_prompt_templates` (code/use_case/system_prompt/user_prompt_template/variables_json/owner_scope). Giữ `knowledge_articles` làm KB vận hành per-tenant (có UI + X2AI search).

**Reconcile:** `ai_prompt_templates` mở rộng (không tạo trùng); `knowledge_documents` = tầng KB governance nền tảng, tách với `knowledge_articles` vận hành; `ai_guardrail_policies`/`ai_retrieval_logs` bổ sung cạnh `ai_policies`/`ai_usage_logs`.

**Verify:** mỗi slice `php -l` sạch + `migrate:fresh --seed` sạch + render /fila → **HTTP 200**. **Tổng 209 bảng.** Đợt này chỉ data-model + /fila + FeatureGateService; **12 màn WEB-UX-22 bespoke chưa dựng** (đợt sau).

---

## 2026-07-01 — Addendum SuperAdmin / P1: chuẩn hoá Feature-gate (reconcile)

**Quyết định (chủ dự án):** addendum = spec chuẩn → reconcile; đợt này chỉ data-model (+seed +/fila +service), chưa dựng 12 màn WEB-UX-22.

**Files:** migration `..._000018_reconcile_feature_gate`; XOÁ models `SaasPlan`/`TenantModule` + resources SaasPlans/TenantModules; models mới `Module`,`Feature`,`Plan`,`PlanFeature`(mới),`TenantEntitlement`,`TenantModuleOverride`; sửa `Subscription` (plan_id→Plan); `App\Support\Platform\FeatureGateService`; sửa `DemoDataSeeder::seedTier4Saas`; regenerate Subscription resource + 4 resource mới.

**Tóm tắt:** thay first-cut `saas_plans/plan_features/tenant_modules` (STARTER/PRO/ENT) bằng mô hình addendum: `modules`(M01–M12)+`features`, `plans`(popular/full/intelligent)+`plan_features`(pivot+limits), `tenant_entitlements`, `tenant_module_overrides`; `subscriptions.saas_plan_id`→`plan_id`. `FeatureGateService` giải quyền theo thứ tự plan_features + entitlements + overrides − hết hạn/khoá.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (12 module / 30 feature / 3 plan / 76 plan_feature / 30 entitlement); gate: tenant demo (gói intelligent) có 28 feature, hasFeature(x2ai/rag)=yes, moduleEnabled(M10 override)=no ✓; render 5 /fila → **HTTP 200**.

**Tiếp:** P2 platform content · P3 global account/binding · P4 shared partners · P5 document templates · P6 KB/AI governance.

---

## 2026-07-01 — Slice B7: đóng nốt gap → PHỦ 100% CANONICAL_ENTITY_MAP

**Files:** migration `..._000017_close_entity_gaps`; models `ActivityLog`, `AiRequest`, `AiApproval`, `AutomationStep`, `KnowledgeChunk`; `seedEntityGapClose()`; 3 resource /fila (ActivityLog, AiApproval, AiRequest).

**Tóm tắt:** `activity_logs` (T1, C9) + T6 `ai_requests`, `ai_approvals` (human-in-the-loop), `automation_steps` (bảng hoá steps), `knowledge_chunks` (RAG). Seed: 5 activity, ai_requests từ ai_usage_logs, ai_approvals từ log pending_approval, automation_steps từ steps JSON, knowledge_chunks từ content_text.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 3 /fila → **HTTP 200**.

**✅ HOÀN TẤT TOÀN BỘ ENTITY:** T1 21/21 · T2 40/40 · T3 31/31 · T4 28/28 · T5 25/25 · T6 14/14. **185 bảng.** Mọi entity trong CANONICAL_ENTITY_MAP đã có migration + model + seed; các entity chính đã có resource /fila mặc định. Phân quyền 3 lớp (platform/tenant/project) áp cho Notification + KB (scopeVisibleTo) và cột tenant/project/building trên bảng vận hành.

---

## 2026-07-01 — Batch B / Slice B6: Tier 5 Marketplace/Loyalty/Dịch vụ/BĐS/Smart Home (HOÀN TẤT Tier 5)

**Files:** migration `..._000016_create_marketplace_ecosystem`; 15 models (`MarketplaceProduct/Order/OrderItem`, `ServiceProvider/ServiceOrder`, `LoyaltyAccount/Transaction`, `Voucher`, `RealEstateListing/ListingInquiry`, `SmartHomeAccount/SmartDevice/SmartScene/SensorEvent/EnergyReading`); `seedTier5Ecosystem()`; 8 resource /fila.

**Tóm tắt:** marketplace_products/orders(+items), service_providers/orders, loyalty_accounts/transactions, vouchers, real_estate_listings/inquiries, smart_home_accounts/devices/scenes/sensor_events/energy_readings. Seed đầy đủ demo mỗi bảng.

**Verify:** `php -l` sạch 17 file; `migrate:fresh --seed` sạch; render 8 /fila → **HTTP 200**.

**✅ Tier 5 HOÀN TẤT (25/25). Batch B xong. Tổng 180 bảng.** Coverage: T1 20/21 · T2 40/40 · T3 31/31 · T4 28/28 · T5 25/25 · T6 10/14. Còn: `activity_logs` (optional), T6 `ai_requests/ai_approvals/automation_steps/knowledge_chunks` (hoãn — xem B7).

---

## 2026-07-01 — Batch B / Slice B5: Tier 5 Bàn giao/Bảo hành + Cộng đồng

**Files:** migration `..._000015_create_handover_community`; 12 models (`HandoverBatch/Unit/Checklist/PunchItem`, `WarrantyRequest`, `CommunityGroup/Post`, `Event/EventRegistration`, `Poll/Option/Vote`); `seedTier5Community()`; 5 resource /fila (HandoverBatch, WarrantyRequest, CommunityPost, Event, Poll).

**Tóm tắt:** handover_batches(+units,+checklists,+punch_items), warranty_requests, community_groups/posts, events(+registrations), polls(+options,+votes). Seed 1 đợt bàn giao 6 căn + checklist/punch, 2 bảo hành, 1 nhóm + 3 post, 1 sự kiện + đăng ký, 1 poll + 3 lựa chọn + votes.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 5 /fila → **HTTP 200**.

---

## 2026-07-01 — Batch B / Slice B4: Tier 4 Form Builder (HOÀN TẤT Tier 4)

**Files:** migration `..._000014_create_form_builder`; models `DynamicForm`, `FormVersion`, `FormSection`, `FormField`, `FormWorkflow`, `FormSubmission`, `FormSubmissionValue`; `seedTier4FormBuilder()`; 3 resource /fila (DynamicForm, FormField, FormSubmission).

**Tóm tắt:** `dynamic_forms`(+versions,+sections,+fields,+workflows) + `form_submissions`(+values). Seed 2 biểu mẫu (published) + section/fields/workflow + 2 lượt nộp/mỗi form.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 3 /fila → **HTTP 200**.

**✅ Tier 4 HOÀN TẤT (28/28). Tổng 153 bảng.** Còn Tier 5 (ecosystem 0/25).

---

## 2026-07-01 — Batch B / Slice B3: Tier 4 Nhà thầu + Tài sản + Đồng hồ + IoT

**Files:** migration `..._000013_create_contractors_assets_meters`; 12 models (`Contractor`, `Contract`(+`Package`/`Acceptance`), `ContractorKpi`, `ContractorSettlement`, `AssetCategory`, `Asset`, `MaintenancePlan`, `Meter`(+`Reading`), `IotDevice`); `seedTier4AssetsContractors()`; 7 resource /fila.

**Tóm tắt:** contractors/contracts(+packages,+acceptances) (C7), contractor_kpis, contractor_settlements, asset_categories/assets, maintenance_plans, meters(+readings), iot_devices. Seed 2 nhà thầu + hợp đồng/gói/nghiệm thu/kpi/quyết toán, 4 nhóm + 6 tài sản, 2 kế hoạch bảo trì, 4 đồng hồ + chỉ số, 4 IoT.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 7 /fila → **HTTP 200**.

---

## 2026-07-01 — Batch B / Slice B2: Tier 4 Admin ops

**Files:** migration `..._000012_create_admin_ops`; models `SupportTicket/Comment`, `DataFixRequest`, `ImportJob`, `ExportJob`, `IntegrationConnection`, `PaymentGatewayConfig`; `seedTier4AdminOps()`; 6 resource /fila.

**Tóm tắt:** `support_tickets`(+comments), `data_fix_requests`, `import_jobs`, `export_jobs`, `integration_connections`, `payment_gateway_configs`. Seed 3 ticket, 2 data-fix, 2 import + 2 export, 3 integration, 2 gateway.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 6 /fila → **HTTP 200**.

---

## 2026-07-01 — Batch B / Slice B1: Tier 4 SaaS billing

**Files:** migration `..._000011_create_saas_billing`; models `SaasPlan/PlanFeature`, `Subscription`, `SubscriptionInvoice/Line`, `TenantModule`, `UsageMetering`; `seedTier4Saas()`; 4 resource /fila (SaasPlan, Subscription, SubscriptionInvoice, TenantModule).

**Tóm tắt:** `saas_plans`(+features, platform-global), `subscriptions`, `subscription_invoices`(+lines, C2), `tenant_modules`, `usage_metering`. Seed 3 gói, 1 thuê bao Enterprise + 2 hóa đơn, 5 module, 4 metric usage.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render 4 /fila → **HTTP 200**.

---

## 2026-07-01 — Batch A / Slice A4: Tier 3 An ninh & thiết bị (HOÀN TẤT Tier 3)

**Files:** migration `..._000010_create_security_and_access`; models `PatrolRoute/PatrolCheckpoint/PatrolSession`, `SecurityIncident`, `SosAlert`, `AccessDevice`, `Camera`, `AlertAction`; `DemoDataSeeder::seedTier3Security()`; 5 resource /fila (PatrolRoute, SecurityIncident, SosAlert, AccessDevice, Camera).

**Tóm tắt:** `patrol_routes`(+`checkpoints`,+`sessions`), `security_incidents`, `sos_alerts`, `access_devices`, `cameras`, `alert_actions` (trên ioc_alerts, C10). Seed: 2 tuyến×4 chốt + session, 3 sự cố, 3 SOS, 4 access device, 5 camera, alert actions.

**Bẫy:** đặt tên quan hệ `guard()` trên model đụng `Eloquent\Model::guard(array $guarded)` → Fatal. Đổi thành `guardUser()`.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render `/fila/{patrol-routes,security-incidents,sos-alerts,access-devices,cameras}` → **HTTP 200**.

**✅ Tier 3 HOÀN TẤT (data + /fila). Batch A (Tier 2 vá + Tier 3) xong.** Tiếp: Batch B (Tier 4 + Tier 5).

---

## 2026-07-01 — Batch A / Slice A3: Tier 3 Phê duyệt + Tài chính vận hành

**Files:** migration `..._000009_create_approvals_and_ops_finance`; models `ApprovalRequest/ApprovalStep`, `Fund/FundTransaction`, `PaymentRequest`, `CashVoucher`; `DemoDataSeeder::seedTier3Finance()`; 4 resource /fila (ApprovalRequest, PaymentRequest, CashVoucher, Fund).

**Tóm tắt:** `approval_requests` (đa bước, morph subject) + `approval_steps`; `funds` + `fund_transactions` (số dư luỹ kế); `payment_requests` (đề nghị chi); `cash_vouchers` (phiếu thu/chi). Seed: 2 quỹ, 4 đề nghị chi (mixed), phiếu chi+thu → giao dịch quỹ cập nhật số dư, 3 yêu cầu duyệt × 3 bước.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render `/fila/{approval-requests,payment-requests,cash-vouchers,funds}` → **HTTP 200**.

**Tiến độ Tier 3:** ~26/31 (còn A4: patrol/security/sos/access_devices/cameras/alert_actions).

---

## 2026-07-01 — Batch A / Slice A2: Tier 3 Work Order đầy đủ + SLA + Ca trực

**Files:** migration `..._000008_work_orders_full_and_shifts`; models `WorkOrderAssignment/Checklist/ChecklistItem/Attachment/Signature`, `SlaPolicy`, `Shift`, `DutyRoster` + mở rộng `WorkOrder`; `DemoDataSeeder::seedTier3Ops()`; 4 resource /fila (WorkOrder, SlaPolicy, Shift, DutyRoster).

**Tóm tắt:** Làm giàu `work_orders` (project/apartment/assignee/team/description/category/scheduled/started/completed/cost) + con `work_order_assignments`, `work_order_checklists`(+`_items`), `work_order_attachments` (C6), `work_order_signatures`. `sla_policies` (C4 config). `shifts` + `duty_rosters`. Seed: 8 WO làm giàu + assignment/checklist(3 item)/attachment/signature; 4 SLA; 3 ca × 3 ngày roster.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; render `/fila/{work-orders,sla-policies,shifts,duty-rosters}` → **HTTP 200**.

**Tiến độ Tier 3:** ~20/31 (còn: approvals/funds/cash — A3; patrol/security/sos/devices/cameras — A4).

---

## 2026-07-01 — Batch A / Slice A1: vá nốt Tier 2 (5 bảng + model + seed + /fila)

**Phạm vi:** Lấp entity Tier 2 còn thiếu, kèm seeding + resource /fila mặc định.

**Files:** migration `..._000007_create_tier2_patch`; models `EmergencyAlert`, `QrPaymentToken`, `ServiceEvaluation`, `AccessLog`, `IntercomEvent`; `DemoDataSeeder::seedTier2Patch()`; 5 resource /fila (`make:filament-resource --generate --panel=fila`).

**Tóm tắt:** `emergency_alerts` (băng cảnh báo cư dân), `qr_payment_tokens` (QR thu phí), `service_evaluations` (đánh giá sau xử lý), `access_logs` (ra/vào), `intercom_events` (chuông cửa). Đều BelongsToTenant + scope project/building. Seed: 2 cảnh báo, 5 QR, 5 đánh giá, 12 access log, 5 intercom.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch; 5 route /fila (index/create/edit) đăng ký; render `/fila/{emergency-alerts,access-logs,service-evaluations,qr-payment-tokens,intercom-events}` → **HTTP 200**.

**Tiến độ Tier 2:** 40/40 ✅ (đủ).

---

## 2026-07-01 — Tier 2 (Resident MVP): tạo trọn CSDL các entity còn thiếu (16 bảng)

**Phạm vi:** Lấp Tier 2 theo ENTITY_PRIORITY (MASTER handoff) — CHỈ tầng dữ liệu (migration + model + seed), chưa UI. Phân quyền 3 lớp bake vào schema.

**Files:**
- Migrations (mới): `..._000003_create_notifications`, `..._000004_create_amenities_bookings`, `..._000005_extend_feedback_and_children`, `..._000006_create_visitors_and_packages`.
- Models (mới): `Notification`(+`scopeVisibleTo`/`canManageBy`), `NotificationAudience/Channel/DeliveryLog/Read`, `Amenity/AmenitySlot/AmenityBooking/BookingQrPass`, `FeedbackComment/Attachment/Assignment/StatusHistory`, `VisitorRegistration/VisitorPass`, `PackageDelivery`. Sửa `FeedbackRequest` (+relations/casts).
- `DemoDataSeeder::seedTier2()`.

**Tóm tắt (tên canonical theo CANONICAL_ENTITY_MAP):**
- **Notification (C5)**: `notifications` (owner_level platform|tenant|project, tenant_id nullable cho platform) + `notification_audiences` (all/tenant/project/building/apartment/role/resident/user) + `notification_channels` + `notification_delivery_logs` + `notification_reads`. `scopeVisibleTo`/`canManageBy` theo 3 lớp (giống KB).
- **Amenity**: `amenities` + `amenity_slots` + `amenity_bookings` + `booking_qr_passes`. Scope tenant/project/building.
- **Feedback (C3)**: làm giàu `feedback_requests` (project_id/resident_id/user_id/code/description/channel/assigned_to/team/sla/resolved/closed/rating) + `feedback_comments`/`feedback_attachments`/`feedback_assignments`/`feedback_status_histories`.
- **Visitor (C12)**: `visitor_registrations` + `visitor_passes`. **Package**: `package_deliveries`.
- Mọi bảng vận hành mang tenant_id+project_id+building_id để RBAC 3 lớp lọc (platform tất cả · công ty toàn tenant · BQL dự án mình). Đã có Invoice/Fee/Payment/Receipt từ trước ⇒ Tier 2 data coi như đủ.

**Verify:** `php -l` sạch 24 file; `migrate:fresh --seed` sạch. Counts: notifications 5 / audiences 5 / channels 6 / delivery_logs 8 / reads 4 · amenities 4 / slots 8 / bookings 6 / qr 3 · feedback_comments 12 / attachments 3 / assignments 6 / histories 6 · visitor_reg 4 / passes 3 · packages 5. Relations traverse OK (amenity→slots/bookings→qrPass; feedback→comments/assignments/history/assignee; visitor→passes; notification→audiences/channels/reads + recipient/read count). **3-tier Notification::visibleTo**: superadmin 5/5; BQL thấy platform-published + tenant-published + toàn bộ dự án mình, không lộ draft cấp trên.

**Còn lại:** chưa có UI cho Tier 2 (đúng phạm vi yêu cầu — chỉ tạo CSDL). GuestPass = visitor_passes (đã có); PackageDelivery xong.

---

## 2026-07-01 — Fix lỗi SQL 1366 khi lưu content_text (PDF sinh UTF-8 không hợp lệ)

**Phạm vi:** Sửa `QueryException 1366 Incorrect string value '\xED\xA0\xBD...'` khi cập nhật/tạo bài KB có đính kèm PDF.

**Files đổi:** `app/Support/Knowledge/DocumentTextExtractor.php`.

**Tóm tắt:**
- **Nguyên nhân**: cột `content_text` LÀ utf8mb4 (chấp nhận emoji 4-byte hợp lệ), nhưng `smalot/pdfparser` trích ra **CESU-8 / lone surrogate** (`\xED\xA0\xBD\xED\xB4\xB4` = cặp surrogate của 🔴) — đây KHÔNG phải UTF-8 hợp lệ nên MySQL từ chối (1366), không phụ thuộc charset.
- **Sửa**: thêm `DocumentTextExtractor::clean()` = `iconv('UTF-8','UTF-8//IGNORE')` (bỏ chuỗi byte lỗi, GIỮ emoji hợp lệ) + strip ký tự điều khiển. Áp dụng ở `htmlToText()`, `fromPdf()` và output `build()` ⇒ mọi `content_text` (create + edit, cả seeder) đều là UTF-8 sạch trước khi lưu.

**Verify (script + DB thật):** `clean()` trên chuỗi có surrogate+emoji+ctrl → UTF-8 hợp lệ, giữ 🔴, bỏ surrogate & ctrl. `KnowledgeArticle::update(content_text=sanitized)` = OK; update bằng byte gốc vẫn lỗi 1366 (đúng kỳ vọng). `php -l` sạch. Code PHP có hiệu lực ngay ở request kế (không cần restart serve).

**Lưu ý:** không cần reseed (content_text seed sinh từ body sạch). Hard-refresh & thử upload lại.

---

## 2026-07-01 — Fix upload tệp KB bị 302 (php.ini máy ADMIN)

**Phạm vi:** Sửa lỗi upload tệp trên khung Livewire trả về **302** (không lưu được).

**Files đổi:** `C:\Users\ADMIN\.config\herd\bin\php84\php.ini` (ngoài repo).

**Tóm tắt:**
- **Nguyên nhân**: `FileUploadController@handle` gọi `Validator::validate()`; khi tệp bị PHP loại bỏ vì vượt `upload_max_filesize`/`post_max_size`, request (không phải JSON) → ValidationException → **redirect 302**. Máy này (profile **ADMIN**) vẫn để mặc định `upload_max_filesize=2M`, `post_max_size=8M` (bản vá trước đó nằm ở profile `chtch`, không áp cho ADMIN) → tệp >2MB bị chặn.
- **Sửa**: nâng `upload_max_filesize=20M`, `post_max_size=25M` trong php.ini mà máy nạp (`php_ini_loaded_file` = `C:\Users\ADMIN\.config\herd\bin\php84\php.ini`), rồi **restart `php artisan serve`** (process cũ giữ giá trị 2M cho tới khi khởi động lại).

**Verify:** `php -r ini_get(...)` → `upload_max_filesize=20M | post_max_size=25M`; kill process serve cũ (PID cũ) + chạy lại `php artisan serve` (server báo running); probe `/admin` → 302 (redirect login, bình thường). FileUpload KB đặt `maxSize(10240)` (10MB) < 20M nên quá cỡ sẽ báo lỗi ở client thay vì 302.

**Lưu ý:** nếu chạy qua Herd FPM/domain khác (không phải `php artisan serve`), FPM cũng cần restart để nạp php.ini mới. Hard-refresh trình duyệt trước khi thử lại.

**Cập nhật — nguyên nhân thứ 2 (temp dir):** sau khi nâng size vẫn 302, log server báo `PHP Warning: File upload error - unable to create a temporary file`. `upload_tmp_dir` để trống → PHP dùng system temp; system temp Windows vẫn ghi được, NHƯNG lỗi xuất hiện khi **khởi động `php artisan serve` từ tool Bash (Git Bash)** — Git Bash xuất `TMP`/`TEMP` kiểu MSYS (`/tmp`…) mà PHP trên Windows không tạo file được. Sửa: (1) ghim `upload_tmp_dir` + `sys_temp_dir` = `C:\Users\ADMIN\AppData\Local\Temp` trong php.ini (xác định, không phụ thuộc shell); (2) **luôn chạy `php artisan serve` từ PowerShell** (env Windows chuẩn), KHÔNG chạy từ Bash tool. Verify: `ini_get('upload_tmp_dir')` = `C:\Users\ADMIN\AppData\Local\Temp`, `tempnam` OK; server chạy lại từ PowerShell, `/admin` → 302 login.

---

## 2026-07-01 — KB 3 cấp + X2AI đọc nội dung tệp KB (tool search_knowledge)

**Phạm vi:** Phân quyền Cơ sở tri thức theo RBAC 3 tầng + để X2AI đọc/tra cứu tài liệu KB (gồm text từ PDF/DOCX) trong phạm vi quyền.

**Files đổi:**
- `composer.json` (+`smalot/pdfparser` ^2.12 — trích text PDF)
- `database/migrations/2026_07_01_000002_knowledge_3tier_ownership.php` (mới)
- `app/Models/KnowledgeArticle.php` (bỏ BelongsToTenant → `scopeVisibleTo` + `canManageBy`), `app/Models/KnowledgeArticleShare.php` (mới)
- `app/Support/Knowledge/DocumentTextExtractor.php` (mới), `app/Support/X2AI/X2aiKnowledgeConnector.php` (mới)
- `app/Support/X2AI/X2aiClient.php` (+`knowledgeSearchTool` + runTool), `app/Livewire/X2aiChat.php` (bật tool + system prompt)
- `app/Filament/Pages/AiKnowledgeBase.php` (query visibleTo, owner/share cột+filter, gán owner khi tạo, trích content_text, action Chia sẻ, gate canManageBy), `app/Filament/Pages/AiCenter.php` + `AiGovernance.php` (KB count theo visibleTo)
- `database/seeders/DemoDataSeeder.php` (owner_level/share/content_text + tài liệu platform + dự án khác)

**Tóm tắt:**
- **3 cấp sở hữu** `owner_level` platform|tenant|project + `share_mode` private|descendants|custom + bảng `knowledge_article_shares` (chia sẻ tùy chọn tới tenant/project). `tenant_id` nới thành nullable (tài liệu platform). `scopeVisibleTo($user)`: superadmin thấy tất cả; công ty (tenant-op) thấy mọi tài liệu công ty + dự án trong tenant + tài liệu platform chia sẻ xuống; BQL chỉ thấy tài liệu dự án mình + tài liệu công ty/platform chia sẻ xuống. `canManageBy()` gate sửa/chia sẻ. UI: cột Cấp/Chia sẻ, filter, action **Chia sẻ** (platform chọn công ty+dự án; công ty chọn dự án), owner gán tự động theo cấp người tạo.
- **X2AI đọc tệp**: `DocumentTextExtractor` trích text (PDF smalot · DOCX ZipArchive · HTML strip) → lưu `content_text` khi tạo/sửa bài. Tool `search_knowledge` (X2aiClient) → `X2aiKnowledgeConnector` tìm trong `KnowledgeArticle::visibleTo(user)` (tôn trọng quyền 3 cấp), trả text cho model. Tool luôn bật ở mọi lượt chat; system prompt hướng dẫn dùng + trích dẫn tên tài liệu.

**Bẫy (lặp lại):** cột Filament closure param PHẢI tên `$state` — đặt `$s` cho owner_level/share_mode → 500 "unresolvable [$s]". Đã sửa.

**Verify:** `php -l` sạch; `migrate:fresh --seed` sạch (22 bài: 3 platform/3 công ty/16 dự án + 2 dự án khác, 1 share row, content_text 22/22). Script phân quyền: Superadmin thấy 22/22; **BQL dự án thấy 20/22 (ẩn 2 bài dự án khác, leak=0)**, thấy platform+công ty chia sẻ xuống; tenant-op thấy đủ 19 tài liệu công ty+dự án. `canManageBy`: BQL không quản lý được tài liệu dự án khác/platform (OK). Form dựng `RichEditor`+`FileUpload`; shareFormSchema platform=3 select / tenant=2 select. Extractor htmlToText OK, pdfparser=yes, tệp thiếu → rỗng. `view:cache`+`npm run build` OK; **4 màn HTTP 200**.

**Còn lại:** DOC nhị phân cũ không trích được (chỉ PDF/DOCX). Trích text chạy đồng bộ lúc lưu (tệp lớn có thể chậm — cân nhắc queue sau). Chưa browser-test upload thật + tool trả lời trong chat.

---

## 2026-07-01 — KB: soạn HTML (RichEditor) + đính kèm PDF/DOC + click tiêu đề/danh mục ở listing

**Phạm vi:** Nâng cấp form & bảng Cơ sở tri thức (WEB-UX-09-04).

**Files đổi:**
- `database/migrations/2026_07_01_000001_add_attachments_to_knowledge_articles.php` (mới — cột `attachments` json)
- `app/Models/KnowledgeArticle.php` (cast `attachments => array`)
- `app/Filament/Pages/AiKnowledgeBase.php`
- `resources/views/filament/kb/article-view.blade.php` (mới — modal xem)

**Tóm tắt:**
- Ô **Nội dung** đổi `Textarea` → **`RichEditor`** (soạn HTML; toolbar gọn: bold/italic/underline/strike/h2/h3/list/link/blockquote/codeBlock/undo/redo).
- Thêm **`FileUpload` đính kèm nhiều tệp** PDF/DOC/DOCX (disk `public`, thư mục `kb-attachments`, `preserveFilenames`, `multiple`+`appendFiles`+`reorderable`, ≤10MB/tệp) → lưu mảng path vào `attachments`. Prefill khi sửa (`fillForm` thêm `attachments`).
- **Listing**: cột **Tiêu đề** bấm được → mở modal **Xem** (render HTML nội dung + danh sách tệp tải/mở được, qua `viewArticleAction()` + partial `filament.kb.article-view`); cột **Danh mục** bấm được → `filterByCategory()` set `tableFilters['knowledge_category_id']` lọc bảng.

**Verify:** `migrate` (thêm cột) OK; `php -l` sạch; `view:cache` + `npm run build` OK; **4 màn render HTTP 200**; script: `articleFormSchema` dựng `RichEditor(body)`+`FileUpload(attachments)` OK, `viewArticleAction` OK, partial render OK (có/không tệp), cast `attachments` round-trip 2 tệp + link hiển thị OK.

**Còn lại:** tệp đính kèm hiện chỉ **lưu + tải/mở**; muốn **X2AI đọc nội dung tệp** cần bước trích text PDF/DOC (chưa làm). Nút file-upload/RichEditor/modal mới verify ở mức dựng+render, nên bấm thử trên trình duyệt 1 lượt (upload thật + submit).

---

## 2026-07-01 — AI Engine: nối write-actions (A3) — tạo/sửa workflow, bật-tắt policy/prompt, CRUD bài KB

**Phạm vi:** Biến 3 màn AI Engine từ đọc-thuần thành có thao tác ghi dữ liệu (thật, có audit).

**Files đổi:**
- `app/Filament/Concerns/WritesAudit.php` (mới — helper ghi `audit_logs` cho page)
- `app/Filament/Pages/AiKnowledgeBase.php` + `resources/views/filament/pages/ai-knowledge-base.blade.php`
- `app/Filament/Pages/AiGovernance.php` + `resources/views/filament/pages/ai-governance.blade.php`
- `app/Filament/Pages/AiWorkflowAutomation.php` + `resources/views/filament/pages/ai-workflow-automation.blade.php`

**Tóm tắt:**
- **KB (09-04):** table actions đầy đủ — header `Thêm bài viết` + `Thêm danh mục` (modal schema), record `Sửa`/`Xuất bản`/`Lưu trữ`, bulk `Xuất bản`/`Lưu trữ`/`Xóa`. `syncCategoryCount()` cập nhật `knowledge_categories.articles_count` sau mỗi thay đổi; `published_at` set khi publish.
- **Governance (09-02):** header action `Thêm chính sách` (modal); nút **Bật/Tắt** từng policy (`togglePolicy`) ở tab Chính sách và từng prompt (`togglePrompt`) ở tab Prompt — Livewire `wire:click` (page = Livewire component).
- **Workflow (09-03):** header `Tạo workflow` (modal, set steps mặc định + project từ CurrentContext + created_by); per-workflow `Sửa` qua `mountAction('editWorkflow', { id })` (action method `editWorkflowAction()` + `fillForm` theo arguments, render ẩn `{{ $this->editWorkflowAction }}` để đăng ký modal); `Tạm dừng/Kích hoạt` (`toggleWorkflow`); `Chạy thử` (`runWorkflow` → ghi 1 `ai_workflow_runs` + tăng runs/success + last_run_at).
- Mọi hành động ghi 1 dòng `audit_logs` qua trait `WritesAudit`.

**Bẫy:** nút thao tác nằm trong `<x-slot:action>`/blade của bespoke page vẫn là Livewire → `wire:click` gọi method public OK; action modal có tham số dùng `mountAction(name, {args})` + method `nameAction()` (Filament v5 tự resolve), phải render `{{ $this->nameAction }}` (ẩn cũng được) để modal tồn tại.

**Verify:** `php -l` sạch 4 file; `view:cache` compile sạch; `npm run build` OK; **4 màn render HTTP 200**; script logic (kernel + auth): togglePolicy active↔inactive OK, togglePrompt OK, toggleWorkflow active→paused OK, runWorkflow runs_count+1 & +1 run row OK, KB create + syncCount OK, setStatus publish set published_at OK, xóa khôi phục count OK, 8 dòng audit ghi nhận.

**Còn lại:** form modal (header create + edit-workflow) mới verify ở mức render + closure; nên click thử trên trình duyệt 1 lượt. Steps của workflow chưa cho sửa trong form (giữ template mặc định).

---

## 2026-07-01 — X2AI Copilot: 2 icon (Mới + Lịch sử) lên header, input quay lại đáy

**Phạm vi:** Bố trí lại khung chat.

**Files đổi:**
- `app/Livewire/X2aiChat.php`
- `resources/views/components/x2/ai-fab.blade.php`
- `resources/views/livewire/x2ai-chat.blade.php`

**Tóm tắt:**
- Chuyển 2 nút **Cuộc trò chuyện mới** + **Lịch sử** lên cụm header (cạnh icon phóng to/đóng).
  Header nằm ngoài component Livewire → 2 nút gọi qua `@click="Livewire.dispatch('x2ai-new-chat'|'x2ai-history')"`;
  thêm `#[On('x2ai-new-chat')]` / `#[On('x2ai-history')]` cho `newChat()` / `toggleHistory()`.
- Đưa **ô input xuống đáy** (pinned), vùng dữ liệu/hội thoại lên trên cuộn. Bỏ hàng action cũ ở thân.
- max-height vùng cuộn chỉnh về `calc(66vh - 7.5rem)` (header + input đáy).

**Verify:** `php -l` sạch; `npm run build` (Node 22) OK; `view:cache` compile sạch. Logic phiên/lịch sử
đã verified ở entry trước (method không đổi, chỉ thêm listener event).

**Lưu ý:** hard-refresh trình duyệt.

---

## 2026-07-01 — X2AI Copilot: phiên chat + nút Lịch sử, đảo bố cục (input trên cùng) để scroll chắc chắn

**Phạm vi:** Lịch sử chat theo PHIÊN + sửa dứt điểm lỗi không scroll.

**Files đổi:**
- `database/migrations/2026_06_30_000013_create_ai_chat_sessions.php` (mới)
- `app/Models/AiChatSession.php` (mới), `app/Models/AiChatMessage.php` (+ quan hệ session)
- `app/Livewire/X2aiChat.php`
- `resources/views/livewire/x2ai-chat.blade.php`
- `resources/views/components/x2/ai-fab.blade.php`

**Tóm tắt:**
- **Phiên chat**: bảng `ai_chat_sessions` (title/surface/last_message_at, per user+tenant) + cột
  `ai_chat_session_id` trên `ai_chat_messages` (ADD-ONLY). Mỗi lần mở trang = bắt đầu phiên mới
  (tạo lazy ở tin nhắn đầu, title = prompt đầu, surface = màn hình). `mount()` KHÔNG còn nạp lịch sử
  phẳng — bắt đầu trống.
- **Nút Lịch sử** trên khung chat: `toggleHistory()` mở danh sách phiên (50 gần nhất, theo
  last_message_at); `loadSession($id)` mở lại phiên (verify user_id); `newChat()` tạo phiên mới.
- **Đảo bố cục (theo yêu cầu)**: ô input + hàng action (Lịch sử / Cuộc trò chuyện mới) **nổi trên cùng**;
  vùng dữ liệu/hội thoại tách riêng bên dưới, cuộn độc lập.
- **Scroll chắc chắn (không phụ thuộc build CSS)**: popover dùng inline `style="height:66vh"` (`:class`
  chỉ đổi width); vùng dữ liệu dùng inline `style="max-height:calc(66vh - 9.5rem)"` + `overflow-y-auto`.
- Fix: thiếu `use App\Models\AiChatSession` trong component (lỗi bị `report()` nuốt → phiên không tạo).

**Verify (tinker):**
- Fresh mount: messages=0, sessionId=null. Submit → tạo phiên #1, surface=`admin/residents`, 1 msg.
- Trang mới: bắt đầu trống; Lịch sử liệt kê đúng phiên (title/time); `loadSession` nạp lại đúng nội dung.
- `php -l` sạch; `migrate` tạo bảng + cột OK; `npm run build` (Node 22) OK; `view:cache` sạch.

**Lưu ý:** hard-refresh trình duyệt. Vì chiều cao/scroll giờ là inline-style (không qua Tailwind build),
không còn phụ thuộc cache CSS.

---

## 2026-06-30 — Sidebar: bỏ user card ở chân + ẩn thanh scroll

**Phạm vi:** Chrome sidebar Filament `/admin`.

**Files đổi:**
- `app/Providers/Filament/AdminPanelProvider.php`
- `resources/views/filament/hooks/sidebar-footer.blade.php` (xóa)
- `resources/css/filament/admin/theme.css`

**Tóm tắt:**
- Bỏ block người dùng (avatar + tên + chức danh) ở chân sidebar: gỡ render hook
  `PanelsRenderHook::SIDEBAR_FOOTER`; xóa blade `sidebar-footer`; dọn CSS chết
  (`.fi-sidebar-footer`, `.x2-user*`).
- Ẩn thanh scroll sidebar (vẫn cuộn được): `.fi-sidebar(-nav)` `scrollbar-width:none` +
  `::-webkit-scrollbar{display:none}`.

**Verify:**
- `php -l` sạch; `npm run build` (Node 22) OK; CSS build chứa `scrollbar-width:none` +
  `fi-sidebar-nav::-webkit-scrollbar`; không còn tham chiếu `sidebar-footer`/`SIDEBAR_FOOTER`.

**Lưu ý:** hard-refresh trình duyệt.

---

## 2026-06-30 — X2AI Copilot: lưu lịch sử chat theo tài khoản + fix scroll/input biến mất

**Phạm vi:** Lưu lịch sử chat per-account; sửa lỗi vùng nội dung không cuộn + ô input biến mất.

**Files đổi:**
- `database/migrations/2026_06_30_000012_create_ai_chat_messages.php` (mới)
- `app/Models/AiChatMessage.php` (mới)
- `app/Livewire/X2aiChat.php`
- `resources/views/livewire/x2ai-chat.blade.php`

**Tóm tắt:**
- **Lịch sử chat theo tài khoản**: bảng `ai_chat_messages` (tenant_id/user_id/role/content, ADD-ONLY) +
  model `AiChatMessage`. `mount()` gọi `loadHistory()` (100 lượt gần nhất của user, assistant render lại
  Markdown→html). `submit()` lưu lượt user, `pushAssistant()` lưu lượt assistant (best-effort, try/catch).
  History gửi cho API giới hạn 16 lượt gần nhất (`array_slice`) để chặn token phình.
- **Fix scroll + input biến mất**: nguyên nhân chuỗi `flex-1/min-h-0` không khóa được chiều cao qua
  ranh giới component Livewire → vùng cuộn nở theo nội dung, đẩy input ra ngoài `overflow-hidden`.
  Thêm trần cứng theo viewport cho vùng cuộn: `max-h-[calc(66vh_-_7.5rem)]` (panel 66vh − header/input)
  → luôn cuộn được và input luôn hiển thị, không phụ thuộc flex.

**Verify:**
- `php -l` sạch; `migrate` tạo `ai_chat_messages` OK; `npm run build` (Node 22) OK, CSS có
  `calc(66vh - 7.5rem)`; `view:cache` compile sạch.
- Tinker: `submit()` → 1 dòng DB; component mới `mount()` đọc lại đúng (role=user, nội dung khớp).

**Lưu ý:** hard-refresh trình duyệt (asset mới).

---

## 2026-06-30 — X2AI Copilot: chat 2 bước (ChatGPT-style), chiều cao 2/3 màn hình, fix upload file

**Phạm vi:** UX khung chat + sửa lỗi không tải được file đính kèm.

**Files đổi:**
- `app/Livewire/X2aiChat.php`
- `resources/views/livewire/x2ai-chat.blade.php`
- `resources/views/components/x2/ai-fab.blade.php`
- `C:\Users\chtch\.config\herd\bin\php84\php.ini` (ngoài repo — cấu hình máy dev)

**Tóm tắt:**
- Chiều cao mặc định khung chat đổi `h-[86.4rem] max-h-[85vh]` → **`h-[66vh]`** (2/3 màn hình);
  bản mở rộng vẫn `w-[50vw] h-[66vh]`. Nội dung cuộn, input ghim đáy (giữ nguyên).
- **Chat 2 bước kiểu ChatGPT**: tách `send()` → `submit()` (hiện bong bóng prompt NGAY, gom
  pendingText/screenText, set `awaitingReply`, KHÔNG gọi API) + `generate()` (gọi model, append reply).
  `generate()` được kích bởi `x-init="$wire.generate()"` trên phần tử "thinking" (key theo số message).
  Input/nút khóa khi `awaitingReply`. Tự cuộn xuống đáy qua event `x2ai-scroll` (dispatch ở
  submit + pushAssistant, Alpine `x-on:x2ai-scroll.window`). Gate/approval/log chuyển sang `generate()`.
- **Fix upload file**: nguyên nhân `upload_max_filesize=2M` (< rule 10MB) trong php.ini của Herd
  → ảnh >2MB bị PHP chặn trước khi tới Livewire. Nâng `upload_max_filesize=20M`, `post_max_size=25M`.

**Verify:**
- `php -l` sạch; `npm run build` (Node 22) OK; `view:cache` compile sạch.
- Tinker: `submit()` → 1 message (role=user), awaiting=1, input rỗng, pendingText giữ, KHÔNG gọi API;
  `generate()` guard no-op khi không awaiting. php.ini sau sửa: upload=20M post=25M.

**Lưu ý:** **phải restart `php artisan serve` (và Herd nếu chạy FPM)** để php.ini mới có hiệu lực.
Hard-refresh trình duyệt sau build.

---

## 2026-06-30 — X2AI Copilot: permission/risk gate + UX chat (input đáy, markdown, bỏ toggle, cao gấp đôi)

**Phạm vi:** Mục 3 governance gate + 4 yêu cầu UX khung chat.

**Files đổi:**
- `app/Support/X2AI/X2aiPolicyGate.php` (mới)
- `app/Livewire/X2aiChat.php`
- `database/seeders/DemoDataSeeder.php`
- `resources/views/livewire/x2ai-chat.blade.php`
- `resources/views/components/x2/ai-fab.blade.php`

**Tóm tắt:**
- `X2aiPolicyGate` (mới): quyết định từ RBAC + `ai_policies` (active, không hardcode):
  `canUse` (perm `ai.use`, mặc định mở nếu chưa seed), `dataLookupAllowed` (perm `ai.data_lookup`
  **và** đã cấu hình `X2AI_DATA_API_URL` — chưa có thì ở chế độ context để khỏi gọi tool stub),
  `effectiveMode`, `riskFor`, `requiresApproval` (high + chính sách risk/high active → cần duyệt),
  `guidelines` (đẩy các chính sách active vào system prompt).
- Seeder: tạo 2 permission `ai.use` / `ai.data_lookup`; cấp `ai.use` cho mọi role, `ai.data_lookup`
  cho company_admin/hq_finance/operations_director/building_manager/accountant/customer_service.
- `X2aiChat`: bỏ toggle (mode theo quyền, set ở `mount`/`send`); gate `canUse` (chặn → log `rejected`),
  `requiresApproval` (→ log `pending_approval`, không gọi model); `logUsage()` nhận thêm mode/status/risk/
  requiresApproval; reply render Markdown→HTML an toàn (`GithubFlavoredMarkdownConverter`, html_input=strip)
  lưu sẵn `html`; system prompt thêm guidelines + yêu cầu định dạng Markdown, bỏ wording toggle.
- UI: bỏ 2 nút chọn chế độ; bố cục flex — hội thoại cuộn phía trên, **input ghim đáy**; chiều cao
  mặc định **gấp đôi** (`h-[86.4rem] max-h-[85vh]`, vẫn cap viewport), nút Mở rộng giữ `w-[50vw] h-[66vh]`;
  thêm CSS `.x2ai-prose` (bảng/heading/list/code đẹp).

**Verify:**
- `php -l` sạch 3 file PHP; `php artisan view:cache` compile sạch; `npm run build` (Node 22) OK,
  class `86.4rem/85vh/50vw/66vh` có trong CSS.
- `migrate:fresh --seed` OK. Tinker: ai.use/ai.data_lookup tồn tại; super_admin canUse=yes,
  effectiveMode=context (chưa có data API); 6 chính sách active → guidelines; requiresApproval(high)=yes;
  Markdown sinh `<table>`+`<strong>`; `X2aiChat::mount()` chạy OK (mode=context).

**Lưu ý:** cần hard-refresh trình duyệt. Mode `data` (Mode 2) sẽ tự bật khi cấu hình `X2AI_DATA_API_URL`
và user có quyền `ai.data_lookup`.

---

## 2026-06-30 — X2AI Copilot: nối ai_usage_logs + UI khung chat 2 kích thước

**Phạm vi:** Module AI Copilot (WEB-UX-09) — audit usage thật + nâng cấp UI.

**Files đổi:**
- `app/Support/X2AI/X2aiClient.php`
- `app/Livewire/X2aiChat.php`
- `resources/views/components/x2/ai-fab.blade.php`

**Tóm tắt:**
- `X2aiClient::ask()` nay thu thập telemetry mỗi lượt: `lastInputTokens`/`lastOutputTokens`
  (cộng dồn qua vòng lặp tool-use), `lastLatencyMs`, `lastModel`, `lastStatus`
  (`success`/`failed` ở mọi nhánh: thiếu key, HTTP fail, exception).
- `X2aiChat::send()` sau mỗi lượt ghi 1 dòng `AiUsageLog` qua `logUsage()`:
  tenant/project/building/user (auto-scope `BelongsToTenant`), surface (title màn/URL DOM),
  mode, model, action, risk_level (data=medium · context=low), status, token in/out,
  latency_ms, prompt/response_excerpt, cost quy đổi VND theo giá list từng model.
  Bọc try/catch → lỗi ghi log không làm hỏng câu trả lời.
  ⇒ Màn AiGovernance (09-02) tab Audit và AiCenter (09-01) phản ánh usage THẬT, không chỉ seed.
- `ai-fab.blade.php`: chiều cao mặc định gấp đôi (`max-h-[21.6rem]` → `max-h-[43.2rem]`);
  thêm nút "Mở rộng" (Alpine `expanded`) → panel `w-[50vw] h-[66vh]` (½ rộng × ⅔ cao viewport),
  body `flex-1` cuộn trong; tắt → về compact.

**Verify:**
- `php -l` sạch cả 2 file PHP.
- `npm run build` (Node 22) OK; class tùy biến `50vw`/`66vh`/`43.2rem` có trong CSS build.
- Tinker insert/delete `ai_usage_logs`: `inserted id=91 cost=0.36 before=90` → `deleted; now=90` (schema khớp).

**Lưu ý:** dòng live tính giá haiku $1/$5 per M (chính xác hơn) nên rẻ hơn dòng seed ($3/$15).
Cần hard-refresh trình duyệt sau build.

**Còn lại liên quan:** mục 3 — permission/risk gate qua `ai_policies` (chưa làm).

---

## 2026-06-30 — Slice AI Engine: 7 bảng + 4 màn bespoke "X2 AI Engine" (WEB-UX-09-01→04)

**Phạm vi:** Dựng cả mục "X2 AI Engine" trên `/admin` (data-model-first đầy đủ, chủ dự án chốt cả 4 màn).

**Files đổi:**
- `database/migrations/2026_06_30_000011_create_ai_engine_tables.php` (mới)
- `app/Models/AiUsageLog.php`, `AiPolicy.php`, `AiPromptTemplate.php`, `AiWorkflow.php`, `AiWorkflowRun.php`, `KnowledgeCategory.php`, `KnowledgeArticle.php` (mới)
- `database/seeders/DemoDataSeeder.php` (`seedAiEngine`)
- `app/Providers/Filament/AdminPanelProvider.php` (nav group 'X2 AI Engine' + 'Tài chính – Phí')
- `app/Filament/Pages/AiCenter.php`, `AiGovernance.php`, `AiWorkflowAutomation.php`, `AiKnowledgeBase.php` + 4 blade `resources/views/filament/pages/ai-*.blade.php` (mới)
- `resources/views/components/x2/ai-fab.blade.php` (nghe `x2ai-open`), `app/Livewire/X2aiChat.php` (`#[On('x2ai-prefill')]`)

**Tóm tắt:**
- Migration ADD-ONLY 7 bảng: `ai_usage_logs` (audit từng lượt), `ai_policies`, `ai_prompt_templates`, `ai_workflows`(+steps json)/`ai_workflow_runs`, `knowledge_categories`/`knowledge_articles`. Model dùng `BelongsToTenant` (trừ `AiWorkflowRun`).
- Seed `seedAiEngine`: 90 usage log/30 ngày, 7 chính sách, 8 prompt, 6 workflow + runs, 6 danh mục / 17 bài KB.
- 4 Page bespoke, KPI/biểu đồ TÍNH từ DB (không hardcode): `AiCenter` (`ai/center`, 09-01), `AiGovernance` (`ai/governance`, 09-02 — tab Alpine, tab Audit = HasTable trên `ai_usage_logs`), `AiWorkflowAutomation` (`ai/workflows`, 09-03 — chọn workflow → canvas node từ `steps` + cấu hình + nhật ký), `AiKnowledgeBase` (`ai/knowledge`, 09-04 — HasTable bài viết + danh mục + Support Copilot CTA).
- Nút "Gợi ý nhanh"/Support Copilot → window event `x2ai-open` (FAB nghe `x-on:x2ai-open.window`) + Livewire `x2ai-prefill` → `X2aiChat::prefill()`.

**Verify:** `migrate:fresh --seed` sạch; `php -l` sạch; `getViewData()` chạy được cả 4; `view:cache` compile sạch; `npm run build` OK; **4 màn render HTTP 200** (đã đăng nhập admin, headless kernel).

**Lưu ý:** đây là khung đọc + duyệt; action ghi dữ liệu (tạo/sửa workflow, bật-tắt policy, thêm bài KB) CHƯA nối. (Usage logging thật + policy gate được bổ sung ở các entry phía trên.)

---

## 2026-07-01 — SuperAdmin WEB-UX-22 Slice 0+1: nền móng + xương sống định danh

**Phạm vi:** Khởi động track SuperAdmin (gói addendum). Slice 0 = nền móng gating; Slice 1 = luồng định danh (rule #1: tài khoản gốc → duyệt gắn căn → thành cư dân). Ưu tiên theo nghiệp vụ + độ đầy đủ dữ liệu.

**Files mới/đổi:**
- `app/Providers/Filament/AdminPanelProvider.php` — thêm nav group **'Nền tảng (SuperAdmin)'**.
- `app/Filament/Concerns/PlatformScreen.php` (mới) — trait gating: `canAccess()`/`shouldRegisterNavigation()`; SuperAdmin (isPlatformAdmin) thấy tất; HQ chỉ thấy khi `platformFeature()` được gói bật qua `FeatureGateService` (KHÔNG hardcode gói). Bẫy: KHÔNG redeclare property trait ở class con (default khác → Fatal "define the same property … incompatible") → dùng method `platformFeature()` override.
- `app/Filament/Pages/GlobalUserRegistry.php` + `resources/views/filament/pages/global-user-registry.blade.php` + `account-profile.blade.php` (mới) — **WEB-UX-22-04**. HasTable trên `global_user_accounts`, feature `global_account`. 5 KPI (tổng/định danh/chưa gắn căn/nghi trùng/khoá), lọc loại+định danh+toggle trùng/khoá, action: xem hồ sơ (modal: định danh + căn đã gắn + yêu cầu + nghi trùng), verify định danh, khoá (bắt lý do)/mở khoá, tạo yêu cầu gắn căn.
- `app/Filament/Pages/ResidentBindingQueue.php` + `resources/views/filament/pages/resident-binding-queue.blade.php` + `binding-detail.blade.php` (mới) — **WEB-UX-22-05**. HasTable trên `resident_binding_requests`, feature `resident_binding`. 4 KPI theo trạng thái, lọc trạng thái (mặc định pending)+vai trò, detail modal (hồ sơ + căn + minh chứng + cảnh báo trùng SĐT/email/căn + binding trước đó), action: Duyệt (→ tạo `resident_unit_binding` idempotent + `public_user`→`resident`), Yêu cầu bổ sung, Từ chối (bắt lý do), Phân công duyệt.
- `database/seeders/DemoDataSeeder.php` (`seedGlobalAccounts`) — enrich: 12 tài khoản (đa định danh/loại, 1 khoá, 1 cặp nghi trùng DUP-01, risk cao), 10 yêu cầu phủ đủ 5 trạng thái, 1 tài khoản gắn 2 căn (AC-07).

**Scope FK:** cột là `user_account_id` (KHÔNG phải `account_id`) — relation `account()` trỏ FK này.

**Verify:** `migrate:fresh --seed` sạch (accounts=12, requests=10 đủ 5 trạng thái, bindings=2); `php -l` sạch; `view:cache` compile; **2 màn render HTTP 200** (đăng nhập platform admin, headless kernel); script logic: Duyệt tạo binding + đổi type + idempotent, Từ chối có lý do, phát hiện trùng DUP-01, Verify, tạo yêu cầu, 4 dòng audit (binding.approve/reject/create + account.*). Đạt AC-01..08.

**NEXT:** Slice 2 = 22-01 Platform Content Dashboard (control tower, tổng hợp slice 1 + content). Rồi Slice 3 content (22-02/03), Slice 4 thư viện (22-06..09), Slice 5 KB/AI (22-10..12). Chưa browser-click modal submit.

---

## 2026-07-01 — SuperAdmin WEB-UX-22 Slice 2–5: HOÀN TẤT 12/12 màn

**Phạm vi:** Dựng nốt 10 màn SuperAdmin còn lại (bespoke `/admin`, nav group 'Nền tảng (SuperAdmin)', gate qua trait `PlatformScreen`). Làm lần lượt theo nghiệp vụ.

**Slice 2 — Control tower:**
- `PlatformContentDashboard` (`platform/dashboard`, 22-01) — 7 KPI + 3 chart (content theo loại / KB theo scope / TK mới theo tuần) + 3 worklist (content chờ duyệt / binding chờ / index AI lỗi) + quick actions. Tất cả tính từ DB.

**Slice 3 — Content nền tảng:**
- `PlatformContentCms` (`platform/content`, 22-02) — CRUD + vòng đời draft→pending_review→published→archived + duplicate; publish/archive gate `isPlatformAdmin` + audit. Thêm relation `creator`/`approver` vào PlatformContent.
- `PublicProjectLibrary` (`platform/public-projects`, 22-03) — CRUD dự án + uploadMedia + linkTenant (TenantProjectLink) + togglePublic; detail modal (media/tiện ích/công ty liên kết).

**Slice 4 — Thư viện dùng chung:**
- Trait `SharedPartnerLibrary` (Concerns) + `ContractorLibrary` (`platform/contractors`, 22-06) & `SupplierVendorLibrary` (`platform/suppliers`, 22-07) — 1 trait, 2 page khác `partnerType()`. verify/prefer/blacklist/assign; supplier thêm SP, contractor thêm chứng chỉ. AC-14 (blacklist không gán được nếu không override).
- `DocumentTemplateLibrary` (`platform/document-templates`, 22-08) — CRUD + activate/deprecate + **share (owner KHÔNG đổi, AC-17)** + **clone (mẫu mới owner mới, AC-18)**. Thêm relation `clones` vào DocumentTemplate.
- `TemplateInheritancePolicy` (`platform/template-inheritance`, 22-09) — HasTable trên shares + áp chính sách theo danh mục (đếm mẫu ảnh hưởng) + rollback; force_apply cần SuperAdmin (AC-19).

**Slice 5 — KB & AI Governance:**
- `PlatformKnowledgeBase` (`platform/knowledge-base`, 22-10) — CRUD KB + index/reindex AI + archive (bỏ index) + share (KnowledgeScope, ai_read). sensitivity + ai_index_status (AC-20/21/22).
- `AiKnowledgeConfig` (`platform/ai-knowledge-config`, 22-11) — HasTable prompt (withoutGlobalScope) + create/edit/test/toggle; guardrail list toggle qua `wire:click toggleGuardrail`. KPI token/blocked từ ai_retrieval_logs.
- `KnowledgeAuditLog` (`platform/knowledge-audit`, 22-12) — HasTable audit_logs (lọc theo prefix governance) + export CSV (streamDownload) + panel retrieval AI gần đây, `mountAction('retrievalDetail',{id})` xem tài liệu dùng/bị chặn + snapshot quyền + token (AC-25/26/27).

**Bẫy đã trả giá slice này:**
- Trait `table()` đụng `InteractsWithTable::table` → giải bằng `use InteractsWithTable, SharedPartnerLibrary { SharedPartnerLibrary::table insteadof InteractsWithTable; }`.
- Quên `use Filament\Pages\Page;` → "Class Page not found".
- **BelongsToTenant global scope** giới hạn platform admin (tenant_id=1) → thêm `withoutGlobalScope('tenant')` cho mọi query platform-wide (ResidentBindingRequest/ResidentUnitBinding/TenantProjectLink/TenantPartnerAssignment/AiPromptTemplate). AC-01.
- Blade không dùng được `static::$title` → truyền qua getViewData.

**Data enrich:** shared partners 7 nhà thầu (đủ preferred/verified/unverified/blacklisted) + 4 NCC (có SP catalog); public_projects 5 (media). 

**Verify:** `migrate:fresh --seed` sạch; `php -l` sạch cả 10 file; `view:cache` compile; **12/12 màn render HTTP 200**; 2 script logic (`logic_sa.php` định danh AC-01..08; `logic_sa2.php` content publish / project link / partner verify+assign / template share owner-giữ + clone / KB index / guardrail toggle / audit governance) — tất cả pass + ghi audit. Scripts ở scratchpad.

**CÒN LẠI (polish, chưa làm):** browser-click submit các modal form; nối index AI thật (hiện mô phỏng set indexed); retrieval simulator thật ở 22-11 (hiện test prompt = xem prompt ghép); API controllers/routes + tests tự động (PHPUnit) theo CLAUDE_CODE_TASK_PROMPT.

---

## 2026-07-01 — Batch 07 SaaS Billing (reconcile) — Round 1: tầng DB

**Quyết định owner:** Batch 07 = canonical → reconcile (bỏ bảng saas sơ khai cũ, thay bằng bộ đầy đủ). Làm theo rounds; Round 1 = DB + models + FeatureGate + seed + /fila.

**Migration `2026_07_01_000024_reconcile_saas_billing_batch07`:**
- DROP: `subscriptions`, `subscription_invoices`, `subscription_invoice_lines`, `usage_metering` (slice B1 cũ). GIỮ feature-gate layer (plans/plan_features/modules/features/tenant_entitlements/tenant_module_overrides).
- CREATE 19 bảng: plan_prices, subscription_contracts, tenant_subscriptions, subscription_items, subscription_addons, subscription_renewals, usage_meters, usage_periods, usage_records, quota_alerts, billing_invoices, billing_invoice_lines, billing_payments, billing_reconciliations, billing_adjustments, credit_notes, pass_through_wallets, pass_through_transactions, billing_audit_logs. `tenant_subscriptions.plan_id` → `plans` (catalog feature-gate hiện có).

**Models:** 19 model mới (KHÔNG dùng BelongsToTenant — billing cấp platform, SuperAdmin thấy tất → tránh bẫy global-scope). Xoá 4 model cũ + 2 /fila resource cũ (Subscriptions, SubscriptionInvoices).

**FeatureGateService:** `Subscription` → `TenantSubscription`; `current_period_start/end` → `start_date/end_date`. Verify tenant#1 vẫn 28 features (không đổi).

**Seed (`seedBatch07Billing`):** 6 tenant billing (TEN-0001..0006, đủ active/trial/pending_renewal/suspended) + contracts + subscriptions + items + 4 addon + 1 renewal + 8 usage_meter + kỳ USAGE-2026-05 (locked) + 14 usage_record (có overage) + 3 quota_alert + 2 invoice (partially_paid/issued) + 8 line + 1 payment + 4 wallet + 4 transaction + 3 adjustment + 1 credit_note + 9 plan_price.

**/fila resources:** sinh 15 resource `make:filament-resource --generate --panel=fila` (Plan đã có từ addendum). Tất cả list render HTTP 200.

**Verify:** `migrate:fresh --seed` sạch (9.4s); counts đúng (tenant_subscriptions=7, usage_records=14, invoices=2…); FeatureGate 28 features; /fila 8 resource render 200; /admin/dashboard + /admin/platform/dashboard + /admin/ai/center + /admin/residents vẫn 200 (reconcile không vỡ).

**NEXT:** Round 2 = 9 custom page `/admin` billing (SaaS Revenue Dashboard, Subscription Detail, Contract/Renewal, Usage Metering, Overage/Quota, Invoice Generation, Invoice Detail+Payment, Pass-through Wallet, Billing Audit+Adjustment) + các action (upgrade/downgrade/addon/lock-usage/generate-invoice/record-payment/reconcile/top-up/adjustment→credit-note) ghi `billing_audit_logs`. Round 3 = API `platform/billing/*` + PHPUnit tests.

---

## 2026-07-01 — Batch 07 SaaS Billing — Round 2: 9 custom page /admin

**Nav group mới 'SaaS Billing'.** Trait `WritesBillingAudit` (ghi `billing_audit_logs` before/after cho mọi hành động). Gate qua `PlatformScreen` (SuperAdmin/Billing admin). 9 màn bespoke:
- `SaasRevenueDashboard` (`billing/revenue`, 27-01) — MRR/ARR/churn/overage/overdue + MRR theo plan + top tenant + dự báo gia hạn (read-only, tính từ DB).
- `SubscriptionManagement` (`billing/subscriptions`, 27-02/03) — HasTable + đổi gói (up/down) / add-on / pause / resume / renew / cancel + detail modal.
- `ContractRenewalManager` (`billing/contracts`, 27-04) — HasTable HĐ + pipeline gia hạn (wire:click duyệt/từ chối) + mark expired / terminate.
- `UsageMeteringDashboard` (`billing/usage`, 27-05) — HasTable usage record + header action recalculate/lock/unlock/generateAlerts (period-lock workflow).
- `OverageQuotaAlert` (`billing/quota-alerts`, 27-06) — HasTable alert + assign/resolve/dismiss/convert-to-addon(tạo SubscriptionAddon+MRR)/convert-to-upgrade.
- `InvoiceGeneration` (`billing/invoice-generation`, 27-07) — page xem trước + generate hóa đơn nháp từ thuê bao+addon+overage(kỳ đã khóa)+VAT, bỏ qua đã có.
- `InvoiceManagement` (`billing/invoices`, 27-08) — HasTable + approve/send/void + recordPayment(partial→partially_paid, đủ→paid) + reconcile + detail modal.
- `PassThroughWalletDashboard` (`billing/wallets`, 27-09) — HasTable ví + topup/requestTopup/approveTopUp(wire:click)/deduct/configure auto-topup + cảnh báo số dư thấp.
- `BillingAuditAdjustment` (`billing/adjustments`, 27-10) — HasTable adjustment + approve/reject/need-more + issueCreditNote(áp vào hóa đơn) + timeline audit.

**Bẫy `$s`→`$state` (lần 4).** SubscriptionManagement dùng `$s` cho record-param → sed blanket `$s`→`$state` khiến record closure bị Filament resolve state theo TÊN → 500 ("Argument #1 $state must be TenantSubscription, null given"). Bài học: **record closure đặt `$record` (hoặc `$ct`/`$r`/`$a`), chỉ scalar column-value mới `$state`**. 3 màn kia đã dùng `$ct`/`$r`/`$a` nên an toàn.

**Verify:** php -l sạch; view:cache; **9/9 render HTTP 200**; logic `logic_b07.php`: đổi gói/addon/renew, lock usage+sinh 3 alert, sinh 3 hóa đơn, thanh toán một phần, đối soát, ví ±, credit note — 13 dòng billing_audit_logs. Đạt AC subscription/contract/usage/quota/invoice/wallet/adjustment.

**CÒN LẠI:** Round 3 = API `platform/billing/*` (controllers/routes English) + PHPUnit tests (visibility/lifecycle/invoice/wallet/adjustment). Browser-click modal submit chưa test.

---

## 2026-07-01 — Batch 07 SaaS Billing — Round 3: API + tests (HOÀN TẤT)

**API `platform/billing/*` (routes tiếng Anh).** Đăng ký `api:` trong bootstrap/app.php + middleware alias `platform.admin` (`App\Http\Middleware\EnsurePlatformAdmin` — chặn nếu không `isPlatformAdmin`). `routes/api.php` prefix `platform/billing`, 39 route.

**8 controller** (`App\Http\Controllers\Platform\Billing\`, đều dùng trait `WritesBillingAudit`):
- SaasRevenueController@index (MRR/ARR/churn/overage/overdue/top-tenant).
- TenantSubscriptionController (index/show/store + upgrade/downgrade/addAddon/removeAddon/pause/resume/suspend/renew).
- UsageMeteringController (index + recalculate/lock/unlock/generateAlerts).
- QuotaAlertController (index/resolve/convertToAddon/convertToUpgrade).
- BillingInvoiceController (index/show/generate/approve/send/void/recordPayment/reconcile).
- PassThroughWalletController (index/topUp/deduct/configureAutoTopup).
- BillingAdjustmentController (index/approve/reject/issueCreditNote).
- BillingAuditLogController@index.

**Tests:** `tests/Feature/Batch07BillingApiTest.php` (sqlite :memory: + RefreshDatabase, fixtures tối thiểu, actingAs platform admin). **10 test / 39 assertion PASS** phủ 12 flow TEST_SCENARIOS: 403 non-admin, create sub, upgrade→MRR, add-on→MRR, lock usage + gen overage alert, convert alert→addon, generate invoice (line usage_overage) + partial payment→partially_paid, wallet deduct, adjustment approve→credit note (áp vào hóa đơn), suspend→resume. Mỗi flow assert `billing_audit_logs`.

**Lưu ý auth API:** hiện xác thực qua `auth()->user()` (phiên Filament / actingAs trong test). Chưa gắn token Sanctum — nếu cần gọi API stateless từ ngoài, thêm Sanctum sau (localized: đổi middleware group).

**=> BATCH 07 HOÀN TẤT (Round 1 DB + Round 2 9 UI + Round 3 API/tests).** Còn tùy chọn: browser-click modal submit; Sanctum token; proration khi upgrade (hiện đổi MRR, chưa cộng chênh lệch vào hóa đơn kỳ tới).

---

## 2026-07-01 — Lưu context: handoff SuperAdmin + Batch 07

Tạo `docs/SESSION_HANDOFF_20260701_SUPERADMIN_BILLING.md` — snapshot đầy đủ phiên (SuperAdmin WEB-UX-22 12 màn + Batch 07 SaaS Billing 3 rounds): kiến trúc bổ sung (PlatformScreen/WritesBillingAudit/nav groups/FeatureGate reconcile), bảng đối chiếu 21 màn↔page↔slug, cách chạy/verify, 8 bẫy Filament, việc còn lại. Kiểm chứng lại: migrate:fresh --seed sạch, platform+billing render 200, Batch07 API 10/10 test PASS.

---

## 2026-07-17 — BQL-01 cụm Căn hộ + Danh sách cư dân + Chuẩn trang listing

**Chuẩn trang listing** `docs/LISTING_PAGE_STANDARD.md` (áp mọi màn list /admin): header = title ở topbar + breadcrumb (icon, click được) + action ở header Filament (`getHeaderActions`, nút tạo màu `gold`); **KPI card tính lại theo filter**; **X2FilterBar** (inline select + search + drawer nâng cao + chip); **toggle cột** (dropdown "Cột" trong filter bar: `cols` init all-true + `->visible()` + deferred `wire:model` + Áp dụng/Đặt lại, KHÔNG dùng `->toggleable()`); **bulk action inline** (không `BulkActionGroup`); **mobile card** (<768px ẩn `.fi-ta-content`, hiện `.x2-mobile-cards`); **freeze cột** mặc định (ô chọn + `code` sticky trái, thao tác sticky phải; bỏ `->striped()`); bỏ tab điều hướng (dùng sidebar).

**BẪY (đã trả giá):** (1) bảng phải `->query(fn () => $this->filteredQuery())` — **closure**, Builder tĩnh bị Filament cache Table → filter đóng băng; (2) đổi filter phải `resetPage(getTablePaginationPageName())` **+ `flushCachedTableRecords()`** (KHÔNG có `resetTablePage()`); (3) **Filament = v4**: layout `Section/Grid` ở `Filament\Schemas\Components`, action modal dùng `->schema()` (không `->form()`); (4) toggle cột: `cols` init all-true để checkbox khớp cột đang hiện.

**Màn đã dựng:**
- **05 Danh sách căn hộ** (`ApartmentDirectory`) = reference chuẩn listing.
- **06 Chi tiết căn hộ 360** (`ApartmentProfile`, `/apartments/{id}/profile`) bản GIÀU (ref BQL-01-03): KPI strip 7 · 7 section-tab (Thông tin/Cư dân/Xe-thẻ/Công nợ/Phản ánh/Tài liệu/Lịch sử) · tab Thông tin 3 cột (công tơ, cảnh báo, thông tin nhanh). Action thật: Sửa (slide-over form) · Đổi trạng thái (+`apartment_status_histories`) · Tạo ghi chú (append note) · Xuất hồ sơ (CSV) · Phản ánh (FeedbackRequest).
- **07 Cây căn hộ** (`ApartmentTree`) 2 khung cố định scroll dọc riêng: trái cây Dự án→Tòa→Tầng→căn; phải toggle Danh sách/Layout (Layout = upload ảnh mặt bằng + hotspot — **option C, để đợt sau**) + danh sách theo tầng wrap (m²+chủ hộ/"Chưa gắn") + panel chi tiết.
- **01 Danh sách cư dân** (`ResidentDirectory`) theo chuẩn: KPI breakdown trạng thái (Tổng/Hoạt động/Chờ duyệt/Tạm khóa/Thiếu-dữ-liệu=thiếu CCCD) tính theo filter; giữ wizard duyệt hàng loạt.

**Nav:** "Cây căn hộ" thành **mục con của "Hồ sơ căn hộ"** (`navigationParentItem`); nav cha active cả ở màn chi tiết. **Cross-link 2 chiều cư dân ↔ căn hộ** ở mọi màn list/detail/tree.

**Migrations (nullable, không phá seed):** `2026_07_17_000001` (apartments: handover_price/contract_no/contract_signed_at/ownership_term; residents: residence_status) · `000002` (apartments: balcony_direction/position/furniture_status/purpose/contract_type/electric|water|gas_meter_no/documents json). Backfill demo qua `DB::table` (bỏ global scope tenant/project).

**Commits (main, tác giả Joa. Chinh <chtchinh@gmail.com>):** 78d934c → 4292630 → 23740fd → 28838cc → 2e40475 → 1f8fa26 → a235070. Verify: `php -l` + `npm run build` + render 200 + Livewire::test cho filter/tab/toggle/action.

**CÒN LẠI BQL-01:** 04 Chi tiết cư dân 360 (dựng theo `BQL-01-04`, đối xứng chi tiết căn hộ) → 03 wizard thêm → 02 timeline → 08 households/09 residency/10 data-quality. Rồi BQL-02, BQL-03. Layout mặt bằng (option C) làm sau.

---

## 2026-07-24 — Filament: quản lý Cổng thanh toán (payment_channels)

Dựng `PaymentChannelResource` (panel **fila**, `/fila/payment-channels`, discover tự động qua `FilaPanelProvider`) để BQL/owner tự bật/tắt cổng + nhập tài khoản nhận VietQR per tenant + per project. Bám convention v5 hiện có (`form(Schema)`, `table(Table)`, thư mục `Schemas/Tables/Pages`, trait `SoftDeletableResource`).

- **Form** (3 Section): (1) Phạm vi — tenant (`->relationship('tenant','name')`, `->live()`) + project (`->options()` lọc theo `Get('tenant_id')`, nullable, placeholder "Tất cả dự án"); (2) Cổng — channel (vietqr/vnpay/momo, `->live()`) + display_name + is_enabled (Toggle, default true) + sort; (3) config ĐỘNG theo channel: vietqr → 4 field `config.bank_bin|bank_code|account_no|account_name` (dot-notation map vào JSON nhờ cast `config=>array`); vnpay/momo → Placeholder ghi chú "khoá bí mật ở ENV" + Select `config.env`.
- **Table**: tenant, project (placeholder "— Tất cả dự án"), channel (badge màu), display_name, is_enabled (`ToggleColumn` toggle nhanh), sort; filter channel (Select) + is_enabled (Ternary) + TrashedFilter.
- **Model**: thêm `project(): BelongsTo` vào `App\Models\PaymentChannel` (project_id nullable, không FK constraint).
- **Nav**: group "Thanh toán", icon `OutlinedCreditCard`, label "Cổng thanh toán".
- **Tenancy**: panel fila không bật Filament tenancy → chọn tenant thủ công; global scope `BelongsToTenant` vẫn tự lọc theo tenant của user đăng nhập (nếu có), không phá scope.

**Verify:** `filament:optimize-clear` OK; `route:list --path=fila` thấy 3 route (index/create/edit); `php -l` sạch; class autoload + `project()` OK; `artisan about` chạy không lỗi.

---

## 2026-07-27 — Nhập dự án từ batdongsan.com.vn ("Lấy tiếp") + kiểm chứng chống bot

Xây tính năng thu thập metadata dự án BĐS NGAY TRONG APP, upsert vào `public_projects`.

- **Service `App\Services\Projects\BdsProjectImporter`**: `fetchMore(cityKeys, pages)` đọc con trỏ trang từ `bds_import_states`, lấy N trang kế tiếp mỗi khu vực, `parseCards()` (DOMDocument/DOMXPath — KHÔNG thêm package), `upsertCard()` theo `code`. Logic chuẩn hoá của `PublicProjectBdsSeeder` chuyển thành `public static` (codeFrom/parseConfigs/developer/tidy/province/projectType/status) — seeder gọi lại (DRY).
- **Migration + Model** `bds_import_states` (city unique, last_page, last_status, last_run_at).
- **Config `config/bds.php`** (cities ha-noi/tp-hcm/da-nang/phu-quoc + slug_fallback kien-giang, pages_per_run=3, delay_ms=400, transport, curl_binary).
- **Nút "Lấy tiếp"** header action trên `Sa/Pages/PublicProjectLibrary` (chỉ SuperAdmin): CheckboxList khu vực + số trang → `fetchMore()` đồng bộ → Notification tổng theo khu vực + audit `public_project.fetch`.
- **Command** `projects:fetch-more {--pages=3} {--city=*}` (cron/CLI).

**KIỂM CHỨNG CHỐNG BOT (thật, DB `x2bms`):** batdongsan sau Cloudflare managed challenge (lọc TLS/JA3). PHP Guzzle/ext-curl (OpenSSL) → **403 challenge** kể cả đủ header. Binary curl.exe **Schannel** (System32 8.21.0 / Git 8.16.0) → **200 + 10 card/trang** ổn định. Nên mặc định `transport=curl` (shell qua `Process`), có `auto` (fallback) và `http`. `looksBlocked()` xử lý duyên dáng + notification cảnh báo. ⚠️ Trên Linux prod curl thường OpenSSL → có thể lại bị chặn: cần chốt proxy/scraping API/curl-impersonate.

**Verify:** migrate ✅; `projects:fetch-more --pages=1 --city=ha-noi` → public_projects 5→15 (+10), chạy tiếp trang 2 → +10, cursor `last_page=2 status=ok` ✅; data mẫu `BDS-PJ6746 | JSC 34 | Hà Nội | handover` ✅; `php -l` sạch, không mojibake. CHƯA commit (chủ dự án commit).

---

## 2026-07-27 (bổ sung) — Enrich detail + export→seed đồng bộ

Nâng cấp `BdsProjectImporter`:
- **`enrichDetail(PublicProject)`**: fetch trang chi tiết theo `source_url`, parse bảng "Thông tin dự án" (selector THẬT `tbody.re__project-attr > tr > td.re__attr-item-label + td.re__attr-item-value`) → `metadata_json['detail']={nhãn tiếng Việt:giá trị}` + `detail_fetched_at`; thêm FAQ (`re__collapse-box`) → `detail_faq`, và `price`/`legal`/`developer_unit`. Map cột: apartments←"Số căn hộ", blocks←"Số tòa", project_type←"Loại hình", developer_name←"Chủ đầu tư" (nếu trống). Tích hợp vào `fetchMore` (cờ `bds.enrich_detail` default true, command `--no-detail`, delay giữa request, lỗi→`detail_error` bỏ qua êm). `upsertCard` giữ khoá làm giàu khi upsert lại.
- **Sửa `looksBlocked`**: bỏ phụ thuộc số card (trang chi tiết không có card → bị false-positive). Chỉ coi bị chặn khi body<20KB + token challenge thật (`_cf_chl_opt`/`challenge-error-text`/`cf-chl-`/`Just a moment`). Lưu ý `challenge-platform` có cả trên trang hợp lệ.
- **`projects:export-json`** + **`PublicProjectImportSeeder`**: dump rows nguồn batdongsan ra JSON đủ 13 cột → server chỉ `git pull` + `db:seed --class=PublicProjectImportSeeder` (KHÔNG gọi batdongsan). Giữ `PublicProjectBdsSeeder` cũ.

**Verify (DB thật):** `fetch-more --city=da-nang --pages=1` → 9/10 dự án mới có detail; mẫu `FourS Tower` detail={Pháp lý, Số tòa:"3 tòa", Số căn hộ:"1.281 căn", Chủ đầu tư:"Tập đoàn Sun Group"} → apartments=1281/blocks=3/project_type="Căn hộ chung cư" ✅. `export-json` → 30 dự án (9 detail) JSON no-BOM UTF-8 ✅; `db:seed PublicProjectImportSeeder` upsert 30 idempotent ✅. `php -l` sạch, không mojibake. CHƯA commit.

---

## 2026-07-27 (bổ sung 2) — Địa chỉ phường/quận, toạ độ, entity Chủ đầu tư

Nâng cấp thư viện dự án public:
- **parseAddress** (`BdsProjectImporter`, public static): tách `address` → ward/district/province/street theo tiền tố (Phường/Xã/Thị trấn; Quận/Huyện/Thành phố/Thị xã); quận/huyện dạng bare (không tiền tố "Bình Tân"/"Sơn Trà") nhận khi có phường trước. Lưu verbatim. Migration `000011` thêm cột `ward`,`district`,`latitude`,`longitude`(decimal 10,7). Áp vào upsertCard + 2 seeder + backfill.
- **enrichDetail mở rộng**: lấy địa chỉ chi tiết hơn từ `div.re__project-address` (bỏ link "Xem bản đồ") → thay address nếu tốt hơn + re-parse; lấy toạ độ từ URL Google Maps `?q=lat,lng` (lọc khung VN). Kiểm chứng THẬT: trang chi tiết CÓ cả địa chỉ số nhà/đường lẫn lat/lng.
- **Entity Chủ đầu tư** (`developers`, migration `000012`): dedup theo slug, `public_projects.developer_id` FK (giữ `developer_name`). Model `Developer::upsertByName()`. Importer + 2 seeder upsert Developer + link. `DeveloperResource` /sa (nhóm "Dự án") CRUD + logo upload + cột "Số dự án" + RelationManager dự án.
- **Bảng dự án** (`PublicProjectLibrary` + `PublicProjectsTable`): cột "Địa điểm" = Phường·Quận + Tỉnh (description), searchable ward/district/province; SelectFilter province + district (distinct DB). Cột "Chủ đầu tư" link `developer.name`.
- **Export/import**: thêm ward/district/lat/lng + object developer; import tạo lại developers + link.

**Verify (DB thật):** migrate 2 bảng ✅. Backfill 715 dự án: ward 697/district 696; **452 CĐT dedup** (Masterise/Vingroup 18, Sun Group 15 → 1 record; 56 CĐT >1 dự án); developer_id 606 ✅. parse "Phường Đại Kim/Quận Hoàng Mai/Hà Nội" ✅. enrich The Keisho → address "Ngõ 17 Đường Cổ Linh...", lat=21.0310955 lng=105.8933182 ✅. `route:list --path=sa` có `sa/developers` (index/create/edit) ✅. export 710 (617 detail) no-BOM + developer object; re-seed import 710 idempotent ✅. `php -l` 17 file sạch, không mojibake/BOM. CHƯA commit.

---

## 2026-07-27 (bổ sung 3) — Ảnh dự án (watermark), hiển thị toạ độ + detail trên /sa

- **Ảnh**: `parseDetail`/`enrichDetail` lấy gallery từ trang chi tiết (`re__project-album__media`, quy full-size + gom ảnh cùng lô upload `YYYY/MM/DD`) → `metadata_json.images` (mảng URL), `cover_image` (baseline = ảnh card, nâng lên ảnh gallery khi enrich), `images_watermarked`. `upsertCard` giữ các khoá ảnh khi upsert lại. Chưa tải file, chỉ lưu URL.
- **KẾT LUẬN WATERMARK (kiểm chứng thật)**: ảnh batdongsan CÓ WATERMARK — mọi ảnh hậu tố `_wm` (vd `...-fb78_wm.jpg`, HTTP 200). Bản KHÔNG `_wm` KHÔNG truy cập được (HTTP 530). Đã đặt `images_watermarked=true` để sau thay ảnh chính thống.
- **Hiển thị**: Bảng dự án (`PublicProjectsTable` fila + `PublicProjectLibrary` sa) thêm cột **Ảnh** (ImageColumn cover) + **Toạ độ** (link "📍 Maps" `google.com/maps?q=lat,lng`, toggleable). Form sửa (`PublicProjectForm`) tách 3 Section: cơ bản / địa chỉ+vị trí (ward/district/province + lat/lng + link Google Maps) / thông tin chi tiết collapsible (Placeholder bảng `metadata_json.detail` read-only + gallery ảnh có cảnh báo watermark).
- **fetch-more --pages=15 x4 city** (kèm enrich+ảnh) chạy NỀN để tăng phủ + có ảnh/toạ độ/detail.

**Verify (DB thật):** enrich The Keisho → images=6, cover set, `images_watermarked=true`, lat/lng, detail 3 nhãn ✅. Livewire render OK: PublicProjectLibrary (sa), ListPublicProjects (fila), EditPublicProject #8 form (Placeholder detail+gallery+map), ListDevelopers (sa) — assertOk() ✅. `php -l` 4 file sạch, không mojibake/BOM. CHƯA commit.

---

## 2026-07-27 (bổ sung 4) — Hiển thị địa chỉ cũ/mới + "Tìm ảnh & thông tin" chính thống (mock)

- **Địa chỉ cũ ↔ mới (2025)**: Form (`PublicProjectForm`) Placeholder hiện địa chỉ cũ (ward/district/province) + địa chỉ mới `metadata_json.address_new.full_new` + badge `address_new_confidence` (high xanh/medium vàng); chưa resolve → "Chưa xác định — chạy projects:resolve-new-address". Bảng: `PublicProjectsTable` thêm cột "Tỉnh mới (2025)" (badge, toggleable); `PublicProjectLibrary` thêm dòng "tỉnh cũ → tỉnh mới" ở cột Địa điểm. (UI chỉ HIỂN THỊ; command resolve build riêng.)
- **"Tìm ảnh & thông tin"** (SuperAdmin, trang Edit fila): `config/enrichment.php` (provider mock|google_cse|serpapi + keys); `ProjectEnrichmentService` + interface `EnrichmentProvider` (Mock/GoogleCse/SerpApi). Action `mountUsing` fetch ứng viên → modal preview lưới ảnh + Select ảnh bìa + CheckboxList ảnh gallery + CheckboxList info (kèm link nguồn). Xác nhận → `metadata_json.official_images/official_cover/official_url/official_info` + nối info vào description + `enrichment_log`. Ảnh chính thống ưu tiên thay ảnh batdongsan watermark ở form gallery.

**Verify (DB thật):** provider mock trả 5 ảnh (picsum) + 3 info không cần key ✅; applySelection ghi official_images(2)/cover/url/info(1)/enrichment_log(provider=mock) + nối description ✅. Render assertOk: Edit form (address cũ/mới + badge, cả nhánh CÓ và CHƯA resolve), List (cột Tỉnh mới), PublicProjectLibrary (sa), mount action enrichSearch (nạp ứng viên) ✅. `php -l` 10 file sạch, không mojibake/BOM. fetch-more nền đang chạy — tổng public_projects=1403 (đang tăng). CHƯA commit.

---

## 2026-07-27 (bổ sung 5) — Command backfill enrich-missing + lấy thêm dự án

- **Command mới `projects:enrich-missing {--limit=300} {--only=images|detail|all}`** (`app/Console/Commands/EnrichMissingProjects.php`): lặp public_projects có `source_url` mà thiếu `metadata_json.images` (hoặc `detail`), gọi lại `BdsProjectImporter::enrichDetail()` để bổ sung ảnh + toạ độ + detail. Idempotent, có progress bar + delay, bỏ qua êm khi bị chặn (chạy lại để tiếp tục).
- Chạy `fetch-more --pages=30` (4 TP) + `enrich-missing --limit=400 --only=images` (nền) để tăng phủ + backfill ảnh. Ảnh batdongsan VẪN watermark (tham chiếu) — ảnh sạch chờ tính năng "Tìm ảnh" có key.

---

## 2026-07-27 (bổ sung 6) — THƯ VIỆN ẢNH dự án (ProjectMedia) + lấy nhiều ảnh hơn

- **parseDetail**: nâng giới hạn ảnh gallery 20 → **40**/dự án.
- **Migration `2026_07_27_000013`**: `project_media` thêm `source`(batdongsan|official|manual), `is_cover`(bool), `is_watermarked`(bool) — guard hasColumn.
- **Service `ProjectMediaSync`** + **command `projects:sync-media {--limit=} {--id=*}`**: materialize ProjectMedia từ `metadata_json.images`(batdongsan, watermark)+`official_images`(official). Dedup (public_project_id,file_url), 1 ảnh bìa (official_cover→cover_image→ảnh đầu), sort_order tăng dần (official trước). Idempotent.
- **RelationManager "Thư viện ảnh"** (PublicProjectResource fila): lưới ảnh, badge source + cờ watermark, toggle is_active, action "Đặt làm ảnh bìa", reorder, thêm ảnh thủ công (upload/URL, source=manual).
- **`PublicProject::coverUrl()`**: ảnh bìa từ ProjectMedia is_cover (official/manual>batdongsan) fallback metadata; dùng ở form+2 bảng.
- Files mới (để chủ dự án commit): `app/Console/Commands/SyncProjectMedia.php`, `app/Services/Projects/ProjectMediaSync.php`, `app/Filament/Resources/PublicProjects/RelationManagers/MediaRelationManager.php`, migration `..._000013`, + command `EnrichMissingProjects.php` (phiên trước).

**Verify (DB thật):** migrate ✅. sync-media The Keisho → 6 ProjectMedia, đúng 1 is_cover, source=batdongsan, is_watermarked=true, sort 1-6 ✅. RelationManager + Edit page render assertOk; "Đặt làm ảnh bìa" → đúng 1 cover, coverUrl() dùng media ✅. sync-media toàn bộ: **+20992 media, 1917 dự án có ảnh**. Tổng sau: total_projects=2242, total_media=21005, projects_with_media=1922, ~10.9 ảnh/dự án, covers=1917 ✅. `php -l` sạch, không mojibake/BOM. fetch-more(HN/HCM 30tr) + enrich-missing(500) đang chạy NỀN. CHƯA commit.

---

## 2026-07-27 (bổ sung 7) — Mở rộng 10 tỉnh + giãn nhịp chống rate-limit

- **`config/bds.php`** (thay đổi CODE — cần commit): thêm 10 tỉnh/TP (đã verify slug ra 10 card thật): hai-phong, can-tho, dong-nai, khanh-hoa (Nha Trang), quang-ninh (Hạ Long), lam-dong (Đà Lạt), ba-ria-vung-tau, binh-duong, hung-yen, bac-ninh. (Lưu ý: slug bare `vung-tau` KHÔNG có card → dùng `ba-ria-vung-tau`.) Tổng cities = 14.
- **`delay_ms` 400 → 800** giãn nhịp giảm bị Cloudflare rate-limit (quan sát: cào nặng bắt đầu bị chặn giữa chừng — ha-noi/tp-hcm dừng 'blocked' ở vòng trước).
- Chạy nền: fetch-more --pages=20 (10 tỉnh mới + tp-hcm) → enrich-missing --limit=800 → sync-media.

**Số liệu (trước vòng này):** total=3137, with_images=3130, ProjectMedia=34455.

---

## 2026-07-27 (bổ sung 8) — Đạt 6005 dự án; gộp nhãn tỉnh; backfill CĐT (local)

- **Vòng lặp toàn quốc** (city `toan-quoc` = slug `du-an-bat-dong-san`, thêm vào `config/bds.php`) chạy tới khi **count=6005** (≥ mục tiêu 6000). ProjectMedia=64754.
- **Chuẩn hoá tỉnh**: `BdsProjectImporter::canonicalProvince()` (bỏ tiền tố Tỉnh/TP kể cả dạng dính "TP.HCM", gộp HCM/Hà Nội/BR-VT..., tách tỉnh từ chuỗi địa chỉ dài, bỏ rác→null); wire vào `province()`+`parseAddress`. Command **`projects:normalize-province {--dry-run}`**: distinct province **111 → 62**, gộp 49 nhãn / 469 dòng. Top: Hồ Chí Minh 1500, Hà Nội 1216, Bình Dương 373, Đồng Nai 243, Long An 235, Đà Nẵng 197, BR-VT 196, Quảng Ninh 155, Khánh Hòa 149, Quảng Nam 121, Bắc Ninh 115, Hải Phòng 114.
- **Backfill CĐT (LOCAL, không fetch)**: thêm `BdsProjectImporter::backfillDeveloperFromMeta()` + mode `enrich-missing --only=developer` — recover CĐT từ `metadata.detail['Chủ đầu tư']`/FAQ/description sẵn có. developer_id **2954 → 4733** (+1779, tức thì, không đụng Cloudflare). Còn 1272 dự án nguồn không có CĐT. developers=3097.
- Ghi chú: images(5995)/coords(5999)/detail(6000) đã gần đủ nên KHÔNG chạy enrich network (tránh hammer Cloudflare).

**File CODE cần commit:** `app/Console/Commands/NormalizeProvince.php` (mới), `app/Console/Commands/EnrichMissingProjects.php` (thêm mode developer), `app/Services/Projects/BdsProjectImporter.php` (canonicalProvince + backfillDeveloperFromMeta), `config/bds.php` (toan-quoc + 10 tỉnh + delay 800).

## 2026-07-29 — Cảnh báo khẩn cấp cư dân (CD-HOME-04), lớp đọc API

App cư dân có route name `emergencyAlert` từ lâu nhưng không dựng được màn vì
backend chưa mở dữ liệu. Bảng `emergency_alerts` thì đã có sẵn từ Tier 2 kèm
Filament resource cho BQL soạn — thiếu đúng lớp đọc phía cư dân.

**Đã thêm:** `EmergencyAlertService` + `EmergencyAlertController` +
`EmergencyAlertResource`, route `GET resident/emergency-alerts{,/{id}}`, và khối
`emergency` trong `GET resident/home`.

**Ba quyết định đáng ghi:**

1. **Không dùng route model binding.** Cư dân có `tenant_id = NULL` nên global
   scope tenant là no-op — binding mặc định sẽ nạp cả cảnh báo của dự án khác
   rồi mới kiểm tra (hoặc không kiểm tra). Resolve qua query đã scope; ngoài
   phạm vi trả 404.

2. **Chi tiết trả cả cảnh báo đã `resolved`.** Cư dân bấm vào push từ hôm qua mà
   nhận 404 thì không hiểu chuyện gì xảy ra. Trả nội dung + `resolved_at` để app
   hiện "đã kết thúc".

3. **`starts_at`/`ends_at` null nghĩa là gì.** null starts = hiệu lực ngay (BQL
   không điền giờ), null ends = chưa biết bao giờ xong. Điều kiện "đang hiệu
   lực" viết theo đúng nghĩa đó chứ không coi null là ngoài khoảng.

**Kênh liên hệ khẩn:** nguồn duy nhất có trong schema là `bql_teams`
(hotline + email BQL dự án). Bản đồ nghiệp vụ còn muốn số bảo vệ / kỹ thuật và
sơ đồ sơ tán — **chưa bịa cột**, đã ghi vào mục chờ owner chốt của
`RESIDENT_API_REFERENCE` (đề xuất bảng `project_emergency_contacts` riêng, vì
BQL cần form sửa số trong Filament, json metadata không có form tử tế).

**Bẫy dữ liệu dev:** bộ `bql_teams` sẵn có thuộc danh mục HQ-01 (tenant 9,
project 4+), KHÁC dự án cư dân demo (tenant 1, project 1) — trùng tên "Sunshine
Garden" nên rất dễ tưởng đã có. `contactsForProject(1)` vì thế trả rỗng.
`ResidentDemoContentSeeder::seedEmergency()` seed BQL cho project 1 + 2 cảnh báo
(1 active critical, 1 resolved).

**Verify:** `php -l` sạch; chạy request qua HTTP kernel (Herd nginx không truy
cập được từ shell phiên này) — list 200 xếp critical trước, detail 200 kèm
`contacts`, id ngoài phạm vi 404, `home.emergency` trả đúng cảnh báo critical.

## 2026-07-29 (tiếp) — Ngữ cảnh căn hộ + seed dự án thứ hai

**available_contexts kèm nhãn.** Bảng chọn căn hộ ở app cần tên căn/toà/dự án chứ
không phải mỗi id; thiếu thì app hoặc phải gọi thêm một vòng cho từng căn, hoặc bịa
dữ liệu — bản cũ đúng là bịa (ba dự án không có thật).

**Seeder dự án thứ hai.** Tài khoản demo #6 có hai căn ở hai dự án khác nhau nhưng chỉ
dự án 1 có dữ liệu, nên đổi căn hộ sang dự án 3 là mọi tab rỗng — không phân biệt được
"scope chạy đúng" với "app hỏng". `SecondProjectDemoSeeder` seed Đại Phúc Riverside với
nội dung **khác hẳn** (chủ đề ven sông) để nhìn phát biết ngữ cảnh đã đổi.

**Bẫy tenant:** dự án 3 thuộc **tenant 2**, còn bảng `vouchers` scope theo TENANT chứ
không theo project. Tạo ưu đãi dưới tenant 1 thì cư dân dự án 3 không thấy gì mà cũng
không có lỗi nào báo.

Đối chứng sau seed (cùng token, chỉ đổi `X-Context-Id`):

| | Sunshine Garden | Đại Phúc Riverside |
|---|---|---|
| bài cộng đồng | 15 | 7 |
| sự kiện | 5 | 3 |
| nhóm | 6 | 4 |
| tiện ích | 4 | 5 |
| ưu đãi | 9 | 4 |
| quà đổi điểm | 8 | 2 |

## 2026-07-29 (cuối) — Bậc thang nhóm cộng đồng

Chủ dự án chốt mô hình 4 nấc, trùng với thang trải nghiệm app đã có
(public → member → verified resident):

| Nấc | `kind` | Ai thấy | Ai đăng |
|---|---|---|---|
| 1 | `platform` | mọi người | chỉ X2/BQL |
| 2 | `project_interest` | user đã quan tâm dự án | chỉ CĐT/BQL |
| 3 | `project_resident` | cư dân đã xác thực | cư dân (hậu kiểm) |
| 4 | `private` | thành viên được duyệt | thành viên |

**Vấn đề chặn phải xử lý trước:** "dự án" đang là HAI bảng không nối với nhau —
`projects` (27 dòng, vận hành) và `public_projects` (6.005 dòng, danh mục
batdongsan). "Dự án quan tâm" lúc đăng ký lưu vào `user_public_projects` → neo vào
bảng danh mục; nhóm cư dân neo vào bảng vận hành. Không có khoá nối thì "khách quan
tâm Sunshine Garden" và "cư dân Sunshine Garden" là hai chữ khác nhau — khách mua nhà
xong thành cư dân mà hệ thống không biết đó là cùng một dự án, **bậc thang đứt ở nấc
giữa**. Đã thêm `projects.public_project_id`.

**Hai chỗ khác phải sửa theo:**
- `community_group_members` khoá theo `resident_id`, mà thành viên nhóm "quan tâm"
  chưa phải cư dân → thêm `user_id` nullable.
- Thêm `left_at`: cư dân bán nhà/hết hạn thuê mất quyền nhóm nhưng **bài cũ giữ
  nguyên** (xoá lịch sử thảo luận làm hỏng ngữ cảnh của người khác đang đọc) — đánh
  dấu để app gắn nhãn "cư dân cũ".

**`can_post` tính ở server**, không để app suy từ `kind`: quyền là chuyện của server,
app chỉ vẽ. Tách `post_policy` khỏi `kind` vì nhóm riêng của cư dân thì thành viên
đăng, nhóm riêng của BQL thì không — cùng `kind=private`.

**Bẫy khi seed:** nhóm chủ đề cũ (Chợ nội khu, Yêu bếp, Thể thao…) rơi vào `kind` mặc
định của migration là `project_resident`. Sai vai trò — "Cư dân {dự án}" là bảng tin
chung ai cũng ở trong, mấy nhóm kia là chủ đề tự chọn → phải là `private`. Seeder sửa
lại: 1 platform · 2 interest · 2 resident · 11 private.

### 29/07 (tiếp) — Màn SA nối dự án vận hành ↔ danh mục công khai

Chủ dự án chốt: **SA nối, BQL phân quyền sau**. Dựng ngay công cụ để việc nối tay (22 dự
án, việc của con người, chậm) chạy **song song** với Giai đoạn 1 của Community Domain —
chứ không xếp hàng sau nó.

`Sa/Pages/ProjectCatalogLinking` — bảng dự án vận hành, nối/đổi/gỡ liên kết tới
`public_projects`, có audit từng thao tác.

**Ba chi tiết cố ý:**

- **Badge số dự án chưa nối** trên điều hướng (hiện 22). Việc đối chiếu dữ liệu nền tảng
  không có ai nhắc thì không ai nhớ.
- **Nhãn danh mục LUÔN kèm tỉnh/quận**, cả ở ô tìm lẫn ở cột. Chỉ có tên thì không phân
  biệt được hai "Sunshine Garden" ở hai tỉnh — mà đó chính là cách nối nhầm.
- **Cảnh báo đặt ngay trên bảng**, không giấu trong tooltip: nối nhầm **không tạo ra lỗi
  nào nhìn thấy được**. Hệ thống vẫn chạy bình thường, chỉ là người theo dõi nhận nội
  dung của dự án không phải của họ. Người nối phải đọc trước khi bấm.

Kèm quan hệ `Project::publicProject()` — trước chỉ có cột `public_project_id` trần.

Verify: badge 22 · stats 27/5/22 · quan hệ nạp ra "Sunshine Garden" · route đăng ký.

---

## 2026-07-31 — Chốt nghiệp vụ công nợ, cài bộ gate delivery, lọc bảng kê đã phát hành

> Nhật ký này **trễ 3 commit** trước phiên (chưa có entry nào cho 30/07: cư dân nộp
> chứng từ `503f28c`, BQL duyệt `1a42017`, timezone `94e1134`). Ghi bù phần hôm nay.

### Audit trước, không code trước

Chạy 3 audit song song (x2mobile công nợ · x2bms billing · cộng đồng 2 repo). Kết quả
đáng nói nhất **không** phải thiếu tính năng, mà là **tài liệu nói khác code**:

- `PROGRESS_TRACKER` đánh 🟢 cho BQL-03-03 (tạo/chạy kỳ phí), 03-04 và 03-06 (duyệt &
  phát hành). Thực tế: **không có runner, không có dòng code nào set
  `approval_status='published'`**. 1.360 bảng kê hiện có đều do `DemoDataSeeder` sinh.
  `statement_publish_logs` và `statement_approvals` có bảng + model, **không write-path**.
- 07-08 (kiểm duyệt cộng đồng) đánh 🟢 nhưng `/admin` **không có màn cộng đồng nào** —
  `AdminPanelProvider` cố ý không `discoverResources()`, nên Resource chỉ nằm ở `/fila`
  dạng scaffold auto-gen.

Đã sửa các mốc đó trong tracker. Để 🟢 cho scaffold là tài liệu nói dối, và nó đắt hơn
không có tài liệu: người đọc tin rồi lập kế hoạch trên đó.

Nợ đầy đủ: `docs/delivery/TECH_DEBT_REGISTER.md` — 60 mục, 7 nhóm, mỗi mục có bằng
chứng `file:line`.

### Chốt 9 quyết định nghiệp vụ công nợ (D1–D9)

`docs/BILLING_OWNER_DECISIONS_20260731.md`. **Thắng** gói handoff 30/07 ở chỗ hai bên
khác nhau. Ba điều đảo ngược so với handoff:

1. **Tiền VND là SỐ NGUYÊN đồng** — handoff ghi `DECIMAL(20,2)` + decimal string. Đảo.
   API trả `"1234000"`, Flutter dùng `int`. Việc này còn *giải quyết luôn* vi phạm "tiền
   không dùng float" mà app đang mắc: `int` chính xác hơn cả decimal string, và bỏ được
   epsilon `0.009` app đang phải so sánh bằng.
2. **HQ ngành dọc ĐƯỢC duyệt chứng từ tiền** — handoff ghi HQ chỉ quan sát. Cấm chỉ áp
   cho T1 SuperAdmin `/sa`: nhà cung cấp phần mềm không xem được sao kê của công ty vận
   hành, duyệt là xác nhận việc mình không có cách nào biết. HQ **sở hữu** tài khoản đó.
3. **5 billing family là điều kiện bắt buộc, không phải lựa chọn kiến trúc.** D4 chốt thứ
   tự phân bổ mặc định QL → Nước → Điện → Xe → Khác. Nhưng `fee_types.category` gộp điện
   và nước chung vào `utility` (9 fee type), nên **không có cách nào xếp Nước trước
   Điện**. Đây là lý do backfill `fee_category` phải nhắm vào family mới, không vào bộ
   category cũ — kẻo backfill hai lần.

Hai chiều schema mà quyết định tạo ra, handoff chưa có: **chiều tài sản** trên dòng phí
và trên ngăn tiền thừa (tiền thừa của xe BKS nào phải vào ngăn của chính xe đó), và
**override thứ tự ưu tiên theo từng dự án**.

**Ba cấp phí** (câu chủ dự án hỏi): hình dạng `family › fee_type › tài sản` đúng, nhưng
cấp 3 **không nên là dòng trong danh mục phí**. `fee_types` là danh mục dùng chung của
tenant (~39 dòng); nếu mỗi biển số là một dòng thì danh mục nổ thành hàng nghìn, và mỗi
lần cư dân mua xe là *sửa danh mục phí*. Biển số đã là thuộc tính của `vehicles`
(`plate_no`, `parking_card_no`, `monthly_fee`, `valid_to`). Mô hình này chạy đều cho cả 5
family (điện/nước → `meters`, phí quản lý → chính căn hộ) — dấu hiệu nó đúng.

**Engine tính phí → Phase 2** (`BILLING_FEE_ENGINE_PHASE2_PLAN.md`), giai đoạn đầu kế
toán import (`BILLING_IMPORT_SPEC_20260731.md`). Điều này **gỡ chặn lớn nhất**: trước đây
D3/D4/D6 đều phụ thuộc "phải có gì sinh ra khoản phí". Và nó cho một lợi ích không thấy
ngay — bộ số kế toán import thành **bộ test vàng** để nghiệm thu engine sau này; không có
nó thì engine chỉ tự đối chiếu với chính nó.

Mẫu import đặt trên `StagingImporter` đã có sẵn, không làm hạ tầng mới. File mẫu `.xlsx`
sinh từ chính `columns()` của profile nên **luôn khớp code** — theo đúng khuôn
`ImportsResidentsFromExcel`.

### Cài bộ gate AI-First Delivery, sửa 5 điểm

Gói `handoff/x2bms/X2BMS_AI_FIRST_DELIVERY_SKILL_20260731`. Phương pháp đúng và chẩn
đoán chính xác — audit xác nhận từng điểm nó cảnh báo bằng bằng chứng thật. Nhưng sửa 5:

- **G9 anti-bypass + G10 money & authority** (gói gốc không có gate nào về bất biến tài
  chính). G9 quan trọng vì gate gốc kiểm "làm đúng chưa", không kiểm "còn cửa sau nào
  không" — đúng khoảng trống sinh ra `MyWork.php:338` và form `/fila/payments`.
  `ResidentPaymentClaimReviewer` làm rất đúng (transaction + 2 lớp lock + idempotent + 11
  test) nhưng có **4 đường vòng** qua nó.
- **Filament matrix**: "Thu phí/công nợ → Resource" → **Custom Page bắt buộc**. Bảng có
  bất biến tiền không được có Resource sửa được.
- **`docs/` → `docs/delivery/`** — không trộn tài liệu phương pháp với tài liệu sản phẩm.
- **Phase plan viết lại theo trạng thái thật** (bản gốc giữ ở `_ORIGINAL.md`). Bản gốc
  xếp `resident-identity` làm reference slice — nhưng nó **phần lớn đã xong**; xếp
  community cuối — nhưng nó ~90% xong và đang có hồi quy sống. Reference slice đổi thành
  **Billing Charge Import**: việc đang cần thật, đi trọn vòng, và buộc qua G9+G10.
- **Artifact theo tầng rủi ro** thay vì 10 cho mọi module — repo ~100 màn và đã có drift.

Chốt quan hệ hai hệ tài liệu trong `CLAUDE.md`: `docs/modules/` là **đầu vào thiết kế**
(trước khi code), Track 1–4 là **đầu ra vận hành** (sau khi code), `PROGRESS_TRACKER` là
**nguồn duy nhất về trạng thái**. Không để hai hệ cùng đánh trạng thái.

### Cư dân chỉ thấy bảng kê ĐÃ PHÁT HÀNH (D1) — đã làm

`Statement::scopeVisibleToResident()` — **định nghĩa duy nhất một chỗ**, đòi cả
`approval_status='published'` **và** `published_at IS NOT NULL`. Đòi cả hai có lý do:
`approval_status` là cột chuỗi, một mass-update lỡ tay (kiểu `MyWork::decide()`) đặt được
nó mà không đặt mốc thời gian; mốc thời gian là bằng chứng khó giả hơn.

Áp cho **cả 3 đường đọc**, không chỉ danh sách: `statements` index + show,
`billing/summary`, `billing/summary/trend`. Đây là phần dễ bỏ sót và là phần quan trọng
nhất — nếu chỉ lọc danh sách mà không lọc công nợ tổng thì cư dân thấy nợ 8tr rồi mở danh
sách chỉ có hóa đơn 5tr. Lệch kiểu đó làm cư dân gọi BQL, tệ hơn cả việc lộ hóa đơn.

Chi tiết bảng kê chưa phát hành trả **404 không phải 403**: 403 vẫn tiết lộ "có một bảng
kê ở đây mà bạn không được xem", và cư dân sẽ hỏi BQL về hóa đơn chưa chốt.

`tests/Feature/ResidentStatementVisibilityTest.php` — 7 test, khóa cả ca
`approval_status=published` mà thiếu `published_at`, và hồi quy cách ly căn hộ.

**Sửa kèm:** `ExampleTest` đòi 200 ở `/` trong khi `routes/web.php:13` là
`redirect('/admin')` — **đỏ vĩnh viễn từ đầu dự án**. Sửa cho đúng hành vi thật. Một test
luôn đỏ trong suite huấn luyện người ta bỏ qua màu đỏ, và đó là cách lỗi thật bị lọt.

### Dở dang — B1 (import khoản phí)

Đã có: `app/Enums/BillingFamily.php` (5 family + `defaultPriority` 100/200/300/400/900 +
`fromFeeType()` là chỗ duy nhất chứa logic suy family; tách `utility` kiểm **nước trước
điện** có chủ ý vì "Nước nóng trung tâm" chứa cả "nước", còn "Phí sạc xe điện" chứa "điện"
nhưng là phương tiện → về `other` cho BQL gán tay) và migration thêm
`subject_type`/`subject_id` + `service_period_start/end` + `due_date` cấp dòng cho
`statement_lines` (guarded, có `down()`, 2 index).

**Migration CHƯA CHẠY.** Còn: `RowNormalizers::money()`, backfill command,
`BillingChargeImportProfile`, màn import, seed, test.

### Ghi chú môi trường

`php` **không gọi được từ Git Bash** nhưng chạy tốt qua PowerShell — Herd cài `php.bat`
(batch Windows), Git Bash không nhận. PHP 8.4.15 · Laravel v13.17.0 · Filament v5.6.7 ·
MySQL `x2bms`. Ghi chú cũ trong tracker "máy dev không có PHP" là sai.

Verify: `php artisan test` → **91/91 pass**, 303 assertion.

## 2026-07-31 (tiếp) — Phase B1: Billing Charge Import (reference slice)

Đi trọn slice mẫu theo `docs/delivery/04_INITIAL_PHASE_PLAN.md` Phase B1:
`docs/BILLING_IMPORT_SPEC_20260731.md`. Migration cột (`subject_type/id`,
`service_period_start/end`, `due_date` trên `statement_lines`) đã có sẵn từ phiên trước
nhưng **chưa chạy** — chạy xong mới bắt đầu.

**Backfill `fee_category` → 5 family (D2, `billing:backfill-fee-family`).** Phát hiện khi
viết lệnh: 2211/7212 dòng ĐÃ có `fee_category`, nhưng mang giá trị CŨ (`management|
parking|service` — copy thẳng từ `fee_types.category` trước khi khái niệm family tồn
tại). Không chỉ lấp NULL — phải GHI ĐÈ cả cột, vì riêng `parking` sai hẳn thành phải là
`vehicle`. 4792 dòng cũ hơn không có `fee_type_id` (chuỗi tự do `fee_type` như "Phí gửi
xe ô tô"/"Điện sinh hoạt") — viết resolver từ khoá riêng cho trường hợp này (kiểm "xe"
trước "điện" như `BillingFamily::splitUtility()`, nhưng đây là lối thoát một lần cho dữ
liệu cũ, không phải bản sao của `fromParts()`). Chạy trên DB dev: 6002 dòng đổi, 1210 giữ
nguyên, 0 dòng còn NULL.

**`RowNormalizers::money()` (D7).** Nhận `518000`/`518.000`/`518,000`/`"518 000"`/`518000 đ`
→ `518000` (int); từ chối (trả về CHUỖI thay vì int) khi phần lẻ khác 0. Phân biệt "dấu
ngăn hàng nghìn" với "dấu thập phân" bằng SỐ CHỮ SỐ sau dấu tách CUỐI CÙNG trong chuỗi: 3
chữ số → hàng nghìn (gộp mọi dấu cùng loại, xử lý được `1.234.567`); 1-2 chữ số → thập
phân (chấp nhận nếu toàn số 0, từ chối nếu khác 0). Normalizer không có kênh báo lỗi
riêng (`ImportColumnSpec::extract()` chỉ trả `mixed`) nên đẩy việc phát hiện "không phải
int" xuống `validateRow()` của profile, echo lại đúng chuỗi gốc trong thông báo.

**`BillingChargeImportProfile`** trên `StagingImporter`/`ImportProfile` dùng chung, theo
đúng khuôn `ResidentImportProfile`. 16 cột; family suy từ `fee_type_code` (không có
trong file, để kế toán không phải nhớ). Tài sản (BKS/mã đồng hồ) validate RIÊNG khỏi
tiền: xe luôn bắt buộc; điện/nước chỉ bắt buộc khi căn có >1 đồng hồ cùng loại (đếm qua
`Meter::where('apartment_id','type')`); khớp sai → CHẶN cụ thể ("BKS ... không thuộc
căn ..."), không đoán — vì tài sản sai là tiền thừa vào ngăn của người khác (D6), không
phải sai hiển thị. Chuẩn hoá biển số: bỏ khoảng trắng/`.`/`-`, in hoa, rồi mới so khớp.

Statement luôn sinh `pending` (D1); nếu statement đã tồn tại mà KHÔNG còn `pending` (đã
duyệt/phát hành) thì CHẶN CẢ DÒNG bằng exception — import không được âm thầm thêm dòng
vào một bảng kê cư dân có thể đã thấy. Idempotent theo khoá `(statement_id, fee_type_id,
subject_type, subject_id, service_period_start)` — Eloquent tự dịch `null` trong mảng
where thành `whereNull`, nên hai dòng phí quản lý (không tài sản) cùng kỳ vẫn khớp đúng
dòng cũ thay vì tạo trùng. Re-import **không đụng `paid_amount`/`status`** của dòng đã
tồn tại (chỉ đặt mặc định khi dòng MỚI) — nghĩa vụ và tiền là hai việc khác nhau.
`total_amount` là PHÉP CHIẾU, tính lại từ `SUM(lines.amount)` sau mỗi lần ghi, không cộng
dồn tay.

**Hoàn tác lô** (spec §5.7): thêm cột `rolled_back_at`/`rolled_back_by` trên
`import_batches` — CỐ Ý không thêm giá trị mới vào `status` (xem bẫy enum dưới). Chặn cả
lô nếu BẤT KỲ bảng kê liên quan không còn `pending`; không hoàn tác được một phần.

**Bẫy phát hiện khi viết test (2 cột ENUM chết từ lâu):** `import_batch_rows.row_type`
là ENUM gốc `project|employee|assignment`, được mở rộng thêm `resident` bằng
`ALTER ... MODIFY` **CHỈ trên MySQL**. Trên SQLite (DB test) CHECK constraint GỐC vẫn
còn — `row_type = 'resident'` (đã dùng thật) hay `'billing_charge'` (mới) đều vỡ CHECK
khi test. Không ai gặp vì **trước bản này không có test nào cho luồng import** —
`BillingChargeImportTest` là test đầu tiên chạm bảng này. Sửa triệt để: đổi cột thành
`string` (đổi tên cột tạm rồi xoá cột cũ, không dùng `->change()` vì thiếu doctrine/dbal)
— bỏ hẳn ràng buộc enum ở tầng DB, `ImportProfile::rowType()` tự khai báo giá trị.

**Filament `/admin`:** `Pages/BillingChargeImport.php` (nhóm "Hóa đơn & thanh toán") +
trait `ImportsBillingChargesFromExcel` theo đúng khuôn `ImportsResidentsFromExcel` (tải
mẫu .xlsx sinh từ `columns()`, 2 bước upload→xem trước→ghi nền qua `CommitImportBatchJob`
có sẵn — không viết job mới). Cố ý MỎNG: chi tiết dòng/retry/export dùng chung màn có sẵn
"Nhật ký Import/Export" (đã generic theo `import_type` từ trước); màn mới chỉ thêm bảng
lọc riêng `billing_charges` + nút "Hoàn tác" (nghiệp vụ này generic `StagingImporter`
không biết). Render thật qua `_render_admin.php`: `billing-charge-import` và
`import-history` đều 200.

**Chưa làm (nằm ngoài ranh giới B1, đúng theo phase plan):** seed kịch bản demo end-to-end
(2 dự án/MUST_NOT_LEAK/nợ cũ dồn kỳ) cho việc click tay — bộ 9 test đã tự dựng fixture
riêng cho từng kịch bản này nên coi là đã verify, nhưng CHƯA có seeder riêng để BQL bấm
thử trên `/admin` với dữ liệu demo. `docs/templates/mau_import_khoan_phi.csv` có sẵn từ
trước dùng tạm được cho việc này (đã có đủ QL/điện/nước/2 loại xe/nợ cũ dồn/dòng điều
chỉnh âm).

Verify: `php artisan test` → **100/100 pass** (91 cũ + 9 mới), 330 assertion. `php -l`
sạch 4 file mới.

## 2026-07-31 (tiếp) — Phase B6: Kiểm duyệt cộng đồng ở `/admin`

`docs/COMMUNITY_WRITE_MODERATION_DESIGN.md` §4 đã có spec từ 27/07, chưa 1 dòng code.
Bước 1–3 (migration, API ghi, `POST moderate`) đã xong từ trước — chỉ thiếu bước 5 (web).

**Tách state machine ra `ModerateCommunityPostAction`.** `CommunityPostController::
moderate()` trước đây tự chứa toàn bộ match(hide|unhide|lock|unlock|delete|restore) +
audit. Chuyển vào `app/Actions/Community/ModerateCommunityPostAction.php` để màn
`/admin` mới gọi ĐÚNG MỘT chỗ với app cư dân — nếu để mỗi nơi tự viết lại state
machine thì sớm muộn hai bản lệch nhau (đúng bài học COMMUNITY_WRITE_MODERATION_DESIGN
đã cảnh báo cho "khóa" vs "ẩn"). Controller giờ chỉ lo auth + HTTP response; validate
lý do bắt buộc chuyển thành `InvalidArgumentException` từ action, controller bắt lại
thành 422. Không đổi hành vi request/response — refactor thuần, verify bằng test mới
(chưa có test nào trước đó cho endpoint này).

**Report resolve/dismiss.** `community_post_reports.status/resolved_*` có cột từ
27/07 nhưng **không dòng code nào ghi vào** — report tạo ra rồi nằm mãi `open`, BQL
không có cách nào đóng. Thêm `CommunityPostReport::markResolved()/markDismissed()` —
hai trạng thái khác nhau (đã xử lý bài vì report đúng, vs bỏ qua vì report không có
căn cứ) để sau này biết người báo cáo nào đáng tin.

**`Pages/CommunityModeration.php`** thay Resource scaffold tự sinh. Cố ý MỎNG hơn
spec đầy đủ: có KPI strip + filter (trạng thái, có report) + bảng sort theo
`report_count desc` + row action đủ 3 cặp hành động + modal xem report — CHƯA có màn
chi tiết bài riêng (07-09, cây bình luận drilldown) và chưa có bulk inline. Ghi rõ
trong tracker để không lặp lỗi "để 🟢 cho scaffold" đã xảy ra với BQL-03-03.

**Đóng một đường vòng (tinh thần G9):** `app/Filament/Resources/CommunityPosts/*` là
scaffold tự sinh, KHÔNG hiện ở `/admin` (`AdminPanelProvider` không `discoverResources()`)
nhưng VẪN hiện ở `/fila` (panel đó `discoverResources()` toàn bộ thư mục) — Edit trần
không qua lý do bắt buộc, không audit theo đúng format kiểm duyệt. Xoá hẳn thư mục
này; giờ chỉ còn một đường sửa bài cộng đồng duy nhất.

**Test cô lập tenant/dự án** (trước đây 0 test backend cho cộng đồng, đúng như phase
plan ghi): BQL thuộc dự án khác gọi `POST .../moderate` cho bài dự án mình phụ trách
→ 403, KHÔNG đổi trạng thái bài. Cộng với test state machine (hide/lock/unlock/xóa
mềm+khôi phục/hành động sai) và resolve/dismiss report.

Verify: `_render_admin.php community-moderation,billing-charge-import` → cả hai 200
(`community-moderation` 400KB, có dữ liệu thật 60 bài dự án 1). `php artisan test` →
**109/109 pass** (100 cũ + 9 mới), 349 assertion.

## 2026-07-31 (tiếp) — Community Domain: bắt đầu code (GĐ2 một phần + GĐ4)

`COMMUNITY_IMPLEMENTATION_PLAN.md` (9 giai đoạn, lập 29/07) tới hôm nay vẫn 100% tài
liệu, 0 dòng code. Chủ dự án chốt hai quyết định đang chặn kế hoạch:
1. 22/27 dự án chưa nối danh mục công khai → **để SuperAdmin tự nối tay** ở màn có
   sẵn (`Sa/Pages/ProjectCatalogLinking`), không khớp mờ tên (đúng khuyến nghị R3).
2. 11 nhóm `private` hiện có → **toàn bộ là cư dân tự lập**, không phải câu lạc bộ sở
   thích. Xoá được thế lưỡng nan "đoán sai thì sai quyền" mà audit 29/07 nêu.

Làm GĐ2 (nhóm) và GĐ4 (follow) trước — không theo thứ tự 1→9 gốc — vì đó là hai chỗ
vừa có quyết định, để quyết định không nguội.

### GĐ2 — `community_groups` mở rộng

`app/Enums/CommunityGroupType.php` (6 giá trị) + migration
`2026_07_31_300000_community_group_hierarchy.php`: cột mới + backfill NGAY trong
`up()` — chấp nhận được vì chỉ 16 nhóm, giá trị suy trực tiếp từ `kind`/`project_id`
sẵn có (khác backfill follow/comments ở giai đoạn sau, phức tạp hơn phải tách lệnh
artisan riêng theo đúng quy tắc `COMMUNITY_RISK_ROLLBACK.md` §5).

`private` → `resident_custom_group` DUY NHẤT (không rẽ nhánh `resident_interest_group`)
— khớp quyết định chủ dự án, xoá bẫy audit từng cảnh báo.

**Phát hiện khi viết test đầu tiên cho `GET resident/community/groups`:**
`orderByRaw("FIELD(kind, ...)")` là hàm MySQL, SQLite không có → mọi test chạm endpoint
này sẽ lỗi `no such function: FIELD`. Không ai gặp vì đây là **test đầu tiên** chạm
endpoint (tracker từng ghi "0 test backend cho cộng đồng"). Sửa sang `CASE kind WHEN
... END` — chạy được cả hai driver, cùng thứ tự sắp xếp.

`CommunityGroupResource` thêm `group_type`, `scope{type,id,name}`,
`capabilities{can_post,can_comment,can_invite,can_moderate,can_leave}` — **cộng thêm
cạnh**, giữ nguyên `kind`/`can_post`/`is_default`/`verification_level` (quy tắc R5,
app đang đọc trường cũ). Tiện thể sửa một bug ẩn: `$this->project?->name` trước đây
LUÔN trả `null` vì `CommunityGroup` chưa từng khai `project()` — không method nghĩa là
Eloquent coi `project` không phải relation, trả `null` êm ru, không lỗi. Thêm quan hệ
`project()`/`parent()`/`verificationHistory()`.

Bảng `community_group_verification_history` đã tạo cho GĐ2 mục 4 (nâng gold→blue) —
**chưa viết service dùng nó**, chỉ có chỗ chứa.

### GĐ4 — Follow dự án

`user_project_follows` (trỏ `projects` vận hành) + `GET/POST/DELETE
me/project-follows`, đặt cạnh `me/bootstrap`/`me/devices` (nhóm middleware
`auth:sanctum` bất kỳ ability nào — **không** `ability:resident`, vì đúng lý do kênh
tồn tại là cho người CHƯA phải cư dân, tier `member`).

`community:backfill-project-follows` — CHỈ backfill qua `projects.public_project_id`
đã nối chính xác (Cách A đã đề xuất từ 29/07), có `--dry-run` và `--rollback` (xoá
`user_project_follows`, không đụng `user_public_projects` — đúng chuẩn lệnh backout của
`COMMUNITY_RISK_ROLLBACK.md` §4). Chạy trên DB dev: **0 dòng** — không phải lỗi, dữ
liệu dev chỉ có 2 dòng `user_public_projects` (public_project_id 778, 892), không dòng
nào khớp 5 dự án đã nối (id 1, 1, 2, 230, 2515 — *phát hiện phụ*: hai dự án khác nhau
cùng trỏ `public_project_id=1`, cột này không có unique constraint; chưa sửa, ghi
nhận cho phiên nối tay của SA). Verify logic bằng test fixture tự dựng (idempotent,
chặn dự án chưa nối, rollback sạch).

Verify: `php artisan test` → **121/121 pass** (109 cũ + 12 mới), 387 assertion.

**Còn lại rất nhiều** (xem `docs/PROGRESS_TRACKER.md` mục Community Domain): GĐ1 nền
(CommunityAccessService, capability resolver, idempotency), GĐ3 grants, GĐ5-9, và GĐ7
tách bình luận vẫn đúng vị trí CUỐI CÙNG như kế hoạch gốc — chưa đụng.

## 2026-07-31 (tiếp) — Phase B2: Duyệt & phát hành có maker-checker (D1)

Đóng phần lớn nợ nhóm 1 (TIỀN) trong `docs/delivery/TECH_DEBT_REGISTER.md`: M1, M2,
M4, M9 — cập nhật trạng thái ngay trong file đó thay vì chỉ nói ở đây, để tài liệu
không kể hai câu chuyện khác nhau.

### `StatementApprovalService` — chỗ DUY NHẤT được set `published`

`approve()`/`reject()`/`publish()`, cùng tinh thần `ResidentPaymentClaimReviewer`:
service thuần (không phụ thuộc `auth()`), `lockForUpdate()` + transaction, để `MyWork`,
`StatementList`, hay job nền sau này gọi vào MỘT chỗ thay vì tự viết lại state machine.

- `approve()`: chỉ từ `pending`; chặn tự duyệt bằng so `approver->id` với
  `statements.created_by_user_id` (cột MỚI — trước đây không có gì để so sánh). Bảng kê
  cũ (`created_by_user_id = null`, tạo trước cột này tồn tại) không bị chặn — thiếu dữ
  liệu thì không giả định là vi phạm. Ghi thêm `StatementApproval` (bảng có sẵn từ lâu,
  0 write-path).
- `publish()`: chỉ từ `approved`; set `published_at` + ghi `StatementPublishLog`.
- `reject()`: từ `pending` hoặc `approved`.

`BillingChargeImportProfile::commitRow()` (B1) nay set `created_by_user_id` khi TẠO
statement mới — nếu không có bước này thì `approve()` không có gì để chặn tự duyệt.

### Đóng 3 đường vòng đã xác minh (G9)

1. **`MyWork::decide()` loại `statement`** — trước là
   `Statement::whereKey($id)->update(['approval_status' => ...])` trần, không transaction,
   không guard. Nay gọi `StatementApprovalService`, bắt `InvalidArgumentException` để báo
   lỗi rõ ràng thay vì âm thầm thất bại.
2. **`StatementApprovalQueue::transitionRuns()`** — trước chuyển MÙ mọi bản ghi được
   chọn sang trạng thái đích, kể cả bản ghi đã `published`. Nay lọc `validFrom` theo
   `$status` đích (giống cách `approve()` cùng file đã làm từ trước — bất nhất là
   `transitionRuns()` không học theo). Bản ghi không hợp lệ bị BỎ QUA, báo số bị bỏ qua
   trong thông báo, không chặn cả lô.
3. **`/fila/payments` (`PaymentResource`)** — phát hiện đây là scaffold tự sinh, CHỈ
   discover ở panel `/fila` (`FilaPanelProvider::discoverResources()` toàn bộ thư mục),
   giống hệt bẫy `CommunityPosts` đã xử lý ở Phase B6. Form có `TextInput::make('status')`
   tự do — gõ gì cũng lưu, không sinh allocation/receipt. Bảng còn có
   `EditAction`/`RestoreAction`/`ForceDeleteAction` + bulk restore/force-delete/delete —
   xoá cứng một khoản thanh toán qua một cú click, không qua review nào. Đổi hẳn thành
   CHỈ ĐỌC: xoá `PaymentForm.php`, `CreatePayment.php`, `EditPayment.php`, mọi action sửa/
   xoá trong `PaymentsTable.php`. Duyệt chứng từ thật đi qua `Pages/PaymentClaimQueue.php`
   (đã có, dùng `ResidentPaymentClaimReviewer`).

### UI thật thay nút trang trí

`StatementList.php` (`docs 03-04`) từng có nút "Phát hành bảng kê" là `<button>` KHÔNG
`wire:click` — bấm không làm gì. Bỏ nút đó (phát hành hàng loạt theo kỳ chưa nằm trong
scope B2), thay bằng nút theo TỪNG DÒNG: "Duyệt"/"Từ chối" khi `pending`,
"Phát hành"/"Từ chối" khi `approved` — gọi thẳng `StatementApprovalService` qua 3
Livewire method mới, `wire:confirm` xác nhận trước khi làm.

**Chưa đụng trong bản này** (ghi rõ để không ai tưởng đã xong): `StatementList.php:47`
hardcode `$today='2026-07-02'`, sort hash-shuffle giả lập; filter/search ở blade là
`<select>`/`<input>` không `wire:model`, chưa nối gì; `StatementApprovalQueue` (trục
`BillingRun`) vẫn thiếu transaction + chặn tự duyệt (M3, chưa đóng — khác trục với
`Statement` nên rủi ro thấp hơn nhưng vẫn là nợ thật).

Verify: `_render_admin.php statements,my-work,finance/statement-approvals` +
`_render_fila.php payments` → cả 4 đều 200. `php artisan test` → **129/129 pass**
(121 cũ + 8 mới), 401 assertion.

## 2026-07-31 (tiếp) — Phase B3: Phân bổ tiền theo từng dòng phí (D3)

Đóng thêm M5 (một phần) và M8 trong `docs/delivery/TECH_DEBT_REGISTER.md`.

**Vấn đề trước bản này:** `payment_allocations.statement_line_id` có cột từ lâu
nhưng KHÔNG dòng code nào ghi. `ResidentPaymentClaimReviewer::allocateToClaimedStatement()`
chỉ tạo MỘT `PaymentAllocation` phẳng ở cấp `statement`, cộng thẳng vào
`statement.paid_amount`. Hệ quả: "còn nợ gì theo từng dịch vụ" (màn công nợ D6,
`statement_lines`) không bao giờ đúng vì tiền vào không biết trả cho dòng nào —
mọi dòng phí của một bảng kê mãi mãi hiện `paid_amount = 0` dù bảng kê đã `paid`.

**`StatementLine::allocationSortKey()`** — rút khoá sắp xếp DÙNG CHUNG từ
`ApartmentWalletService::outstandingLines()` (vốn đã có sẵn, đúng, nhưng riêng
một mình): `is_critical` trước (0 = critical), `payment_priority` tăng dần, rồi
`id` tăng dần (nợ cũ trả trước). Quan trọng: is_critical THẮNG payment_priority
— viết test khoá đúng hành vi thật thay vì trực giác ("điện quan trọng" có thể
trả trước "quản lý" dù số payment_priority nhỏ hơn).

**`Statement::recomputePaidAmount()`** — `paid_amount`/`status` giờ là PHÉP
CHIẾU từ `SUM(lines.paid_amount)`, một hàm DUY NHẤT cho cả hai đường ghi tiền
gọi vào sau khi sửa dòng phí.

**`ResidentPaymentClaimReviewer::allocateToClaimedStatement()` viết lại**: đi
qua từng dòng CÒN NỢ theo khoá trên, tạo MỘT `PaymentAllocation` cho MỖI dòng
chạm tới (`statement_line_id` + `statement_id`), rồi gọi `recomputePaidAmount()`.
**Fallback quan trọng**: nếu statement KHÔNG có `StatementLine` nào (dữ liệu cũ
chưa từng itemize — 4/11 test cũ của `ResidentPaymentClaimReviewTest` dựng đúng
kịch bản này), giữ nguyên hành vi PHẲNG cũ ở cấp statement. Không có fallback
này thì tiền "biến mất" (vòng lặp qua 0 dòng, không phân bổ được gì) — phát hiện
ngay khi chạy lại bộ test cũ, 4/11 đỏ.

**`ApartmentWalletService::autoSettleOutstanding()` (M8) sửa theo**: trước ghi
`line.paid_amount` xong không đụng gì `statement.paid_amount` — bảng kê cha lệch
NGAY khi hàm chạy (dead code, 0 caller, nhưng phase plan yêu cầu sửa để không phá
bất biến "nếu bật nguyên trạng"). Một dòng phí có thể nợ dồn qua NHIỀU statement
(nợ cũ dồn kỳ) nên gom `statement_id` CHẠM TỚI vào một tập rồi mới
`recomputePaidAmount()` từng cái — không phải mọi statement của căn hộ.

**`billing:reconcile-statement-balances`** — đối chiếu `statements.paid_amount`
với tổng dòng, tự sửa lệch (đóng một phần M5); báo — KHÔNG tự sửa — dòng phí
`paid_amount > amount` (nhận quá tiền của chính nó), vì sửa đòi quyết định hoàn
khoản nào, không phải chuyện đoán tự động.

Verify: `php artisan test` → **138/138 pass** (129 cũ + 9 mới: 5
`StatementLineAllocationTest` + 2 `ApartmentWalletAutoSettleTest` + 2
`ReconcileStatementBalancesTest`), 423 assertion. `_render_admin.php
statements,my-work,finance/statement-approvals,payments/claims,statements/1` →
tất cả 200.

**Còn nợ (Phase B4, chưa làm)**: `fee_types.payment_priority` hiện mặc định
đồng loạt 100 cho MỌI loại phí (chưa backfill theo family QL→Nước→Điện→Xe→Khác);
override theo từng dự án; UI kéo-thả sắp thứ tự. Không có B4, thứ tự phân bổ
hiện tại chỉ phân biệt được nhờ `is_critical`, không phải gia đình phí.

## 2026-08-01 — Phase B4: Thứ tự ưu tiên phân bổ (D4-bis)

Việc chính của phiên này. Đọc lại `docs/delivery/04_INITIAL_PHASE_PLAN.md` (B4) +
`docs/BILLING_OWNER_DECISIONS_20260731.md` (D4: QL→Nước→Điện→Xe→Khác) trước khi
đụng code — không có gì đổi so với lúc B3 kết thúc, chỉ có `fee_types.payment_priority`
vẫn mặc định đồng loạt `100`.

### Trước khi viết code: soi schema `fee_types` xem có "dấu vết BQL đã sửa tay" chưa

Grep `app/Filament/Resources/FeeTypes/` — **không cột `payment_priority` nào xuất
hiện trong form/table** hiện tại. Nghĩa là hôm nay không có đường nào cho người dùng
sửa cột này ngoài giá trị mặc định `100` lúc tạo. Backfill chạy đè hôm nay AN TOÀN
tuyệt đối. Nhưng "an toàn hôm nay" không phải lý do bỏ qua bảo vệ — thêm cột mới
`fee_types.payment_priority_locked_at` (nullable timestamp, migration
`2026_08_01_000001_...`): NULL = chưa ai từng đặt tay, backfill ghi đè tự do; khác
NULL = có người thật đặt, backfill BỎ QUA. Dùng timestamp thay vì boolean để có luôn
bằng chứng "khi nào" nếu sau này cần điều tra một dòng bị "kẹt" không cập nhật theo
family mới.

### `billing:backfill-fee-priority` — mirror đúng `BackfillFeeCategoryFamily`

Không viết logic suy family lần hai — gọi thẳng `BillingFamily::fromFeeType()`
(đã có từ B1/B3). Có `--dry-run`, báo theo family. Chạy dev: **36/39 dòng đổi, 3 đã
đúng sẵn** (những dòng `category=management` tình cờ đã có `payment_priority=100`
từ trước — trùng với default family, không phải vì ai backfill). Rerun lần 2:
**0 đổi** — idempotent thật, không phải chỉ theo lý thuyết.

### Override theo dự án — bảng mới, không mở rộng `fee_scope_assignments`

Cân nhắc rồi bỏ: thêm cột `payment_priority` vào `fee_scope_assignments` (bảng có
sẵn, gán fee_type/rate vào scope project|building|apartment). Bỏ vì bảng đó phục vụ
MỘT việc (gán biểu giá) — một fee_type có thể áp dụng toàn dự án mà KHÔNG có dòng
`fee_scope_assignments` nào (áp qua đường khác), nên trộn "thứ tự phân bổ" vào đó
sẽ buộc tạo dòng giả chỉ để mang một con số, và ai xoá/sửa gán biểu giá vì lý do
tiền sẽ vô tình xoá luôn override thứ tự — hai vòng đời khác nhau bị ép chung một
bảng. Bảng riêng `fee_type_priority_overrides` (`tenant_id, project_id, fee_type_id,
payment_priority`, unique `[project_id, fee_type_id]`, KHÔNG soft-delete — giống
`fee_scope_assignments`, xoá override = "quay lại mặc định", không cần giữ lịch sử
xoá) — độc lập vòng đời hoàn toàn.

`StatementLine::allocationSortKey()` đổi tối thiểu: rút `payment_priority` ra hàm
mới `effectivePaymentPriority()` — tra override theo `(project_id, fee_type_id)`
trước, không có thì về `fee_types.payment_priority`. `StatementLine` không mang
`project_id` trực tiếp, phải suy qua `statement.building.project_id` — phát hiện
phụ: `Statement` model dùng `building_id` khắp nơi (trait `BelongsToProject`, seed,
test) nhưng **chưa từng khai `building(): BelongsTo`** — thêm quan hệ này (giống
lỗi `CommunityGroup::project()` thiếu đã gặp ở Phase community trước đây, cùng một
dạng bug "gọi `$model->quanHe` êm ru ra `null` vì không phải relation thật").

Cả hai call site B3 (`ResidentPaymentClaimReviewer::allocateToClaimedStatement()`,
`ApartmentWalletService::outstandingLines()`) sửa để eager-load `statement.building`
trước khi `sortBy(allocationSortKey())` — tránh N+1 khi sắp nhiều dòng. Bên
`ResidentPaymentClaimReviewer` mọi dòng cùng MỘT statement nên chỉ cần
`$statement->loadMissing('building')` rồi gán thẳng quan hệ cho từng dòng
(`setRelation('statement', $statement)`), không cần eager-load lặp; bên
`ApartmentWalletService` các dòng có thể trải trên NHIỀU statement (nợ dồn kỳ) nên
dùng `->with(['feeType', 'statement.building'])` trên query.

### Bẫy tự đào rồi tự vá: static cache theo `projectId:feeTypeId`

Bản đầu tiên nhớ kết quả tra override bằng mảng static trong `StatementLine` (memo
hoá cho một lượt chạy, tránh query lặp khi nhiều dòng cùng fee_type+project). Test
`test_override_theo_du_an_doi_thu_tu...` đỏ: override tạo thật trong DB nhưng
`effectivePaymentPriority()` vẫn trả về mặc định. Nguyên nhân: test dùng SQLite
`:memory:` + `RefreshDatabase` (rollback theo transaction) — ID tự tăng của SQLite
với từ khoá `AUTOINCREMENT` cũng nằm TRONG transaction, rollback thì `sqlite_sequence`
cũng lùi lại, nên hai test khác nhau chạy trong cùng tiến trình PHPUnit có thể ra
CÙNG một cặp `(project_id, fee_type_id)` — cache tĩnh (sống suốt tiến trình, không
theo từng test) trả nhầm giá trị `null` đã nhớ từ test trước đó chạy trước nó (test
không override thì thấy override giả từ cache; test có override run sau lại thấy
cache `null` cũ). Bỏ hẳn cache, tra DB trực tiếp mỗi lần — chi phí chấp nhận được
(mỗi lượt phân bổ chỉ vài dòng phí). Không chỉ là vấn đề test: BẤT KỲ cache tĩnh
theo ID nào cũng rủi ro y hệt trong môi trường có khả năng tái dùng ID (kể cả một
worker PHP dài hạn xử lý nhiều tenant) — bỏ đúng, không phải bỏ vì ngại debug thêm.

### UI kéo-thả — `Pages/FeePriorityOrder.php` (`/admin/fees/priority`)

Thao tác trên `fee_type_priority_overrides`, KHÔNG đụng `fee_types` — sửa
`fee_types.payment_priority` ở đây sẽ đổi cho MỌI dự án của tenant, ngược đúng cái
D4 cần ("dự án A khác dự án B"). Dự án đang thao tác lấy từ
`CurrentContext::projectId()` — đúng mô hình "một workspace BQL = một dự án" đã
dùng khắp `/admin`, không cần thêm bộ chọn dự án riêng cho màn này.

Hai trạng thái màn hình: CHƯA tuỳ chỉnh (bảng rỗng, nút "Khởi tạo từ mặc định" — copy
thứ tự tenant-wide hiện tại thành điểm bắt đầu, không phải một bộ số ngẫu nhiên) và
ĐÃ tuỳ chỉnh (kéo-thả qua `Table::reorderable('payment_priority')` của Filament, tự
ghi `payment_priority` liên tục 1,2,3... lên từng override — đủ dùng vì các số này
chỉ so sánh tương đối trong nội bộ một dự án). Thêm nút "Khôi phục mặc định" xoá
sạch override của dự án — quay lại dùng tenant-wide.

### Một trục trặc môi trường không liên quan tới code nghiệp vụ, mất khá nhiều thời gian

Worktree phiên này (`'.claude/worktrees/agent-a76b69c2c45db27a6`) không có `vendor/`
sẵn. Thử tạo junction trỏ sang `vendor` của checkout chính (composer.lock giống hệt,
tưởng an toàn) — SAI: Composer tính `$baseDir` trong `autoload_psr4.php` bằng
`dirname(__DIR__)`, và trên Windows một file được require QUA junction vẫn cho
`__DIR__` là đường dẫn thật của đích junction (checkout chính), không phải đường dẫn
worktree — nên PSR-4 autoload nạp `App\` từ **`app/` của checkout chính**, class
mới viết trong worktree biến mất khỏi tầm nhìn autoloader (`class_exists()` trả
`false`) dù file nằm đúng chỗ, đúng namespace. Class NÀO đã tồn tại ở checkout
chính (mọi file kế thừa từ trước) vẫn autoload bình thường — nên `billing:backfill-
fee-family` chạy được mà `billing:backfill-fee-priority` (file mới) báo "not
defined", dễ tưởng nhầm là lỗi khai báo command. Gỡ junction, `composer install
--ignore-platform-reqs` (thiếu `ext-pcntl`/`ext-posix` cho Horizon — không cần cho
test) cài vendor THẬT trong worktree — hết vấn đề. Ghi lại vì bẫy này sẽ tái diễn
với bất kỳ agent nào tưởng "trỏ symlink/junction sang vendor có sẵn cho nhanh" là an
toàn khi composer.lock giống hệt.

Verify: `php -d memory_limit=1G vendor/bin/phpunit` (lệnh `php artisan test` mặc
định qua `php.ini` 128M không đủ, không liên quan gì tới thay đổi phiên này) →
**142 test, 141 pass** (137 cũ + 4 mới `FeePaymentPriorityTest`: backfill đúng
family · backfill không đụng dòng khoá tay + ổn định khi rerun · override đổi thứ
tự CHỈ dự án đó · dự án khác vẫn mặc định tenant-wide). 1 lỗi còn lại là
`ScreenTelemetryTest::test_tong_hop_theo_ngay_dem_dung_va_chay_lai_khong_nhan_doi`
— xác minh lại bằng `git stash` (chỉ tệp ĐÃ SỬA, không đụng migration/model/command
mới) rồi chạy lại: **lỗi y hệt trên baseline B3**, không liên quan gì tới B4 (đọc
lỗi: `AppScreenDailyStat` không thấy bản ghi — domain app telemetry, không đụng
billing/fee_types). Không sửa trong phiên này — ngoài phạm vi B4, ghi nhận để phiên
khác xử lý riêng.

**Đã migrate + backfill thật trên DB dev** (`x2bms`, dùng chung với checkout
chính): 2 migration mới, `billing:backfill-fee-priority` đã chạy (không phải
`--dry-run`) — 36 dòng `fee_types.payment_priority` đã đổi thật theo family.

**Phase B5 (D6, ngăn tiền thừa theo tài sản + màn công nợ theo dịch vụ) — KHÔNG làm
trong phiên này.** Lý do: xử lý sự cố môi trường (vendor/junction ở trên) ăn phần
lớn ngân sách phiên; B4 cần chạy thật + verify test trước khi mở rộng thêm sang
`ApartmentWalletBucket` (thêm chiều tài sản) — làm ẩu B5 trên nền B4 chưa nguội thì
rủi ro cao hơn lợi ích. Để nguyên cho phiên sau, đọc `statement_lines.subject_type/
subject_id` (migration `2026_07_31_100000_...`) và
`Support/Import/Profiles/BillingChargeImportProfile.php` (đã có resolve tài sản
xe/đồng hồ) làm điểm bắt đầu.
hiện tại chỉ phân biệt được nhờ `is_critical`, không phải gia đình phí.
