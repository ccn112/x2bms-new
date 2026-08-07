# ADR-002 — Canonical sources for BQL Communication module

Status: Accepted (2026-08-07) · Context: `handoff/X2_BMS_BQL_NOTIFICATION_HANDOFF_20260807`,
gate spec 01 §3 ("write a short ADR naming canonical + adapter/deprecation plan before code").
Supersedes nothing; complements ADR-001 (tenant-scope discipline).

## Decisions

1. **Campaign root = `notifications`.** No parallel campaign/distribution table. Extend additively with
   `content_type` (announcement|news|event|poll) and `workflow_status` (full state machine). Keep `type`
   and `status` columns unchanged for backward compatibility; **`status=published` remains the sole
   resident-visibility gate**. Mapping: workflow `sent`/`completed` → `status=published`; workflow
   `cancelled`/`rejected` → `status=archived`/`draft` as appropriate.

2. **Delivery ledger = `notification_delivery_logs`.** It is the live, written, indexed per-(recipient×channel)
   record. `notification_delivery_snapshots` (created by the i18n localization migration, no model/writer)
   is OUT_OF_SCOPE for this module and will not be written by communication code. If i18n later needs a
   unified ledger, that is a separate consolidation ADR. Recipient-level tracking (BQL-NOTI-08) aggregates
   `notification_delivery_logs` under a new `notification_recipients` (resolved, deduped audience) parent.

3. **Content-to-campaign link = polymorphic `entity_type/entity_id`** already on `notifications`. Event and
   Poll stay canonical in their own tables (`events`/`event_registrations`, `polls`/`poll_options`/`poll_votes`);
   a campaign references them. Vote/registration remain canonical in the domain tables — the campaign never
   duplicates them.

4. **Templates = existing `notification_templates(+versions+localizations)`.** These tables exist (i18n
   migration) but are unwired. We add thin Eloquent models + a channel-template read path rather than
   creating a third template table. Seed `channel_templates.json` maps into them.

5. **Comments/attachments = generic polymorphic `comments`/`attachments`** via `HasComments`/`HasAttachments`
   (already wired onto `Notification`). `community_comments` is a separate feed system and is not touched.

6. **Approval + snapshot are NEW additive tables** (no existing source): `notification_approvals`,
   `notification_snapshots`, `notification_audience_groups`, `notification_recipients`. All tenant/building
   scoped with composite-FK pattern (MySQL) + negative tests.

7. **Event fan-out = existing `ActivityEmitter::emit()` + `AuditLog`.** No new `app/Events`/`app/Listeners`
   bus. Domain-event names in spec 12 are conceptual; audit rows carry actor/tenant/before-after/correlation.

8. **"Emergency" priority** is represented as `priority=urgent` + `category`/emergency flag, not a new
   `priority` enum value, to avoid churn on a hot indexed column. Quiet-hours bypass keys off this flag.

## Consequences

- Zero breaking change to resident API/app (status gate + response fields preserved).
- New wizard coexists with old `NotificationCenter` compose behind feature flag `x2.bql_wizard_enabled`
  until parity, then old compose is deprecated (not deleted) for one release (rollout spec 15).
- All new tables reversible; legacy rows backfill deterministically (`content_type=announcement`).
