# X2-BMS — Máy trạng thái theo Domain (Domain State Machines)

> Audit READ-ONLY — 2026-07-18. Nguồn: 4 enum PHP (`app/Enums`) + grep các Filament Page / Http Controller + giá trị **thực tế** trong MySQL `x2bms`.
>
> **Kết luận cốt lõi:** Hệ thống hầu như KHÔNG có "state machine" chính quy. Chỉ **4 enum PHP** tồn tại; đại đa số trạng thái là **cột string tự do**, giá trị hợp lệ được định nghĩa rải rác trong các hằng `const STATUS = [...]` của từng Filament Page và trong các lời gọi `->update(['status' => '...'])`. Chuyển trạng thái là **ngầm định (implicit)** — không có guard tập trung; nút bấm trên UI quyết định transition nào khả dụng qua điều kiện `->visible(...)`.
>
> Cột "Side effect" liệt kê: ghi `audit_logs` (qua trait `App\Filament\Concerns\WritesAudit` hoặc inline), ghi bảng lịch sử `*_status_histories`/`*_status_logs`, tạo bản ghi liên quan, gửi Filament `Notification` (toast UI, **không** phải push mobile).

---

## PHẦN A — 4 ENUM PHP CHÍNH QUY

### A1. `FeedbackStatus` (`app/Enums/FeedbackStatus.php`) — cột `feedback_requests.status`

| case | value | label | tone |
|---|---|---|---|
| New | `new` | Mới | red |
| Assigned | `assigned` | Đã phân công | amber |
| InProgress | `in_progress` | Đang xử lý | blue |
| Resolved | `resolved` | Đã xử lý | green |
| Closed | `closed` | Đã đóng | slate |

- Helper `pendingValues()` = `[new, assigned, in_progress]` (dùng đếm KPI "chờ xử lý").
- Giá trị thực tế DB: closed(43), resolved(43), new(23), in_progress(19), assigned(19).

**Chuyển trạng thái** — `app/Filament/Pages/FeedbackQueue.php`:
| Từ → Đến | Hàm / dòng | Actor | Side effect |
|---|---|---|---|
| (any) → `assigned` | `assign()` (dòng 199-212) — set `assigned_to_id`, `team_id` | Nhân viên BQL có quyền feedback | Tạo `feedback_assignments`(status=assigned); ghi `feedback_status_histories`; audit `feedback.assign`; toast |
| (any) → new/assigned/in_progress/resolved/closed | `changeStatus()` (dòng 214-221) — `update(['status'=>$to->value])` | BQL | Ghi `feedback_status_histories`(from→to); audit `feedback.status`; toast |
| → tạo Work Order | `createWorkOrder()` (dòng 223-235) | BQL | Tạo `work_orders`(status=pending, code=WO-FB-{id}); audit `feedback.create_wo` |

Không ràng buộc thứ tự bắt buộc (có thể nhảy trạng thái); guard duy nhất là nút UI.

---

### A2. `WorkOrderStatus` (`app/Enums/WorkOrderStatus.php`) — cột `work_orders.status`

| case | value | label | tone |
|---|---|---|---|
| Pending | `pending` | Chờ xử lý | amber |
| InProgress | `in_progress` | Đang xử lý | blue |
| Done | `done` | Hoàn thành | green |
| Overdue | `overdue` | Quá hạn | red |

- Giá trị thực tế DB: done(164), pending(27), in_progress(4). (`overdue` chưa xuất hiện — có thể là trạng thái tính toán/hiển thị.)

**Chuyển trạng thái** — `app/Filament/Pages/WorkOrderKanban.php` (Kanban kéo-thả):
| Transition | Hàm / dòng | Actor | Side effect |
|---|---|---|---|
| → `in_progress` | `move()` (dòng 78-92): nếu chưa có `started_at` thì set `started_at=now()` | Kỹ thuật/BQL | audit `work_order.move`; toast |
| → `done` | `move()`: set `completed_at=now()` (nếu trống); cũng có ở dòng 170 khi nghiệm thu | Người nghiệm thu | audit `work_order.move`/`work_order.signoff`; ghi `work_order_signatures`; toast |
| gán người | `assign` (dòng 123) | BQL | Tạo `work_order_assignments`(status=assigned, role=primary); audit `work_order.assign` |
| cập nhật checklist | dòng 150 | Kỹ thuật | audit `work_order.checklist` |

