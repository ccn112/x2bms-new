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

## 2026-08-07 · T2 — Content types ✅
- Migration additive `...000006` events/event_registrations (registration_status/deadline, allow_guests/max_guests,
  fee_amount, qr_checkin, waitlist_count, checked_in_count, cancel_reason, checked_in_at/waitlisted_at) — GIỮ
  events.status chuẩn hoá.
- Migration additive `...000007` polls (summary, anonymous, vote_scope, allow_change_vote, max_choices,
  result_visibility, opens_at) + poll_options.option_key + poll_votes.apartment_id (+ index apartment-scope MySQL).
- Model casts/consts: Event (REGISTRATION_STATUSES), EventRegistration (STATUSES), Poll (VOTE_SCOPES/RESULT_VISIBILITY),
  PollVote (resident/apartment relations).
- Service `ContentSubtypeService`: validate subtype + tạo/link Event/Poll canonical trong scope + news meta.
  Dùng chung wizard (T3) + seeder (T6). Write path đăng ký/vote vẫn ở resident API (T5).
- Test `ContentSubtypeTest` 5 pass. Tổng Communication 13/13. CommunityPollScope không hồi quy.

## 2026-08-07 · T3 — Wizard BQL-NOTI-02→06 ✅ (functional)
- `app/Filament/Pages/CommunicationWizard.php` slug `notifications/create`, nav "Vận hành", feature-flag
  x2.bql_wizard_enabled. Wizard 5 bước Filament (Nội dung → Đối tượng → Kênh → Hẹn giờ/duyệt → Xem lại).
  Server draft tạo ở mount(); field động theo content_type (news/event/poll); scope theo QUYỀN người soạn
  (mirror NotificationCenter, chốt chặn server G9 qua canManageBy).
- Wire domain: ContentSubtypeService (subtype + link Event/Poll), AudienceResolver (resolve + dedupe +
  snapshot recipients), CampaignCostEstimator (ước tính chi phí), NotificationApprovalService (tuyến duyệt),
  NotificationSnapshotService (chốt snapshot khi gửi duyệt). Dual-write notification_audiences (compat dispatcher cũ).
- Action: **Lưu nháp** + **Gửi duyệt** (send_now KHÔNG bypass duyệt — locked decision). Duyệt & phát hành ở T4 detail.
- Placeholder live: ước tính người nhận, chi phí, tuyến duyệt, preflight.
- Blade `communication-wizard.blade.php` (.x2-bql-page). Giữ NotificationCenter compose cũ tới khi parity.
- Test `CommunicationWizardTest` (Livewire) 1 pass/9 assert: mount→draft, fill→submit gửi duyệt resolve 1 người nhận,
  snapshot v1, approval requested, pending_approval.
- **Visual follow-up (T3.1):** bố cục 2/3 form + 1/3 preview sticky theo ảnh duyệt (hiện gộp preview vào placeholder
  từng bước — spec 05 80/20, ảnh định nghĩa hierarchy không phải pixel). Ghi backlog.

## 2026-08-07 · T4 — Detail (07) + Recipients (08) + publish flow ✅
- `NotificationPublisher`: đóng vòng approved→queued→sending→(ghi delivery app-inbox upsert idempotent +
  gọi push/external dispatcher sẵn có)→sent→completed. Map status=published (cư dân thấy). Snapshot đã chốt KHÔNG sửa.
- `app/Filament/Pages/CommunicationDetail.php` slug `notifications/detail?record=` (ngoài menu). BQL-NOTI-07:
  highlights + KPI (người nhận/đã gửi/đã đọc/lỗi) + preview nội dung + kênh + tuyến duyệt + snapshot.
  Actions vòng đời qua service: Duyệt / Yêu cầu sửa / Từ chối (maker-checker: người tạo không tự duyệt) /
  Phát hành / Hủy / Nhân bản. BQL-NOTI-08: bảng người nhận (PII mask email/phone, filter vai trò/trạng thái).
