# PACKAGE_SELECTION_PLAN — X2-BMS

> **Status:** proposal for owner review. **No package installed yet.** Survey done 2026-06 against
> Laravel **13.17**, Filament **5.6.7**, PHP **8.4**, Livewire **4** (the versions already scaffolded).
> Re-verify exact constraints with `composer require <pkg> --dry-run` / `composer why-not` at install time.

## 1. Scale context that drives every choice

| Fact | Design consequence |
|---|---|
| ~5,000,000 end users (cư dân/khách) via **mobile API** | Token auth must not do heavy per-request work; **do NOT enroll 5M users in RBAC pivot tables** — derive their capabilities from `resident_apartment_relations` + token abilities. Mass notification = queued fan-out, never sync. |
| ~10,000 admin users (BQL/admin) via **Filament web** | Full RBAC (roles/permissions) is fine at this size; permission cache + Filament Shield. |
| SaaS multi-tenant, **single DB first**, `tenant_id`/`building_id` scope | Use **row-level global scopes + Filament native tenancy** — avoid heavy DB-per-tenant packages now. |
| UI + sample data = **data contract**, no hardcode | Packages must let seeders/queries drive views (e.g. media via DB, not static files in repo). |

**Guiding rule (per owner):** prefer Laravel core + first-party + widely-maintained Spatie/Filament packages; avoid niche/low-maintenance plugins. When no solid package exists, **build it** on canonical entities rather than adopt an abandoned plugin.

## 2. Summary — install timing

| Need | Chosen | Install now? | Phase |
|---|---|---|---|
| Auth web | Laravel + Filament panel auth (core) | ✅ already present | M0 |
| Auth mobile API | `laravel/sanctum` | ⏳ when API starts | M2 |
| RBAC/policy | `spatie/laravel-permission` + `bezhansalleh/filament-shield` | ⏳ M2 (foundation) | M2 |
| Tenant scope | Core global scopes + Filament tenancy (no package) | ✅ build in M2 | M2 |
| Audit log | `spatie/laravel-activitylog` + curated domain `audit_logs` | ⏳ M2 | M2 |
| Media/upload | `spatie/laravel-medialibrary` (+ Filament plugin) | ⏳ when media screens | M3 |
| Import/export | Filament native Import/Export **+** `spatie/simple-excel` | ⏳ M4/M5 | M4 |
| Notification/queue | Laravel core + `laravel/horizon` + `laravel-notification-channels/fcm` | ⏳ M3 | M3 |
| Workflow approval | **Build custom** + `spatie/laravel-model-states` | ⏳ M5 | M5 |
| Dynamic form builder | **Build custom** (Filament Schema at runtime) | ❌ build, no pkg | M5/M6 |
| API docs | `dedoc/scramble` | ⏳ M2 | M2 |
| Testing/screenshot | `pestphp/pest` + **Playwright** (installed) | ✅ Playwright done | M0+ |
| AI copilot/audit | `laravel/ai` (Laravel AI SDK, first-party) | ⏳ M6 | M6 |

Nothing is needed *today* beyond what is installed; the plan times each install to the milestone that first uses it.

---

## 3. Detailed selection

### 3.1 Auth — Web (admin)
- **Package:** Laravel core auth + Filament 5 panel authentication (already installed). No extra package.
- **Purpose:** session login for ~10k admin/BQL users into Web Admin / Web Action.
- **Install now:** present (core).
- **Compat risk:** none (core / Filament 5).
- **Fallback:** n/a.
- **X2 modules:** Web Admin, Web Action, portals (CĐT/BQT/Nhà thầu/NCC) — all admin-side panels.

