# Kế hoạch thực thi phân hệ BQL (trên panel /admin)

_Lập 2026-07-02. Bối cảnh: đã tách 3 panel (/sa /hq /admin), shell đã chốt (title header, search giữa, bỏ subtitle, 6-card/hàng). `/admin` = workspace BQL, scope project+building qua `CurrentContext`._

## Nguyên tắc nền (áp cho mọi slice)
- **Model đã có sẵn** — build BQL chủ yếu là dựng **page UI + widget + table/action + workflow** trên model có sẵn (xem cột "Model" mỗi module), KHÔNG dựng lại schema. Chỉ thêm migration khi thiếu cột/bảng phụ.
- Mọi page là custom Filament `Page` (`app/Filament/Pages`) + blade trong `resources/views/filament/pages`, dùng `x-x2.*` (kpi-row 6 cột, kpi-card, section-card, status-badge, data-table, action-bar chỉ-actions).
- Scope bắt buộc: `tenant_id` + `project_id`, lọc `whereIn(building_id, $ctx->buildingIds())`. Không màn tĩnh — số liệu từ DB/seed.
- Action nhạy cảm: gọi policy tường minh, `requiresConfirmation`, lý do bắt buộc (approve/reject), ghi `AuditLog`.
- AI: chỉ đẩy context vào FAB chung (`ProvidesAiContext::shareAiContext`), không card AI inline.
- Realtime MVP: polling nhẹ (`wire:poll`) cho dashboard/inbox; WebSocket để giai đoạn Hardening.
- Empty-state chủ động + seed mẫu cho mọi tab.

---

## Trạng thái & lộ trình theo module

| Module | Màn thiết kế | Đã có | Cần build | Model backing (đã có) |
|---|---|---|---|---|
| **00 Foundation** | 10 | Dashboard (OperationalDashboard) | My Work inbox, Audit viewer, Permission state, Project settings preview | AuditLog, User, Project, Building |
| **01 Cư dân/Căn hộ** | 10 | Apartment dir/profile, Resident dir/detail/create, Binding queue, Approval queue | Apartment tree-view, Household relationship, Move-in/out history, Resident timeline, Data-quality dashboard | Apartment, Resident, ResidentApartmentRelation, ResidentUnitBinding |
| **02 Duyệt/Xe/Thẻ** | 10 | Approval queue, Vehicles & cards | Đối chiếu hồ sơ, Duyệt xe, Chi tiết thẻ ra vào, Cấp thẻ/quyền, Sinh trắc & QR, Hàng đợi bổ sung, Rule duyệt, Dashboard access | AccessCard, AccessDevice, AccessLog, Vehicle, VisitorPass |
| **03 Phí/Bảng kê/Công nợ** | 10 | Statement approval | Tổng quan phí, Biểu phí config, Tạo kỳ phí, Chạy phí/bảng kê, Chi tiết bảng kê căn hộ, Công nợ theo căn, Miễn giảm, Nhắc nợ/chiến dịch, Báo cáo kỳ phí | FeeType, FeeRate, FeeFormula(+Version), FeeScopeAssignment, BillingPeriod, BillingRun(+Item), Statement(+Line/Approval/PublishLog), Debt, BillingAdjustment, DebtReminderCampaign(+Log) |
| **04 Thanh toán/Đối soát** | 10 | — | Dashboard thu ngân, Hàng đợi GD, Thu tại quầy QR/tiền mặt, Chi tiết GD, Import sao kê, Đối soát/matching, Phân bổ công nợ, Biên lai/hoá đơn, Hoàn tiền, Báo cáo đối soát | Payment, PaymentAllocation, PaymentRequest, CashFund, CashTransaction, CashVoucher, Receipt, BankAccount, BankStatementImport, BankTransaction, ReconciliationMatch, BillingReconciliation, QrPaymentToken |
| **05 Phản ánh/SLA/WorkOrder** | 10 | Feedback queue, WorkOrder kanban | Chi tiết phản ánh, Tạo phản ánh, Dashboard SLA/KPI, Quy trình SLA, Giao việc, Lịch tiến độ, Nghiệm thu, Đánh giá cư dân, Báo cáo | FeedbackRequest(+Assignment/Comment/Attachment/StatusHistory), SlaPolicy/SlaEvent, WorkOrder, ServiceEvaluation |
| **06 Vận hành/Bảo trì/Tài sản** | 10 | (libraries ở /sa) | Dashboard vận hành, Bảng bảo trì, Lịch định kỳ, Danh mục thiết bị, Chi tiết thiết bị, Tạo WO bảo trì, Nghiệm thu, Chi tiết nhà thầu, Giao ban tồn đọng | Asset, AssetCategory, MaintenancePlan, WorkOrder, Contractor, Contract, Meter |
| **07 Truyền thông/Form/Khảo sát** | 10 | Notification center | Dashboard truyền thông, Trung tâm chiến dịch, Chi tiết duyệt gửi, Form designer, Hộp thư biểu mẫu, Khảo sát/bình chọn, Kết quả, Kiểm duyệt cộng đồng, Chi tiết bài báo cáo, Phân tích | DynamicForm, FormField, FormSubmission, Poll, CommunityPost, Event |
| **08 An ninh/Khách/SOS** | 10 | — | Dashboard an ninh, Quản lý khách, Chi tiết yêu cầu khách, Check-in QR/kiosk, Bảng tuần tra, Chi tiết ca, Bãi xe/thẻ, Giám sát SOS, Chi tiết SOS, Bàn giao ca | VisitorRegistration, VisitorPass, PatrolRoute/Checkpoint/Session, DutyRoster, Shift, SecurityIncident, SosAlert, EmergencyAlert, Camera, AccessLog |
| **09 Báo cáo/Duyệt/Audit** | 10 | — (AI ở /hq) | Dashboard báo cáo, Trung tâm phê duyệt, Chi tiết yêu cầu duyệt, Nhật ký audit, Chi tiết bản ghi audit, Báo cáo tài chính, Báo cáo vận hành/SLA, Export center | ApprovalRequest, AuditLog, ExportJob + đọc lại số liệu 03/04/05 |

