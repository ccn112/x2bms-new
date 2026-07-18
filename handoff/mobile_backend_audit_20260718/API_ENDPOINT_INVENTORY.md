# API_ENDPOINT_INVENTORY — X2-BMS Backend (Audit 2026-07-18)

> **Kiểu tài liệu:** Kiểm kê READ-ONLY, không sửa mã nguồn. Nguồn: `routes/api.php`, `routes/web.php`, `bootstrap/app.php`, các controller trong `app/Http/Controllers/Platform/*` + `app/Http/Controllers/Auth/*`, và các Filament Page trong `app/Filament/*`.

---

## 0. HEADLINE (đọc trước tiên)

**Toàn bộ API hiện có của X2-BMS là API dành RIÊNG cho nền tảng / SuperAdmin (platform-only). KHÔNG có bất kỳ endpoint nào phục vụ cư dân (resident) hay Ban Quản lý (BQL).**

Cụ thể:

1. `routes/api.php` (~162 dòng) chỉ chứa 3 nhóm, tất cả nằm sau middleware `platform.admin`:
   - `prefix: platform/billing` — vòng đời SaaS billing (thuê bao, usage, hóa đơn, ví pass-through, điều chỉnh, audit).
   - `prefix: platform/integrations` — Integration Center (connections, API keys, webhooks, events, retry queue, security).
   - `prefix: platform/support` — Support Center (ticket, data-correction, knowledge base).
2. **Xác thực KHÔNG dùng Sanctum token.** Middleware `platform.admin` (`App\Http\Middleware\EnsurePlatformAdmin`) chỉ đọc `auth()->user()` (phiên đăng nhập Filament / `actingAs` trong test) và kiểm tra `->isPlatformAdmin()`. Không có guard token, không có `/oauth`, không có `/sanctum` route, không có endpoint đăng nhập API.
3. **KHÔNG có API Resource nào** — thư mục `app/Http/Resources` không tồn tại. Mọi phản hồi là model Eloquent / paginator / mảng thô được `response()->json(...)` trực tiếp.
4. Nghiệp vụ resident/BQL (duyệt cư dân, khóa/mở tài khoản, đặt lại mật khẩu, đổi trạng thái căn hộ, cấp/thu thẻ, duyệt xe, phản ánh, thông báo...) **chỉ tồn tại dưới dạng Filament Page "actions" (server-side UI)** — xem §5. Không có endpoint HTTP tương ứng.

**Kết luận cho đội mobile:** Ứng dụng Flutter cư dân và app BQL đều **KHÔNG dùng lại được bất kỳ endpoint nào** đang có. Cần **xây một tầng Mobile API hoàn toàn mới** (khuyến nghị: guard token Sanctum/Passport, prefix riêng ví dụ `/api/mobile/*` hoặc `/api/v1/*`, kèm chuẩn envelope/error — xem file `API_RESPONSE_AND_ERROR_STANDARD.md`).

---

## 1. Cấu hình định tuyến & middleware (bối cảnh)

Trích `bootstrap/app.php`:

- `withRouting(web: routes/web.php, api: routes/api.php, ...)` → routes trong `api.php` **tự động mang prefix `/api`** và middleware group `api` mặc định của Laravel. Vì vậy đường dẫn đầy đủ là `/api/platform/...`.
- `->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'))` → mọi lỗi (validation, 403, 404, 500) dưới `/api/*` được **render dạng JSON** thay vì HTML.
- Alias middleware: `platform.admin => App\Http\Middleware\EnsurePlatformAdmin`.
- Guest bị chuyển hướng tới `route('filament.admin.auth.login')` (không phải JSON) — chỉ áp dụng cho route web guarded, không cho `/api/*`.

`EnsurePlatformAdmin::handle()`:
```
$user = $request->user() ?? auth()->user();
if (! $user || ! $user->isPlatformAdmin()) {
    abort(403, 'Chỉ SuperAdmin/Billing admin được truy cập API billing.');
}
```
→ Không có kiểm tra permission chi tiết (Filament Shield / Spatie) ở tầng API; chỉ 1 cổng nhị phân `isPlatformAdmin()`. Phân quyền mịn hơn (billing vs integration vs support) **chưa** được tách ở tầng route.

