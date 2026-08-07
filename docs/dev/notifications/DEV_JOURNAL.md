# DEV JOURNAL — BQL Communication (BQL-NOTI)

Nhánh: `feat/bql-notification-communication-wizard`. Gói: `handoff/X2_BMS_BQL_NOTIFICATION_HANDOFF_20260807`.

## 2026-08-07 · T0 — Audit + gate
- Đọc trọn 15 spec + data seed. Chạy audit code hiện có → `CURRENT_NOTIFICATION_AUDIT.md`.
- Chốt canonical sources → `ADR-002` (campaign=notifications, delivery=notification_delivery_logs,
  content link=entity_type/entity_id, comments=generic, snapshot/approval/recipients/groups = mới).
- Kế hoạch 7 phase → `IMPLEMENTATION_PLAN.md`.
- Baseline test: 344 · 340 pass · 3 skip · **1 fail pre-existing** (`BillingRunner` 7 chỗ withoutGlobalScopes
  chưa baseline, từ engine billing phiên 2026-08-04 — KHÔNG thuộc module này, không sửa).

## 2026-08-07 · T1 — Domain foundation ✅
**Schema (additive, reversible):**
- `2026_08_07_000001` mở rộng `notifications`: content_type, workflow_status, allow_feedback, cta_label/target,
  content_meta, audience_rule, audience_locked, audience_snapshot_hash, send_strategy, approval_route_key,
  snapshot_version, sent_at, completed_at, cost_estimate/actual + backfill legacy.
- `..._000002` notification_audience_groups (saved segments, tenant/building scoped, composite FK MySQL).
- `..._000003` notification_recipients (resolved + dedupe, audience_reasons, composite FK MySQL).
- `..._000004` notification_approvals + notification_approval_steps (maker-checker, config route).
- `..._000005` notification_snapshots (immutable, hashed, versioned).

**Enums:** CommunicationContentType, CommunicationWorkflowStatus (state machine + tone), CommunicationApprovalStatus,
CommunicationSendStrategy.

**Models:** NotificationAudienceGroup, NotificationRecipient, NotificationApproval, NotificationApprovalStep,
NotificationSnapshot + mở rộng Notification (casts enum/array, quan hệ recipients/approvals/snapshots/latestSnapshot,
helper contentEvent/contentPoll).

**Services (`app/Services/Notifications/`):** CampaignStateMachine (guard + map status cư dân),
AudienceRuleValidator (whitelist field/operator, chuẩn hoá 2 shape), AudienceResolver (scope tenant TƯỜNG MINH,
dedupe theo cư dân, ghi snapshot recipients), AudienceEstimator, NotificationSnapshotService (capture + diverges),
NotificationApprovalService (route theo config, maker-checker), CampaignCostEstimator.

**Config:** `config/x2.php` +bql_wizard_enabled +demo_seed_enabled; `config/x2_communication.php` (approval_routes,
channel_pricing, paid_channels, quiet_hours).

**Test:** `tests/Feature/Communication/CommunicationDomainTest.php` — 8 pass/27 assert (state machine hợp lệ+invalid,
validator whitelist+normalize, resolver dedupe + **MUST_NOT_LEAK cross-tenant**, filter vai trò, snapshot version+diverge,
route khẩn cấp + luồng duyệt 2 bước maker-checker). Migration validated trên sqlite in-memory.
Ratchet: thêm `AudienceResolver` (2 chỗ, re-scope tenant tường minh) vào baseline; vi phạm còn lại chỉ là BillingRunner pre-existing.

**Còn (T1 chuyển sang phase sau):** NotificationCampaignPolicy (gắn ở T3 khi wire action), delivery status enum chi tiết
(map notification_delivery_logs khi build T4), thin template models (T6).
