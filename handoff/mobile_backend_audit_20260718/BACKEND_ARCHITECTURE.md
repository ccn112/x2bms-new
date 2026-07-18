# BACKEND_ARCHITECTURE — X2-BMS

> Phân tích kiến trúc backend (READ-ONLY audit) — ngày 2026-07-18, HEAD `3d34216`.
> Trọng tâm: đánh giá mức độ sẵn sàng cho mobile. **Kết luận nhanh ở §7.**

---

## 1. Kiến trúc theo module/miền

X2-BMS là **ứng dụng Filament (admin panel) đa panel**, KHÔNG phải backend API. Nghiệp vụ
được tổ chức theo **3 tầng tổ chức ↔ 4 Filament panel**:

| Panel | Path | Provider | Đối tượng | Guard panel |
|---|---|---|---|---|
| **BQL** (Ban quản lý dự án) | `/admin` | `AdminPanelProvider` (default) | Nhân sự vận hành 1 dự án | `Authenticate` + `canAccessPanel` |
| **HQ** (Công ty vận hành / Tenant) | `/hq` | `HqPanelProvider` | Điều hành đa dự án cấp công ty | `Authenticate` + `EnsureHqAccess` |
| **SuperAdmin** (nền tảng SaaS) | `/sa` | `SaPanelProvider` | Quản trị nền tảng | `Authenticate` (platform admin) |
| **Fila** (CRUD gốc) | `/fila` | `FilaPanelProvider` | CRUD thô ~150 Resource | `Authenticate` |

- `/admin`, `/hq`, `/sa` chỉ **discoverPages** (trang bespoke thiết kế theo DS-01), KHÔNG
  discover Resources → tránh đụng slug với `/fila`.
- `/fila` là nơi duy nhất **discoverResources** (`app/Filament/Resources/*`) — CRUD nguyên bản
  cho ~150 model, dùng khi cần thao tác dữ liệu trực tiếp.
- Mỗi Resource theo layout Filament v5: thư mục con `Pages/` + `Schemas/` (form) + `Tables/` (bảng).

### Tổ chức nghiệp vụ theo miền (qua trang Filament)
- **BQL `/admin`** (35 trang): Cư dân & Căn hộ (danh sách, chi tiết 360, cây căn hộ, quan hệ
  hộ khẩu, move-in/out, data quality), An ninh & Kiểm soát (thẻ ra vào, xe, dashboard), Vận hành
  (work-order kanban, feedback, my-work), Tài chính–Phí (sổ công nợ, aging, catalog phí, kỳ phí,
  bảng kê + duyệt), Hệ thống (audit log, phiên đăng nhập, bảo mật, hồ sơ).
- **HQ `/hq`** (56 trang): Tài chính đa dự án (overview, cashflow, công nợ theo dự án/loại phí,
  collection rate, forecast), Dự án (directory, create, assignment, modules, package), Nhân sự &
  phân quyền (RBAC matrix, role management), Billing nền tảng (invoices, wallet, pass-through),
  AI (governance, knowledge base/sources/test, workflow automation), Support/SLA, Form builder.
- **SuperAdmin `/sa`** (39 trang): SaaS revenue, subscription/contract, invoice generation, usage
  metering, quota alert, Integration Center (connections/api-keys/webhooks/security), Support Center,
  Platform CMS/Knowledge, Design System (DS-01 catalog), thư viện nhà cung cấp/nhà thầu/template.

---

## 2. Luồng request

### Web / Filament (Livewire) — luồng CHÍNH
- Toàn bộ tương tác đi qua **session** (`config/session.php` driver = `database`).
- Middleware panel (ví dụ `AdminPanelProvider`): `EncryptCookies → AddQueuedCookies →
  StartSession → AuthenticateSession → ShareErrors → PreventRequestForgery (CSRF) →
  SubstituteBindings → …`, auth = `Filament\Http\Middleware\Authenticate`.
- Guard mặc định = `web` (session + Eloquent provider `App\Models\User`) — xem `config/auth.php`.
- `bootstrap/app.php`: guest bị redirect về `route('filament.admin.auth.login')`; đăng ký alias
  middleware `platform.admin`.
- Ủy quyền: `User::canAccessPanel()` cho phép vào panel nếu `is_platform_admin` HOẶC có role bất kỳ
  (chặn 5M cư dân, chỉ ~10k nhân sự). `AppServiceProvider::boot()` đặt `Gate::before` cho
  `super_admin` bypass mọi authorization.
- Multi-tenancy: trait `App\Models\Concerns\BelongsToTenant` thêm **global scope** theo `tenant_id`
  lấy từ user đăng nhập + auto-fill khi tạo; no-op trong console (seeder/migration). Có
  `BelongsToProject` tương tự cho scope dự án.