**Scope:** tất cả endpoint API hiện tại là **platform-level** (SaaS operator/SuperAdmin), không mang scope tenant/project của người gọi — dữ liệu được lọc theo query param (`tenant_id`, `status`...) chứ không theo ngữ cảnh người dùng.

---

## 2. Nhóm Platform / Billing

Prefix đầy đủ: `/api/platform/billing` · Middleware: `platform.admin` · Scope: **platform** · Auth: phiên Filament (không token) · Permission: `isPlatformAdmin()` · Response: **model/paginator thô, không Resource**.

| Method | Route | Controller@action | Request (params/body) | Response (shape) | Status | Mobile readiness |
|---|---|---|---|---|---|---|
| GET | /api/platform/billing/revenue-dashboard | SaasRevenueController@index | — | JSON thô: `{mrr, arr, active_subscriptions, trial, churn, overage_revenue, overdue_invoices, mrr_by_plan[], top_tenants[]}` | implemented | Platform-only |
| GET | /api/platform/billing/subscriptions | TenantSubscriptionController@index | query: `status?`, `tenant_id?`, `per_page?`(20) | **Laravel paginator** của TenantSubscription (with tenant, plan) | implemented | Platform-only |
| POST | /api/platform/billing/subscriptions | @store | body: `tenant_id*`, `plan_id*`, `billing_cycle*`(monthly\|quarterly\|yearly), `mode?`(active\|trial), `start_date?`, `end_date?`, `auto_renew?` | model TenantSubscription (with items), **201** | implemented | Platform-only |
| GET | /api/platform/billing/subscriptions/{subscription} | @show | route model bind | model (with tenant,plan,contract,items,addons,invoices) | implemented | Platform-only |
| POST | .../subscriptions/{subscription}/upgrade | @upgrade | body: `plan_id*` | model fresh | implemented | Platform-only |
| POST | .../subscriptions/{subscription}/downgrade | @downgrade | body: `plan_id*` | model fresh | implemented | Platform-only |
| POST | .../subscriptions/{subscription}/pause | @pause | — | model fresh (status=suspended) | implemented | Platform-only |
| POST | .../subscriptions/{subscription}/resume | @resume | — | model fresh (status=active) | implemented | Platform-only |
| POST | .../subscriptions/{subscription}/suspend | @suspend | — | model fresh | implemented | Platform-only |
| POST | .../subscriptions/{subscription}/renew | @renew | — | model fresh (gia hạn theo billing_cycle) | implemented | Platform-only |
| POST | .../subscriptions/{subscription}/addons | @addAddon | body: `name*`, `mrr*`, `wallet_type?`, `addon_code?` | model SubscriptionAddon, **201** | implemented | Platform-only |
| DELETE | .../subscriptions/{subscription}/addons/{addon} | @removeAddon | route bind | `{ok:true}` | implemented | Platform-only |
| GET | /api/platform/billing/usage | UsageMeteringController@index | query filters | paginator/JSON UsageRecord/Period | implemented | Platform-only |
| POST | .../usage-periods/{period}/recalculate | @recalculate | — | JSON | implemented | Platform-only |
| POST | .../usage-periods/{period}/lock | @lock | — | JSON | implemented | Platform-only |
| POST | .../usage-periods/{period}/unlock | @unlock | — | JSON | implemented | Platform-only |
| POST | .../usage-periods/{period}/generate-alerts | @generateAlerts | — | JSON | implemented | Platform-only |
| GET | /api/platform/billing/quota-alerts | QuotaAlertController@index | query filters | paginator/JSON | implemented | Platform-only |
| POST | .../quota-alerts/{alert}/resolve | @resolve | — | JSON | implemented | Platform-only |
| POST | .../quota-alerts/{alert}/convert-to-addon | @convertToAddon | — | JSON | implemented | Platform-only |
| POST | .../quota-alerts/{alert}/convert-to-upgrade | @convertToUpgrade | — | JSON | implemented | Platform-only |
| GET | /api/platform/billing/invoices | BillingInvoiceController@index | query: `status?`, `tenant_id?`, `per_page?`(20) | **paginator** BillingInvoice (with tenant) | implemented | Platform-only |
| GET | .../invoices/{invoice} | @show | route bind | model (with tenant,lines,payments) | implemented | Platform-only |
| POST | .../invoices/generate | @generate | body: `period_id?` | `{created:int}` **hoặc** `{message:"Ky usage chua khoa"}` **422** | implemented | Platform-only |
| POST | .../invoices/{invoice}/approve | @approve | — | model fresh (status=issued) | implemented | Platform-only |
| POST | .../invoices/{invoice}/send | @send | — | model fresh (status=sent) | implemented | Platform-only |
| POST | .../invoices/{invoice}/void | @void | body: `reason?` | model fresh (status=voided) | implemented | Platform-only |
| POST | .../invoices/{invoice}/payments | @recordPayment | body: `amount*`(numeric≥0), `payment_method?`, `transaction_ref?` | model invoice fresh (paid/remaining/status cập nhật) | implemented | Platform-only |
| POST | .../invoices/{invoice}/reconcile | @reconcile | — | model BillingReconciliation | implemented | Platform-only |
| GET | /api/platform/billing/wallets | PassThroughWalletController@index | query filters | paginator/JSON | implemented | Platform-only |
| POST | .../wallets/{wallet}/top-up | @topUp | body: amount... | JSON | implemented | Platform-only |
| POST | .../wallets/{wallet}/deduct | @deduct | body: amount... | JSON | implemented | Platform-only |
| POST | .../wallets/{wallet}/configure-auto-topup | @configureAutoTopup | body: cấu hình | JSON | implemented | Platform-only |
| GET | /api/platform/billing/adjustments | BillingAdjustmentController@index | query filters | paginator/JSON | implemented | Platform-only |
| POST | .../adjustments/{adjustment}/approve | @approve | — | JSON | implemented | Platform-only |
| POST | .../adjustments/{adjustment}/reject | @reject | body: reason? | JSON | implemented | Platform-only |
| POST | .../adjustments/{adjustment}/credit-note | @issueCreditNote | — | JSON (credit note) | implemented | Platform-only |
| GET | /api/platform/billing/audit-logs | BillingAuditLogController@index | query filters | paginator/JSON | implemented | Platform-only |

