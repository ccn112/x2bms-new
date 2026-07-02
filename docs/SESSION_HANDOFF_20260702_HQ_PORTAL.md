# X2-BMS — Session Handoff (2026-07-02) · HQ Portal (Cổng Công ty) + chuẩn bị Web BQL

Bàn giao phiên 2026-07-02. Phiên này: (1) fix `migrate:fresh` FAIL do `getTableListing()`; (2) **xây trọn HQ Portal** — panel `/hq` (tầng GIỮA: Platform → **HQ/Công ty vận hành** → BQL), **50/50 màn** qua 5 batch. Đọc kèm `docs/DEV_JOURNAL.md` (7 entry mới nhất ở đầu là chi tiết phiên này).

## 0. Đọc trước (reading order phiên sau)
1. `docs/DEV_JOURNAL.md` — nhật ký từng thay đổi (mới nhất ở đầu).
2. File này + `docs/SESSION_HANDOFF_20260701_SOFTDELETE_BATCH08_BATCH10.md` (Batch 08/10) + `..._SUPERADMIN_BILLING.md` (Batch 07/SuperAdmin).
3. Memory tự nạp: `x2bms-hq-portal-build-track` (chi tiết HQ), `x2bms-web-admin-architecture`, `x2bms-dev-env`, `x2-bms-backend-runbook`.
4. Handoff nguồn HQ: `D:\Chinh\x2\handoff\X2_BMS_HQ_FULL_CLAUDE_CODE_HANDOFF_20260702\`.

## 1. Trạng thái git
- Branch `main`; commit cuối `e672bef "platform"`. **Toàn bộ công việc HQ CHƯA commit** (working tree dirty): 7 file sửa + 48 file mới. Chưa commit theo quy ước (chờ chủ dự án yêu cầu). Remote `origin` = `github.com/ccn112/x2bms-new`.

## 2. Fix đầu phiên — migrate:fresh FAIL
- `Schema::getTableListing()` (Laravel 13/MySQL) trả bảng của **MỌI database** trên server (`appsale._action_logs`…). Migration soft-delete `2026_07_01_000025` quét nhầm → sửa `includeTables()` lọc theo `DB::connection()->getDatabaseName()`. Chi tiết: DEV_JOURNAL entry "2026-07-02 — Fix migrate:fresh".

## 3. HQ Portal — as-built (tầng Tenant HQ)
**Kiến trúc (Phase 0):** panel riêng `app/Providers/Filament/HqPanelProvider.php` (id/path `hq`), gate `app/Filament/Concerns/HqScreen.php` (platform admin | tenant operator), middleware `app/Http/Middleware/EnsureHqAccess.php`. Context = tenant + **đa dự án**: `CurrentContext::hqProjectIds()/setHqProjects()/hqAllProjectsSelected()` + `session('hq_tenant_id')` (platform admin thao tác "as a company"). Routes context: `POST /context/hq-projects`, `GET /context/hq-tenant/{tenant}`. Shell: `resources/views/filament/hq/{brand,header-cluster}.blade.php`. Pages: `app/Filament/Hq/Pages/*`, views `resources/views/filament/hq/pages/*`. Tái dùng bộ component X2 + `admin/theme.css` + X2AI fab.

**7 nav group** (đúng handoff): Tổng quan · Quản lý dự án · Nhân sự & BQL · Billing & Gói dịch vụ · Biểu mẫu & Tri thức · Hỗ trợ & Phân quyền · Báo cáo.

**5 batch × 10 màn = 50 màn (+landing `/hq/overview`), 51/51 route render HTTP 200:**
| Batch | Nav group | Migration | Điểm chính |
|---|---|---|---|
| HQ-01 Dự án/BQL/nhân sự/gói | Quản lý dự án + Nhân sự & BQL | `2026_07_02_000001` | 7 bảng mới (bql_teams, employee_project_assignments, employee_assignment_histories, project_subscription_periods, project_module_overrides, import_batches, import_batch_rows) |
| HQ-02 Billing/ví/platform | Billing & Gói dịch vụ | `..._000002` | 7 bảng (wallets, wallet_transactions, wallet_topup_requests, billing_rate_cards, plan_change_requests(+items), metric_snapshots) + reuse Batch 07 |
| HQ-05 Tài chính công nợ đa dự án | Báo cáo | `..._000003` | 8 bảng (debt_reminder_campaigns/logs, cash_funds/transactions, expenses, report_schedules/export_jobs, ai_insights) + aggregate qua metric_snapshots |
| HQ-03 Tài liệu/biểu mẫu/AI KB | Biểu mẫu & Tri thức | `..._000004` | 12 bảng (documents/libraries/versions, sop/checklist templates+items, template_assignments, config_inheritance_rules, ai_knowledge_sources/sync_logs, ai_test_questions/runs) + reuse dynamic_forms/knowledge_* |
| HQ-04 Phân quyền/hỗ trợ | Hỗ trợ & Phân quyền | `..._000005` | 4 bảng (permission_groups(+items), two_factor_settings, login_sessions) + reuse spatie/user_role_scopes/support_* (Batch 10) |

**Nguyên tắc dữ liệu:** listing thực = bản ghi thật; số headline lớn (docs 1.842, users 1.248, tickets 386, công nợ 1.024 tỷ…) + dashboard/aggregate dùng `metric_snapshots` (đúng khuyến nghị handoff, tránh seed hàng chục nghìn dòng). Tất cả trong tenant demo **Sunshine Group** (`T-SSG-HQ`).

## 4. Seed & tài khoản
- Tenant HQ demo: **Sunshine Group** (`T-SSG-HQ`), 24 dự án, 128 nhân sự.
- **HQ operator:** `hq@sunshinegroup.vn` / `Bms@2026!` (role `company_admin`, scope cấp tenant → `isTenantOperator()` true).
- Platform admin: `x2bms@x2bms.vn` / `Bms@2026!` (vào `/hq` chọn công ty ở thanh trên).
- Seed methods trong `DemoDataSeeder`: `seedHq01/seedHq02/seedHq05/seedHq03/seedHq04` (gọi cuối `run()`).

## 5. Cách chạy & verify
```powershell
php artisan migrate:fresh --seed      # sạch ~37s; 5 migration HQ 000001–000005 OK
php artisan view:clear
```
- **Render headless:** `_render_hq.php` (gitignore) ở project root — `php _render_hq.php "billing/overview,users,..."` render mỗi `/hq/<slug>` in HTTP status. **Mặc định login `hq@sunshinegroup.vn`** (tenant-scoped, để context công ty đúng); tham số 2 để đổi user. Dùng lại cho mọi verify HQ.
- Bash dùng `~/.config/herd/bin/php.bat`.

## 6. Bẫy đã trả giá (nhớ khi build BQL tiếp)
1. **Record sub-page có slug `{param}` vẫn đăng ký nav** → Filament dựng link thiếu param → 500 mọi màn. Fix: override `public static function shouldRegisterNavigation(): bool { return false; }`.
2. **Filament route-model-binding**: `{project}`/`{ticket}` tự resolve ra Model → `mount(Project $project)` (không `int`). Guard tenant phải **bypass cho platform admin**.
3. **Page class trùng tên model import** (vd `BillingReconciliation`) → "Cannot redeclare class" → alias `use ... as XxxModel`.
4. **`dynamic_forms.current_version` là INTEGER** (không phải string 'v2.3').
5. **Heredoc inline dễ vỡ** khi content có ký tự đặc biệt → viết file generator `.sh` bằng Write rồi `bash`, xoá sau.
6. Div-by-zero ở chart khi tenant rỗng data → `max(array_merge([1], ...))`.

## 7. Việc còn lại của HQ (tùy chọn, không chặn BQL)
- Nhiều form/wizard HQ đang **read-rich (UI-level)**; mới `ProjectCreate` (HQ-01-02) + `ProjectAssignment.assign()` (HQ-01-06) ghi thật. Wire ghi thật cho các form/wizard khác nếu cần.
- Sanctum stateless cho API; Playwright screenshot per màn.
- Một số headline dùng metric_snapshots thay vì N bản ghi thật — cố ý (demo).

## 8. Khởi động Web BQL (phiên sau)
- **BQL = tầng Project** (vận hành MỘT dự án): `tenant_id + project_id`, workspace = 1 project (dùng `CurrentContext::projectId()` đã có, KHÔNG phải đa dự án như HQ). Building chỉ là filter.
- Workspace switcher đã có key `'bql'` trong `CurrentContext::WORKSPACES`; scope `accessibleProjectIds()` cho BQL staff.
- Panel: cân nhắc panel `/bql` riêng (giống mẫu HqPanelProvider) HOẶC dùng lại `/admin` (đã có nhiều màn BQL: Cư dân, Vận hành, Tài chính…). Hỏi chủ dự án chọn hướng như đã làm với HQ.
- Tái dùng mạnh: `residents/apartments/vehicles/access_cards`, `work_orders/sla_events/ioc_alerts`, `feedbacks`, `statements/billing_runs/debts/payments` (resident finance), `visitors/packages`, `amenities/bookings`, `community/events/polls`, `access_devices/logs`. Phần lớn bảng nghiệp vụ BQL ĐÃ có từ các phiên trước — chủ yếu là dựng UI theo ảnh handoff BQL.
- **Handoff BQL:** KHÔNG có gói riêng — màn BQL nằm trong `D:\Chinh\x2\handoff\X2_BMS_MASTER_HANDOFF_20260628\` (gói App Cư dân/BQL, xem `MODULE_BUILD_ORDER`) + `X2_BMS_WEB_UX_SCREEN_DESIGN_HANDOFF_FOR_CLAUDE_CODE_20260629`. Đọc `docs/BQL_FLOW_PLAN.md` (đã có sẵn) trước.
- **Quy ước bắt buộc:** mỗi lần đổi code ⇒ append 1 entry `docs/DEV_JOURNAL.md` trước khi báo cáo.