**Độ phủ hiện tại ≈ 13/99 màn.** Model backing gần như đầy đủ cho cả 10 module.

---

## Thứ tự thực thi (đã chốt: dependency 01→…)

### Slice 0 — Hoàn tất Foundation (BQL-00)  ✅ DONE (2026-07-02)
- ✅ My Work & Approval Inbox (`/admin/my-work`): 5 tabs có count (Việc của tôi/Chờ tôi duyệt/Thông báo/Cảnh báo SLA/Đã xử lý), 3 card ưu tiên, filter panel, bảng tổng hợp đa nguồn (ApprovalRequest, Statement, ResidentApprovalRequest, PaymentRequest, WorkOrder, FeedbackRequest, SlaEvent, IocAlert, AuditLog) + action Duyệt/Từ chối/Mở ghi audit. Render OK, dữ liệu thật.
- ✅ Audit Log Viewer (`/admin/audit-logs`): 4 KPI + bảng AuditLog scope tenant+building, filter action/tòa/ngày, modal chi tiết. (AuditLog model thêm quan hệ user()/building().)
- ✅ Permission / Invalid-context state (`/admin/access-denied`, ẩn khỏi nav): shield + lý do + nút chọn lại context/về dashboard + panel thông tin user.
- ✅ Project settings preview (`/admin/project-settings`): banner kế thừa + 6 nhóm cấu hình (badge nguồn/override) + cột phải tổng quan/donut/người cập nhật.
- **Còn nợ (hardening sau):** wiring guard `EnsureProjectContext` redirect → `/admin/access-denied`; before/after + ip/user_agent/risk cho audit (cần thêm cột); left-filter panel My Work bổ sung Tòa/Trạng thái/Hạn/Giao bởi.

### Slice 1 — BQL-01 hoàn thiện (5 màn)  ✅ DONE (2026-07-02)
- ✅ Cây căn hộ (`/admin/apartments/tree`): tree Tòa→Tầng (đếm căn) + grid căn hộ theo scope + KPI.
- ✅ Quan hệ hộ gia đình (`/admin/households`): card theo căn, thành viên + vai trò + chủ hộ, lọc vai trò/tìm kiếm.
- ✅ Lịch sử chuyển đến/đi (`/admin/move-history`): gộp ResidentApartmentRelation (đến) + ApartmentStatusHistory (đổi TT), KPI + bảng.
- ✅ Dòng thời gian cư dân (`/admin/resident-timeline`): gộp tạo hồ sơ + gắn căn + phản ánh, timeline theo ngày, tìm theo cư dân.
- ✅ Chất lượng dữ liệu (`/admin/residents/data-quality`): KPI hoàn thiện/thiếu/trùng, breakdown bars, bảng bản ghi cần xử lý (tag lỗi). Tất cả render OK, dữ liệu thật.