> **Ghi chú tiền tệ:** amounts lưu & trả về dạng số thập phân VND (cast `(float)`), currency hard-code `'VND'`. VAT hard-code 10%. Không có định dạng minor-unit/integer. (Xem file chuẩn.)

---

## 3. Nhóm Platform / Integration

Prefix đầy đủ: `/api/platform/integrations` · Middleware: `platform.admin` · Scope: **platform** · Auth: phiên Filament · Response: model/JSON thô. Secret của connection/API key/webhook chỉ **trả về 1 lần** khi tạo/rotate.

| Method | Route | Controller@action | Request | Response | Status | Mobile readiness |
|---|---|---|---|---|---|---|
| GET | /api/platform/integrations/overview | IntegrationOverviewController@index | — | JSON tổng quan | implemented | Platform-only |
| GET | .../overview... /audit-logs | @auditLogs (route `audit-logs`) | query | paginator/JSON | implemented | Platform-only |
| GET | .../connections | IntegrationConnectionController@index | query: `status?`, `environment?`, `per_page?`(20) | **paginator** (with category) | implemented | Platform-only |
| POST | .../connections | @store | body: `name*`, `category_id?`, `provider_code*`, `environment*`(sandbox\|staging\|production), `base_url?` | model, **201** | implemented | Platform-only |
| GET | .../connections/{connection} | @show | route bind | model (with category,credentials,mappings,checks) | implemented | Platform-only |
| POST | .../connections/{connection}/test | @test | — | `{result, latency_ms, http_status}` | implemented (mô phỏng random) | Platform-only |
| POST | .../connections/{connection}/enable | @enable | — | model fresh | implemented | Platform-only |
| POST | .../connections/{connection}/disable | @disable | — | model fresh | implemented | Platform-only |
| POST | .../connections/{connection}/rotate-secret | @rotateSecret | — | `{secret, masked}` (**secret 1 lần**) | implemented | Platform-only |
| GET | .../api-keys | IntegrationApiKeyController@index | query | paginator/JSON | implemented | Platform-only |
| POST | .../api-keys | @store | body | model, **201** (secret 1 lần) | implemented | Platform-only |
| GET | .../api-keys/{apiKey} | @show | route bind | model | implemented | Platform-only |
| POST | .../api-keys/{apiKey}/rotate | @rotate | — | `{secret...}` (1 lần) | implemented | Platform-only |
| POST | .../api-keys/{apiKey}/revoke | @revoke | — | JSON | implemented | Platform-only |
| POST | .../api-keys/{apiKey}/suspend | @suspend | — | JSON | implemented | Platform-only |
| POST | .../api-keys/{apiKey}/resume | @resume | — | JSON | implemented | Platform-only |
| GET | .../webhooks | WebhookEndpointController@index | query | paginator/JSON | implemented | Platform-only |
| POST | .../webhooks | @store | body | model, **201** (secret 1 lần) | implemented | Platform-only |
| GET | .../webhooks/{webhook} | @show | route bind | model | implemented | Platform-only |
| POST | .../webhooks/{webhook}/test | @test | — | JSON kết quả | implemented | Platform-only |
| POST | .../webhooks/{webhook}/enable | @enable | — | model fresh | implemented | Platform-only |
| POST | .../webhooks/{webhook}/disable | @disable | — | model fresh | implemented | Platform-only |
| POST | .../webhooks/{webhook}/rotate-secret | @rotateSecret | — | `{secret...}` (1 lần) | implemented | Platform-only |
| GET | .../webhooks/{webhook}/deliveries | @deliveries | query | paginator/JSON | implemented | Platform-only |
| GET | .../events | IntegrationEventController@index | query | paginator/JSON | implemented | Platform-only |
| GET | .../events/{event} | @show | route bind | model | implemented | Platform-only |
| POST | .../events/{event}/replay | @replay | — | JSON | implemented | Platform-only |
| GET | .../retry-queue | IntegrationRetryQueueController@index | query | paginator/JSON | implemented | Platform-only |
| POST | .../retry-queue/{job}/retry-now | @retryNow | — | JSON | implemented | Platform-only |
| POST | .../retry-queue/{job}/skip | @skip | — | JSON | implemented | Platform-only |
| POST | .../retry-queue/{job}/dead-letter | @deadLetter | — | JSON | implemented | Platform-only |
| GET | .../security-settings | IntegrationSecurityController@show | — | JSON settings | implemented | Platform-only |
| PUT | .../security-settings | @update | body settings | JSON | implemented | Platform-only |
| POST | .../security-settings/enforce-hmac | @enforceHmac | — | JSON | implemented | Platform-only |
| POST | .../security-settings/emergency-disable | @emergencyDisable | — | JSON | implemented | Platform-only |

