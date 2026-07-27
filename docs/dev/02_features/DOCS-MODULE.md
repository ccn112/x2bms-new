# DOCS-MODULE — Chức năng (Module Tài liệu)

> 🔒 Đối tượng: dev nội bộ. Track 2. Trung tâm tài liệu CMS kiểu GitBook.

## Tổng quan
Module tài liệu nội bộ tự code (không dùng package GitBook): **soạn thảo** trên Filament, **đọc** trên web `/docs`, **phân quyền** theo đối tượng, **quản lý version** từng trang.

## 1. Soạn thảo (Filament, panel `/sa` SuperAdmin, nav group "Tài liệu")
- **DocSpaceResource** — CRUD không gian: title, key (auto-slug từ title), description, audience (6 giá trị), icon, sort, is_published. Bảng reorderable theo `sort`, cột đếm số trang.
- **DocPageResource** — CRUD trang:
  - Section "Vị trí": chọn **space** (live) · **parent** (lọc theo space đang chọn, loại chính nó) · sort · status (draft/published).
  - Section "Nội dung": title (live → auto-slug) · slug · **MarkdownEditor** `body` (upload ảnh vào disk `public/docs/attachments`, chèn trực tiếp).
  - `updated_by` tự set = user hiện tại khi tạo/sửa.
  - Bảng: cây (mô tả trang cha), badge space/status, đếm version, người sửa, thời điểm; filter theo space/status/trashed.
- **RevisionsRelationManager** (tab "Lịch sử version" trong trang Edit):
  - Bảng version (chỉ đọc): version, title, note, người sửa, thời điểm.
  - Action **Xem**: modal hiển thị title + body của revision.
  - Action **Khôi phục**: xác nhận → ghi lại nội dung revision cũ về trang (sinh version mới).

## 2. Đọc (reader web `/docs`)
Route **KHÔNG** đặt middleware `auth` — `DocsController` tự phân quyền (xem §3):
| Method | URL | Tên | Mô tả |
|---|---|---|---|
| GET | `/docs` | `docs.index` | Danh sách space (guest thấy public; user thấy public + theo quyền), dạng thẻ. |
| GET | `/docs/search?q=` | `docs.search` | Tìm kiếm LIKE theo title/body trong space được phép. |
| GET | `/docs/{space:key}/{path?}` | `docs.show` | Đọc trang; `path` là chuỗi slug phân cấp. |
| GET | `/` (chỉ trên host docs) | `docs.home` | Landing site tài liệu công khai (subdomain). |

- **Layout 3 cột (Phase 3):** trái = sidebar (search + danh sách space + cây trang) · giữa = nội dung · phải = **"Trong trang này"** (mục lục heading của trang).
- **Render markdown an toàn:** `Support/Docs/DocsMarkdown` (commonmark + GFM; `html_input=strip`, `allow_unsafe_links=false` → chống XSS).
- Blade tự chứa CSS (navy/gold theo DS-01, responsive, có nút mở mục lục ở mobile): `resources/views/docs/*`.
- ⚠️ Route `{space:key}` có ràng buộc negative-lookahead loại `api`/`api.json` để **không nuốt** route Scramble sẵn có `/docs/api`.

### 2b. Reader polish (Phase 3)
- **Anchor heading:** `DocsMarkdown::render()` trả `['html', 'headings']`; gán `id` (slug) cho mọi `h2`/`h3` qua `Str::slug` (hỗ trợ tiếng Việt: "Cài đặt & Cấu hình" → `cai-dat-cau-hinh`), tự **dedupe** slug trùng (`van-hanh`, `van-hanh-2`). `toHtml()` cũ giữ nguyên (gọi `render()['html']`).
- **Cột phải "Trong trang này":** liệt kê heading (h2 cấp 1, h3 thụt), link `#slug`, **sticky** khi cuộn, **scrollspy** (IntersectionObserver, JS thuần) highlight mục đang xem. Chỉ hiện khi trang có ≥2 heading. `< 1100px` ẩn cột phải → thay bằng khối `<details>` "Trong trang này" gọn ở đầu nội dung.
- **Version rõ ràng:** ngay dưới tiêu đề hiện pill **"Phiên bản N · cập nhật dd/mm/yyyy"**; nếu >1 revision có dropdown "Xem phiên bản" (`?v={n}`). Khi xem revision cũ → banner "Đang xem phiên bản cũ vN · Về bản mới nhất →".
- Link nội bộ dùng URL tương đối (giữ host public/subdomain — không phá chế độ guest/public của Phase 2).