### 3.2 Auth — Mobile API (5M end users)
- **Package:** `laravel/sanctum` (first-party token auth with abilities).
- **Purpose:** issue per-device tokens to App Cư dân / App BQL; scope by token abilities.
- **Install now:** no — at M2 when first API endpoints land.
- **Compat risk:** **low** — first-party, ships with Laravel 13.
- **Fallback:** stateless **JWT** (`firebase/php-jwt` via custom guard) **only if** the `personal_access_tokens` per-request DB read becomes a measured bottleneck at 5M scale. (Not Passport/OAuth2 — unnecessary for first-party clients.)
- **Scale notes:** `personal_access_tokens` will be huge → schedule token pruning (`sanctum:prune-expired`), keep the lookup index, consider caching token→user resolution; use read replica for auth reads.
- **X2 modules:** App Cư dân, App BQL, resident web portal API.

### 3.3 RBAC / policy
- **Packages:** `spatie/laravel-permission` (roles/permissions) + `bezhansalleh/filament-shield` (auto-generates panel resource/page/widget permissions).
- **Purpose:** admin-side RBAC + Laravel Policies; Shield wires permissions into Filament.
- **Install now:** no — M2 (foundation/IAM).
- **Compat risk:** **low** — spatie/permission supports Laravel 12+ (incl. 13); Filament Shield publishes a **5.x** line. Re-check Shield minor at install.
- **Fallback:** Laravel native Gate/Policy only (drop Shield; define permissions by hand).
- **Scale notes:** **Do not** attach the 5M residents to `model_has_roles`/`model_has_permissions`. Residents' rights come from `resident_apartment_relations` + Sanctum abilities. Spatie **teams feature = OFF** (tenancy handled by scope columns, §3.4). Enable permission cache.
- **X2 modules:** IAM/RBAC (WEB-08, WEB-ACTION-02), every policy-gated action across the system.

### 3.4 Tenant scope (multi-tenant, single DB)
- **Package:** **none** — Laravel global scopes + a `BelongsToTenant` trait + **Filament 5 native multi-tenancy** for panel-level tenant/building switching.
- **Purpose:** enforce `tenant_id`/`building_id` row-level scope on every business query.
- **Install now:** build in M2 (core code, no dependency).
- **Compat risk:** none (core).
- **Fallback:** `spatie/laravel-multitenancy` **only if** we later move to DB-per-tenant. (Explicitly **avoid `stancl/tenancy`** now — multi-DB oriented, heavy, conflicts with single-DB row-level design.)
- **X2 modules:** all — foundation for every list/report/action.

### 3.5 Audit log
- **Packages:** `spatie/laravel-activitylog` (engine) **+** curated domain `audit_logs` table (already in canonical map).
- **Purpose:** activitylog auto-captures model changes (causer/subject/old→new); the curated `audit_logs` holds the **mandated immutable audit** (finance, RBAC, approval, security, PII, AI) written explicitly in services.
- **Install now:** no — M2.
- **Compat risk:** **low** — spatie supports Laravel 13.
- **Fallback:** `owen-it/laravel-auditing` (audit-compliance focused) if we want model-attached audit instead of activitylog.
- **Scale notes:** route audit writes through the queue for high-volume models; partition/retention policy on `activity_log`.
- **X2 modules:** Audit (WEB-08-04), Finance approvals, Security/SOS, IAM, AI governance (WEB-ACTION-08).

### 3.6 Media / file upload
- **Package:** `spatie/laravel-medialibrary` (+ `filament/spatie-laravel-media-library-plugin` field) on **S3-compatible** storage.
- **Purpose:** attach images/PDF/video to feedback, work orders, handover, assets; conversions on queue.
- **Install now:** no — M3 (first media-bearing screens).
- **Compat risk:** medialibrary **low** (requires `illuminate/* ^13.0`, v11.23 Jun 2026). The **Filament media plugin = medium risk** (Filament-5 plugin ecosystem can lag) → verify at install.
- **Fallback:** Filament native `FileUpload` + direct S3 `Storage` (skip the medialibrary Filament field).
- **Scale notes:** never store media in repo/DB; S3 + queued conversions; signed URLs.
- **X2 modules:** Feedback attachments, WorkOrder evidence, Handover/warranty, Assets/IOC, Community/Marketplace.