---

## 4. Nhóm Platform / Support

Prefix đầy đủ: `/api/platform/support` · Middleware: `platform.admin` · Scope: **platform** · Auth: phiên Filament · Response: model/JSON thô. Mọi hành động nhạy cảm ghi `support_audit_logs`.

| Method | Route | Controller@action | Request | Response | Status | Mobile readiness |
|---|---|---|---|---|---|---|
| GET | /api/platform/support/dashboard | SupportCenterController@dashboard | — | JSON tổng quan | implemented | Platform-only |
| GET | .../reports/resolution | @report | query | JSON | implemented | Platform-only |
| GET | .../audit-logs | @auditLogs | query | paginator/JSON | implemented | Platform-only |
| GET | .../tickets | SupportTicketController@index | query: `status?`, `priority?`, `sla_state?`, `per_page?`(25) | **paginator** (with tenant,owner,team) | implemented | Platform-only |
| POST | .../tickets | @store | body: `subject*`, `description?`, `tenant_id?`, `module?`, `priority*`(low\|medium\|high\|critical), `environment?` | model, **201** | implemented | Platform-only |
| GET | .../tickets/{ticket} | @show | route bind | model (with tenant,owner,team,messages,statusLogs,escalations,dcr) | implemented | Platform-only |
| POST | .../tickets/{ticket}/assign | @assign | body: `team_id?`, `owner_id?` | model fresh | implemented | Platform-only |
| POST | .../tickets/{ticket}/escalate | @escalate | body: `to_level*`, `reason*` | model fresh (with escalations) | implemented | Platform-only |
| POST | .../tickets/{ticket}/close | @close | body: `resolution_summary?`, `csat_score?` | model fresh | implemented | Platform-only |
| POST | .../tickets/{ticket}/reopen | @reopen | — | model fresh | implemented | Platform-only |
| POST | .../tickets/{ticket}/messages | @addMessage | body: `type*`(internal\|customer\|system), `body*` | model message, **201** | implemented | Platform-only |
| GET | .../data-correction-requests | DataCorrectionController@index | query | paginator/JSON | implemented | Platform-only |
| POST | .../data-correction-requests | @store | body | model, **201** | implemented | Platform-only |
| GET | .../data-correction-requests/{dcr} | @show | route bind | model | implemented | Platform-only |
| POST | .../data-correction-requests/{dcr}/approve | @approve | — | JSON | implemented | Platform-only |
| POST | .../data-correction-requests/{dcr}/reject | @reject | body: reason? | JSON | implemented | Platform-only |
| POST | .../data-fix-wizard/{dcr}/create-snapshot | @snapshot | — | JSON | implemented | Platform-only |
| POST | .../data-fix-wizard/{dcr}/execute | @execute | — | JSON | implemented | Platform-only |
| POST | .../data-fix-wizard/{dcr}/rollback | @rollback | — | JSON | implemented | Platform-only |
| GET | .../knowledge-base/articles | SupportCenterController@kbIndex | query | paginator/JSON | implemented | Platform-only |
| POST | .../knowledge-base/articles | @kbStore | body | model | implemented | Platform-only |
| POST | .../knowledge-base/articles/{article}/publish | @kbPublish | — | JSON | implemented | Platform-only |
| POST | .../knowledge-base/articles/{article}/archive | @kbArchive | — | JSON | implemented | Platform-only |

