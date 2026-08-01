# X2-BMS · KẾ HOẠCH LỚP API + REALTIME CHO APP BQL MOBILE

> Lập 2026-08-01. Nguồn scope: `handoff/x2bms/_BUSINESS_MAP_20260725/02_APP_BQL_business_map.md`
> (`D:\Code\handoff` là symlink → Google Drive `/My Drive/XHUB/handoff`).
> Trạng thái hiện tại: `grep bql routes/api.php = 0` — **chưa có API app BQL mobile**, chưa
> có broadcast event nào. Đây là **gap tầng lớn**, chủ dự án đã xác nhận nằm trong phạm vi.
>
> Nguyên tắc bất di: **KHÔNG viết lại logic nghiệp vụ đã có ở Web BQL (Filament).** API BQL
> mobile là *lớp trình bày thứ hai* trên cùng Model/Service/Action — đúng như Community đã
> dùng chung `ModerateCommunityPostAction` cho cả app cư dân lẫn web.

---

## 1. Phạm vi theo bản đồ — 6 nhóm / 24 màn / nhiều persona

**Persona (RBAC):** `bql_manager` · `supervisor` · `staff` · `bql_cskh` · `technician` ·
`bql_communication` · `accountant` · `chief_accountant` · `security_guard` ·
`security_supervisor`. Dùng chung nền tảng (DB/API/IAM/AI) với app cư dân nhưng **tách
bottom-nav + RBAC**.

**Bottom nav 5 tab:** Tổng quan · Công việc · Cảnh báo · **Cư dân** · **Cá nhân**
(X2AI = icon nổi, không phải tab). ⚠️ Hai tab **Cư dân** và **Cá nhân** hiện **0 màn**
trong inventory 24 màn — khoảng trống (Q2/Q3).

| Nhóm | Màn (mã — tên) | Persona chính |
|---|---|---|
| **04 Dashboard & Cảnh báo** | QL-DASH-01 Dashboard & ca trực · QL-DASH-02 Dashboard vận hành · QL-DASH-03 Trung tâm cảnh báo · QL-DASH-04 AI ca trực | bql_manager, supervisor, staff |
| **05 Xử lý phản ánh** | QL-FB-01 Hàng đợi · QL-FB-02 Chi tiết sự cố · QL-FB-03 Trả lời & điều phối · QL-FB-04 AI hỗ trợ | bql_cskh, manager, technician |
| **06 Công việc kỹ thuật** | QL-WO-01 Danh sách · QL-WO-02 Chi tiết · QL-WO-03 Checklist · QL-WO-04 Bảo trì/nghiệm thu | technician, supervisor |
| **08 Thông báo & Truyền thông** | QL-NOTI-01 Quản lý · QL-NOTI-02 Soạn · QL-NOTI-03 Phạm vi & lịch · QL-NOTI-04 Hiệu quả | bql_communication, manager |
| **11 Tài chính / Ký số** | QL-FIN-01 Bảng kê chờ duyệt · QL-FIN-02 Chi tiết/ký số · QL-FIN-03 Duyệt chi · QL-FIN-04 Xác nhận thu | accountant, chief_accountant, manager |
| **12 An ninh / Tuần tra / SOS** | QL-SEC-01 Tuần tra · QL-SEC-02 Ghi sự cố · QL-SEC-03 Duyệt khách/xe · QL-SEC-04 SOS | security_guard, supervisor, manager |

**Response envelope chuẩn (map yêu cầu):** mỗi endpoint tách khối `data` · `meta` ·
`permissions` · `actions` · `ai_suggestions` · `audit`, và mang context
tenant/project/building/apartment/role.

---

## 2. Bản đồ endpoint → Model/Service Web BQL đã có (để tái dùng)

> Cột "Nền đã có" trỏ tới code hiện hữu trong `x2bms` (theo `PROGRESS_TRACKER.md`). Nếu
> có Service/Action → API mobile GỌI VÀO, không dựng logic mới. Nếu chỉ có Model/Filament
> Page → cần tách logic ra Service dùng chung trước (khoản nợ ghi ở §5).