## 3. Phân quyền (Phase 2 — có lớp công khai)
Điều khiển ở `DocsController::canView()`:
1. Space `is_published=false` → không ai xem (kể cả admin, ở reader).
2. Space `is_public=true` → **guest cũng xem được** (chỉ trang `published`).
3. Space nội bộ (`is_public=false`) → yêu cầu **đăng nhập** + quyền `docs.view.{audience}`.
   - Guest gặp space nội bộ → **redirect** `filament.admin.auth.login` (không phải 403).
   - Đã đăng nhập nhưng thiếu quyền → **403**.
- `visibleSpaces()` (sidebar/landing/search) lọc đúng theo luật trên: guest chỉ liệt kê space public; user liệt kê public + theo quyền. Guest **không** thấy trang `draft` (query luôn `where status=published`).
- `docs.manage` cho soạn thảo. super_admin thấy tất cả (Gate::before).
- Chi tiết map role → xem `docs/dev/03_data_arch/DOCS-MODULE.md` và `DocsPermissionSeeder`.

## 3b. Site tài liệu công khai (Phase 2 — `doc.x2.fino.vn`)
- Phục vụ từ **chính app x2bms** qua subdomain (không app riêng). Config `config/docs.php` key `host` = `env('DOCS_HOST', 'doc.x2.fino.vn')`.
- Route root `/`:
  - Host = `DOCS_HOST` → gọi thẳng `DocsController@index` (landing site tài liệu).
  - Host khác → redirect `/admin` như cũ.
- `/docs/*` chạy trên **mọi host** (domain-agnostic) nên subdomain cũng truy cập được `/docs/{space}/...`.
- Link nội bộ trong reader dùng **URL tương đối** (`route(..., absolute: false)`) → giữ nguyên host đang duyệt (host chính hoặc subdomain), không nhảy chéo domain.
- Cột `doc_spaces.is_public` (bool, default false) + Toggle "Công khai" trong `DocSpaceResource`.

## 3c. Reader nâng cao (Phase 4)
- **Tìm kiếm full-text:** MySQL `FULLTEXT(title, body)` (migration `..._000005`) + `MATCH…AGAINST … IN BOOLEAN MODE` (mỗi từ khoá `+từ*`), order theo relevance (natural mode). Fallback LIKE khi: driver ≠ mysql, hoặc boolean mode rỗng kết quả (từ ngắn/stopword). Tôn trọng quyền y hệt reader (chỉ space được xem + `status=published`). Kết quả có **snippet ngữ cảnh** (~40 từ quanh match, bỏ markdown) + **highlight `<mark>`** (escape trước, chèn mark sau — chống XSS) + link tới trang; nếu từ khoá khớp một heading `##`/`###` thì link kèm **anchor** `#slug` (badge "khớp tiêu đề mục").
- **X2AI trong reader:** tái dùng **nguyên** hạ tầng `<x-x2.ai-fab>` + Livewire `x2ai-chat` (KHÔNG dựng AI mới). Ngữ cảnh trang share qua `$x2aiContext` (`title` = "Tài liệu · {space} · {page}"); nội dung trang tự bắt qua `window.x2aiCaptureScreen()` (đọc `<main>`). **Chỉ bật cho user đã đăng nhập + `X2aiPolicyGate::canUse()`** → guest/public KHÔNG thấy chat, KHÔNG nạp asset/endpoint AI (tránh chi phí token + abuse). Asset (`@vite('resources/css/app.css')` + `@livewireScripts`, Alpine đi kèm Livewire 3) chỉ nạp trong nhánh điều kiện này.
- **Copy code:** JS thuần trong layout gắn nút "Copy" (→ "Đã sao chép") vào mọi `<pre>` trong `.docs-content`; copy plaintext (`navigator.clipboard`, fallback `execCommand`). Không thêm dependency.
- **Sửa nhanh từ reader:** user có quyền `docs.manage` (đã đăng nhập) thấy nút **"✎ Sửa trang"** deep-link `/sa/doc-pages/{id}/edit` (mở tab mới). Ẩn hoàn toàn với guest/user không quyền (`@auth @can('docs.manage')`).

