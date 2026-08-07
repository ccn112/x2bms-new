# CURRENT NOTIFICATION AUDIT — BQL Communication (2026-08-07)

Repo-first audit required by `handoff/X2_BMS_BQL_NOTIFICATION_HANDOFF_20260807`
(specs 01/02). Every claim below is verified against source. This gates all
implementation: nothing is built until each capability is classified
REUSE / EXTEND / CREATE / DEPRECATE_WITH_ADAPTER / OUT_OF_SCOPE.

## 0. Baseline

- Branch: `feat/bql-notification-communication-wizard` (off `main` @ `b715691`).
- Working tree clean at branch creation (DS-04 committed to main first).
- Test baseline: `php artisan test` → **344 tests, 340 passed, 3 skipped, 1 failed, 1364 assertions** (~139s).
  - **Pre-existing failure (NOT this work):** `TenantScopeRatchetTest::test_khong_sinh_them_cho_bo_tenant_scope_moi`
    — `app/Services/Billing/Engine/BillingRunner.php` has 7 `withoutGlobalScopes()` not recorded in
    `tests/Architecture/tenant_scope_baseline.json` (from billing engine P2.1, session 2026-08-04).
    Recorded as pre-existing; this module must not add new unbaselined tenant-scope bypasses.
- Panels: `/admin` (`AdminPanelProvider`) discovers **custom Pages only** (`discoverPages`, no `discoverResources`).
  Stock CRUD `Resource` classes live on the `/fila` panel. `/hq` and `/sa` are separate panels.
  → All 7 BQL-NOTI screens must be **custom Filament Pages under `app/Filament/Pages`** to appear on `/admin`.

## 1. Reuse / Extend / Create matrix

| Capability | Verdict | Canonical source (file) | Action |
|---|---|---|---|
| Campaign root | **EXTEND** | `app/Models/Notification.php` + `..._000003_create_notifications.php` | Add `content_type`, `workflow_status`, snapshot fields (additive) |
| Content: announcement | REUSE | `notifications.type=announcement` | Map to `content_type=announcement` |
| Content: news | **CREATE (subtype)** | — | `content_type=news` + subtype fields (news_meta) |
| Content: event | **EXTEND (link)** | `app/Models/Event.php`, `events`, `event_registrations` | Reference via `entity_type/entity_id`; add BQL /admin surface |
| Content: poll | **EXTEND (link)** | `app/Models/Poll.php`, `polls/poll_options/poll_votes` | Reference via `entity_type/entity_id`; add multi-choice + scope fields |
| Audience scope rows | REUSE | `notification_audiences` (scope_type/scope_id) | Keep; add rule DSL + resolved snapshot |
| Audience rule DSL | **CREATE** | — | `audience_rule` json + validator (whitelist fields/ops, spec 07) |
| Resolved recipients (deduped) | **CREATE** | — | `notification_recipients` (per user, audience_reasons, dedupe) |
| Saved audience groups | **CREATE** | — (community_groups is a different concept) | `notification_audience_groups` (tenant/building scoped) |
| Channels (per campaign) | REUSE | `notification_channels` (channel/enabled/config json) | Keep; extend config per spec 04 |
| Channel dispatch | REUSE | `MultiChannelNotifier`, `NotificationExternalChannelDispatcher`, `NotificationPushDispatcher`, `EmailChannelDispatcher` (real), `PendingProviderChannelDispatcher` (stub) | Reuse; wrap in publisher/jobs |
| Channel provisioning (per building) | REUSE | `BuildingChannelSettings` page + `building_notification_channels` | Do not add another config page |
| Channel config resolve | REUSE | `ChannelConfigResolver` | Reuse |
| Delivery ledger | REUSE | `notification_delivery_logs` (**canonical**, live writers) | Extend for recipient-status screen (see ADR-002) |
| Delivery snapshots (i18n) | OUT_OF_SCOPE | `notification_delivery_snapshots` (schema only, no writer) | Leave to i18n; do NOT write to it (ADR-002) |
| Read/ack tracking | REUSE | `notification_reads` (`read_at`, `acknowledged_at`), `ResidentNotificationService::markRead` | Reuse |
| Analytics | REUSE/EXTEND | `NotificationAnalyticsService` + `NotificationAnalytics` page | Add CTA click tracking (additive event) |
| Compose surface | **EXTEND → wizard** | `NotificationCenter.php` (modal Action compose) | Add 5-step wizard page; keep old modal until parity (rollout phase 2) |
| Templates | **EXTEND** | `notification_templates(+versions+localizations)` (schema only, unwired) | Wire thin models for channel templates; do NOT create a 3rd table |
| Approval workflow | **CREATE** | — | `notification_approvals` + steps + service (config-driven, no hardcoded roles) |
| Snapshot (immutable sent) | **CREATE** | — | `notification_snapshots` (content+audience+channels+approval, hashed) |
| Scheduling | EXTEND | `notifications.publish_at` | Add scheduler job claiming due campaigns atomically |
| Resident API (list/detail/read/ack) | REUSE | `Api/V1/Resident/NotificationController`, `NotificationResource`/`NotificationDetailResource`, `BellReader` | Additive fields only (spec 12) |
| Events register / check-in API | REUSE | `CommunityController` events routes | Reuse for CTA |
| Poll vote API | REUSE | `CommunityController::polls/vote` | Reuse; extend for multi-choice + apartment scope |
| Comments/attachments | REUSE | `comments`/`attachments` + `HasComments`/`HasAttachments` | Reuse polymorphic |
| Tenant/building hard-lock | REUSE | composite FK on `notifications`, `BelongsToTenant`, ratchet baseline | Every new table tenant/building scoped + negative tests |
| Design system | REUSE | `resources/views/components/x2/*`, skill `x2bms-admin-listing-page` | No new CSS framework; scope to `.x2-bql-page` |