`work_order_assignments.status`: assigned / done (thực tế DB).

---

### A3. `ResidentApprovalStatus` (`app/Enums/ResidentApprovalStatus.php`) — cột `resident_approval_requests.status`

| case | value | label | tone |
|---|---|---|---|
| Pending | `pending` | Chờ duyệt | amber |
| Approved | `approved` | Đã duyệt | green |
| Rejected | `rejected` | Từ chối | red |
| NeedMore | `need_more` | Cần bổ sung | blue |

- DB thực tế: chỉ có pending(11).

**Chuyển trạng thái** — 3 điểm code (giá trị string, KHÔNG dùng enum khi update):
| Transition | Nơi xử lý | Actor | Side effect |
|---|---|---|---|
| → `approved` | `ResidentApprovalQueue.php:68` `$req->update(['status'=>'approved'])`; trước đó dòng 55 tạo/đặt resident `status='active'` | BQL duyệt | audit `resident.approve` |
| → `rejected` | `ResidentApprovalQueue.php:74` | BQL | audit `resident.reject` |
| → `need_more` | `ResidentApprovalQueue.php:80` | BQL | audit `resident.need_more` |
| approve/reject (màn chi tiết) | `AccountApprovalDetail.php:76-99` `decide()` với map `['approve'=>'approved','need_more'=>'need_more','reject'=>'rejected']`; bắt buộc nhập `note` khi reject/need_more | BQL | audit `account.{decision}`; toast |
| approve/reject (My Work) | `MyWork.php:339` `ResidentApprovalRequest::update(['status'=> approved|rejected])` | Người được giao | (không audit riêng tại đây) |

⚠️ **Enum có case `need_more` nhưng UI `AccountApprovalDetail` còn hiển thị thêm `reviewing`** (statusMeta dòng 110) — `reviewing` không nằm trong enum → lệch giữa enum và UI.

---

### A4. `VehicleType` (`app/Enums/VehicleType.php`) — cột `vehicles.type`

| case | value | label |
|---|---|---|
| Car | `car` | Ô tô |
| Motorbike | `motorbike` | Xe máy |
| Bicycle | `bicycle` | Xe đạp |

⚠️ **Lệch enum ↔ UI:** `VehicleRequests.php:57` định nghĩa TYPE = car / motorbike / **ev (Xe điện)** / bicycle — có thêm `ev` không có trong enum. Mobile nên chấp nhận cả 4 giá trị.

(Đây là enum phân loại, không phải state machine — xem `vehicles.status` ở Phần B.)

---

## PHẦN B — TRẠNG THÁI DẠNG STRING (KHÔNG CÓ ENUM PHP)

> Không có state machine chính quy. Tập giá trị hợp lệ suy ra từ hằng `const STATUS` và các `update()`. Cột "DB thực tế" = giá trị đang tồn tại.

### B1. Cư dân — `residents` (nhiều cột status song song)

| Cột | Giá trị (nguồn) | DB thực tế | Chuyển ở đâu |
|---|---|---|---|
| `status` | active / pending / inactive (`ResidentDirectory.php:275` STATUS; STATUS_TONE dòng 72) | active(1301), pending(3), inactive(2) | Tạo mới = `pending` (`ResidentCreate.php:170`); duyệt→`active` (`ResidentApprovalQueue.php:55`, bulk `ResidentDirectory.php:462`); khóa→`inactive` (`ResidentDirectory.php:407/476`, `ResidentDetail.php:177`); mở khóa→`active` (`ResidentDetail.php:186`) |
| `link_status` | unlinked / linked | unlinked(1304), linked(2) | Đặt khi gắn tài khoản toàn cục (binding) |
| `profile_status` | cho_bo_sung / hoat_dong | cho_bo_sung(1304), hoat_dong(2) | Hồ sơ KYC hoàn thiện |
| `kyc_status` | unverified / verified | unverified(1304), verified(2) | Xác minh danh tính |
| `residence_status` | permanent / (tạm trú…) | permanent(1306) | Batch 2026_07_17 thêm cột |
| `face_match_status` | not_checked / … | not_checked(1306) | Đối chiếu khuôn mặt (chưa dùng) |

