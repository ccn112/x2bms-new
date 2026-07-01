# X2-BMS — Session Handoff (2026-07-01) · SuperAdmin Platform + SaaS Billing

Bàn giao phiên làm việc 2026-07-01 (phiên nối tiếp, sau `SESSION_HANDOFF_20260701.md`).
Phiên này xây **2 gói lớn**: **SuperAdmin Platform WEB-UX-22** (12 màn) và **Batch 07 — SaaS Billing B2B** (đầy đủ 3 rounds).
Đọc kèm `docs/DEV_JOURNAL.md` (3 entry cuối là chi tiết phiên này).

## 0. Đọc trước (reading order cho phiên sau)

1. **`docs/DEV_JOURNAL.md`** — nhật ký mọi lần đổi code (mới nhất ở cuối).
2. File này + `docs/ADMIN_UI_BUILD_PLAN.md` (Phase 5 ≈ SuperAdmin/SaaS).
3. `docs/CANONICAL_ENTITY_MAP.md`, `docs/WEB_FORM_RECONCILIATION.md`.
4. Handoff nguồn: `D:\Chinh\x2\handoff\X2_BMS_SUPERADMIN_PLATFORM_LIBRARY_AI_ADDENDUM_20260701\` và
   `D:\Chinh\x2\handoff\X2_BMS_BATCH_07_SAAS_BILLING_CRUD_HANDOFF_20260701\`.

---

## 1. Kiến trúc bổ sung trong phiên (khung dùng lại)

- **Nav groups mới** (`AdminPanelProvider`): `'Nền tảng (SuperAdmin)'`, `'SaaS Billing'`.
- **Trait `App\Filament\Concerns\PlatformScreen`** — gate màn platform: `canAccess()`/`shouldRegisterNavigation()`.
  SuperAdmin (`isPlatformAdmin`) thấy tất; HQ (tenant operator) chỉ thấy khi gói bật `platformFeature()` (override method,
  giải qua `FeatureGateService`). **Không hardcode gói.**
- **Trait `App\Filament\Concerns\WritesBillingAudit`** — ghi `billing_audit_logs` (before/after json) cho mọi hành động billing.
- **Trait `App\Filament\Concerns\WritesAudit`** (đã có) — ghi `audit_logs` cho các màn SuperAdmin.
- **`FeatureGateService` reconcile:** chuyển từ bảng `subscriptions` (cũ) sang `tenant_subscriptions` (Batch 07);
  `current_period_*` → `start_date/end_date`. Vẫn giữ layer feature-gate: `plans/plan_features/modules/features/tenant_entitlements/tenant_module_overrides`.

---

## 2. SuperAdmin Platform — WEB-UX-22 (12 màn, DONE)

Tất cả bespoke `/admin`, nav group 'Nền tảng (SuperAdmin)', gate `PlatformScreen`. Feature codes THẬT (không `sa_*`):
`global_account, resident_binding, platform_content, public_project, contractor_library, supplier_library, document_template, kb_inheritance, rag, prompt_guardrail, ai_audit`.

| Màn | Page class | Slug |
|---|---|---|
| 22-01 Dashboard | `PlatformContentDashboard` | `platform/dashboard` |
| 22-02 CMS | `PlatformContentCms` | `platform/content` |
| 22-03 Public Project | `PublicProjectLibrary` | `platform/public-projects` |
| 22-04 User Registry | `GlobalUserRegistry` | `platform/user-registry` |
| 22-05 Binding Queue | `ResidentBindingQueue` | `platform/binding-queue` |
| 22-06 Contractor | `ContractorLibrary` | `platform/contractors` |
| 22-07 Supplier | `SupplierVendorLibrary` | `platform/suppliers` |
| 22-08 Doc Template | `DocumentTemplateLibrary` | `platform/document-templates` |
| 22-09 Inheritance Policy | `TemplateInheritancePolicy` | `platform/template-inheritance` |
| 22-10 KB Library | `PlatformKnowledgeBase` | `platform/knowledge-base` |
| 22-11 AI Config | `AiKnowledgeConfig` | `platform/ai-knowledge-config` |
| 22-12 Audit | `KnowledgeAuditLog` | `platform/knowledge-audit` |

- 22-06/07 dùng chung trait `App\Filament\Concerns\SharedPartnerLibrary` (khác `partnerType()`), giải đụng `table()` bằng `insteadof`.
- Xương sống định danh (rule #1): account gốc (`global_user_accounts`) → duyệt binding → `resident_unit_bindings` + `public_user`→`resident`. **FK là `user_account_id`.**
- Verify: 12/12 render 200 + logic (`scratchpad/logic_sa.php`, `logic_sa2.php`) đạt AC-01..34.

---

## 3. Batch 07 — SaaS Billing B2B (canonical reconcile, DONE 3 rounds)

**Đây là billing X2-BMS THU TIỀN công ty thuê nền tảng — KHÁC hẳn resident fee billing.**

### Round 1 — DB (migration `2026_07_01_000024_reconcile_saas_billing_batch07`)
- DROP bảng saas sơ khai cũ: `subscriptions`, `subscription_invoices(+lines)`, `usage_metering`.
- CREATE 19 bảng: `plan_prices, subscription_contracts, tenant_subscriptions, subscription_items, subscription_addons,
  subscription_renewals, usage_meters, usage_periods, usage_records, quota_alerts, billing_invoices, billing_invoice_lines,
  billing_payments, billing_reconciliations, billing_adjustments, credit_notes, pass_through_wallets, pass_through_transactions, billing_audit_logs`.
- 19 model mới (KHÔNG `BelongsToTenant` — billing cấp platform). Xoá model/resource cũ (Subscription*, UsageMetering).
- Seed `DemoDataSeeder::seedBatch07Billing`: 6 tenant billing (TEN-0001..0006, đủ active/trial/pending_renewal/suspended) + contract/addon/usage(locked+overage)/quota/invoice/payment/wallet/adjustment/credit-note.
- 15 /fila resource (`make:filament-resource --generate --panel=fila`).

### Round 2 — 9 custom page `/admin` (nav 'SaaS Billing', gate PlatformScreen, trait WritesBillingAudit)
| Màn | Page class | Slug |
|---|---|---|
| 27-01 Revenue Dashboard | `SaasRevenueDashboard` | `billing/revenue` |
| 27-02/03 Subscription | `SubscriptionManagement` | `billing/subscriptions` |
| 27-04 Contract & Renewal | `ContractRenewalManager` | `billing/contracts` |
| 27-05 Usage Metering | `UsageMeteringDashboard` | `billing/usage` |
| 27-06 Overage/Quota | `OverageQuotaAlert` | `billing/quota-alerts` |
| 27-07 Invoice Generation | `InvoiceGeneration` | `billing/invoice-generation` |
| 27-08 Invoice & Payment | `InvoiceManagement` | `billing/invoices` |
| 27-09 Wallet | `PassThroughWalletDashboard` | `billing/wallets` |
| 27-10 Audit & Adjustment | `BillingAuditAdjustment` | `billing/adjustments` |

### Round 3 — API + tests
- `bootstrap/app.php` đăng ký `api:` + alias middleware `platform.admin` (`App\Http\Middleware\EnsurePlatformAdmin`).
- `routes/api.php` prefix `platform/billing` (39 route), 8 controller `App\Http\Controllers\Platform\Billing\*`.
- `tests/Feature/Batch07BillingApiTest.php` — **10 test / 39 assertion PASS** (sqlite :memory: + RefreshDatabase, actingAs admin), phủ 12 flow TEST_SCENARIOS.

---

## 4. Cách chạy & verify (Windows + Herd)

```powershell
# PowerShell (php trên PATH). Reseed sạch:
php artisan migrate:fresh --seed
php artisan view:cache