## 2. Notification model (canonical campaign) — key facts

`notifications` columns: `id, tenant_id (nullable), owner_level (platform|tenant|project), source (bql|interaction),
project_id, building_id, code, type (announcement|billing|maintenance|emergency|community|system),
category, subtype, action_key, entity_type, entity_id (polymorphic contentable — already present!),
requires_ack, title, summary, body, priority (low|normal|high|urgent), status (draft|scheduled|published|archived),
is_pinned, cover_path, publish_at, expires_at, published_at, read_count, recipient_count, created_by_id,
published_by_id, timestamps, deleted_at`.

- **Resident visibility depends on `status=published`** (`scopeVisibleTo`, `ResidentNotificationService::visibleQuery`).
  → New `workflow_status` must NOT replace `status`; keep `status` as the resident-facing publish gate.
  Map: workflow `sent`/`completed` → `status=published`.
- `entity_type/entity_id` already gives polymorphic `contentable` → event/poll link with no new column.
- `priority` currently has no `emergency`; seed uses `priority=emergency`. → add `emergency` to priority (additive) OR
  map emergency to `urgent` + `type=emergency`. **Decision:** keep `priority` set, treat "emergency" as `priority=urgent`
  with an explicit `is_emergency`/category flag; documented in ADR-002 to avoid enum churn on a hot column.

## 3. Duplication / dual-source risks and resolutions (see ADR-002)

1. Delivery: `notification_delivery_logs` (live) vs `notification_delivery_snapshots` (i18n, unwired) → **logs canonical**.
2. Templates: unwired `notification_templates*` vs inline compose → **wire notification_templates; no 3rd table**.
3. Comments: generic `comments` vs `community_comments` → notifications use **generic `comments`** (unchanged).
4. "Channel" naming: `App\Models\NotificationChannel` (row) vs `App\Enums\NotificationChannel` (push cat) vs
   `App\Models\BuildingNotificationChannel` (provisioning) → keep names; new code references by fully-qualified use.
5. Read state split (`notification_reads` / `activity_notifications` / `resident_bell_state`) → intentional per ADR-001, REUSE.
6. Events/Polls only on `/fila` → add BQL `/admin` management pages (EXTEND) as part of content integration.
7. No domain event bus (`app/Events`/`app/Listeners` absent) → use existing `ActivityEmitter::emit()` + `AuditLog`
   pattern, not a new bus (spec 12 says "actual naming must follow existing code").

## 4. Migration / rollback risk

- All schema changes additive (nullable columns, new tables). Composite-FK pattern reused for new tenant/building tables.
- MySQL-only guards where composite FK / triggers are used (match existing `..._000002`/`..._000004` pattern; sqlite tests skip).
- Rollback: feature flag `x2.bql_wizard_enabled` gates new wizard/detail pages; old `NotificationCenter` compose remains.
- No destructive drops. Legacy notifications backfill `content_type=announcement`, `workflow_status` derived from `status`.