### Slice 2 — BQL-02 (Access/Vehicle/Card) — 🟡 ĐANG LÀM (2026-07-02)
Nguyên tắc: slot ảnh MATCH → bám ảnh; slot lệch/thiếu → theo UI_SCREEN_CONTRACT. Nav group mới **An ninh & Kiểm soát**.
- ✅ Tổng quan kiểm soát (`/admin/access`): 6 KPI (sự kiện hôm nay, thẻ hoạt động/sắp hết hạn, xe, chờ duyệt, thiết bị online) + bảng sự kiện ra/vào + thẻ sắp hết hạn. (dashboard theo text, vì ảnh 02-10 thật là resident profile)
- ✅ Duyệt đăng ký xe (`/admin/access/vehicle-requests`, bám 02-04): 5 KPI + Filament table + approve/reject/revoke + bulk, audit. (seed vehicles đều active, chưa có type)
- ✅ Thẻ & quyền ra vào (`/admin/access/cards`, bám 02-06): 5 KPI + table + Cấp thẻ mới (form) + thu hồi/kích hoạt lại, audit.
- ✅ Hồ sơ truy cập cư dân (`/admin/access/resident-profile`, bám 02-10): resident picker + 4 summary + bảng xe + thẻ + hoạt động ra/vào + cảnh báo. Live.
- ✅ Chi tiết duyệt tài khoản (`/admin/residents/approvals/{id}`, bám 02-02): summary + bảng đối chiếu khai báo↔hệ thống (khớp/chênh/chưa có) + panel Phê duyệt/Bổ sung/Từ chối (ghi audit). Link từ ResidentApprovalQueue.
- **BQL-02 coi như xong luồng chính.** Còn tuỳ chọn: Quyền tài khoản & thiết bị (02-03 ≈ Hồ sơ cá nhân, ít dữ liệu), Danh sách yêu cầu truy cập (02-08, trùng các queue đã có).
- Lưu ý enum: nhiều field (Vehicle.type/status, AccessCard.type/status, Resident/ApprovalRequest.status/role) là **BackedEnum cast** → luôn `enumVal()` về scalar trước khi dùng làm key/ so sánh trong PHP/blade.

### Slice 3 — BQL-03 Tài chính-Phí (9 màn) ← giá trị nghiệp vụ cao nhất
Tổng quan phí/công nợ → Biểu phí (FeeType/FeeRate/FeeFormula) → Tạo kỳ phí (BillingPeriod) → Chạy phí/bảng kê (BillingRun→Statement) → Chi tiết bảng kê căn hộ → Duyệt phát hành (đã có, nối tiếp) → Công nợ theo căn (Debt) → Miễn giảm (BillingAdjustment) → Nhắc nợ/chiến dịch (DebtReminderCampaign) → Báo cáo kỳ phí.

### Slice 4 — BQL-04 Thanh toán/Đối soát (10 màn)
Dashboard thu ngân → Hàng đợi GD → Thu tại quầy (QR/tiền mặt) → Chi tiết GD → Import sao kê (BankStatementImport) → Đối soát/matching (ReconciliationMatch) → Phân bổ công nợ (PaymentAllocation) → Biên lai (Receipt) → Hoàn tiền → Báo cáo đối soát.

### Slice 5 — BQL-05 Phản ánh/SLA/WorkOrder (8 màn)
### Slice 6 — BQL-06 Vận hành/Bảo trì/Tài sản (9 màn)
### Slice 7 — BQL-07 Truyền thông/Form/Khảo sát (9 màn)
### Slice 8 — BQL-08 An ninh/Khách/SOS (10 màn)
### Slice 9 — BQL-09 Báo cáo/Duyệt/Audit (8 màn)
### Slice 10 — Hardening: policy coverage, realtime WS, read-model/KPI snapshot cho dashboard, seed refresh, test acceptance.

## Cách làm mỗi màn (checklist)
1. Đọc ảnh UI + UI_SCREEN_CONTRACT + WEB_INTERACTION_SPEC + WORKFLOW_STATE_MACHINE của batch tương ứng.
2. Tạo `Page` + `getViewData()` scope project/building, KPI dùng `<x-x2.kpi-row :cols="N">`.
3. Bảng: search/filter/sort/pagination/row+bulk action.
4. Workflow action + policy + audit + notification.
5. Empty-state + kiểm tra seed có dữ liệu; bổ sung seed nếu trống.
6. Đẩy AI context vào FAB nếu màn "có AI".
7. Route render 200; đối chiếu layout với ảnh.