### 3.7 Import / export (Excel/CSV)
- **Packages:** **Filament 5 native Import/Export actions** (queued, chunked) for admin grids; **`spatie/simple-excel`** (openspout, streaming/low-memory) for large base-data imports.
- **Purpose:** resident/apartment/asset import (WEB-ACTION-01), report export.
- **Install now:** no — Filament native at M4; simple-excel when a large import is built.
- **Compat risk:** simple-excel **low** (L13 OK). `maatwebsite/excel` supports L13 **but only PhpSpreadsheet 1.x** and is memory-heavy → **avoid for large files**; use only where rich .xlsx formatting is required.
- **Fallback:** `maatwebsite/excel` for complex formatted exports; raw `fputcsv` streams for the simplest cases.
- **Scale notes:** imports of 100k+ rows must be chunked + queued; never load whole file in memory (hence simple-excel/openspout).
- **X2 modules:** WEB-ACTION-01 (import nền), Reports/BI export (WEB-07), Finance statements export.

### 3.8 Notification / queue
- **Packages:** Laravel core Notifications + Queues on **Redis**; **`laravel/horizon`** (monitoring/ops); **`laravel-notification-channels/fcm`** (mobile push). SMS/Zalo/email = provider channels (community or thin custom).
- **Purpose:** multi-channel notification (in-app/push/SMS/email), background jobs, mass fan-out.
- **Install now:** no — M3 (notifications + queue infra).
- **Compat risk:** Horizon **low** (first-party, L13). FCM channel **low–medium** (verify channel package L13 at install).
- **Fallback:** database queue (dev) instead of Redis; raw FCM HTTP v1 via `Http` client if the channel package lags.
- **Scale notes:** 5M fan-out → chunk by `notification_audiences`, dedicated queues/workers, rate-limit per provider, Horizon supervisors; never notify synchronously in a request.
- **X2 modules:** Truyền thông/Notification (WEB-04, WEB-ACTION-05), SOS/alerts, all async jobs (billing runs, imports, AI).

#### 5 delivery channels (confirmed)

| # | Channel | Laravel mapping | Package / provider | Risk | Used by |
|---|---|---|---|---|---|
| 1 | **In-app** (chuông/feed) | `database` channel | core (no pkg) → `notifications`/`notification_reads` | none | App Cư dân, App BQL, Web portals |
| 2 | **Push mobile** | custom channels | **FCM** (`laravel-notification-channels/fcm`) for Android **+ APNs** for iOS (FCM can relay APNs, or `laravel-notification-channels/apn`) | low–med (verify L13) | App Cư dân, App BQL |
| 3 | **Email** | `mail` channel | core Mailable + driver (SES/SMTP) | none | all |
| 4 | **SMS** | custom channel | VN brandname SMS vendor (e.g. via `laravel-notification-channels/*` or thin `Http` client) | med (vendor) | OTP, công nợ, SOS |
| 5 | **Zalo ZNS** | custom channel | Zalo Notification Service (thin custom channel over `Http`) | med (no first-party pkg) | thông báo phí, biên lai, nhắc thanh toán |

All five share one `Notification` class with `via()` selecting channels per audience preference + per-channel delivery logged to `notification_delivery_logs` (cost/status). APNs/FCM may be unified under one "push" provider if FCM handles iOS relay; listed separately to keep iOS native APNs as a fallback.

### 3.9 Workflow / approval
- **Approach:** **Build custom** on canonical `approval_requests`/`approval_steps` + **`spatie/laravel-model-states`** for guarded status transitions + domain events.
- **Purpose:** statement publish approval, expense approval, notification approval, AI approval queue.
- **Install now:** no — M5.
- **Compat risk:** model-states **low** (L13). Custom code = no compat risk.
- **Fallback:** plain enum + transition guards inside service classes (no package).
- **Why not a plugin:** no dominant, well-maintained Laravel approval-engine package; adopting a niche one violates the maintenance rule.
- **X2 modules:** Finance approval (WEB-ACTION-03), Notification approval (WEB-ACTION-05), AI approval (WEB-ACTION-08), feedback→work order flow.

