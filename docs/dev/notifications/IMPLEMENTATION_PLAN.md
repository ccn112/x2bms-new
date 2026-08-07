# IMPLEMENTATION PLAN — BQL Communication (2026-08-07)

Additive, feature-flagged, per-phase commit. Grounded in `CURRENT_NOTIFICATION_AUDIT.md` + `ADR-002`.
Feature flag: `config('x2.bql_wizard_enabled')` (env `X2_BQL_WIZARD=true`, default true in local/staging).

## Phase 1 — Domain foundation (T1)  [additive schema + services + guards]

**Migrations** (`database/migrations/2026_08_07_00000X_*`, MySQL guards where composite FK):
1. `add_communication_fields_to_notifications` — `content_type` enum default `announcement`,
   `workflow_status` enum default `draft`, `scheduled_at` (alias reuse `publish_at`? keep `publish_at`),
   `snapshot_version` uint, `approval_route_key`, `is_emergency` bool, `allow_feedback` bool,
   `require_read_ack` (reuse `requires_ack`), `pin_in_app` (reuse `is_pinned`), `cta_label`, `cta_target`,
   `audience_rule` json, `audience_locked` bool, `audience_snapshot_hash`, `send_strategy` enum,
   `sent_at`, `completed_at`, `cost_estimate` decimal, `cost_actual` decimal. Backfill `content_type`/`workflow_status`.
2. `create_notification_audience_groups` — tenant/building scoped saved segments (seed_key, name, rule json).
3. `create_notification_recipients` — resolved deduped audience (notification_id, user_id, resident_id,
   apartment_id, role, audience_reasons json, channels_planned json); composite FK; unique (notification_id,user_id).
4. `create_notification_approvals` (+ steps) — route_key, status, actor, role, scope, sla_due_at, acted_at,
   reason, snapshot_hash, correlation_id.
5. `create_notification_snapshots` — immutable content+audience+channels+approval payload + hash + version.
6. Thin models for existing `notification_templates(+versions+localizations)`.

**Enums** (`app/Enums/Notifications/`): `ContentType`, `WorkflowStatus`, `DeliveryStatus` (map to logs), `ApprovalStatus`, `SendStrategy`.
**Services** (`app/Services/Notifications/`): `CampaignStateMachine` (transition guard), `AudienceRuleValidator`
(whitelist), `AudienceEstimator`, `AudienceResolver` (dedupe + reasons + snapshot), `NotificationSnapshotService`,
`NotificationApprovalService` (config-driven route resolution). Policy `NotificationCampaignPolicy`.
**Tests:** transition valid/invalid, DSL whitelist, dedupe multi-apartment, snapshot invalidation on audience change,
approval route resolution, tenant/building negative (MUST_NOT_LEAK), composite-FK reject. **No new unbaselined `withoutGlobalScopes`.**

## Phase 2 — Content types (T2)
`content_type` wiring + subtype validation. News subtype fields (author/featured/visibility/publish_at/slug) as
`subtype`+json or `news_meta` json on notifications. Event/Poll link + capacity/waitlist/vote-scope/multi-choice
resolution on the domain tables. Subtype renderers for preview.

## Phase 3 — Wizard UI BQL-NOTI-02..06 (T3)
Custom Page `app/Filament/Pages/CommunicationWizard.php` slug `notifications/create`, `.x2-bql-page`, 5 steps with
server draft autosave (writes `notifications` row in `workflow_status=draft`), stepper, 2/3–1/3 layout, per-step
validate, back without loss, lock when sending/completed, approval-invalidation on post-approval edits. Reuses
`RichEditor`, X2 components, `MultiChannelNotifier` via `NotificationPublisher`. Livewire audience-builder + channel
cards (capability state from `ChannelConfigResolver`) + preview projections. Keep old compose behind flag.

## Phase 4 — Detail + recipients (T4)
`CommunicationDetail.php` slug `notifications/{record}` (tabs: overview/content/audience/channels/recipients/feedback/
activity/audit; KPI reads snapshot; subtype panels; actions clone/resend/export/cancel/revoke — never mutate sent snapshot).
`CommunicationRecipients.php` slug `notifications/{record}/recipients` (2/3 KPI+tabs+search+table, 1/3 sticky filter;
masked PII default; `notifications.view_recipient_pii` unmask; filters; bulk resend/remind/export via jobs).
Analytics: add CTA click tracking (additive). Jobs: `ResolveNotificationAudience`, `CreateNotificationDeliveries`,
`DispatchNotificationBatch`, `RetryNotificationDeliveries`, `SendNotificationReminder`, `AggregateNotificationAnalytics`,
`ClosePoll`, `FinalizeEvent`, `ExpireSecureLinks` — idempotent, `Queue::fake` testable.

## Phase 5 — Resident API additive (T5)
Extend `NotificationResource`/`NotificationDetailResource`/`BellReader` with `content_type, content_version, cover,
attachments, cta/deep_link, event_summary+registration_status, poll_summary+vote_status, read_ack`. Reuse existing
event register / poll vote endpoints for CTA. No breaking field changes; contract test locks existing keys.

## Phase 6 — Seeders (T6)
`CommunicationDemoSeeder` (idempotent, `X2_DEMO_SEED`, non-prod): audience groups, templates, approval routes,
12 announcements / 8 news / 6 events / 6 polls with real building/resident relations by code (not numeric id),
`demo_small` deliveries (50–200) via fake provider; `demo_scale` separate command. Poll aggregates match ballots;
event registrations ≤ capacity, overflow → waitlist.

## Phase 7 — Tests + evidence + deploy (T7)
Full unit/feature/security/Filament/tenant-isolation/API-compat/seeder-smoke. Docs:
`IMPLEMENTATION_REPORT.md`, `UAT_EVIDENCE.md`, `ROUTE_SCHEMA_TEST_INVENTORY.md`. DEV_JOURNAL per phase.
Deploy: `migrate --force` (additive) + `db:seed --class=CommunicationDemoSeeder` (non-prod). Rollback: flag off + reversible migrations.

## Commit cadence
One commit per phase (migration + code + test green) on `feat/bql-notification-communication-wizard`; no push until owner asks.
Update `docs/dev/notifications/DEV_JOURNAL.md` each phase.
