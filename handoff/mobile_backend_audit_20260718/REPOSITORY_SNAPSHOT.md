# REPOSITORY_SNAPSHOT — X2-BMS Backend

> Ảnh chụp kho mã (READ-ONLY audit) — ngày 2026-07-18. Nhánh `main`, HEAD `3d34216`.
> Mục tiêu: cung cấp bức tranh tổng thể phục vụ đội mobile đánh giá bề mặt backend.
> Không chứa secrets/.env; mọi giá trị nhạy cảm chỉ liệt kê dưới dạng khóa `env()`.

---

## 1. Cây thư mục quan trọng

Chỉ liệt kê mã nguồn dự án (KHÔNG gồm `vendor/`, `node_modules/`).

```
x2bms/
├─ app/
│  ├─ Console/
│  │  └─ Commands/            ArchiveStaleLogs.php   (1 command: logs:archive)
│  ├─ Enums/                  4 enum PHP (xem §3)
│  ├─ Filament/               ← TRÁI TIM nghiệp vụ (878 file PHP)
│  │  ├─ Concerns/            10 trait dùng chung (audit, scope, AI context…)
│  │  ├─ Pages/               35 trang BQL (/admin) — Livewire bespoke pages
│  │  ├─ Hq/Pages/            56 trang Cổng Công ty (/hq)
│  │  ├─ Sa/Pages/            39 trang SuperAdmin (/sa)
│  │  └─ Resources/           ~150 Resource CRUD (/fila) — mỗi cái có Pages/Schemas/Tables
│  ├─ Http/
│  │  ├─ Controllers/         20 controller — CHỈ Platform/* + Auth (xem §4)
│  │  │  ├─ Auth/             ResidentPasswordResetController
│  │  │  └─ Platform/         Billing / Integration / Support (API nền tảng)
│  │  └─ Middleware/          EnsurePlatformAdmin, EnsureHqAccess  (2 file)
│  ├─ Livewire/               ContextSwitcher, GlobalSearch, X2aiChat  (3 component)
│  ├─ Models/                 286 model Eloquent
│  │  └─ Concerns/            BelongsToTenant, BelongsToProject (multi-tenancy)
│  ├─ Policies/               9 policy (Apartment/Building/…/User)
│  ├─ Providers/
│  │  ├─ AppServiceProvider.php
│  │  └─ Filament/            AdminPanelProvider, FilaPanelProvider, HqPanelProvider, SaPanelProvider
│  └─ Support/                Dịch vụ domain (KHÔNG phải Actions/Jobs)
│     ├─ Context/             CurrentContext (project/tenant/workspace scope)
│     ├─ Identity/            ResidentIdentityMatcher
│     ├─ Integration/         IntegrationSecret
│     ├─ Knowledge/           DocumentTextExtractor
│     ├─ Platform/            FeatureGateService
│     └─ X2AI/                X2aiClient + 3 connector/gate (Copilot AI)
├─ routes/
│  ├─ api.php                 CHỈ platform admin (billing/integration/support)
│  ├─ web.php                 redirect + context-switch (session)
│  └─ console.php             1 scheduled command (logs:archive 02:30)
├─ database/
│  ├─ migrations/             67 migration
│  ├─ factories/
│  └─ seeders/                DatabaseSeeder, DemoDataSeeder
├─ config/                    17 file config (app, auth, queue, filesystems, services, …)
├─ resources/
│  ├─ css/filament/           theme DS-01 (navy/gold)
│  ├─ js/
│  └─ views/                  auth / components / filament / livewire (Blade)
├─ tests/
│  ├─ Feature/                3 test API platform + 1 example
│  ├─ Unit/                   1 example
│  └─ Browser/                4 spec Playwright (.spec.ts)
└─ docs/                      ~35 tài liệu (ERD, build plan, handoff, listing standard…)
```