# Render 1 màn (headless kernel, đăng nhập platform admin) — script ở scratchpad phiên trước:
#   scratchpad\render_sa.php <slug,phẩy>
# Chạy test Batch 07:
php artisan test --filter=Batch07BillingApiTest
```

- Dev admin: `x2bms@x2bms.vn` (is_platform_admin). Bash dùng `~/.config/herd/bin/php.bat`.
- Verify chuẩn mỗi màn: `php -l` → `view:cache` → render HTTP 200 → script logic → ghi `DEV_JOURNAL.md`.

---

## 5. Bẫy đã trả giá (nhớ khi code Filament tiếp)

1. **Column closure param PHẢI `$state`** (đặt `$s` → 500 "unresolvable"). Đã dính **4 lần**.
2. **Record-closure PHẢI `$record`** (hoặc `$ct`/`$r`/`$a`…), **không** đặt `$state` cho param type-hint model
   → Filament resolve state theo TÊN → action closure nhận null → 500. (Sinh ra khi sed blanket `$s`→`$state`.)
3. Trait định nghĩa `table()` đụng `InteractsWithTable::table` → `use A, B { B::table insteadof InteractsWithTable; }`.
4. Không redeclare **property** trait ở class con với default khác → Fatal → dùng **method** override (`platformFeature()`).
5. **BelongsToTenant global scope** giới hạn platform admin (tenant_id=1) → thêm `withoutGlobalScope('tenant')` cho query platform-wide.
6. Blade không dùng được `static::$title` → truyền qua `getViewData()`.
7. Không đặt method Page trùng tên Livewire (`transition`/`mount`/`render`/`dispatch`); không đặt relation tên `guard()`.
8. Import thiếu `use Filament\Pages\Page;` → "Class Page not found".

---

## 6. Trạng thái tổng & việc còn lại

**DONE:** CSDL 100% canonical + addendum + Batch 07; SuperAdmin 12 màn; Batch 07 (DB+UI+API+tests); AI Engine ×4; BQL-1/2/3; finance queue; resident flows.

**Còn lại / tùy chọn:**
- Batch 07: gắn **Sanctum** để gọi API stateless từ ngoài (hiện auth qua phiên Filament/actingAs); **proration** khi upgrade (đang đổi MRR, chưa cộng chênh lệch vào hóa đơn kỳ kế); browser-click submit modal thật.
- BQL còn: **BQL-4** Tài chính (công nợ WEB-FORM-08 + duyệt chi + biên lai), BQL-5 dashboard+ca trực+cảnh báo, BQL-6 an ninh/SOS.
- SuperAdmin: index AI thật (đang mô phỏng), retrieval simulator thật (22-11).
- Nhiều màn bespoke `/admin` khác trong `ADMIN_UI_BUILD_PLAN.md` (Phase 1–6) — `/fila` mới là CRUD thô.

**Kiểm chứng cuối phiên:** `migrate:fresh --seed` sạch; SuperAdmin 12/12 + Billing 9/9 render 200; Batch 07 API 10/10 test PASS.
