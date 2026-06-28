# X2-BMS — Session Context & Build Log (2026-06-28 → 06-29)

Snapshot of what was decided and built so far, exported from the working session. Companion docs:
`PACKAGE_SELECTION_PLAN.md`, `CANONICAL_ENTITY_MAP.md`, `ERD_DRAFT.md`, `contracts/`.

## 1. Origin

Built fresh from the UI handoff `x2-bms/handoff/X2_BMS_MASTER_HANDOFF_20260628` (272 approved screens
across 3 packages: App Cư dân/BQL, Web Admin, Web Action). UI + sample data are the **data contract**:
no hardcoded values in views — seeders create the data, queries feed the views.

## 2. Locked decisions

- **Two repos:** `x2-bms-backend` (this — Laravel 13 + Filament 5 + API + seeder + Playwright) and `x2-bms-mobile` (React Native, later).
- **DB:** MySQL/MariaDB (single DB, row-level `tenant_id`/`building_id` scope).
- **UI rule:** Filament **custom-page-first** — Custom Page (Livewire + Blade + Tailwind) when layout doesn't match the handoff image; default Filament Resource only for simple CRUD. Charts are **custom SVG/CSS** (Filament native widgets can't reproduce the designs).
- **No hardcode**; every sample number comes from the seeder; after `migrate:fresh --seed` each screen reproduces its handoff image.
- **i18n:** content multi-language (vi default + en) via `spatie/laravel-translatable` (the Filament translatable plugin has no F5 build yet → manual per-locale fields).
- **Testing:** Playwright screenshot test per screen/batch.

## 3. Stack (installed & verified)

Laravel **13.17** · Filament **5.6.7** · Livewire **4.3.3** · PHP **8.4** (Herd) · Tailwind **v4** + Vite · MariaDB **12.3** · Node **22** (build/test only).

Packages: sanctum, spatie/laravel-permission (+ bezhansalleh/filament-shield), spatie/laravel-activitylog,
spatie/laravel-translatable, spatie/laravel-medialibrary (+ filament plugin), spatie/laravel-settings (+ filament plugin),
spatie/laravel-schedule-monitor, spatie/simple-excel, stechstudio/filament-impersonate, dedoc/scramble.
**Deferred:** laravel/horizon (prod/Linux — needs ext-pcntl), FCM channel (push feature), pulse/backup/health (M7).

## 4. Shared UI — X2 component set

`resources/views/components/x2/`: PageShell, Topbar, Sidebar, SectionCard, KpiCard, AiPanel, StatusBadge,
DataTable, ActionBar, AuditFooter + **AdminShell** (composes the chrome + nav with active state; used by all custom pages).
Prop-driven, no hardcoded sample data. Custom layout: `resources/views/components/layouts/x2-app.blade.php`.

## 5. Milestones completed

- **M0 — Reconcile + scaffold:** `CANONICAL_ENTITY_MAP.md` (12 cross-package naming conflicts resolved), `ERD_DRAFT.md`, backend scaffold, 10 X2 components, Playwright harness.
- **M1 — Pilot WEB-01-01** (Bảng điều khiển vận hành): Livewire custom page reproducing the image exactly (96.2% / 2,45 tỷ / 56 / 18 / donut 132) — full data-first pipeline + screenshot test.
- **M2 — Foundation:** Sanctum, RBAC (spatie + Shield), `BelongsToTenant` scope, audit (activitylog + curated `audit_logs`), Scramble, admin auth, Filament resources (Building/Apartment/Department).
- **M3/M4 — Packages:** media/settings/schedule-monitor/simple-excel/impersonate installed + base config.
- **Tier-1 completion:** floors, areas, residents, resident_apartment_relations; resources for Tenant/Project/Floor/Resident/User; 6 roles; `shield:generate` (9 policies / 108 permissions).
- **WEB-02 — Cư dân & Căn hộ (4 screens):**
  - 02-01 `/residents` — resident directory (KPIs + searchable table).
  - 02-02 `/apartments` + `/apartments/{id}/profile` — list + rich detail (collection donut, vehicles, cards, debts).
  - 02-03 `/vehicles-cards` — vehicles + access cards.
  - 02-04 `/resident-approvals` — approval queue with working approve/reject/need-more (creates resident + relation + audit).
  - New entities: `vehicles`, `access_cards`, `resident_approval_requests`. 4 contracts under `docs/contracts/WEB-02/`.

## 6. Domain model so far

Tenant → Project → Building → Floor/Area → Apartment; Department; User (HasRoles/HasApiTokens/FilamentUser);
Resident ↔ Apartment (resident_apartment_relations); Finance (billing_periods, statements, statement_lines, debts);
Feedback (feedback_categories, feedback_requests); Operations (work_orders, sla_events, ioc_alerts);
Vehicles/AccessCards; ResidentApprovalRequest; AuditLog; AiSuggestion. All business models use `BelongsToTenant`.

Dev admin: `x2bms@x2bms.vn` / `Bms@2026!` (role super_admin). Demo tenant Sunshine Garden (SG-A), 120 apartments/residents.

## 7. Engineering gotchas (also in the README)

- **Node 22** required for Vite 8 / Playwright (default Node 20 too old; `node_modules` must be installed under Node 22).
- **Herd composer** (`~/.config/herd/bin/composer.bat`, v2.9); avoid `^` in version constraints (cmd eats the caret).
- **`php artisan serve`** is slow/flaky under test load → run with `-d opcache.enable_cli=1` and `PHP_CLI_SERVER_WORKERS=8` (set in `.env`).
- **super_admin** bypass via `Gate::before` in `AppServiceProvider` (so Filament pages don't 403 after a fresh seed without `shield:generate`).
- **Playwright auth:** one-time login via `auth.setup.ts` → `.auth/admin.json` storageState reused by all tests; **reseed before each run**.

## 8. Current state & next

`migrate:fresh --seed` clean; **Playwright 7/7 PASS** (setup + WEB-01-01 + 4× WEB-02 + admin foundation).
**Next:** WEB-03 Tài chính – Phí – Công nợ, or finish WEB-01 (02/03/04); then continue batches per handoff `MODULE_BUILD_ORDER`.