| Nhóm | Endpoint (map) | Nền đã có ở Web BQL |
|---|---|---|
| 04 | `GET dashboard` · `GET alerts` · `PATCH alerts/{id}/ack` · `GET tasks/today` | `OperationalDashboard`, `AccessControlDashboard`, model `IocAlert`/`AlertAction`, `MyWork` |
| 05 | `GET feedback` · `GET feedback/{id}` · `PATCH .../assign` · `POST .../reply` · `POST .../convert-to-work-order` | `FeedbackQueue`, model `FeedbackRequest`/`FeedbackComment`/`FeedbackStatusHistory`, `WorkOrderKanban`, `SlaPolicy`/`SlaEvent` |
| 06 | `GET work-orders` · `GET .../{id}` · `PATCH .../status` · `POST .../checklist` · `POST .../photos` · `POST .../sign-off` | `WorkOrders` resource, model `WorkOrder`/`WorkOrderAssignment`/`WorkOrderChecklist(Item)`/`WorkOrderSignature`/`WorkOrderAttachment`, `MaintenancePlan` |
| 08 | `GET/POST notifications` · `GET .../{id}` · `POST .../schedule` · `POST .../approve` · `GET .../metrics` | `NotificationCenter`, model `Notification`/`NotificationAudience`/`NotificationChannel`/`NotificationDeliveryLog`/`NotificationRead` |
| 11 | `GET finance/approvals` · `GET .../invoices/{id}` · `POST .../invoices/{id}/approve` · `POST .../payment-requests/{id}/approve` · `POST .../receipts/{id}/confirm` | ⭐ `StatementApprovalService` (đã có write-path duyệt/phát hành + 8 test), `MyWork::decide()`, model `Statement`/`StatementApproval`, `CashVoucher`/`Receipt`, `ApprovalRequest`/`ApprovalStep` |
| 12 | `GET security/patrols` · `POST .../patrols/{id}/checkpoints` · `POST .../incidents` · `GET .../visitor-passes` · `PATCH .../visitor-passes/{id}` · `POST .../sos/{id}/resolve` | model `PatrolRoute`/`PatrolCheckpoint`/`PatrolSession`, `SecurityIncident`, `VisitorRegistration`/`VisitorPass`, `SosAlert` |

**Điểm mạnh:** nhóm 11 (tài chính) đã có `StatementApprovalService` — chỗ DUY NHẤT set
`published`, chặn tự duyệt, guard trạng thái. API mobile duyệt bảng kê chỉ cần gọi vào đây.

---

## 3. Kế hoạch giai đoạn (docs-first, đúng nếp x2bms)

### GĐ0 — Chốt scope + quyết định nền (CHẶN, cần chủ dự án)
Không viết route trước khi chốt 4 điều:
1. **Q1 — Ánh xạ 5 tab → 24 màn** (tab nào mở nhóm nào). Không chốt = build lệch điều hướng.
2. **Q2/Q3 — 2 tab "Cư dân" & "Cá nhân" trống màn.** Cần định nghĩa: Cư dân = danh bạ/tra
   cứu theo căn (đọc); Cá nhân = hồ sơ nhân sự + ca trực + thiết bị + theme.
3. **Repo:** app BQL là **repo Flutter MỚI** hay thêm target vào `x2mobile` (hiện là app
   *cư dân*)? Đề xuất: **repo/app riêng** (`x2bms_ops` hoặc target riêng trong monorepo
   Dart) — khác bottom-nav, khác RBAC, khác vòng đời phát hành store.
4. **Q7 — IAM:** `staff_users` (BQL) ↔ `GlobalUserAccount` + `MobileDevice` thống nhất thế
   nào (liên quan [[x2bms-account-activation-decision]]).

### GĐ1 — Nền API BQL (`/api/bql/*`)  ← nền tảng, làm trước mọi domain
- Prefix `/api/v1/bql/*`, middleware `auth:sanctum` + `ability:staff` + `throttle:api`.
  (Ability `staff` đã tồn tại — verify được bằng `nv1@x2bms.vn`.)
- **Context nhân sự đa dự án:** khác `X-Context-Id` của cư dân. Nhân sự gán theo
  `EmployeeProjectAssignment` → viết `BqlContextService` (song song `ResidentContextService`)
  giới hạn dữ liệu theo tòa/dự án được phân công. **Bẫy đã biết:** cư dân từng rò ngữ
  cảnh đa dự án khi thiếu header (tracker mobile §14) — làm chặt ngay từ đầu ở service dùng chung.
- **RBAC theo role trong staff:** `ability:staff` mới chỉ là cửa ngoài; phân quyền màn/hành
  động theo 10 persona qua policy (dùng lại `PermissionGroup`/`PermissionMatrix` của HQ-04).
- Response envelope chuẩn (§1) qua một `BqlResource` base + middleware gắn `permissions`/`actions`.
- **1 endpoint mẫu đầu tiên** `GET /api/v1/bql/dashboard` + `GET tasks/today` (đọc, ít rủi
  ro) để đóng khung envelope/context/RBAC trước khi nhân rộng.

### GĐ2 — Realtime (Reverb) + push
Map mô tả realtime ở mức nghiệp vụ, **KHÔNG có tên event/channel kỹ thuật** → ta thiết kế:
- **Laravel Reverb** (WebSocket, không phí, hợp Laravel 13 hơn Pusher). Config
  `broadcasting` + `reverb` (hiện chưa có — cần dựng).
- **Kênh:** `private-project.{projectId}` (nhân sự join theo `EmployeeProjectAssignment`);
  cân nhắc `private-building.{buildingId}` cho lọc mịn hơn.
- **Event đề xuất** (map chỉ mô tả luồng, tên do ta đặt):
  `SosTriggered` · `SosResolved` · `FeedbackSlaBreached` · `WorkOrderAssigned` ·
  `WorkOrderOverdue` · `ApprovalRequested` · `AlertRaised` (kênh hội tụ QL-DASH-03) ·
  `NotificationDelivered` (cập nhật QL-NOTI-04).