Actor: BQL cư dân. Side effect: audit `resident.lock`/`unlock`/`bulk_approve`/`bulk_lock`; các thao tác đổi status resident **không** ghi bảng history riêng (chỉ audit_logs).

### B2. Căn hộ — `apartments.status`

Nguồn: `ApartmentProfile.php:49-56` & `ApartmentDirectory.php`.
| Giá trị | Nhãn |
|---|---|
| `occupied` | Đang ở / Đã ở |
| `vacant` | Trống |
| `pending_attach` | Chờ gắn cư dân |
| `maintenance` | Đang sửa chữa |
| `handover_pending` | Chờ bàn giao |
| `locked` | Khóa |

- DB thực tế: occupied(1305).
- Transition: `ApartmentProfile.php:128` `update(['status'=>$data['status']])` (đổi tự do qua form).
- Actor: BQL căn hộ. Side effect: ghi **`apartment_status_histories`** (from_status/to_status/changed_by_id) + audit `apartment.change_status`.

### B3. Gắn căn / Binding — `resident_binding_requests.status` & `resident_unit_bindings.status`

Nguồn STATUS: `ResidentBindingQueue.php:60`.
| Giá trị request | Nhãn |
|---|---|
| `pending` | Chờ duyệt |
| `need_more_info` | Cần bổ sung |
| `approved` | Đã duyệt |
| `rejected` | Từ chối |
| `cancelled` | Đã hủy |

- DB thực tế request: pending(5), approved(2), cancelled(1), need_more_info(1), rejected(1).
- Transition (`ResidentBindingQueue.php`):
  - → `approved` (dòng 181): `update(['status'=>'approved','reviewed_by','reviewed_at'])` **+ tạo `resident_unit_bindings`(status=active, starts_at=now)** (dòng 191); audit `binding.approve`.
  - → `rejected` / `need_more_info` (`setStatus()` dòng 204-210): set reviewed_by/reviewed_at/review_note; audit `binding.reject`/`binding.need_more`.
- `resident_unit_bindings.status`: active (DB thực tế). Actor: BQL duyệt gắn căn.
- ⚠️ Lệch: request dùng `need_more_info`, còn enum ResidentApprovalStatus dùng `need_more` — hai luồng khác nhau, giá trị khác nhau.

### B4. Thông báo — `notifications.status`

Nguồn: `NotificationCenter.php:59` STATUS.
| Giá trị | Nhãn |
|---|---|
| `draft` | Nháp |
| `scheduled` | Hẹn giờ |
| `published` | Đã phát hành |
| `archived` | Lưu trữ |

- DB thực tế: published(3), draft(1), scheduled(1).
- Transition (`NotificationCenter.php`):
  - Tạo: dòng 170 `status = now ? 'published' : (scheduledAt ? 'scheduled' : 'draft')`; audit `notification.create`.
  - → `published` (dòng 210): set recipient_count, published_at, published_by_id; audit `notification.publish`.
  - → `archived` (dòng 257): audit `notification.archive`.
- Actor: BQL truyền thông. Side effect: `notification_delivery_logs`(status=sent) khi phát hành; `notification_reads` cập nhật khi cư dân đọc.

### B5. Duyệt bảng kê phí — `billing_runs.approval_status` (+ `statements.approval_status`)

Nguồn STATUS: `StatementApprovalQueue.php:56-62`.
| Giá trị | Nhãn |
|---|---|
| `pending` | Chờ duyệt |
| `reviewing` | Đang rà soát |
| `need_more` | Cần bổ sung |
| `approved` | Đã duyệt |
| `rejected` | Từ chối |
| `published` | Đã phát hành |