- Link "Chi tiết (mới)" từ NotificationCenter (feature-flag).
- Test: CommunicationPublishTest 2 (duyệt+phát hành tạo delivery inbox; chặn phát hành khi chưa duyệt) +
  detail render. **Suite Communication 17/17, 60 assert.**
- **Follow-up (T4.1):** trang recipients riêng 2/3+1/3 sticky filter + bulk resend/remind/export qua JOB
  (hiện publish đồng bộ cho demo/tòa nhỏ; job hoá cho audience lớn). CTA click tracking (analytics) additive.

## 2026-08-07 · T5 — Resident API additive ✅
- `NotificationResource` (list) +content_type +cta +allow_feedback. `NotificationDetailResource` (detail)
  +content_type +content_meta +cta +allow_feedback +expires_at + **event summary** (venue/capacity/registered/
  registration_status/deadline/fee/qr) + **poll summary** (question/options[key,label,votes]/vote_scope/anonymous/
  result_visibility). App route theo content_type; đăng ký/vote vẫn gọi API community events/polls (spec 12).
- Test `CommunicationApiContractTest` 3/29: **khóa key hợp đồng cũ** (16 key list) + content_type + event/poll summary.
  Không hồi quy ResidentNotificationSummary/BellReader/Ack (11/11).
- **Follow-up:** thêm content_type vào BellReader (feed hợp nhất) nếu app cần; hiện giữ nguyên (an toàn).

## 2026-08-07 · T6 — Seeder demo ✅
- `CommunicationDemoSeeder` (non-prod + X2_DEMO_SEED, provider FAKE): 12 thông báo / 8 tin / 6 sự kiện /
  6 poll + 11 nhóm người nhận + 6 template. Idempotent theo (tenant, code=SEED:<seed_key>).
- Map dữ liệu seed → demo THẬT: mã tòa S1/S2… → tòa demo (SG-A/SG-B); role co_owner→owner,
  household_member→member (roles demo owner|tenant|member); resident_status verified→active.
- Vòng đời theo status seed (draft/pending/approved/scheduled/completed/cancelled); campaign đã phát hành
  resolve người nhận + read_count ~60%. Event/Poll qua ContentSubtypeService (poll vote_count khớp seed).
  Delivery samples map sang cư dân demo (fake, không gửi mạng). Data JSON copy vào database/seeders/data/communication/.
- Đăng ký trong DatabaseSeeder (non-prod, tự guard flag). Fix: notification_templates.risk enum low|medium|high|critical.
- Test `CommunicationSeederTest` (fixture demo tối thiểu): counts 12/8/6/6, 11 nhóm, poll aggregate khớp,
  published có người nhận, **idempotent** (chạy lại không nhân đôi). **Suite Communication 21/21, 99 assert.**

## 2026-08-07 · T7 — Acceptance + evidence + deploy ✅
- **Full suite: 365 test · 361 pass · 3 skip · 1 fail · 1463 assert** (~72s). +21 test Communication đều xanh;
  fail duy nhất = BillingRunner ratchet **pre-existing** (không phải module này). Zero regression.
- Docs: `IMPLEMENTATION_REPORT.md` (trạng thái theo màn DONE/PARTIAL/NOT_IMPLEMENTED + deploy/rollback),
  `ROUTE_SCHEMA_TEST_INVENTORY.md`, `UAT_EVIDENCE.md` (UAT-01→06 map test + kiểm thủ công).
- Cập nhật `docs/PROGRESS_TRACKER.md`: 07-02/07-03 → ✅ (có test).
- **PARTIAL/follow-up:** BQL-NOTI-04 cấu hình chi tiết per-kênh · BQL-NOTI-06 preview đa kênh đầy đủ ·
  BQL-NOTI-08 trang riêng 2/3+1/3 + bulk job · job-hoá publish audience lớn · provider Zalo/SMS thật (chờ owner) ·
  ảnh màn hình thật (owner/QA chạy). Chi tiết trong IMPLEMENTATION_REPORT.
