# TENANCY_AND_CONTEXT — Kiểm toán backend X2-BMS cho API Mobile

> Kiểm toán CHỈ ĐỌC ngày 2026-07-18. Không có secret/token/mật khẩu/.env trong tài liệu này (chỉ tham chiếu TÊN biến).
> Nền tảng: Laravel 13, Filament 5, laravel/sanctum, spatie/laravel-permission. Multi-tenant 1 DB (row-level).

---

## 1. Cây phân cấp: Tenant → Company → Project → Building → Floor → Apartment

Đơn vị nghiệp vụ (context) là **PROJECT (dự án)** — một Ban quản lý (BQL) vận hành đúng một dự án gồm nhiều tòa nhà. Building KHÔNG phải context, chỉ là bộ lọc trong bảng dữ liệu.

| Cấp | Model (file) | Trait scope | Khóa ngoại chính | Ghi chú |
|-----|--------------|-------------|------------------|---------|
| Tenant (công ty vận hành / SaaS tenant) | `app/Models/Tenant.php` | — (KHÔNG scope) | gốc cây | `hasMany(Company)`, `hasMany(Project)`; cast `app_config`→array |
| Company | `app/Models/Company.php` | — | `tenant_id` | `Tenant::companies()` |
| Project (dự án = context BQL) | `app/Models/Project.php` | `BelongsToTenant` | `tenant_id`, `company_id` | `hasMany(Building)`, `hasMany(Block)` |
| Block | `app/Models/Block.php` | (xem model) | `project_id` | phân cụm giữa Project và Building |
| Building (tòa nhà) | `app/Models/Building.php` | `BelongsToTenant` | `tenant_id`, `project_id`, `block_id` | **có cột `project_id` thật** → là "cầu nối" scope dự án cho phần lớn bảng |
| Floor (tầng) | `app/Models/Floor.php` | `BelongsToTenant` + `BelongsToProject` | `tenant_id`, `building_id` | scope dự án qua `building_id` |
| Apartment (căn hộ) | `app/Models/Apartment.php` | `BelongsToTenant` + `BelongsToProject` | `tenant_id`, `building_id`, `floor_id` | `belongsToMany(Resident)` qua `resident_apartment_relations` |

**Điểm quan trọng cho mobile:** đa số bảng nghiệp vụ **KHÔNG có cột `project_id`**; chúng scope dự án gián tiếp qua `building_id ∈ buildings của dự án`. Chỉ `blocks / buildings / teams / users / ai_*` (và một số ít khác) có cột `project_id` thật (theo `docs/DEV_JOURNAL.md`). Điều này ảnh hưởng cách viết truy vấn scope thủ công trong API.

`residents` liên kết tới người dùng toàn cục qua `residents.user_id` (nullable — chỉ có khi tài khoản đăng nhập đã được kích hoạt/liên kết). Xem chi tiết ở `AUTH_AND_RESIDENT_IDENTITY.md`.

---

## 2. Giải quyết context hiện tại — `app/Support/Context/CurrentContext.php`

Chỉ có **một** file trong `app/Support/Context/`: `CurrentContext.php` (đọc toàn bộ). Context dựa hoàn toàn vào **SESSION + `auth()->user()`** (web/Filament), KHÔNG dựa vào token/header.

### 2.1 Khóa session được dùng

| Khóa session | Ý nghĩa | Ghi/đọc bởi |
|--------------|---------|-------------|
| `current_project_id` | Dự án đang làm việc (context BQL) | `setProject()` / `projectId()` |
| `hq_tenant_id` | Platform admin "đóng vai" một công ty ở cổng HQ | `tenantId()`, `availableProjects()` |
| `hq_selected_project_ids` | Tập dự án cổng HQ tổng hợp (rỗng = tất cả) | `setHqProjects()` / `hqProjectIds()` |
| `current_workspace` | Workspace đang chọn: `bql` / `hq` / `superadmin` | `setWorkspace()` / `workspace()` |

### 2.2 Cách resolve