- DB `billing_runs.approval_status`: approved(2), pending(2), reviewing(1), need_more(1), rejected(1). `billing_runs.status` riêng: completed(7).
- DB `statements.approval_status`: published(1230), pending(130). `statements.status` (thu tiền): partial(622), paid(466), issued(272).
- Transition (`StatementApprovalQueue.php`):
  - → `approved` (`approve()` dòng 186-192): chỉ với run đang ở `{pending, reviewing, need_more}`; set approver_id; audit `billing.approve`.
  - → `rejected` / `need_more` (`transitionRuns()` dòng 194-199): bắt buộc `note`; audit `billing.reject`/`billing.need_more`.
  - phân công người duyệt (dòng 169): set approver_id; audit `billing.assign`.
  - action `billing.publish` được tham chiếu trong lịch sử audit (dòng 99) — phát hành bảng kê tới cư dân.
- Actor: người duyệt (User `is_platform_admin` hoặc có role). Side effect: audit_logs; (khi duyệt statement lẻ: `MyWork.php:338` set `statements.approval_status`).

### B6. Công nợ — `debts.recovery_status` & `debt_reminder_campaigns.status`

| Cột | Giá trị | DB thực tế |
|---|---|---|
| `debts.recovery_status` | new / in_progress / overdue_handling / NULL | new(8), in_progress(8), overdue_handling(8), NULL(4) |
| `debt_reminder_campaigns.status` | running / paused / completed | running(4), paused(1), completed(1) |

Không tìm thấy transition chính quy trong Filament Page (thao tác qua Resource/HQ page nhắc nợ); chuyển ngầm khi chạy chiến dịch.

### B7. Thanh toán / Quỹ

| Cột | Giá trị hợp lệ | DB thực tế | Nơi xử lý |
|---|---|---|---|
| `payments.status` | confirmed (+ pending/…) | confirmed(9) | Tạo khi thu tiền |
| `payment_requests.status` | draft / pending / approved / paid / rejected | approved(1), draft(1), paid(1), pending(1) | `MyWork.php:340` approve→approved / reject→rejected; luồng chi qua approval |
| `cash_vouchers.status` | posted | posted(2) | Ghi sổ phiếu |
| `billing_invoices.status` | issued / partially_paid / paid | paid(5), partially_paid(2), issued(1) | Hóa đơn SaaS |

Actor: kế toán/BQL tài chính; approve chi qua `approval_requests`. Side effect: `fund_transactions`, `payment_allocations`.

### B8. Xe — `vehicles.status`

Nguồn STATUS: `VehicleRequests.php:47`.
| Giá trị | Nhãn |
|---|---|
| `pending` | Chờ duyệt |
| `reviewing` | Đang rà soát |
| `need_more` | Cần bổ sung |
| `active` / `approved` | Đã phê duyệt |
| `revoked` | Đã thu hồi |
| `rejected` | Từ chối |
| `expired` | Hết hạn |

- DB thực tế: active(108).
- Transition (`VehicleRequests.php`, `transitionVehicles()` dòng 138):
  - approve: chỉ từ {pending, reviewing, need_more} → `active`; audit `vehicle.approve`.
  - reject: → `rejected` (bắt buộc note); audit `vehicle.reject`.
  - revoke: từ {active, approved} → `revoked`; audit `vehicle.revoke`.
- Actor: BQL an ninh/thẻ xe.

### B9. Thẻ ra vào — `access_cards.status`

Nguồn: `AccessCards.php`.
| Giá trị | DB thực tế |
|---|---|
| active / revoked | active(115), revoked(5) |

- Transition: cấp thẻ→`active` (dòng 109, audit `card.issue`); thu hồi→`revoked` (dòng 137, audit `card.revoke`); kích hoạt lại→`active` (dòng 144, audit `card.reactivate`).
- `access_logs.status`: granted (DB) — chỉ ghi log, không SoftDeletes.

### B10. Khách & Bưu kiện

| Cột | Giá trị | DB thực tế |
|---|---|---|
| `visitor_registrations.status` | pending / approved / checked_in / checked_out | mỗi loại 1 |
| `visitor_passes.status` | active / used | active(2), used(1) |
| `package_deliveries.status` | notified / received / picked_up | notified(2), received(2), picked_up(1) |

Chuyển ngầm qua thao tác lễ tân (approve khách → tạo `visitor_passes`; nhận/giao bưu kiện).

### B11. Tiện ích & Đặt chỗ — `amenity_bookings.status`

