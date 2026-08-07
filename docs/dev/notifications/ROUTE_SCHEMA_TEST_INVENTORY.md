# ROUTE / SCHEMA / TEST INVENTORY — BQL Communication · 2026-08-07

## Routes (/admin panel — custom Pages, discoverPages)
| Route | Page | Ghi chú |
|---|---|---|
| `/admin/notifications/create` | `CommunicationWizard` | Wizard 5 bước (BQL-NOTI-02→06); feature-flag |
| `/admin/notifications/detail?record={id}` | `CommunicationDetail` | Chi tiết (07) + người nhận (08); ngoài menu |
| `/admin/notifications/center` | `NotificationCenter` (cũ) | Giữ nguyên + link "Chi tiết (mới)" |
| `/admin/notifications/analytics` | `NotificationAnalytics` (cũ) | Giữ nguyên |
| `/admin/notifications/channel-settings` | `BuildingChannelSettings` (cũ) | Giữ nguyên |
| `/admin/notifications/delivery-audit` | `NotificationDeliveryAudit` (cũ) | Giữ nguyên |

Resident API (giữ nguyên route, payload additive): `GET /api/v1/resident/notifications[/{id}]`, `.../read`, `.../ack`,
`.../comments`, `GET bell`, `community/events`, `community/polls` — không đổi route/deep-link.

## Schema (migrations additive, reversible)
| Migration | Thay đổi |
|---|---|
| `2026_08_07_000001` | `notifications` +content_type, workflow_status, allow_feedback, cta_label/target, content_meta, audience_rule, audience_locked, audience_snapshot_hash, send_strategy, approval_route_key, snapshot_version, sent_at, completed_at, cost_estimate/actual + backfill |
| `..._000002` | `notification_audience_groups` (saved segments; composite FK MySQL) |
| `..._000003` | `notification_recipients` (resolved+deduped; composite FK MySQL) |
| `..._000004` | `notification_approvals` + `notification_approval_steps` |
| `..._000005` | `notification_snapshots` (immutable, hashed, versioned) |
| `..._000006` | `events`/`event_registrations` +registration_status/deadline, guests, fee, qr_checkin, waitlist/checked-in, checked_in_at/waitlisted_at |
| `..._000007` | `polls` +anonymous/vote_scope/allow_change_vote/max_choices/result_visibility/opens_at; `poll_options.option_key`; `poll_votes.apartment_id` |

Canonical (không tạo trùng): campaign=`notifications`, delivery=`notification_delivery_logs`, comments=`comments`,
event/poll = bảng domain sẵn có (link qua entity_type/entity_id). `notification_delivery_snapshots` (i18n) OUT_OF_SCOPE.

## Enums / Services / Models mới
- Enums: `CommunicationContentType`, `CommunicationWorkflowStatus`, `CommunicationApprovalStatus`, `CommunicationSendStrategy`.
- Services (`app/Services/Notifications/`): `CampaignStateMachine`, `AudienceRuleValidator`, `AudienceResolver`,
  `AudienceEstimator`, `CampaignCostEstimator`, `NotificationSnapshotService`, `NotificationApprovalService`,
  `ContentSubtypeService`, `NotificationPublisher`.
- Models: `NotificationAudienceGroup`, `NotificationRecipient`, `NotificationApproval`, `NotificationApprovalStep`,
  `NotificationSnapshot` (+ mở rộng `Notification`, `Event`, `EventRegistration`, `Poll`, `PollOption`, `PollVote`).
- Config: `config/x2.php` (bql_wizard_enabled, demo_seed_enabled), `config/x2_communication.php` (approval_routes,
  channel_pricing, paid_channels, quiet_hours).

## Tests (`tests/Feature/Communication/`) — 21 tests, 99 assertions, all green
| File | Phủ |
|---|---|
| `CommunicationDomainTest` (8) | state machine hợp lệ/invalid + map status; DSL whitelist + normalize; **resolver dedupe + MUST_NOT_LEAK cross-tenant**; filter vai trò; snapshot version + diverge; route khẩn cấp + duyệt 2 bước maker-checker |
| `ContentSubtypeTest` (5) | link Event/Poll + options; validate thiếu venue/lựa chọn; news meta |
| `CommunicationWizardTest` (2) | Livewire mount→draft, gửi duyệt resolve người nhận + snapshot + approval + pending_approval; render trang chi tiết |
| `CommunicationPublishTest` (2) | duyệt+phát hành tạo delivery inbox; chặn phát hành khi chưa duyệt |
| `CommunicationApiContractTest` (3) | khoá 16 key hợp đồng cũ + content_type + event/poll summary |
| `CommunicationSeederTest` (1) | counts 12/8/6/6 + 11 nhóm + poll aggregate khớp + published có người nhận + idempotent |

Regression đã kiểm: NotificationAudienceScope, MultiChannelNotifier, NotificationExternalChannelDispatcher,
ResidentNotificationSummary, BellReader, NotificationAck, CommunityPollScope — không hồi quy.