### 3.10 Dynamic form builder (runtime, user-defined)
- **Approach:** **Build custom** on canonical `dynamic_forms`/`form_sections`/`form_fields`/`form_submissions`, rendered at runtime via **Filament 5 Schema** built from DB rows. No third-party package.
- **Purpose:** WEB-FORM-12 online form/biểu mẫu builder.
- **Install now:** no — M5/M6.
- **Compat risk:** build effort only (no compat risk). Highest *design* risk item → prototype early.
- **Fallback:** start with a constrained field-type set; expand iteratively.
- **X2 modules:** WEB-FORM-12 Form Builder, survey/poll authoring.

### 3.11 API documentation
- **Package:** `dedoc/scramble` (OpenAPI 3.1, infers from types/code, no annotations).
- **Purpose:** auto-doc the mobile/web API for App + portal teams.
- **Install now:** no — M2 once API routes exist.
- **Compat risk:** **low** (PHP 8.1+/Laravel 10+; used on L13).
- **Fallback:** `knuckleswtf/scribe` (annotation-based) or hand-written OpenAPI.
- **X2 modules:** all `/api/v1/*` (resident, bql, admin, portals).

### 3.12 Testing / screenshot
- **Packages:** `pestphp/pest` (unit/feature; PHPUnit already present) + **Playwright** (screenshot/E2E — **installed & validated in the WEB-01-01 pilot**).
- **Purpose:** feature tests + per-screen/per-batch screenshot regression vs handoff images.
- **Install now:** Playwright **done**; Pest optional (can stay on core PHPUnit).
- **Compat risk:** none.
- **Fallback:** Laravel Dusk for browser tests (native) if we drop Playwright — but Playwright already works and diffs screenshots better.
- **X2 modules:** every screen (acceptance = screenshot test), all services (feature tests).

### 3.13 AI copilot / AI audit
- **Package:** **`laravel/ai` (Laravel AI SDK)** — first-party, production-stable in Laravel 13; unified agents/tools/structured-output/streaming across providers incl. **Anthropic/Claude**.
- **Purpose:** X2AI copilot (operational suggestions, drafts), tool-calling agents, embeddings/RAG; all human-in-the-loop.
- **Install now:** no — M6 (X2AI), though pilot already seeds `ai_suggestions`.
- **Compat risk:** **low** — first-party for L13.
- **Fallback:** `prism-php/prism` (mature multi-provider, agentic loops, pgvector RAG) if we outgrow the SDK; or the official Anthropic SDK directly.
- **AI audit:** every suggestion/accept/reject → `ai_action_logs` + `audit_logs` (mandated per ARCHITECTURE). Governance via `ai_policy_checks`.
- **X2 modules:** X2AI panel (all apps), WEB-07-04 AI Insight, WEB-ACTION-08 AI/Automation/Governance.

---

## 3A. Additional Filament 5 plugins & ops tooling considered

> **Design tension:** X2-BMS renders complex screens as **custom Blade/Livewire** to match the handoff images.
> So Filament UI plugins (charts/kanban) are useful mainly for **standard CRUD/admin screens** and **ops dashboards**,
> not the pixel-matched custom screens. The list below favors **official Filament** + **Spatie-backed** plugins.

### A. Official Filament–Spatie integration plugins (low risk — maintained by Filament team)

