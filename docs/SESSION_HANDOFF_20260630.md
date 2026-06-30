# X2-BMS — Session Handoff (2026-06-30)

Bàn giao ngữ cảnh để tiếp tục ở phiên khác. Đọc file này + `docs/CANONICAL_ENTITY_MAP.md` + `docs/WEB_FORM_RECONCILIATION.md` trước khi code tiếp.

---

## 1. Bối cảnh & phương pháp (đã chốt với chủ dự án)

- **Dự án**: X2-BMS Web Admin — Laravel 13 + Filament 5 + Livewire + Tailwind v4. Xây từ 3 bộ handoff dưới `D:\Chinh\x2\` (`X2_BMS_MASTER_HANDOFF_20260628`, `..._WEB_UX_SCREEN_DESIGN...20260629`, `..._Header_Profile_20260630`).
- **2 panel Filament**: `/admin` (themed navy/gold, chỉ custom Pages — UX sản phẩm) · `/fila` (stock, discover Resources CRUD). Cùng login.
- **Phương pháp build**: *data-model-first, vertical slice theo tier* (ENTITY_PRIORITY). Mỗi slice: migration (CHỈ THÊM, không sửa migration đã chạy) → model/relation/cast → seeder (review ảnh UI) → Filament/UI → verify `migrate:fresh --seed`.
- **Source of truth tên bảng** = `docs/CANONICAL_ENTITY_MAP.md` (đã giải quyết C1–C12 xung đột tên giữa 3 gói).

## 2. Quyết định kiến trúc cốt lõi (KHÔNG vi phạm)

- **RBAC 3 tầng**: Platform (`is_platform_admin`; super_admin…) → **Công ty vận hành = Tenant** (`tenant_id`, project_id null; company_admin/hq_finance/operations_director) → **Ban quản lý dự án = Project** (`tenant_id`+`project_id`; building_manager(BQL)/accountant/…). BQL scope ở **cấp Project**, KHÔNG phải building. Building chỉ là filter. Bảng `user_role_scopes` là nguồn sự thật; helpers `User::isPlatformAdmin()/isTenantOperator()/accessibleProjectIds()`.
- **Workspace = Project** (CurrentContext session-backed). Switcher 3 tầng ở header.
- **Danh tính SaaS** (xem `memory x2bms-saas-identity-model`): 1 con người = 1 `users` toàn cục (account_type=resident, tenant_id NULL, KYC) ↔ N hồ sơ `residents` per-tenant (BQL nhập, tên có thể lệch). Nối bằng **CCCD (`id_no`)**, KHÔNG bằng tên. `residents.user_id` + `link_status`.
- **X2AI = MỘT khung chat nổi duy nhất** (FAB toàn cục `/admin`). Mọi màn có AI → đẩy ngữ cảnh vào FAB, KHÔNG panel AI riêng/ô chat inline.
- **Duyệt bảng kê ở cấp lô `billing_runs`** (per tòa/kỳ), không per-căn.

## 3. ĐÃ LÀM trong phiên này

### DB / data (migrate:fresh --seed sạch — verified)
- **Slice 1 — RBAC scope**: migration `2026_06_30_000002`; `users.project_id`, bảng `user_role_scopes` + model; 14 role 3 tầng.
- **Slice 2 — Tier-1 org**: migration `..._000003`; làm giàu cột `tenants/projects/buildings/apartments`; tạo `companies/blocks/apartment_status_histories/staff_profiles/teams` (+models). Seed: tenant profile, company, project Sunshine có pháp lý/quy mô, 4 nhân viên BQL scope project, tổ đội, status history.
- **Danh tính SaaS**: migration `..._000005`; `users` thêm account_type/phone/id_no/dob/gender/nationality/kyc_status/kyc_verified_at/avatar_path; `residents` +link_status/linked_at; `ResidentIdentityMatcher`. Seed minh hoạ: account "Nguyễn Văn Anh" ↔ "Nguyễn Văn A"@Sunshine + "Anh A"@Đại Phúc (tenant 2), cùng CCCD.
- **Resident form mở rộng**: migration `..._000004` (KYC/avatar/contact/occupation/documents…).
- **Slice 3a — Fee catalog**: migration `..._000006`; `fee_types/fee_rates/fee_formulas(+versions)/fee_scope_assignments`. Seed 5 loại phí khớp demo.
- **Slice 3b — Billing**: migration `..._000007`; cột cho statements/statement_lines/billing_periods; `billing_runs(+items)/statement_approvals/statement_publish_logs`.
- **Slice 3c — Payments**: migration `..._000008`; `payment_methods/bank_accounts/payments/payment_allocations/receipts/bank_statement_imports/bank_transactions/reconciliation_matches`.
- **billing_runs approval lifecycle**: migration `..._000010` (approval_status/apartment_count/created_by/approver/sla/note). Seed 7 lô đa trạng thái.
- (migration `..._000009` thêm `statements.approval_status` — lifecycle per-statement, giữ lại.)
- Demo: 2 tenant, dự án Sunshine Garden (Tòa A 120 + B 40) + Riverside (R1/R2/R3, data) + Đại Phúc (cross-company).

### UI
- **Bespoke `/admin`**: `ResidentCreate` (Thêm cư dân, WEB-FORM-02-01, 3 cột + form 6 section + avatar + KYC + upload); `StatementApprovalQueue` (`/admin/finance/statement-approvals`, WEB-FORM-07-04, duyệt lô billing_runs, KPI + bulk Duyệt/Từ chối/Yêu cầu bổ sung/Phân công + quy trình 5 bước). Verified render 200 + action thật.
- **`/fila` resources**: Tier-1 forms giàu (Tenant/Project/Building/Apartment + StaffProfile) + cụm Tài chính (FeeType/FeeRate/BillingPeriod/Statement/Payment/BankTransaction, sinh bằng `make:filament-resource --generate`, nhóm "Tài chính – Phí").
- **WEB-UX-03 Workspace switcher**: chip header, route `/context/workspace/{key}` (audited), gate theo RBAC (platform thấy 3, project staff chỉ bql + locked state).
- **WEB-UX-09 X2AI Copilot** (đã nối API thật, verified live):
  - FAB nổi toàn cục `<x-x2.ai-fab>` (panel ×1.2 = `w-96` + `max-h-[21.6rem]`), chat Livewire `App\Livewire\X2aiChat`.
  - `App\Support\X2AI\X2aiClient` → Anthropic Messages API qua Laravel Http; config `config/services.php → x2ai` (key `X2AI_API_KEY`/`ANTHROPIC_API_KEY`, model `X2AI_MODEL` mặc định `claude-haiku-4-5`).
  - **2 chế độ**: *Ngữ cảnh* (đọc roles/workspace/project + accessibleScreens + **màn hình hiện tại đọc từ DOM** `window.x2aiCaptureScreen()` `.fi-main` innerText + **upload ảnh/PDF** → vision/document blocks) · *Tra cứu CSDL* (tool `lookup_data` → `X2aiDataConnector`, stub đến khi có `X2AI_DATA_API_URL`).
- **WEB-UX-09 "X2 AI Engine" — 4 màn bespoke `/admin` DONE + verified (Slice AI Engine):**
  - DB: migration `..._000011_create_ai_engine_tables` (CHỈ THÊM) + 7 model: `ai_usage_logs` (audit từng lượt AI: surface/mode/model/action/risk_level/status/requires_approval/tokens/cost), `ai_policies`, `ai_prompt_templates`, `ai_workflows`(+steps json)/`ai_workflow_runs`, `knowledge_categories`/`knowledge_articles`. Seed `DemoDataSeeder::seedAiEngine` (90 log/30 ngày, 7 chính sách, 8 prompt, 6 workflow + runs, 6 danh mục / 17 bài KB).
  - Nav group mới **'X2 AI Engine'** (icon sparkles). 4 Page, KPI/biểu đồ đều TÍNH từ DB (không hardcode): `AiCenter` (`ai/center`, 09-01 Trung tâm AI), `AiGovernance` (`ai/governance`, 09-02 — tab Alpine, tab Audit = HasTable trên ai_usage_logs), `AiWorkflowAutomation` (`ai/workflows`, 09-03 — chọn workflow → canvas node từ steps + cấu hình + nhật ký chạy), `AiKnowledgeBase` (`ai/knowledge`, 09-04 — HasTable bài viết + danh mục + Support Copilot CTA).
  - Nút "Gợi ý nhanh" / Support Copilot → window event `x2ai-open` (FAB nghe `x-on:x2ai-open.window`) + Livewire `x2ai-prefill` → `X2aiChat::prefill()`. Verified: migrate:fresh --seed sạch, php -l, getViewData, view:cache, **4 màn HTTP 200** (đã đăng nhập).

## 4. Quy ước/bẫy kỹ thuật (đã trả giá)
- Filament closure cột: tham số state PHẢI tên `$state` (đặt `$s` → 500 BindingResolutionException).
- Action method nhận records: type-hint `Illuminate\Support\Collection` (không `Eloquent\Collection`) — vì action 1-dòng bọc `collect([$r])`.
- **Thêm class Tailwind mới trong blade ⇒ phải `npm run build`** (Tailwind v4 quét lúc build; `@source` đã gồm app/Filament, resources/views/filament, components/x2).
- Bảng con không có `tenant_id` ⇒ KHÔNG dùng `$scope` (có building_id) khi create.
- PHP/Herd: `php` chạy trên PowerShell PATH; Bash dùng `~/.config/herd/bin/php.bat`.

## 5. Cách chạy & verify
- Reseed: `php artisan migrate:fresh --seed` (DemoDataSeeder).
- Build assets: `npm run build` (sau mỗi đổi class Tailwind). Hard refresh trình duyệt.
- Login: `x2bms@x2bms.vn` / `Bms@2026!` (super_admin, platform).
- Resident account demo (app, không vào /admin): `nguyenvananh@gmail.com` / `Resident@2026!`.
- X2AI: cần `.env` → `X2AI_API_KEY=...` (đã cấp & verified), `X2AI_MODEL=claude-haiku-4-5`; chạy `php artisan config:clear`.
- Render-test headless: tinker script dựng `Request` + `Kernel::handle` + `auth()->guard('web')->setUser($admin)` (xem scratchpad cũ).

## 6. CÒN LẠI / việc tiếp theo (đề xuất ưu tiên)
1. **Form/list còn thiếu**: Filament form `/admin` cho fee catalog (WEB-FORM-06), billing (07-01/02/03), và **màn Công nợ & thanh toán (WEB-FORM-08)** bespoke `/admin` (KPI + bảng công nợ theo căn + donut kênh thanh toán + nhắc nợ). Các màn duyệt khác reuse pattern `StatementApprovalQueue`.
2. **X2AI client-side actions** (chủ dự án quan tâm): tool `navigate/click/fill` để AI thao tác trên trang (Alpine/Livewire dispatch). Hiện AI mới ĐỌC màn hình.
3. **Mode 2 CSDL**: khi chủ dự án cấp API → map `X2aiDataConnector::query()` theo shape thật + test.
4. **Markdown trong bong bóng chat** (AI trả lời có `**`/`#` hiển thị thô) — render markdown hoặc ép plain text.
5. **WEB-UX tiếp**: 02 Profile/2FA/Phiên (đang link `#`), 07 Global search/command palette, 08 Notification center (cần bảng `notifications`), 10 Audit UI.
6. **Slice DB tiếp**: Tier-2 notifications + feedback children; Tier-3 ops (work_order con, funds/cash_vouchers, security/patrol).
7. **Còn ở tầng data, CHƯA có UI**: phần lớn Round 2/3 của gói WEB_ACTION (Form Builder, IoT/IOC, contractors, marketplace, SaaS billing, AI governance…). 4 ảnh thiếu cần re-upload: WEB-FORM-10-02, 13-02, 13-04, 14-02.

## 7. Memory liên quan (đã ghi, tự nạp phiên sau)
- `x2bms-web-admin-architecture` — 2 panel, context, theme, RBAC 3 tầng, workspace switcher, X2AI (FAB/2 mode/đọc DOM).
- `x2bms-saas-identity-model` — danh tính toàn cục vs hồ sơ per-tenant.
- `x2bms-webform-build-track` — tiến độ slice + 2 bẫy Filament.
- `x2bms-build-roadmap` — track UX pages.