---

## 5. Route Web (`routes/web.php`)

Không phải API JSON — đây là route trình duyệt (Filament + guest reset password). Liệt kê để đủ bức tranh.

| Method | Route | Handler | Auth / Middleware | Scope | Mục đích | Mobile readiness |
|---|---|---|---|---|---|---|
| GET | / | closure → redirect `/admin` | none | — | Vào panel BQL | Irrelevant |
| GET | /reset-password/{token} | ResidentPasswordResetController@show | **guest** (không auth) | — | Trang HTML đặt lại mật khẩu (view `auth.reset-password`) | **Tham khảo** cho luồng reset mobile; hiện trả HTML, không JSON |
| POST | /reset-password | ResidentPasswordResetController@store | **guest** | — | Xử lý reset qua Password broker; validate `token*`, `email*`, `password*`(confirmed, min 8); lỗi = ValidationException (redirect back, không JSON) | **Tham khảo**; cần bản JSON riêng cho mobile |
| GET | /context/project/{project} | closure | `auth` | tenant/project | Đổi ngữ cảnh dự án, ghi AuditLog, `back()` | Web-only |
| GET | /context/workspace/{key} | closure (`key` ∈ bql\|hq\|superadmin) | `auth` | — | Đổi workspace → redirect panel tương ứng | Web-only |
| POST | /context/hq-projects | closure | `auth` | tenant | Set scope đa dự án cho HQ | Web-only |
| GET | /context/hq-tenant/{tenant} | closure | `auth` + `isPlatformAdmin()` (abort 403) | platform | Platform admin đổi công ty đang thao tác | Web-only |
| GET | /dashboard, /residents, /apartments, /vehicles-cards, /resident-approvals | `Route::redirect` → `/admin/...` | none | — | Redirect legacy sang panel Filament | Irrelevant |

Panel Filament (định nghĩa trong `app/Providers/Filament/*PanelProvider.php`): `/admin` (BQL — `FilaPanelProvider`/`AdminPanelProvider`), `/hq` (công ty vận hành), `/sa` (SuperAdmin), `/fila` (stock CRUD). Đây là **UI server-side (Livewire/Filament)**, không phải API — mobile không dùng lại được.

---

## 6. Nghiệp vụ chỉ có ở Filament (KHÔNG có API) — mobile cần biết

Đây là các "actions" nghiệp vụ hiện **chỉ** chạy qua UI Filament (Livewire action, không có endpoint HTTP). Nếu mobile cần các nghiệp vụ này, **phải viết endpoint mới**. (Nguồn: grep `Action::make('...')` trong `app/Filament/Pages`, `app/Filament/Concerns`, `app/Filament/Sa/Pages`.)