- `projectId()`: đọc `session('current_project_id')`, **xác thực** id đó phải nằm trong `availableProjects()`; nếu không hợp lệ → mặc định = dự án của tòa nhà "home" của user (`user.building_id → Building.project_id`) hoặc dự án đầu tiên khả dụng.
- `tenantId()`: nếu là platform admin và có `session('hq_tenant_id')` → dùng tenant đó; ngược lại `auth()->user()->tenant_id ?? project()->tenant_id`.
- `availableProjects()` (nguồn của mọi validation 3 tầng):
  - Platform admin → tất cả Project (lọc theo `hq_tenant_id` nếu đang đóng vai công ty).
  - Tenant operator (HQ) → mọi Project trong tenant của user.
  - BQL cấp dự án → chỉ `accessibleProjectIds()` (∪ home project), trong phạm vi tenant.
- `buildings()` / `buildingIds()`: tòa nhà thuộc `projectId()` hiện tại (dùng làm bộ lọc bảng).
- Chuyển context: `setProject(int)`, `setWorkspace(string)`, `setHqProjects(array)` — tất cả ghi session. Route web `/context/project/{project}`, `/context/workspace/{key}`, `/context/hq-projects`, `/context/hq-tenant/{tenant}` (xem `routes/web.php`, đều trong nhóm middleware `auth`).

### 2.3 Workspace (3 tầng dưới dạng lựa chọn) — hằng `WORKSPACES`

| key | Nhãn | Ai được phép (`workspaceAllowed()`) |
|-----|------|--------------------------------------|
| `bql` | BQL Dự án | mọi nhân viên (mặc định) |
| `hq` | Cổng Công ty (HQ) | platform admin HOẶC tenant operator |
| `superadmin` | SuperAdmin | chỉ platform admin |

---

## 3. Scope truy vấn — các Concern & global scope

### 3.1 `BelongsToTenant` (`app/Models/Concerns/BelongsToTenant.php`)

- Thêm global scope `tenant`: `where(table.tenant_id, currentTenantId())`.
- `currentTenantId()` = `auth()->user()?->tenant_id`. **No-op khi `app()->runningInConsole()`** (migration/seeder/queue) và khi không có tenant.
- Khi `creating`: auto-fill `tenant_id` từ user nếu để trống.
- **Nguồn tenant CHỈ là `auth()->user()->tenant_id`** — không đọc session, không đọc header. (Lưu ý: dùng trực tiếp `user->tenant_id`, KHÔNG dùng `CurrentContext::tenantId()`, nên cơ chế "đóng vai công ty" `hq_tenant_id` KHÔNG ảnh hưởng scope model này.)

### 3.2 `BelongsToProject` (`app/Models/Concerns/BelongsToProject.php`) — opt-in

- Thêm global scope `project`. Tự phát hiện cột: `project_id` nếu bảng có; ngược lại `building_id`; nếu không có cột nào → không scope. (Kết quả cache tĩnh trong `$projectScopeColumnCache` — xem RỦI RO 5.4.)
- Nếu cột = `project_id` → `whereIn(project_id, $projectIds)`. Nếu = `building_id` → sub-select `building_id IN (select id from buildings where project_id IN $projectIds)`.
- `currentProjectIds()` trả **null (không scope)** khi: console; không có user; user là **platform admin HOẶC tenant operator**. Ngược lại (BQL cấp dự án) → `accessibleProjectIds()` (mảng rỗng = "không được cấp dự án nào" = không thấy gì).
- Khi `creating`: chỉ auto-fill cột `project_id` thật, lấy từ `CurrentContext::projectId()` (dựa session).
- Bypass: `Model::withoutGlobalScope('project')`.

### 3.3 Cách dùng `withoutGlobalScope` trong codebase (đã kiểm)