- **Bảng `alerts` hội tụ:** feedback quá SLA (05) + WO quá hạn (06) + công nợ (11) + sự
  cố/SOS (12) đẩy chung vào `alerts` → hiển thị realtime QL-DASH-03. Cần model `IocAlert`
  (đã có) làm nền + observer/job đẩy sự kiện.
- **Push FCM** cho SOS/approval khi app nền (tái dùng `MobileDevice` đã có token).

### GĐ3 → GĐ8 — Từng domain, theo thứ tự giá trị
1. **An ninh/SOS/Tuần tra (12)** — giá trị realtime cao nhất, đúng "việc ngoài hiện
   trường"; SOS là luồng khẩn.
2. **Phản ánh (05) + Công việc (06)** — vòng đời phản ánh→WO→nghiệm thu, dùng nhiều ảnh
   hiện trường (đã có `pickImagesCompressed` phía app cư dân để tham chiếu).
3. **Tài chính/Ký số (11)** — tái dùng `StatementApprovalService`; **chặn Q5** (ngưỡng số
   tiền theo cấp duyệt chưa định lượng).
4. **Dashboard/Cảnh báo (04)** — hoàn thiện sau khi có nguồn dữ liệu từ các domain trên.
5. **Thông báo (08)** — soạn/lịch/duyệt/metrics; **chặn Q9** (tiêu chí "important/emergency"
   bắt buộc duyệt) + **Q10** (chi phí kênh SMS/Zalo).

Mỗi domain: viết Service dùng chung (nếu Web BQL chỉ có Filament Page) → route + Resource
→ policy per-role → test HTTP thật → cập nhật tracker.

---

## 4. 12 điểm CHỜ CHỦ DỰ ÁN CHỐT (từ map, mục 12)

| Mã | Nội dung | Mức chặn |
|---|---|---|
| **Q1** | Ánh xạ 5 bottom-tab → 24 màn | 🔴 chặn GĐ0 |
| **Q2** | Tab "Cư dân" chưa có màn (danh bạ/tra cứu theo căn) | 🔴 chặn GĐ0 |
| **Q3** | Tab "Cá nhân" chưa có màn (hồ sơ NV/ca trực/thiết bị/theme) | 🔴 chặn GĐ0 |
| **Q4** | Màn Bàn giao ca thiếu (DB có `shift_handover_logs`/`duty_rosters`, không UI) | 🟠 |
| **Q5** | Ngưỡng số tiền cho từng cấp duyệt/ký số | 🔴 chặn GĐ3 domain 11 |
| **Q6** | Quyền "mở cửa/thang từ xa" — ai được (rủi ro an ninh) | 🟠 domain 12 |
| **Q7** | IAM: `staff_users` ↔ `GlobalUserAccount` + `MobileDevice` | 🔴 chặn GĐ1 |
| **Q8** | AI shift summary xuất hiện 2 nơi (QL-DASH-04 + QL-FB-04) → 1 nguồn | 🟡 |
| **Q9** | Policy duyệt thông báo (tiêu chí important/emergency) | 🟠 domain 08 |
| **Q10** | Chi phí kênh SMS/Zalo + hạn mức ngân sách truyền thông | 🟠 domain 08 |
| **Q11** | Kho vật tư/tồn kho cho work order (chưa có bảng inventory) | 🟡 domain 06 |
| **Q12** | Lỗi enum `work_order_status`: `accepted` xuất hiện 2 lần | 🟢 làm sạch |

**Nguồn đặc tả event/channel thực** (map trỏ, chưa đọc trong plan này):
`API_IMPACT_REVIEW.md` · `WORKFLOW_MAP.md` · `CROSS_PACKAGE_DEPENDENCY.md` (cùng thư mục handoff).

---

## 5. Nợ kỹ thuật cần lường trước

- Nhiều nghiệp vụ BQL hiện **chỉ nằm trong Filament Page/Action**, chưa tách Service dùng
  chung → API mobile sẽ ép phải refactor-để-tái-dùng (không được copy logic). Ưu tiên tách
  những chỗ Filament Page ↔ mobile cùng làm một việc.
- **`ability` = OR:** route cần mở cho nhiều persona phải liệt kê đủ (`ability:staff,manager`),
  giống bài học route `moderate` của Community.
- **Realtime chưa có hạ tầng:** Reverb cần thêm supervisor/process trên VPS CloudPanel
  (khác PHP-FPM) — hạ tầng deploy phải chốt trước khi bật event thật.
- **Verify độc lập:** bám chuẩn "chỉ ✅ khi có test HTTP/Livewire thật" của tracker.

---

## 6. Bước kế tiếp đề xuất

1. Chủ dự án chốt **Q1, Q2, Q3, Q7, Q5** (5 điểm 🔴) — ít nhất Q1/Q7 để mở khoá GĐ0–GĐ1.
2. Chốt **repo/app riêng vs target trong `x2mobile`**.
3. Sau khi chốt: dựng GĐ1 (nền `/api/v1/bql/*` + `BqlContextService` + envelope) với
   endpoint mẫu `GET dashboard`/`tasks/today`, verify HTTP bằng `nv1@x2bms.vn`.
