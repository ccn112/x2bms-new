# X2-BMS — Review & Kế hoạch luồng Ban Quản Lý (BQL)

> Lập 2026-07-01. BQL = vận hành cấp **dự án** (RBAC tầng 3: platform → công ty → **BQL/project**).
> Repo này = **web Filament `/admin`**. Luồng "App BQL" (React Native, QL-*) là track riêng
> ngoài repo — cùng nghiệp vụ; web `/admin` phủ bản desktop, API cho app làm sau.
> **Data cho MỌI luồng BQL đã sẵn** (Tier 2/3 đã xong) → giờ chỉ dựng UX bespoke.

## ✅ CHECKPOINT 2026-07-01 — tiến độ & điểm resume

| Bước | Trạng thái | Màn / file |
|---|---|---|
| **BQL-1 Phản ánh** | ✅ DONE | `FeedbackQueue` (`/admin/feedback/queue`) + `feedback-detail` |
| **BQL-2 Công việc Kanban** | ✅ DONE | `WorkOrderKanban` (`/admin/work-orders/kanban`) + `work-order-detail` |
| **BQL-3 Thông báo** | ✅ DONE | `NotificationCenter` (`/admin/notifications/center`) + `notification-detail` |
| **BQL-4 Tài chính** | ▶ **RESUME HERE** | Công nợ WEB-FORM-08 + duyệt chi (`payment_requests`/`approval_requests`+steps) + biên lai (`receipts`) — reuse approval pattern |
| BQL-5 Dashboard + ca trực + cảnh báo | ⏳ | Nâng cấp `OperationalDashboard` (shifts/duty_rosters + ioc_alerts/alert_actions) |
| BQL-6 An ninh / SOS | ⏳ | patrol/security_incidents/sos_alerts/visitor/access — trung tâm an ninh |

**Nhắc khi resume (bẫy đã trả giá):**
- Cột Filament closure param **PHẢI tên `$state`** (đặt `$s` → 500 "unresolvable"). Đã dính 3 lần.
- KHÔNG đặt method Page tên trùng Livewire (`transition`/`mount`/`render`/`dispatch`…) → Fatal "must be public". BQL-1 đã đổi `transition`→`changeStatus`.
- `WorkOrder.category` là CỘT string (chỉ có quan hệ `department()`) — đừng eager-load `category`.
- Pattern tái dùng đã có: queue+KPI (`StatementApprovalQueue`/`FeedbackQueue`), Kanban DnD (`WorkOrderKanban`), detail modal timeline (`feedback-detail`/`work-order-detail`/`notification-detail`), `mountAction(name,{id})` cho action theo thẻ, scope `CurrentContext::buildingIds`, audit `WritesAudit`.
- Verify mỗi màn: `php -l` + `view:cache` + render HTTP 200 (headless kernel, xem scratchpad) + script logic; rồi ghi `DEV_JOURNAL.md`.

---

## 1. Rà soát: 6 nhóm nghiệp vụ BQL (app QL-* ↔ web WEB-*)

| # | Nhóm | App BQL (mobile) | Web Admin | Data | UI /admin hiện có |
|---|---|---|---|---|---|
| 1 | **Điều hành & Ca trực & Cảnh báo** | QL-DASH-01..04 | WEB-01 | shifts/duty_rosters, ioc_alerts(+alert_actions) ✅ | OperationalDashboard (cơ bản) ⚠️ cần bổ sung ca trực/cảnh báo |
| 2 | **Xử lý phản ánh** | QL-FB-01..04 (queue→chi tiết→điều phối→AI) | (WEB feedback) | feedback_requests +comments/attachments/assignments/status_histories +SLA ✅ | ❌ chưa có |
| 3 | **Công việc kỹ thuật** | QL-WO-01..04 (list→chi tiết→checklist→nghiệm thu) | WEB-05 Kanban | work_orders +assignments/checklists/items/attachments/signatures ✅ | ❌ chưa có |
| 4 | **Thông báo & Truyền thông** | QL-NOTI-01..04 (quản lý→soạn→phạm vi/lịch→hiệu quả) | WEB-04 + WEB-UX-08 | notifications +audiences/channels/reads/delivery_logs (3 lớp) ✅ | ❌ chưa có |
| 5 | **Tài chính / Duyệt / Ký số** | QL-FIN-01..04 (bảng kê→duyệt ký→duyệt chi→biên lai) | WEB-03 | billing_runs, statements, payment_requests, approval_requests, cash_vouchers, receipts ✅ | ✅ StatementApprovalQueue (duyệt bảng kê); ❌ duyệt chi/công nợ/biên lai |
| 6 | **An ninh / Tuần tra / SOS** | QL-SEC-01..04 (checkpoint→sự cố→duyệt khách→SOS) | (WEB security) | patrol_routes/checkpoints/sessions, security_incidents, sos_alerts, visitor_registrations/passes, access_logs ✅ | ❌ chưa có |