### 6.1. BQL — Cư dân (mobile resident/BQL rất cần)
- `ResidentDirectory` (`app/Filament/Pages/ResidentDirectory.php`): create, import, export, quickView, edit, **resetPassword**, linkApartment, resendActivation, notify, createTask, **lock**, history, **approve** (+ bulk lock/export).
- `ResidentDetail` (`.../ResidentDetail.php`): edit, notify, **resetPassword**, addRelation, requestUpdate, **lock**, **unlock**, export.
- `ResidentBindingQueue` (`.../ResidentBindingQueue.php`): approve, needMore, reject, assign, view (duyệt liên kết cư dân ↔ tài khoản toàn cục).
- Luồng đặt lại mật khẩu dùng chung: `app/Filament/Concerns/ResetsResidentPassword.php` — 4 phương thức: mật khẩu tạm (hiện 1 lần), OTP (cache 10 phút), gửi link reset, tạo link copy. Sinh token qua `Password::broker()->createToken()`, link trỏ tới route web `/reset-password/{token}`. **Chưa có API**; SMS/Zalo chưa nối gateway (chỉ gửi email hoặc `mail.test_to`).

### 6.2. BQL — Căn hộ
- `ApartmentDirectory` (`.../ApartmentDirectory.php`): import, export, create, view, edit, **attachResident**, **changeStatus**, dossier, history.
- `ApartmentProfile` (`.../ApartmentProfile.php`): edit, attach, **changeStatus**, note, export.
- `ApartmentTree` (`.../ApartmentTree.php`): attach.

### 6.3. BQL — Xe / Thẻ ra vào
- `VehicleRequests` (`.../VehicleRequests.php`): **approve**, **reject**, **revoke** (duyệt/từ chối/thu hồi đăng ký xe).
- `AccessCards` (`.../AccessCards.php`): **issue**, **revoke**, **reactivate** (cấp/thu/kích hoạt lại thẻ).

### 6.4. BQL — Vận hành khác
- `WorkOrderKanban`: detail, assign, checklist, signoff (lệnh công việc).
- `FeedbackQueue`: comment, assign, createWorkOrder, start, resolve, close (+ assignBulk) — phản ánh cư dân.
- `NotificationCenter`: compose, publish, archive, view — thông báo.
- `StatementApprovalQueue`: approve, reject, need_more, assign, export — duyệt sao kê/phí.
- `AuditLogViewer`: view, export.

### 6.5. SuperAdmin (platform) — có action Filament SONG SONG với API §2–4
Ví dụ đối chiếu (Filament page ↔ API đã có): `SubscriptionManagement` ↔ subscriptions API; `InvoiceManagement`/`InvoiceGeneration` ↔ invoices API; `PassThroughWalletDashboard` ↔ wallets API; `UsageMeteringDashboard` ↔ usage API; `OverageQuotaAlert` ↔ quota-alerts API; `BillingAuditAdjustment` ↔ adjustments API; `SupportTicketQueue`/`DataCorrectionRequests`/`ControlledDataFixWizard`/`SupportKnowledgeBase` ↔ support API; `WebhookEndpointManagement`/`EventLogMonitor`/`IntegrationHealthRetryQueue`/`IntegrationSecuritySettings` ↔ integrations API.

Ngoài ra một số page SuperAdmin **chỉ có Filament, chưa có API**: `GlobalUserRegistry` (verify/suspend/unsuspend/createBinding), `ContractRenewalManager`, `PublicProjectLibrary`, `DocumentTemplateLibrary`, `PlatformContentCms`, `PlatformKnowledgeBase`, `SupportEscalationAssignment`, `TemplateInheritancePolicy`, `SharedPartnerLibrary`.

---

## 7. Tổng kết cho đội mobile

| Câu hỏi | Trả lời |
|---|---|
| Có endpoint nào cho app cư dân (Flutter) dùng ngay không? | **Không.** |
| Có endpoint nào cho app BQL dùng ngay không? | **Không.** |
| API hiện có phục vụ ai? | Chỉ SaaS operator / SuperAdmin (platform.admin). |
| Xác thực bằng gì? | Phiên Filament (`auth()->user()`), **chưa có token API** (Sanctum/Passport chưa dùng cho các route này). |
| Có API Resource / envelope chuẩn không? | Không — trả model/paginator/mảng thô. |
| Nghiệp vụ resident/BQL nằm ở đâu? | Chỉ trong Filament Page actions (§6), không có HTTP endpoint. |
| Cần làm gì? | **Xây tầng Mobile API mới**: guard token, prefix riêng, envelope + error chuẩn, phân quyền scope tenant/project, bọc lại các nghiệp vụ ở §6. |