| Package | Purpose | Install? | X2 modules |
|---|---|---|---|
| `filament/spatie-laravel-media-library-plugin` | FileUpload field bound to medialibrary (see §3.6) | ⏳ M3 | feedback/WO evidence, handover, assets |
| `filament/spatie-laravel-settings-plugin` (+ `spatie/laravel-settings`) | Typed system/module settings pages | ⏳ M4 | ModuleConfig, system setup (WEB-08), white-label |
| `filament/spatie-laravel-tags-plugin` (+ `spatie/laravel-tags`) | Tagging | optional | feedback/asset/community tags |
| `filament/spatie-laravel-translatable-plugin` (+ `spatie/laravel-translatable`) | Multi-language **content** (vi default + en) | **✅ confirmed — M2** | notifications, fee/feedback categories, marketplace, KB, events/polls, white-label content |

### B. SaaS ops / observability (high value at 5M scale)

| Package | Purpose | Install? | Risk |
|---|---|---|---|
| `laravel/horizon` | Redis queue ops (already in §3.8) | **prod/Linux only** | ⚠️ requires `ext-pcntl` (Unix) + Redis — **cannot install on Windows dev**. Dev uses the `database` queue + `queue:work`; Horizon is deployed on the Linux production host. |
| `laravel/pulse` | Production performance/usage monitoring | ⏳ M3 | low (first-party) |
| `laravel/telescope` | Local/staging debugging (not prod) | ⏳ dev | low (first-party) |
| `spatie/laravel-backup` + `shuvroroy/filament-spatie-laravel-backup` | DB/file backup + panel UI | ⏳ M7 | low/med (verify wrapper F5) |
| `spatie/laravel-health` + `shuvroroy/filament-spatie-laravel-health` | Health checks + panel dashboard | ⏳ M7 | low/med |
| `spatie/laravel-schedule-monitor` | Monitor cron (billing runs, AI jobs) | ⏳ M3 | low |

### C. Admin UX helpers

| Package | Purpose | Install? | Risk / note |
|---|---|---|---|
| `stechstudio/filament-impersonate` | Support staff impersonate resident/admin | ⏳ M4 | low — `^4.0\|^5.0`, Octane-ok. Audit every impersonation. |
| `rmsramos/activitylog` **or** `pxlrbt/filament-activity-log` | Panel viewer for spatie activitylog | ⏳ M2 | low — both F5 + activitylog v5. Useful for WEB-08-04 audit screens (where layout allows). |
| `leandrocfe/filament-apex-charts` (5.x) | Rich charts in Filament-native dashboards | **❌ not adopted** | Owner confirmed Filament-native dashboards can't render the full designed component set. **All charts in designed screens are custom SVG/CSS** in the X2 component set (e.g. WEB-01-01 bar/donut). Kept here only as a reference if a throwaway internal chart is ever needed. |

### D. Build custom — NOT a plugin (UI contract + maintenance rule)

| Feature | Why build | Note |
|---|---|---|
| **Kanban** (WEB-05-01 Bảng công việc) | Only native-F5 option (`alessandro-nuunes/filament-kanban`, Mar 2026) is young/single-maintainer, and the board must match the approved image | Build on `work_orders`; may study that plugin as reference. |
| **Dynamic form builder** (WEB-FORM-12) | No solid F5 plugin (see §3.10) | Render Filament Schema from `dynamic_forms`. |
| **Complex dashboards / drawers / wizards** | Must match handoff pixel layout | Custom Livewire + X2 components (pilot pattern). |

### E. Internationalization (i18n) — confirmed multi-language content

- **Packages:** `spatie/laravel-translatable` (engine — **installed**). Locales: **`vi` (default) + `en`** (extensible).
- ⚠️ **Compat finding (2026-06-28):** `filament/spatie-laravel-translatable-plugin` has **no Filament 5 build** (max v3.x → requires `filament/support ^3`). So the Filament admin UI for translations is handled with **manual per-locale fields / tabbed forms** in resources until the official plugin ships F5. The DB-level i18n (HasTranslations, JSON casts) is unaffected.
- **Scope = user-facing *content* only**, stored as JSON translatable columns. **Not** structural/system data (codes, statuses, FKs).
  - Translatable: `notifications.title/body`, `feedback_categories.name`, `fee_types.name`, `amenities.name`, `marketplace_products.name/description`, `events/polls`, `knowledge_documents`, white-label labels.
  - Non-translatable: enums/status, codes (`SG-A`), money, names of people/apartments.