> **KHÔNG có** các thư mục Laravel thường gặp: `app/Jobs/`, `app/Events/`, `app/Listeners/`,
> `app/Notifications/`, `app/Actions/`, `app/Services/`. Nghiệp vụ nằm trong Filament Pages + Support/*.

---

## 2. Stack & phiên bản (từ `composer.json` / `package.json`)

### Runtime
| Thành phần | Ràng buộc phiên bản |
|---|---|
| PHP | `^8.3` |
| Laravel Framework | `^13.8` |
| Filament | `5.*` |
| Laravel Sanctum | `^4.3` |
| Laravel Tinker | `^3.0` |

### `require` (chính)
| Package | Version |
|---|---|
| `bezhansalleh/filament-shield` | `^4.2` — RBAC/permission cho Filament |
| `dedoc/scramble` | `^0.13.30` — sinh tài liệu OpenAPI cho API |
| `filament/filament` | `5.*` |
| `filament/spatie-laravel-media-library-plugin` | `^5.6` |
| `filament/spatie-laravel-settings-plugin` | `^5.6` |
| `laravel/framework` | `^13.8` |
| `laravel/sanctum` | `^4.3` |
| `laravel/tinker` | `^3.0` |
| `smalot/pdfparser` | `^2.12` — trích text PDF (Knowledge) |
| `spatie/laravel-activitylog` | `^5.0` |
| `spatie/laravel-medialibrary` | `^11.23` |
| `spatie/laravel-permission` | `^7.4` — roles/permissions core |
| `spatie/laravel-schedule-monitor` | `^4.3` |
| `spatie/laravel-settings` | `^3.9` |
| `spatie/laravel-translatable` | `^6.14` |
| `spatie/simple-excel` | `^3.10` — import/export Excel |
| `stechstudio/filament-impersonate` | `^5.5` |

### `require-dev`
| Package | Version |
|---|---|
| `fakerphp/faker` | `^1.23` |
| `laravel/pail` | `^1.2.5` |
| `laravel/pao` | `^1.0.6` |
| `laravel/pint` | `^1.27` |
| `mockery/mockery` | `^1.6` |
| `nunomaduro/collision` | `^8.6` |
| `phpunit/phpunit` | `^12.5.12` |

> Ghi chú: bộ test dùng **PHPUnit** (không phải Pest, dù `allow-plugins` có `pestphp/pest-plugin`).

---

## 3. Số liệu đếm

| Hạng mục | Số lượng | Ghi chú |
|---|---:|---|
| Models (`app/Models/*.php`) | **286** | + 2 concern trong `Models/Concerns` |
| Migrations | **67** | prefix `0001_01_01_*` core → `2026_07_17_*` mới nhất |
| Controllers | **20** | tất cả dưới `Platform/*` (17) + `Auth/` (1) + base `Controller.php` + … |
| Filament files (tổng) | **878** | |
| Filament Pages (bespoke) | **130** | 35 `/admin` + 56 `/hq` + 39 `/sa` |
| Filament Resources | **~150** | CRUD gốc trên `/fila` |
| Tests (PHP) | **6** | 3 Feature API + ExampleTest ×2 + TestCase |
| Tests (Browser Playwright) | **4** | `.spec.ts` |
| Enums | **4** | `FeedbackStatus`, `ResidentApprovalStatus`, `VehicleType`, `WorkOrderStatus` |
| Policies | **9** | Apartment, Building, Department, Floor, Project, Resident, Role, Tenant, User |
| Middleware (tùy biến) | **2** | `EnsurePlatformAdmin`, `EnsureHqAccess` |
| Providers | **5** | `AppServiceProvider` + 4 Filament panel provider |
| Livewire components | **3** | `ContextSwitcher`, `GlobalSearch`, `X2aiChat` |
| Support services | **9** | Context / Identity / Integration / Knowledge / Platform / X2AI |
| Console commands | **1** | `ArchiveStaleLogs` (`logs:archive`) |
| Jobs / Events / Listeners / Notifications | **0** | KHÔNG tồn tại thư mục nào |
| Seeders | **2** | `DatabaseSeeder`, `DemoDataSeeder` |

---

## 4. API surface (từ `routes/api.php`)

- **Toàn bộ** endpoint API nằm sau middleware `platform.admin` (`EnsurePlatformAdmin`).
- 3 nhóm prefix, tất cả **chỉ SuperAdmin/Billing admin**:
  - `platform/billing/*` — Batch 07 (subscriptions, usage, invoices, wallets, adjustments, audit)
  - `platform/integrations/*` — Batch 08 (connections, api-keys, webhooks, events, retry-queue, security)
  - `platform/support/*` — Batch 10 (tickets, data-correction, knowledge-base)
- **KHÔNG có** endpoint API nào cho cư dân/BQL (residents, apartments, fees, work-orders, access-control…).
  Toàn bộ nghiệp vụ đó chỉ tồn tại trong Filament Pages (web/Livewire).

---

## 5. Frontend build (nhìn nhanh)

| Thành phần | Phiên bản / cấu hình |
|---|---|
| Vite | `^8.0.0` (`laravel-vite-plugin ^3.1`) |
| Tailwind CSS | `^4.0.0` (`@tailwindcss/vite`) |
| Node tooling | `concurrently ^9.0.1` |
| E2E | `@playwright/test ^1.61.1` |
| Scripts | `npm run build` = `vite build`; `npm run dev` = `vite` |
| Theme | `resources/css/filament/admin/theme.css` (DS-01 navy/gold) |
| Fonts | Inter + Plus Jakarta Sans (bunny.net, nạp qua render hook) |

> Frontend chỉ là asset của Filament/Blade — KHÔNG có SPA/React/Vue tách rời. Mobile sẽ
> phải tự dựng client riêng vì hiện không có API nghiệp vụ để tiêu thụ.