Được dùng có chủ đích ở tầng platform/HQ để nhìn xuyên tenant — mỗi chỗ là cố ý:
- `User::residentMemberships()` (`app/Models/User.php:46`): `hasMany(Resident)->withoutGlobalScope('tenant')` — dữ liệu của chính người đó xuyên mọi công ty (view resident-app / platform).
- SuperAdmin pages: `GlobalUserRegistry`, `AiKnowledgeConfig`, `PublicProjectLibrary`, concern `SharedPartnerLibrary`, page `ResidentBindingQueue` — bypass `tenant` để tổng hợp toàn nền tảng trên `ResidentBindingRequest / ResidentUnitBinding / TenantProjectLink / TenantPartnerAssignment / AiPromptTemplate`.
- Filament resources platform (Integration/Support/Webhook/DataCorrection/SupportKb): `getEloquentQuery()->withoutGlobalScopes([...])` — chỉ trong panel `/sa` (đã chặn bằng quyền panel).
- `SoftDeletableResource` (`withoutGlobalScopes([...])`) để hiện cả bản ghi đã xóa mềm.

### 3.4 Model KHÔNG có scope tenant (cố ý)

- `Tenant` (gốc cây), `UserRoleScope` (đọc trước khi có tenant context — nếu scope sẽ đệ quy vô hạn), `GlobalUserAccount` / `TwoFactorSetting` (danh tính toàn cục), `Notification` (dùng `scopeVisibleTo()` thủ công vì thông báo platform có `tenant_id` null), `User` (KHÔNG dùng `BelongsToTenant`; là danh tính toàn cục).

---

## 4. Mô hình cô lập dữ liệu theo 3 tầng

| Tầng | Panel | "Nhìn thấy" gì | Cơ chế |
|------|-------|----------------|--------|
| Platform (SuperAdmin) | `/sa` | Toàn hệ thống | `isPlatformAdmin()` (cột `is_platform_admin`); cả `tenant` và `project` scope đều no-op; Gate::before bypass |
| Tenant (Công ty vận hành / HQ) | `/hq` | Mọi dự án trong tenant | `isTenantOperator()` (có `UserRoleScope` scope_type=`tenant`); `tenant` scope bao ranh giới, `project` scope no-op |
| Project (BQL dự án) | `/admin` | Chỉ `accessibleProjectIds()` (grant scope_type=`project` ∪ `user.project_id`) | cả `tenant` scope + `project` scope áp dụng |

`accessibleProjectIds()` (`User.php`): platform admin → `null` (không giới hạn); còn lại → project từ `UserRoleScope` (scope_type=project) ∪ `user.project_id`, unique.

---

## 5. RỦI RO rò rỉ dữ liệu cho API Mobile tương lai

> Toàn bộ mô hình cô lập hiện tại được xây cho **web + session Filament**. Một API mobile (dù dùng Sanctum token) vẫn có `auth()->user()`, nên scope tenant/project **về cơ bản vẫn hoạt động** — nhưng các điểm sau cần lưu ý.

### 5.1 Scope phụ thuộc `auth()->user()`, KHÔNG phụ thuộc session token — CHẤP NHẬN được, nhưng lưu ý context
- `BelongsToTenant` lấy tenant từ `user->tenant_id` trực tiếp → hoạt động với Sanctum token. **Nhưng** với tài khoản cư dân (`account_type=resident`) `tenant_id` thường **NULL** (danh tính toàn cục) → global scope `tenant` **no-op** (điều kiện `if ($tenantId !== null)`), nghĩa là cư dân đăng nhập qua API sẽ **KHÔNG bị scope tenant** ở tầng model. Đây là rủi ro rò rỉ lớn nhất: nếu một endpoint mobile cho cư dân query trực tiếp model có `BelongsToTenant` mà user là resident (tenant_id null), bảo vệ tự động biến mất — **phải scope thủ công theo apartment/building mà cư dân được gắn**.

### 5.2 `BelongsToProject` no-op cho platform admin & tenant operator
- Nếu API mobile phục vụ nhân viên HQ/platform, scope dự án tự tắt (đúng thiết kế web). Nhưng nếu một app mobile chỉ nên thấy 1 dự án, không có ràng buộc "context dự án" nào ở tầng model cho các vai này — phải lọc thủ công theo `CurrentContext`/tham số request. Mà `CurrentContext` **đọc session** (`current_project_id`, `hq_*`) → **vô nghĩa khi request là stateless token** (không có session) → `projectId()` sẽ rơi về mặc định (home building/first project), có thể sai dự án.