### API (`routes/api.php`) — luồng PHỤ, rất hẹp
- `bootstrap/app.php` bật render JSON cho `api/*` khi có exception.
- **Xác thực API KHÔNG dùng token Sanctum.** Tất cả route bọc `platform.admin`
  (`EnsurePlatformAdmin`) đọc `$request->user()` / `auth()->user()` — tức **vẫn dựa vào phiên
  Filament** (comment trong file: *"Xác thực qua phiên Filament (actingAs trong test)"*).
- **Sanctum tồn tại nhưng chưa được nối làm auth API**: `User` có trait `HasApiTokens`, nhưng
  KHÔNG có `config/sanctum.php` được publish, KHÔNG có route nào dùng `auth:sanctum`, và KHÔNG có
  chỗ nào gọi `createToken()` (chỉ xuất hiện `HasApiTokens` ở `User` + concern reset mật khẩu cư dân).
  → Bề mặt token API cho mobile hiện **chưa dựng**.

---

## 3. Mẫu service / action / workflow

- **KHÔNG có** pattern Action/Job/Command-bus. Logic nghiệp vụ nằm ở 2 nơi:
  1. **Trực tiếp trong Filament Pages/Resources** (Livewire) — mỗi trang tự query, tự validate,
     tự ghi audit qua trait. Ví dụ `BillingInvoiceController::generate()` gói logic sinh hóa đơn
     inline trong `DB::transaction` (đây là controller, nhưng cùng phong cách "logic tại điểm dùng").
  2. **`app/Support/*` — service theo miền** (9 lớp):

| Service | Vai trò |
|---|---|
| `Support\Context\CurrentContext` | Nguồn chân lý về ngữ cảnh: project/tenant/workspace/HQ-scope. Singleton (đăng ký ở `AppServiceProvider`). Session-backed, validate theo quyền user. **Mọi trang đều phụ thuộc lớp này để scope dữ liệu.** |
| `Support\Platform\FeatureGateService` | Giải quyền tính năng hiệu lực cho tenant: `plan_features + entitlements + overrides − expired − suspended`. Chuẩn để gate hiển thị gói. |
| `Support\Identity\ResidentIdentityMatcher` | Khớp danh tính cư dân toàn cục (theo CCCD, không theo tên) giữa các tenant. |
| `Support\Knowledge\DocumentTextExtractor` | Trích text tài liệu (PDF qua `smalot/pdfparser`) cho Knowledge Base/AI. |
| `Support\Integration\IntegrationSecret` | Xử lý secret cho Integration Center (trả 1 lần khi tạo/rotate). |
| `Support\X2AI\X2aiClient` | Client mỏng bọc Anthropic Messages API (dùng Laravel HTTP/Guzzle, không SDK). Hỗ trợ tool-use (Mode 2 tra cứu DB). Ghi telemetry token/latency. |
| `Support\X2AI\X2aiDataConnector` | Thực thi tool tra cứu dữ liệu cho AI. |
| `Support\X2AI\X2aiKnowledgeConnector` | Kết nối tri thức/RAG cho AI. |
| `Support\X2AI\X2aiPolicyGate` | Chốt chính sách/guardrail AI (rule-based, không tự do). |

- **Concern (trait) dùng chung** ở `app/Filament/Concerns/` thay cho lớp Action: `WritesAudit`,
  `WritesBillingAudit`, `WritesIntegrationAudit`, `WritesSupportAudit` (ghi `*_audit_logs`),
  `FinanceScope`, `HqScreen`, `PlatformScreen`, `ProvidesAiContext`, `ResetsResidentPassword`,
  `SharedPartnerLibrary`, `SoftDeletableResource`.

---

## 4. Queue / Event / Notification / Broadcasting

| Hạ tầng | Trạng thái |
|---|---|
| **Queue** | `config/queue.php` default = `database` (`QUEUE_CONNECTION`). **NHƯNG không có Job nào** (`app/Jobs` không tồn tại), nên không có gì được dispatch lên queue. `composer dev` có chạy `queue:listen` nhưng thực tế rỗng. |
| **Events / Listeners** | **KHÔNG có** (`app/Events`, `app/Listeners` trống/không tồn tại). Chỉ dùng model events nội bộ của trait (`creating`, `bootBelongsToTenant`). |
| **Notifications** | **KHÔNG có** `app/Notifications`. Thông báo trong app dùng Filament `Notification::make()` (toast UI), không phải kênh mail/database notification của Laravel. |
| **Broadcasting** | **KHÔNG có** `config/broadcasting.php`, không WebSocket/realtime. |
| **Mail** | Có `config/mail.php` + `config/services.php` (postmark/resend/ses/slack). Reset mật khẩu cư dân đi qua `ResidentPasswordResetController` (web, token). |
| **Scheduler** | 1 tác vụ: `logs:archive` chạy `dailyAt('02:30')` (`ArchiveStaleLogs`) — dồn log/audit cũ sang bảng `*_archive` (`config/archive.php`). Kèm `spatie/laravel-schedule-monitor`. |
| **Activity log** | `spatie/laravel-activitylog` + bảng `audit_logs` tự quản (ghi qua trait `WritesAudit` và inline trong `routes/web.php` khi đổi ngữ cảnh). |