**Kết luận review:** BQL có 6 luồng lõi; **1 phần (duyệt bảng kê) + dashboard cơ bản đã có**, còn **5 luồng lớn chưa có UI** dù data đã đủ. Đây là phần giá trị cao nhất để làm tiếp vì đúng người dùng vận hành hằng ngày.

## 2. Ràng buộc chung (áp cho mọi màn BQL)
- **Scope cấp dự án**: mọi query lọc theo `CurrentContext::buildingIds()/projectId()` + `accessibleProjectIds()` (như `StatementApprovalQueue`). BQL chỉ thấy dự án mình.
- **Tái dùng (chuẩn hoá 1 lần):** base **Approval queue** (tách từ StatementApprovalQueue) · **Kanban board** · **Detail + timeline pane** (comment/attachment/status history/audit) · **chart SVG** · X2AI FAB đẩy ngữ cảnh màn.
- Mọi hành động: `requiresConfirmation`/approval + ghi `audit_logs` (trait `WritesAudit`).

## 3. Kế hoạch build BQL (thứ tự đề xuất)

### BQL-1 — Hàng đợi & xử lý phản ánh ⭐ (giá trị cao, chưa có)
- **Queue** (QL-FB-01): bảng phản ánh theo dự án + filter (danh mục/trạng thái/ưu tiên/SLA) + KPI (chờ/đang xử lý/quá hạn) + donut theo danh mục.
- **Chi tiết & điều phối** (QL-FB-02/03): timeline comment/attachment, đổi trạng thái, **giao việc** (assignment→ tạo work_order), SLA đếm ngược, đánh giá sau xử lý (service_evaluations).
- X2AI hỗ trợ (QL-FB-04): gợi ý phân loại/soạn trả lời (đẩy ngữ cảnh vào FAB).

### BQL-2 — Công việc kỹ thuật (Kanban)
- **Kanban** (WEB-05/QL-WO-01): cột theo trạng thái (pending/in_progress/done/overdue), kéo-thả đổi trạng thái, filter theo tổ/đội/loại.
- **Chi tiết** (QL-WO-02/03/04): checklist + tick mục, đính kèm ảnh before/after, chữ ký nghiệm thu, chi phí; liên kết ngược phản ánh.

### BQL-3 — Thông báo & Truyền thông
- **Center + soạn** (WEB-04/WEB-UX-08/QL-NOTI): danh sách + soạn (RichEditor) + **chọn phạm vi 3 lớp** (audiences: dự án/tòa/căn/vai trò) + lịch gửi + kênh (app/email/sms) + **hiệu quả** (recipient/read_count, delivery_logs).

### BQL-4 — Tài chính vận hành (mở rộng phần đã có)
- **Công nợ & thanh toán** (WEB-FORM-08): KPI + công nợ theo căn + kênh thu + ghi nhận thanh toán + biên lai (receipts) + nhắc nợ.
- **Duyệt chi / đề nghị thanh toán** (QL-FIN-03): reuse approval pattern trên `payment_requests`/`approval_requests` (+`approval_steps` nhiều bước) → chi quỹ (`cash_vouchers`/`fund_transactions`).
- (Duyệt bảng kê QL-FIN-01/02 = `StatementApprovalQueue` đã có; bổ sung ký số nếu cần.)

### BQL-5 — Điều hành & Ca trực & Cảnh báo (nâng cấp dashboard)
- Bổ sung vào OperationalDashboard: **lịch ca trực** (shifts/duty_rosters), **trung tâm cảnh báo** (ioc_alerts + alert_actions: ack/dispatch/resolve), KPI vận hành realtime.

### BQL-6 — An ninh / Tuần tra / SOS
- **Trung tâm an ninh**: tuần tra (routes/checkpoints/sessions), sự cố (security_incidents), **SOS** (sos_alerts: triggered→ack→resolve), duyệt khách (visitor_registrations→passes), nhật ký ra/vào (access_logs), camera/access_devices.

## 4. Ghi chú
- Mỗi màn: đọc contract + ảnh QL-*/WEB-* trước khi code; verify render 200 + hành động thật; cập nhật `docs/DEV_JOURNAL.md`.
- App BQL (RN) = deliverable riêng; nếu cần, mở track **API** (route tiếng Anh) tái dùng cùng service/policy với web.
- Trùng lắp với `ADMIN_UI_BUILD_PLAN.md`: BQL-1..6 ≈ Phase 1–3 ở đó, nhưng gom theo persona BQL để làm gọn 1 mạch.