| Giá trị | DB thực tế |
|---|---|
| pending / confirmed / cancelled / completed / rejected | confirmed(2), cancelled(1), completed(1), pending(1), rejected(1) |

- `booking_qr_passes.status`: active / used (active(2), used(1)).
- **Liên quan trực tiếp mobile**: cư dân đặt→pending; BQL duyệt→confirmed (set approved_by_id) → phát QR pass (active) → check-in (used) → completed. Chuyển ngầm, chưa thấy state machine tập trung.

### B12. Nội dung cộng đồng

| Cột | Giá trị | DB thực tế |
|---|---|---|
| `community_posts.status` | published (+ draft/…) | published(3) |
| `knowledge_articles.status` | draft / published | published(33), draft(1) |
| `events.status` | upcoming (+ ongoing/ended) | upcoming(1) |
| `polls.status` | open (+ closed) | open(1) |
| `documents.status` | active | active(12) |

KB article: draft→published qua `SupportCenterController` (published_at) / các Sa page (`AiKnowledgeConfig.php:139` toggle active↔inactive).

### B13. SaaS Subscription / Plan Change / Usage

| Cột | Giá trị | DB thực tế | Nơi xử lý |
|---|---|---|---|
| `tenant_subscriptions.status` | active / trial / pending_renewal / suspended / cancelled | active(4), trial(1), pending_renewal(1), suspended(1) | `TenantSubscriptionController` (renew→active dòng 137; addon cancel→cancelled dòng 108; set status dòng 146) |
| `subscription_contracts.status` | draft / active / near_expiry / expired | active(3), draft(1), near_expiry(1), expired(1) | tính theo hạn hợp đồng |
| `plan_change_requests.status` | pending_approval / processing / completed / rejected | completed(78), pending_approval(27), processing(18), rejected(5) | `PlanChangeRequests` (HQ) |
| `usage_periods`/`usage_records.status` | open / calculating / calculated / locked | — | `UsageMeteringDashboard.php` / `UsageMeteringController`: recalculate→calculating; lock→locked (khóa cả records); unlock→open |
| `quota_alerts.status` | open / resolved / converted_to_addon / converted_to_upgrade | | `QuotaAlertController` |

Actor: SuperAdmin (/sa) & Tenant HQ (/hq). Side effect: `billingAudit()` (trait WritesBillingAudit) → `billing_audit_logs`.

### B14. Integration Center