### 3d. Tinh chỉnh giao diện (Phase 4)
- **Bỏ H1 trùng:** `DocsMarkdown::render($md, stripLeadingH1: true)` loại heading `#` cấp 1 ĐẦU body (trùng tiêu đề template); h2/h3 giữ nguyên → TOC không đổi.
- **Version = dropdown:** dòng dưới tiêu đề luôn hiển thị `<select>` liệt kê mọi revision (disabled khi chỉ 1 version) + "· cập nhật dd/mm/yyyy"; giữ banner "phiên bản cũ". *Ghi chú: "version" ở đây là **revision từng trang** (mỗi lần sửa title/body). Nếu chủ dự án muốn "version toàn tài liệu" (đánh số cả bộ) thì là khái niệm khác — cần xác nhận.*
- **Content full-width:** bỏ `max-width` ở `.docs-article`/`.docs-main` → nội dung chạy hết chiều rộng giữa sidebar trái và cột TOC phải (chỉ chừa padding); vẫn responsive.

## 4. Import tài liệu có sẵn
`php artisan docs:import [--fresh]` — nạp `.md` từ `docs/dev` + `docs/guide` (idempotent). Xem chi tiết mapping ở Track 3.

## 3e. Phiên bản sản phẩm + Backlog (Phase 5)
**Phân biệt rõ 2 khái niệm** (xem chi tiết ở Track 3):
- **Lịch sử sửa trang** = revision từng trang (`doc_page_revisions`, control `?v=`). Đã đổi nhãn reader thành "Lịch sử sửa trang" để khỏi lẫn.
- **Phiên bản sản phẩm** = v1.0/v2.0 toàn site (`doc_versions`, control `?ver=<label>`), mỗi version có **backlog** (`doc_version_items`).

**Filament (/sa, nav "Tài liệu"):**
- `DocVersionResource` — CRUD version (label/name/released_at/status/is_current/sort/summary) + `ItemsRelationManager` (backlog: category/title/detail/status/ref_page/sort, reorderable). `is_current` độc nhất: Create/Edit page hook `afterCreate/afterSave` bỏ current ở các version khác.
- `DocPageForm` — thêm Select "Phiên bản" (`version_id`, trống = chung).

**Reader:**
- **Bộ chọn phiên bản** ở sidebar (dropdown `doc_versions`, mặc định `is_current`). Đổi → `?ver=<label>` (JS `docsSetVersion`, xoá `?v` revision). Lọc cây + nội dung: hiện trang `version_id = active` HOẶC `null` (chung). Trang thuộc version khác bị ẩn khỏi cây; mở trực tiếp URL → banner gợi ý "Chuyển sang {label}".
- **Trang `/docs/versions`** — timeline (mới nhất trên cùng): mỗi version có label/name/ngày/status/summary + backlog nhóm theo category (badge status, link `ref_page` nếu có).
- Guest: bộ chọn + `/docs/versions` chỉ thấy version `released`; item backlog trỏ trang không được xem thì ẩn. Không chọn được version chưa phát hành (fallback về hiện hành).
- Giữ "Lịch sử sửa trang" (revision) — JS `docsSetRevision` giữ nguyên `?ver` khi đổi bản sửa.

**Import:** `docs:import` tạo `v1.0` (released, is_current) idempotent + gán trang import `version_id=v1.0`.

## 3f. Docs CMS = nơi xuất bản chính thức (đa nguồn: x2bms + x2mobile)
Docs CMS là **nơi chính thức** đăng tài liệu dev + hướng dẫn của CẢ 2 dự án.
- **Cấu hình `config/docs.php`:** `spaces` (7 space: dev, mobile-dev, ops, cu-dan, bql, hq, sa — mỗi space có title/audience/is_public/sort) + `import_paths` (danh sách nguồn, path tương đối `base_path()`).
- **`docs:import` đa nguồn (idempotent):** mỗi entry → 1 space (`space`) hoặc map theo thư mục con (`mode: guide_audience` cho `docs/guide` x2bms → bql/hq/sa/ops). Nguồn mặc định: `docs/dev`→dev, `docs/guide`→theo audience, `../x2mobile/docs/guide/cu-dan`→cu-dan, `../x2mobile/docs/dev`→mobile-dev.
- **AN TOÀN path thiếu:** entry có `is_dir()` false (vd server không có x2mobile) → log `skip (không tồn tại)`, KHÔNG lỗi, exit 0. Space vẫn được tạo dù nguồn trống (để soạn tay trên Filament).
- **Quy trình xuất bản khi chốt** (skill `cap-nhat-tai-lieu` bước 8): cập nhật markdown → `docs:import` (hoặc soạn trực tiếp `/sa` nhóm "Tài liệu") → thêm 1 mục backlog vào DocVersion hiện hành → gán trang đúng space + version.