### 5.3 `CurrentContext` hoàn toàn session-based → không dùng được cho API stateless
- Mọi `session(...)` trong `CurrentContext` (project switch, workspace, hq scope, đóng vai công ty) không tồn tại trong request Sanctum thuần (token). API mobile **không được** dựa vào `CurrentContext` để phân quyền; cần truyền project/tenant qua tham số đã kiểm quyền, hoặc suy ra từ membership của user.

### 5.4 Cache cột scope tĩnh (`$projectScopeColumnCache`) — rủi ro nhỏ
- Cache theo tên bảng, sống suốt vòng đời process. An toàn trong web (mỗi request mới process/worker), nhưng trong worker Octane/queue dài hạn phục vụ API vẫn ổn vì chỉ map table→column (không phụ thuộc user). Không phải rò rỉ, nhưng cần biết.

### 5.5 Model KHÔNG scope tenant có thể lộ xuyên công ty nếu expose qua API
- `Notification` chỉ an toàn khi luôn gọi `scopeVisibleTo($user)` — không có global scope. Endpoint mobile liệt kê thông báo **bắt buộc** dùng `Notification::visibleTo($user)`, nếu quên sẽ trả thông báo của mọi tenant.
- `GlobalUserAccount`, `ResidentBindingRequest`, `ResidentUnitBinding`, `TwoFactorSetting`, `UserRoleScope`: không scope tenant. Expose bất kỳ cái nào qua API phải tự lọc theo user/quyền.

### 5.6 `withoutGlobalScope('tenant')` trong quan hệ `User::residentMemberships()`
- Đây là quan hệ trả toàn bộ hồ sơ resident của người đó ở MỌI tenant. Đúng cho "app của chính cư dân", nhưng nếu vô tình dùng trong endpoint do nhân viên BQL gọi sẽ lộ dữ liệu công ty khác. Phải tách rõ endpoint "self" (cư dân) vs "quản trị" (BQL).

### 5.7 Không có tầng scope theo apartment cho cư dân
- Không có global scope nào giới hạn dữ liệu theo "căn hộ mà cư dân này thuộc về". Với API cư dân (xem hóa đơn, phản ánh, thông báo căn hộ...), toàn bộ việc lọc theo `resident_apartment_relations` / `ResidentUnitBinding` phải **viết tay** ở controller — đây là bề mặt rò rỉ chính khi xây API mobile cho cư dân.

### 5.8 Policy chỉ kiểm quyền spatie, KHÔNG kiểm quyền-theo-hàng
- Các policy (`ResidentPolicy`, `ApartmentPolicy`, ...) chỉ gọi `$user->can('View:Resident')` v.v. — kiểm **có quyền hay không**, không kiểm "bản ghi này có thuộc tenant/dự án của user". Sự cô lập theo hàng hoàn toàn dựa vào global scope. Nếu API bypass scope (ví dụ `withoutGlobalScope` hoặc truy vấn bảng không có trait) thì policy **không** cứu được → phải cẩn trọng.

---

## 6. Tệp đã đọc (tham chiếu)

- `app/Support/Context/CurrentContext.php`
- `app/Models/Concerns/BelongsToTenant.php`, `BelongsToProject.php`
- `app/Models/{Tenant,Project,Building,Floor,Apartment,Block(ref)}.php`
- `app/Models/{Resident,ResidentApartmentRelation,User,UserRoleScope,Notification}.php`
- `routes/web.php`, `bootstrap/app.php`, `config/session.php`
- `database/migrations/2026_06_28_000007_create_residents_and_structure.php`
- `docs/DEV_JOURNAL.md`, `docs/SESSION_HANDOFF_20260701_SOFTDELETE_BATCH08_BATCH10.md` (bối cảnh)
