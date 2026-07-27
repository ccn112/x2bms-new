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

- **Layout 2 cột:** sidebar trái (ô tìm kiếm + danh sách space + cây trang của space hiện tại) · nội dung phải.
- **Breadcrumb** theo cây; **chọn version** qua `?v={n}` (banner cảnh báo khi xem bản cũ).
- **Render markdown an toàn:** `Support/Docs/DocsMarkdown` (commonmark + GFM; `html_input=strip`, `allow_unsafe_links=false` → chống XSS).
- Blade tự chứa CSS (navy/gold theo DS-01, responsive, có nút mở mục lục ở mobile): `resources/views/docs/*`.
- ⚠️ Route `{space:key}` có ràng buộc negative-lookahead loại `api`/`api.json` để **không nuốt** route Scramble sẵn có `/docs/api`.

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

## 4. Import tài liệu có sẵn
`php artisan docs:import [--fresh]` — nạp `.md` từ `docs/dev` + `docs/guide` (idempotent). Xem chi tiết mapping ở Track 3.

## Verify (2026-07-27, DB thật)
- migrate 3 bảng ✅ · seeder 7 quyền/14 role ✅ · import 5 space/9 trang ✅.
- Observer sinh version (1→2 sau khi sửa body) ✅ · markdown strip `<script>` ✅.
- reader show/search render 200 ✅ · `/docs/api` vẫn về Scramble ✅.
- (2026-07-27, dời panel) Resource dời `App\Filament\Resources` → `App\Filament\Sa\Resources`; `SaPanelProvider` thêm `discoverResources` + nav group "Tài liệu"; `route:list --path=sa` thấy `sa/doc-spaces` + `sa/doc-pages`, `/fila` không còn doc- ✅.
- (2026-07-27, Phase 2 public site) migrate `is_public` ✅ · import `--fresh` set ops=public, dev=private ✅ · giả lập HTTP: guest `/docs` chỉ thấy ops (ẩn dev), `/docs/ops`=200, `/docs/dev`→302 login, host `doc.x2.fino.vn` `/`=landing 200 (ẩn dev), host chính `/`→redirect `/admin` ✅ · guest search không rò trang dev (0 rows) · admin thấy cả 5 space ✅ · lint 8 file sạch.

## Việc còn lại (Phase 3+)
Inline-edit từ reader · full-text search (thay LIKE) · ảnh gắn theo revision · seed `guide/bql|hq|sa` khi có file · polish UI reader (dark mode, anchor heading, copy code) · SEO/meta cho site public · sitemap.