## Verify (2026-07-27, DB thật)
- migrate 3 bảng ✅ · seeder 7 quyền/14 role ✅ · import 5 space/9 trang ✅.
- Observer sinh version (1→2 sau khi sửa body) ✅ · markdown strip `<script>` ✅.
- reader show/search render 200 ✅ · `/docs/api` vẫn về Scramble ✅.
- (2026-07-27, dời panel) Resource dời `App\Filament\Resources` → `App\Filament\Sa\Resources`; `SaPanelProvider` thêm `discoverResources` + nav group "Tài liệu"; `route:list --path=sa` thấy `sa/doc-spaces` + `sa/doc-pages`, `/fila` không còn doc- ✅.
- (2026-07-27, Phase 2 public site) migrate `is_public` ✅ · import `--fresh` set ops=public, dev=private ✅ · giả lập HTTP: guest `/docs` chỉ thấy ops (ẩn dev), `/docs/ops`=200, `/docs/dev`→302 login, host `doc.x2.fino.vn` `/`=landing 200 (ẩn dev), host chính `/`→redirect `/admin` ✅ · guest search không rò trang dev (0 rows) · admin thấy cả 5 space ✅ · lint 8 file sạch.
- (2026-07-27, Phase 4) FULLTEXT migrate ✅ · search 'Coolify' (guest) ra ops + snippet + `<mark>` ✅ · 'Seeding' guest 0 rows (không rò dev), admin 2 rows ✅ · guest page: KHÔNG có AI/livewire/nút sửa ✅ · admin: AI fab + `@livewireScripts` + `@vite` + deep-link `/sa/doc-pages/{id}/edit` ✅ · technician (ai.use, không docs.manage): AI có, nút sửa ẩn ✅ · content `<h1>`=0 (H1 body đã strip), `<h2 id>` còn (TOC) ✅ · dropdown version luôn hiện ✅ · copy-code script ✅ · lint sạch, không mojibake/BOM.
- (2026-07-27, Phase 5) migrate 3 (doc_versions/doc_version_items/version_id) ✅ · import tạo v1.0 gán 12 trang ✅ · active version: guest→v1.0, admin `?ver=v2.0`→v2.0, guest `?ver=v2.0`(planned)→v1.0 ✅ · lọc cây: guest default ẩn trang v2.0, admin `?ver=v2.0` hiện ✅ · banner mismatch khi mở trang khác version ✅ · `/docs/versions`: guest chỉ v1.0(released), admin thấy v2.0+backlog(2 item) ✅ · is_current độc nhất ✅ · lint 13 file sạch, không mojibake/BOM.
- (2026-07-27, Đa nguồn) `config/docs.php` `spaces`+`import_paths` ✅ · `docs:import` (có x2mobile): 16 trang, dev/ops/mobile-dev có nội dung, `../x2mobile/docs/guide/cu-dan` skip êm ✅ · giả lập server KHÔNG có x2mobile (path bịa): exit 0, skip 2 nguồn, KHÔNG lỗi ✅ · 2 space mới cu-dan (resident/public) + mobile-dev (dev/nội bộ) tạo, mobile-dev=4 trang ✅ · H1 tiêu đề trang: class `docs-pagetitle` font 2.1rem, `/api/v1` render `<code>` ✅ · lint sạch, không mojibake/BOM.

## Việc còn lại (Phase 6+)
Ảnh gắn theo revision · seed `guide/bql|hq|sa` khi có file · dark mode reader · SEO/meta + sitemap cho site public · (tùy chọn) so sánh diff giữa 2 phiên bản sản phẩm.