| Cột | Giá trị | Nơi xử lý (Http Controller Platform/Integration/*) |
|---|---|---|
| `integration_connections.status` | disabled / active / rotated | create→disabled; test→active (dòng 66); disable (74); kill-switch bảo mật→disabled toàn bộ |
| `integration_api_keys.status` | active / revoked / suspended | create→active; revoke; suspend; reactivate→active |
| `webhook_endpoints.status` | pending_verification / active / disabled | create→pending_verification; verify/enable→active; disable |
| `integration_credentials.status` | valid / expiring / rotated | rotate→rotated (**không đọc/không xuất giá trị bí mật**) |
| `integration_events.status` | pending / … | retry queue: skipped / dead_letter |

Actor: SuperAdmin/HQ IT. Side effect: `integration_audit_logs` (trait WritesIntegrationAudit).

### B15. Support Center — `support_tickets.status` + `sla_state`

Nguồn: `SupportTicketController.php`, `SupportTicketQueue.php` (Sa).
| `status` | DB thực tế |
|---|---|
| new / open / in_progress / waiting_customer / resolved / escalated / closed / reopened | open(163), resolved(82), in_progress(39), escalated(29), new(2), waiting_customer(2), closed(1) |

| `sla_state` | DB thực tế |
|---|---|
| within_sla / near_breach / breached / paused_waiting_customer / resolved | within_sla(192), resolved(83), near_breach(38), breached(4), paused_waiting_customer(1) |

- Transition (Controller + Sa Page song song, cùng logic):
  - create→`status=new`, `sla_state=within_sla` (`SupportTicketController.php:44`).
  - escalate→`status=escalated` + tạo `support_escalations`(status=active) (dòng 64-65).
  - resolve/close→`status=closed`, `sla_state=resolved`, set closed_at/resolved_at/csat_score (dòng 73).
  - reopen→`status=reopened`, closed_at=null, reopen_count++ (dòng 81).
- Actor: đội Support SaaS. Side effect: `support_ticket_status_logs`(from_status/to_status/changed_by); `support_audit_logs`.

### B16. Sửa dữ liệu có kiểm soát — `data_correction_requests.status`

Nguồn: `DataCorrectionController.php`.
| Giá trị | DB thực tế |
|---|---|
| pending_approval / approved / rejected / executed / rolled_back | pending_approval(1), approved(1), executed(1) |

- Transition: create→pending_approval (dòng 40); approve→approved (dòng 51, set approver_id/approved_at); reject→rejected (dòng 61); execute→executed + tạo `data_fix_executions`(status=executed) (dòng 82-83); rollback→rolled_back + tạo `data_fix_rollbacks` (dòng 91-92).
- Actor: Support + người duyệt. Side effect: snapshot/diff/execution/rollback tables; "Executed with row-level lock".

### B17. Duyệt chung — `approval_requests.status` / `approval_steps.status` / `ai_requests.status` / `ai_approvals.status`

| Cột | Giá trị | DB thực tế | Nơi |
|---|---|---|---|
| `approval_requests.status` | pending / reviewing / approved / rejected | pending(2), approved(1) | `MyWork.php:337` approve→approved / reject→rejected (set decided_at) |
| `approval_steps.status` | pending / approved | pending(5), approved(4) | Bước trong quy trình duyệt |
| `ai_requests.status` | pending_approval / success / failed | success(4), pending_approval(1), failed(1) | Engine AI |
| `ai_approvals.status` | pending / approved / rejected | pending(3) | `AiApprovals` resource |

`MyWork.php` (dòng 337-340) là **hub duyệt gộp** cho 4 loại: approval / resident / payment / statement — mỗi loại chỉ set `approved`/`rejected` bằng string.

---

## PHẦN C — TỔNG KẾT & CẢNH BÁO CHO MOBILE

1. **Không có state machine tập trung.** Không có package như `spatie/laravel-model-states`. Mọi transition là `->update(['status'=>'...'])` rải trong Filament Pages (`app/Filament/Pages`, `app/Filament/Sa/Pages`, `app/Filament/Hq/Pages`) và `app/Http/Controllers/Platform/**`. Mobile backend **phải tự tái hiện guard** nếu expose các hành động này qua API — hiện guard chỉ nằm ở điều kiện `->visible()` của nút UI.

2. **Chỉ 4 enum PHP**; phần còn lại là string. Nhiều bảng có **nhiều cột status song song** (đặc biệt `residents`: 7 cột). Đừng giả định "một trạng thái/bảng".

3. **Lệch enum ↔ UI cần lưu ý:**
   - `VehicleType` thiếu `ev` (UI có).
   - `ResidentApprovalStatus` không có `reviewing` (UI `AccountApprovalDetail` có).
   - Binding dùng `need_more_info`, còn approval dùng `need_more` — **hai chuỗi khác nhau**.
   - `vehicles.status` dùng cả `active` lẫn `approved` cho cùng nghĩa "đã duyệt".

4. **Nguồn lịch sử tin cậy** là các bảng chuyên biệt, KHÔNG phải `audit_logs` (thưa, 22 dòng):
   - `apartment_status_histories`, `feedback_status_histories`, `support_ticket_status_logs` (from_status/to_status/changed_by).
   - Các domain khác (resident.status, vehicle, notification…) **chỉ ghi audit_logs** khi trang có trait `WritesAudit` → lịch sử không đầy đủ.

5. **Side effect KHÔNG bao gồm push notification/job hàng đợi thật.** `Notification::make()` là toast Filament (UI web). `jobs`/`failed_jobs` rỗng. Delivery ra kênh ngoài chỉ ghi `notification_delivery_logs`/`webhook_delivery_attempts` (giả lập). Mobile cần tự xây lớp push.

6. **audit_logs schema** (do WritesAudit/inline ghi): `tenant_id, building_id, user_id, actor_name, action, subject_type, subject_id, description` + timestamps. `action` là chuỗi dạng `domain.verb` (vd `feedback.assign`, `billing.approve`, `card.revoke`).