---

## 5. Tích hợp AI (X2AI Copilot)

- Cấu hình ở `config/services.php → x2ai`: `X2AI_API_KEY` (fallback `ANTHROPIC_API_KEY`),
  model mặc định `claude-haiku-4-5` (override `X2AI_MODEL`), endpoint Anthropic Messages API.
- Surface UI: Livewire `X2aiChat` + nút nổi (`<x-x2.ai-fab />`) trên mọi trang `/admin`.
- **Mode 2 (tra cứu DB) chưa bật hoàn toàn**: `x2ai.data_api.url/token` để trống → tool tra cứu
  báo *"not configured"* thay vì lỗi (điểm làm dở, xem §6).

---

## 6. Những điểm đang làm dở / stub / placeholder

- **API nghiệp vụ cho mobile = 0.** Chỉ có 3 nhóm API platform-admin (billing/integration/support),
  không có endpoint cho cư dân/BQL. (Đây là khoảng trống lớn nhất cho mobile.)
- **Sanctum chưa nối token auth**: `HasApiTokens` có sẵn trên `User` nhưng chưa có config publish,
  chưa có route `auth:sanctum`, chưa có `createToken()`. Nền có nhưng chưa dựng.
- **X2AI Mode 2 (data lookup)** để ngỏ chờ data API (`X2AI_DATA_API_URL/TOKEN` trống).
- **StatementApprovalQueue** có 2 bước quy trình đánh dấu `'todo'` (Duyệt cuối / Phát hành) —
  cột trạng thái wizard chưa hoàn tất (`app/Filament/Pages/StatementApprovalQueue.php:105-106`).
- Phần lớn kết quả grep `placeholder` là **placeholder UI của Filament** (giá trị "—" khi cột rỗng),
  KHÔNG phải stub logic — đã loại trừ khi đánh giá.
- Test coverage rất mỏng: chỉ 3 Feature test (đúng cho 3 nhóm API platform) + 4 Playwright spec cho
  shell UI. Toàn bộ nghiệp vụ BQL/HQ trong Filament Pages **không có test tự động**.

---

## 7. THỰC TẾ QUAN TRỌNG CHO MOBILE (đọc kỹ)

> **Business logic sống trong Filament Pages (Livewire), không trong API.**

1. **Bề mặt API cực hẹp và sai đối tượng.** `routes/api.php` chỉ phục vụ SuperAdmin/Billing admin
   (billing, integration, support). Không có bất kỳ endpoint nào cho các nghiệp vụ mobile cần:
   cư dân, căn hộ, phí/công nợ, thanh toán, phản ánh (feedback), work-order, thẻ ra vào, thông báo…
2. **Xác thực API hiện dựa vào phiên Filament, không phải token.** Ngay cả API platform cũng đọc
   `auth()->user()` từ session; không có luồng đăng nhập cấp token cho client di động. Sanctum
   `HasApiTokens` đã bật ở model nhưng **chưa được nối** (không config, không route, không createToken).
3. **Toàn bộ CRUD + quy tắc nghiệp vụ nằm trong ~130 trang Filament + ~150 Resource**, phụ thuộc
   nặng vào `CurrentContext` (session), global scope `BelongsToTenant/BelongsToProject`, và trait
   audit. Không có tầng service/Action tái sử dụng độc lập với Filament → **không thể tái dùng trực
   tiếp cho API** mà không refactor: cần bóc logic ra service, thay `CurrentContext` (session) bằng
   scope theo token/user, và dựng lớp API + Sanctum.
4. **Không có realtime/queue/notification hạ tầng** cho push mobile (không broadcasting, không job,
   không Laravel notification). Push/thông báo cho app sẽ phải xây mới.
5. **Điểm tựa có thể tái dùng**: model Eloquent (286) + multi-tenancy scope + `FeatureGateService` +
   `CurrentContext` (cần biến thể không-session) + spatie/permission. Đây là nền dữ liệu tốt; phần
   thiếu là **tầng API + auth token + serialization (Resource/DTO)**.

**Tóm tắt một câu:** X2-BMS hiện là hệ quản trị web-Filament hoàn chỉnh về dữ liệu nhưng **gần như
chưa có backend API cho mobile** — muốn có app cần dựng thêm lớp API (routes + controllers/Resources),
bật Sanctum token auth, và bóc nghiệp vụ khỏi Filament Pages thành service dùng chung.