- **UI strings** (labels/buttons/menus) use Laravel's normal `lang/` files + `__()`, separate from DB content translation.
- **Schema impact:** `CANONICAL_ENTITY_MAP.md` / migrations must mark these columns `json` + `HasTranslations` trait. **Seeder** provides `vi` for all sample content (matches handoff) and `en` where available; missing `en` falls back to `vi`.
- **Charts/UI:** all designed charts are **custom SVG/CSS X2 components** (no `filament-apex-charts`) — owner confirmed Filament-native widgets can't reproduce the approved layouts.

> Sources: [Filament Spatie plugins](https://filamentphp.com/plugins/filament-spatie-media-library), [Spatie Settings plugin](https://filamentphp.com/plugins/filament-spatie-settings), [filament-impersonate](https://github.com/stechstudio/filament-impersonate), [rmsramos/activitylog](https://filamentphp.com/plugins/rmsramos-activitylog), [pxlrbt/filament-activity-log](https://github.com/pxlrbt/filament-activity-log), [filament-apex-charts](https://github.com/leandrocfe/filament-apex-charts), [alessandro-nuunes/filament-kanban](https://packagist.org/packages/alessandro-nuunes/filament-kanban).

## 4. Explicitly avoided (and why)

| Package | Reason |
|---|---|
| `stancl/tenancy` | Multi-DB/domain tenancy; conflicts with single-DB row-level design; heavy. |
| `laravel/passport` | OAuth2 server unneeded for first-party mobile clients; Sanctum is the right tool. |
| Niche approval-engine plugins | No well-maintained option; build on canonical tables instead. |
| Niche runtime form-builder plugins | None solid for Filament 5; build custom. |
| `maatwebsite/excel` for bulk import | PhpSpreadsheet 1.x, memory-heavy at scale; use simple-excel/openspout. |

## 5. Compatibility verification (web survey, 2026-06)

| Package | L13 | F5 | Note |
|---|---|---|---|
| Filament 5 | ✅ from v5.6.x | — | ≤5.3.5 lacked L13; **5.6.7 installed & working** |
| laravel/sanctum | ✅ core | — | first-party |
| spatie/laravel-permission | ✅ (v12+ line) | n/a | actively maintained |
| bezhansalleh/filament-shield | ✅ | ✅ 5.x | uses spatie under the hood |
| spatie/laravel-medialibrary | ✅ `^13.0` (v11.23) | — | Filament field plugin: verify |
| spatie/laravel-activitylog | ✅ | — | maintained |
| spatie/simple-excel | ✅ | — | openspout, low memory |
| maatwebsite/excel | ✅ (3.1.69) | — | **PhpSpreadsheet 1.x only** |
| laravel/horizon | ✅ core | — | first-party |
| dedoc/scramble | ✅ | — | OpenAPI 3.1 |
| spatie/laravel-model-states | ✅ | — | state machine |
| laravel/ai (AI SDK) | ✅ core | — | production-stable in L13 |
| prism-php/prism | ✅ | — | fallback for AI |

Sources: [Filament v5 release](https://laravel-news.com/filament-5), [Filament version support](https://filamentphp.com/docs/5.x/introduction/version-support-policy), [spatie/laravel-permission](https://github.com/spatie/laravel-permission), [filament-shield](https://github.com/bezhanSalleh/filament-shield), [spatie/laravel-medialibrary](https://packagist.org/packages/spatie/laravel-medialibrary), [Top Spatie packages for L13 (2026)](https://devsolutionsdaily.com/blog/top-5-spatie-packages-for-laravel-13-you-should-be-using-in-2026), [maatwebsite/excel](https://packagist.org/packages/maatwebsite/excel), [dedoc/scramble](https://github.com/dedoc/scramble), [Laravel AI SDK docs](https://laravel.com/docs/13.x/ai-sdk), [prism-php/prism](https://github.com/prism-php/prism).

## 6. Decisions — RESOLVED with owner (2026-06-28)

1. **Audit engine:** ✅ `spatie/laravel-activitylog` (engine) + curated `audit_logs` for mandated business audit.
2. **Mobile auth at 5M:** ✅ **Sanctum** (opaque DB token, instant revocation, first-party). JWT only revisited if the per-request token read is a *measured* bottleneck. (Sanctum vs JWT comparison recorded in §3.2.)
3. **Excel:** ✅ Filament native Import/Export + **`spatie/simple-excel`** (OpenSpout, streaming, constant RAM) for bulk; `maatwebsite/excel` (PhpSpreadsheet, RAM-heavy) only for small/medium *formatted* reports.
4. **AI:** ✅ first-party **`laravel/ai` (Laravel AI SDK)** as primary; `prism-php/prism` as fallback.
5. **Push/notification channels:** ✅ **all 5** — In-app, Push (FCM+APNs), Email, SMS (VN brandname), Zalo ZNS. See §3.8 table.

6. **Multi-language content:** ✅ **YES** — `spatie/laravel-translatable` + Filament translatable plugin; `vi` default + `en`; JSON columns on content tables only (see §3A.E).
7. **Charts/dashboards:** ✅ **all custom SVG/CSS** in X2 components (Filament-native dashboards/`apex-charts` cannot render the approved designs). `apex-charts` **not adopted**.

→ All open decisions closed. Final, leaned install order is in **§7** (supersedes §2 timing).

## 7. Lean re-review (necessity + resource cost) — FINAL

> Critical second pass. Goal: smallest dependency footprint that still meets the spec. Each line judged on
> *necessity* + *runtime cost at 5M scale* + *coding/maintenance cost* + *F5/L13 upgrade risk*. **This section is authoritative.**

### KEEP — install at M2 (foundation, genuinely needed)

| Package | Why it earns its place | Cost control |
|---|---|---|
| `laravel/sanctum` | Mobile API auth; first-party, tiny | Cache token→user, prune expired, read replica |
| `spatie/laravel-permission` | Admin RBAC (~10k only) | Residents NOT enrolled; permission cache on |
| `bezhansalleh/filament-shield` | Wires Filament policies to spatie | **Curate permissions — do NOT auto-generate one per component** (×272 screens = permission bloat + slower gate checks) |
| core tenant scope (no package) | Row-level multi-tenancy | global scope + trait, zero dep |
| `spatie/laravel-translatable` + Filament plugin | Confirmed vi+en content | JSON only on content tables; keep a plain searchable/slug column for fields you filter/sort |
| `dedoc/scramble` | API docs | Dev/CI-time generation; ~zero prod runtime |

### KEEP but NARROW — audit

| Package | Decision | Reason |
|---|---|---|
| `spatie/laravel-activitylog` | Keep, **apply narrowly** to admin/config/RBAC/finance/security models; **queue** the writes | Logging every change on high-volume resident/transaction tables = write amplification + storage blowup at 5M. The mandated audit lives in the curated `audit_logs` written explicitly in services. |

### DEFER — install only when that phase first uses it (not now)

| Package | When | Note |
|---|---|---|
| `laravel/horizon` | M3 (queue) | needs Redis |
| `spatie/laravel-medialibrary` + Filament media plugin | M3 | S3 + **queued** conversions; never in repo/DB |
| `spatie/simple-excel` | M4 (bulk import) | streaming/low-RAM |
| `spatie/laravel-settings` + Filament settings | M4 (config screens) | |
| `stechstudio/filament-impersonate` | M4 (support) | audit each impersonation |
| `spatie/laravel-schedule-monitor` | M3 | watch billing/AI cron |
| `laravel/ai` | M6 (X2AI) | first-party |
| `laravel/telescope` | dev only (`--dev`) | **disable in prod** (heavy) |
| `laravel/pulse`, `spatie/laravel-backup`, `spatie/laravel-health` (+ Filament wrappers) | M7 (hardening) | Pulse: enable sampling to cap overhead |

### DROP / build instead (save deps & resources)

| Item | Verdict | Reason |
|---|---|---|
| `leandrocfe/filament-apex-charts` | **Drop** | Charts are custom SVG/CSS (owner-confirmed). |
| `filament/spatie-laravel-tags-plugin` | **Drop for now** | No real free-tagging need; `feedback_categories`/simple pivot suffice. |
| activity-log viewer (`rmsramos`/`pxlrbt`) | **Drop → build** | A simple custom audit list page covers WEB-08-04; avoids a UI-plugin dep. |
| `spatie/laravel-model-states` | **Default off** | Use enum + guarded service transitions (we already have enums w/ `label()`/`tone()`). Adopt ONLY if an approval graph proves genuinely complex (re-evaluate M5). |
| `pestphp/pest` | **Skip** | Core PHPUnit already present; don't add a test-DSL dep unless team insists. |
| `maatwebsite/excel` | **Avoid** | PhpSpreadsheet 1.x, RAM-heavy; simple-excel/openspout instead. |

### Net result

- **M2 composer footprint = 6 packages**, all first-party or Spatie (sanctum, spatie/permission, filament-shield, spatie/translatable + plugin, scramble) + zero-dep tenant scope + narrowed activitylog. Lean.
- Everything else is **deferred to the phase that needs it** or **dropped in favor of core/custom**.
- Cross-cutting resource rules: narrow+queue audit; JSON-translatable only for display content; cache Sanctum tokens; queue media conversions; curate Shield permissions; fewer deps = lower F5/L13 upgrade risk.

## 8. INSTALLED STATUS (2026-06-28) — end of M2–M4 install phase

**Installed & base-configured (composer + migrations applied via `migrate:fresh --seed`):**
- `laravel/sanctum` ^4.3 (+ `personal_access_tokens`, `HasApiTokens` on User)
- `spatie/laravel-permission` ^7.4 (+ `bezhansalleh/filament-shield` 4.2 registered in admin panel)
- `spatie/laravel-activitylog` ^5.0
- `spatie/laravel-translatable` ^6.14 (engine)
- `dedoc/scramble` ^0.13
- `spatie/laravel-medialibrary` ^11.23 (+ `filament/spatie-laravel-media-library-plugin` ^5.6, `media` table)
- `spatie/laravel-settings` ^3.9 (+ `filament/spatie-laravel-settings-plugin` ^5.6, `settings` table)
- `spatie/laravel-schedule-monitor` ^4.3
- `spatie/simple-excel` ^3.10
- `stechstudio/filament-impersonate` ^5.5
- **RBAC:** `shield:generate --all --panel=admin --option=policies_and_permissions` → 4 policies + 48 permissions. (Re-run after a fresh DB to repopulate the permission catalog, or add a Shield seeder in the RBAC phase; `super_admin` bypasses regardless.)

**NOT installed — deferred with reason:**
- `laravel/horizon` — **prod/Linux only** (needs `ext-pcntl`, unavailable on Windows). Dev queue = `database`.
- `filament/spatie-laravel-translatable-plugin` — **no Filament 5 build**; use manual per-locale fields in resources (engine `spatie/laravel-translatable` is installed).
- `laravel-notification-channels/fcm` — **deferred to the push-notification feature** (needs Firebase creds; not needed for Web Admin / screen build).
- `laravel/pulse`, `laravel/telescope`, `spatie/laravel-backup`, `spatie/laravel-health` — M7 hardening.

**Verified:** `migrate:fresh --seed` clean; Playwright 2/2 green (dashboard + admin foundation); admin login `x2bms@x2bms.vn` / `Bms@2026!`.
