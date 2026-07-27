# DOCS-MODULE — DB & Kiến trúc (Module Tài liệu)

> 🔒 Đối tượng: dev nội bộ. Track 3. Module tài liệu CMS kiểu GitBook (tự code).

## Bảng

### `doc_spaces` — không gian tài liệu
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| key | string unique | slug định danh, dùng trong URL `/docs/{key}` (routeKey) |
| title | string | |
| description | string nullable | |
| audience | enum | `dev` · `ops` · `bql` · `hq` · `sa` · `resident` |
| icon | string nullable | tên heroicon (tùy chọn) |
| sort | uint | thứ tự hiển thị |
| is_published | bool | reader chỉ hiện space đã publish |
| timestamps | | |

Index: `(audience, is_published, sort)`.

### `doc_pages` — trang tài liệu (cây)
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| space_id | FK → doc_spaces | cascade on delete |
| parent_id | FK → doc_pages nullable | tự tham chiếu (cây phân cấp) |
| slug | string | |
| title | string | |
| sort | uint | |
| body | longText nullable | nội dung **markdown** |
| status | enum | `draft` · `published` |
| updated_by | FK → users nullable | nullOnDelete |
| timestamps + softDeletes | | |

Unique: `(space_id, parent_id, slug)`. Index: `(space_id, parent_id, sort)`.

### `doc_page_revisions` — lịch sử version
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| page_id | FK → doc_pages | cascade on delete |
| version | uint | tăng dần theo page |
| title | string | snapshot |
| body | longText nullable | snapshot markdown |
| note | string nullable | lý do (Tạo trang / Cập nhật nội dung) |
| editor_id | FK → users nullable | nullOnDelete |
| created_at | timestamp | (không có updated_at) |

Unique: `(page_id, version)`.

## Quan hệ (ERD)
```
DocSpace 1───* DocPage ───┐ (parent_id, tự tham chiếu: children/parent)
                          └──* DocPageRevision
DocPage.updated_by ─→ User      DocPageRevision.editor_id ─→ User
```
- `DocSpace hasMany pages` / `rootPages` (parent_id null).
- `DocPage belongsTo space, parent`; `hasMany children, revisions`; `belongsTo editor(updated_by)`.
- `DocPageRevision belongsTo page, editor`.

## Version — cơ chế
`DocPageObserver` (`#[ObservedBy]` trên `DocPage`):
- `created` → snapshot version 1 (note "Tạo trang").
- `updated` → chỉ snapshot khi `wasChanged('title')` hoặc `wasChanged('body')`; version = max+1.
- **Khôi phục** (RelationManager) = ghi title/body của revision cũ trở lại page → observer tạo thêm version mới (lịch sử không bị xóa).

## Phân quyền (spatie, guard `web`)
- Permissions: `docs.view.dev|ops|bql|hq|sa|resident` + `docs.manage`.
- Seed: `DocsPermissionSeeder` (idempotent, `findOrCreate` + `givePermissionTo`), gán 14 role theo 3-tier.
- Reader: `$user->can("docs.view.{$space->audience}")`. super_admin bypass qua `Gate::before`.

## Seed / Import
`php artisan docs:import [--fresh]` — idempotent (`updateOrCreate` theo key/slug):
- `docs/dev/**/*.md` → space `dev`.
- `docs/guide/**/*.md` → audience theo thư mục con (`bql`/`hq`/`sa`, còn lại `ops`); bỏ `SUMMARY.md`.
- slug phẳng từ relative path; title lấy heading `#` đầu tiên (fallback tên file).

Ảnh chèn trong nội dung: `MarkdownEditor` upload vào disk `public` (`docs/attachments`), cần `php artisan storage:link`.
