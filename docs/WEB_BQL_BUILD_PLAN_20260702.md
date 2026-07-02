# Web BQL — Kế hoạch triển khai (tổng hợp từ handoff FULL 00–09 + đối chiếu master)

_Ngày lập: 2026-07-02 · Nguồn: `X2_BMS_WEB_BQL_CLAUDE_CODE_HANDOFF_20260702_FULL_00_09` đối chiếu `X2_BMS_MASTER_HANDOFF_20260628` và mã nguồn hiện tại `x2bms`._

---

> **CẬP NHẬT 2026-07-02 (sau khi tách panel):** Không dựng panel `/bql` riêng. `/admin` CHÍNH LÀ workspace BQL (`CurrentContext` mặc định `workspace=bql`, đã scope project+building). Đã **tách 3 tầng thành 3 panel**: `/sa` (Platform, 35 page), `/hq` (Công ty, +4 page AI), `/admin` (BQL, 13 page thuần). Mục tiêu build 99 màn BQL từ đây gắn lên `/admin`.

## 1. Web BQL là gì và nằm ở đâu trong kiến trúc

Mô hình 3 tầng = 3 panel Filament (đã tách xong):

```
SuperAdmin / Platform   → panel /sa     (EnsurePlatformAdmin, 35 page platform)
Tenant / Công ty HQ     → panel /hq     (EnsureHqAccess, 55 page: HQ-01..05 + 4 AI)
BQL / Vận hành dự án    → panel /admin   (workspace=bql mặc định, 13 page BQL, project+building scope)
+ /fila (stock CRUD Resources, dùng dev, chưa tách tầng)
```

Workspace switcher trên header đổi tầng: bql→/admin, hq→/hq, superadmin→/sa (route `/context/workspace/{key}`, audited).

- **Web BQL = workspace vận hành cấp `project_id` + `building_id`.** Kế thừa cấu hình từ HQ/SuperAdmin, thao tác trên dữ liệu vận hành hằng ngày.
- Handoff định nghĩa **99 màn** trên **10 module** (BQL-00 → BQL-09), mỗi module có 9–10 ảnh UI là source-of-truth.
- Handoff **chỉ định rõ panel riêng `/bql`** (`BqlPanelProvider`, `app/Filament/Bql/{Resources,Pages,Widgets}`), scope bắt buộc qua `EnsureProjectContext` + `CurrentProjectScope`/`CurrentBuildingScope`.

### Đối chiếu với master
- Master `MODULE_BUILD_ORDER` Phase 3 (App BQL MVP) + Phase 4 (Web Admin) + Phase 5 (Web Action) chính là nội dung 10 module BQL này, nhưng handoff BQL chi tiết hơn tới từng màn + hợp đồng UI/API/realtime.
- Entity nền (Tenant/Project/Building/Floor/Apartment/Resident/RBAC) master Phase 1 — **đã có model + resource** trong repo.

---

## 2. Hiện trạng mã nguồn (điểm mạnh tận dụng được)

| Hạng mục | Trạng thái |
|---|---|
| Laravel 13 + Filament 5 + PHP 8.4 | ✅ đúng stack handoff |
| Panel `/admin` (themed) | ✅ 52 custom page — **~20 page thuộc nghiệp vụ BQL** |
| Panel `/fila` (stock CRUD) | ✅ ~120 Resource domain (Apartments, Residents, Billing*, WorkOrders, Assets, Contractors, Sos*, Patrol*, …) |
| Panel `/hq` | ✅ hoàn tất |
| Shell dùng chung | ✅ `x-x2.*` components + render hooks: `topbar-start` (title lên header + search), `header-cluster` (context selector), `ai-fab` |
| `CurrentContext` | ✅ đã có (tenant + project) — cần bổ sung tầng **building scope** cho BQL |
| Theme navy/gold, Inter/Manrope | ✅ `resources/css/filament/admin/theme.css` |

**Kết luận:** không dựng từ số 0. Model + resource domain đã có ở `/fila`; nhiều page nghiệp vụ đã có ở `/admin`. Việc chính là **gom về một panel `/bql` có scope dự án/tòa, dựng đúng layout 99 màn, nối dữ liệu thật**.

---

## 3. Khung giao diện admin — chốt theo yêu cầu (áp cho cả /bql, /admin, /hq)

Đây là các thay đổi shell dùng chung, làm **một lần** rồi mọi panel hưởng:

1. **Tiêu đề màn nằm trên header** (không lặp ở body).
   - Đã có cơ chế trong `topbar-start.blade.php` (JS bơm `.fi-header-heading` lên `#x2-page-title`, ẩn header trong content). Giữ, chuẩn hoá cho panel /bql.
2. **Ô tìm kiếm toàn cục căn GIỮA header** (hiện đang nằm bên trái cạnh title).
   - Tách search khỏi cụm trái; đặt bằng `absolute left-1/2 -translate-x-1/2` trong topbar để căn giữa tuyệt đối, không lệch khi title dài/ngắn. Cụm trái chỉ còn hamburger + title; cụm phải là context/notify/avatar.
3. **Bỏ subtitle** ở mọi page shell để nhường không gian nội dung (dù `UI_SCREEN_CONTRACT` BQL-00 có ghi subtitle — ưu tiên quyết định này của anh; thông tin context/ngày/role chuyển vào chip nhỏ trên header cluster nếu cần).
4. **Card số liệu thống kê: giữ đúng số cột theo thiết kế, KHÔNG tự co về 2–3.**
   - Bản thiết kế 6 card/hàng ⇒ dùng lưới cố định tới `xl:grid-cols-6` (không dừng ở `md:grid-cols-3`).
   - Chuẩn hoá 1 helper lưới KPI dùng chung: `grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4` (giữ 6 ở desktop, chỉ xuống hàng ở màn hẹp thật sự). Với hàng thiết kế 5/4 card thì đặt tương ứng `xl:grid-cols-5` / `xl:grid-cols-4`.
   - Tái dùng `<x-x2.kpi-card>` sẵn có cho từng ô.

> Ghi chú: 3 sửa đổi shell trên đụng vào file dùng chung của cả `/admin` và `/hq`. Sẽ kiểm tra hồi quy 2 panel đó sau khi đổi.

---

## 4. Bản đồ độ phủ 99 màn (đã có / cần dựng)

| Module | Màn | Đã có tương ứng ở /admin hoặc /fila | Cần dựng mới (chính) |
|---|---|---|---|
| **00** Foundation | 10 | OperationalDashboard, NotificationCenter, EventLogMonitor | BqlPanelProvider, Context selector (project+building), My Work inbox, Audit viewer /bql, Permission state, Project settings preview |
| **01** Resident/Apartment | 10 | ApartmentDirectory, ApartmentProfile, ResidentDirectory, ResidentDetail, ResidentBindingQueue | Apartment tree, Household relationship, Move-in/out, Resident timeline, Binding validation queue, Data-quality dashboard |
| **02** Approval/Vehicle/Card | 10 | ResidentApprovalQueue, VehiclesAndCards | Đối chiếu hồ sơ, Duyệt xe, Chi tiết thẻ ra vào, Cấp thẻ/quyền, Sinh trắc & QR, Hàng đợi bổ sung, Rule duyệt, Dashboard access |
| **03** Fee/Statement/Debt | 10 | InvoiceGeneration, InvoiceManagement, StatementApprovalQueue, BillingAuditAdjustment | Biểu phí config, Tạo kỳ phí, Chạy phí/bảng kê, Chi tiết bảng kê căn hộ, Công nợ theo căn hộ, Miễn giảm, Nhắc nợ/chiến dịch, Báo cáo kỳ phí |
| **04** Payment/Cashier | 10 | PassThroughWalletDashboard (một phần) | Dashboard thu ngân, Hàng đợi giao dịch, Thu tại quầy QR/tiền mặt, Chi tiết GD, Import sao kê, Đối soát/matching, Phân bổ công nợ, Biên lai/hoá đơn, Hoàn tiền, Báo cáo đối soát |
| **05** Feedback/SLA/WorkOrder | 10 | FeedbackQueue, WorkOrderKanban | Chi tiết phản ánh, Tạo phản ánh, Dashboard SLA/KPI, Quy trình SLA, Giao việc, Lịch tiến độ, Nghiệm thu, Đánh giá cư dân, Báo cáo |
| **06** Ops/Maintenance/Asset | 10 | ContractorLibrary, SupplierVendorLibrary | Dashboard vận hành, Bảng bảo trì, Lịch định kỳ, Danh mục thiết bị, Chi tiết thiết bị, Tạo WO bảo trì, Nghiệm thu, Chi tiết nhà thầu, Giao ban tồn đọng |
| **07** Comms/Form/Survey | 10 | NotificationCenter, PlatformContentCms | Dashboard truyền thông, Trung tâm chiến dịch, Chi tiết duyệt gửi, Form designer, Hộp thư biểu mẫu, Khảo sát/bình chọn, Kết quả, Kiểm duyệt cộng đồng, Chi tiết báo cáo bài, Phân tích |
| **08** Security/Visitor/SOS | 10 | (chủ yếu chỉ có Resource ở /fila) | Toàn bộ 10 màn: Dashboard an ninh, Quản lý khách, Chi tiết yêu cầu khách, Check-in QR/kiosk, Bảng tuần tra, Chi tiết ca, Bãi xe/thẻ, Giám sát SOS, Chi tiết SOS, Bàn giao ca |
| **09** Reports/Approval/Audit/AI | 10 | KnowledgeAuditLog, AiCenter, AiGovernance | Dashboard báo cáo+AI, Trung tâm phê duyệt, Chi tiết yêu cầu duyệt, Nhật ký audit /bql, Chi tiết bản ghi audit, Báo cáo tài chính, Báo cáo vận hành/SLA, Export center, X2AI Copilot vận hành, Phân tích AI gợi ý |

_(Nhiều page hiện ở `/admin` thuộc tầng SuperAdmin/SaaS — Platform*, Subscription*, Support*, Integration*, Usage*, Webhook* — KHÔNG thuộc BQL; giữ nguyên ở /admin.)_

---

## 5. Kế hoạch theo giai đoạn (bám MODULE_BUILD_ORDER, dựng theo dependency)

### Giai đoạn 0 — Tách panel 3 tầng + shell ✅ ĐÃ XONG (2026-07-02)
- ✅ Tách `/sa` (SaPanelProvider + EnsurePlatformAdmin, 35 page), chuyển 4 page AI sang `/hq`, `/admin` còn 13 page thuần BQL. Rewire workspace switcher redirect. 3 panel boot sạch (route:list OK).
- **Còn lại của Giai đoạn 0:**
  - Áp 4 quyết định shell ở mục 3 (title header / search căn giữa / bỏ subtitle / 6-card/hàng) — làm trên shell dùng chung.
  - Bổ sung màn nền BQL-00 còn thiếu trên `/admin`: My Work & Approval Inbox, Audit Log Viewer (BQL scope), Permission/Invalid-context state, Project Settings Preview. (Dashboard BQL-00-01 đã có = `OperationalDashboard`.)
  - `/sa` header còn dùng bản gọn (chỉ workspace switcher) — tinh chỉnh sau nếu cần.

### Giai đoạn 1 — BQL-01 Cư dân & Căn hộ (nền dữ liệu vận hành)
### Giai đoạn 2 — BQL-02 Duyệt tài khoản/Xe/Thẻ ra vào
### Giai đoạn 3 — BQL-03 Phí/Bảng kê/Công nợ → BQL-04 Thanh toán/Đối soát
### Giai đoạn 4 — BQL-05 Phản ánh/SLA/Work Order
### Giai đoạn 5 — BQL-06 Vận hành/Bảo trì/Tài sản/Nhà thầu
### Giai đoạn 6 — BQL-07 Truyền thông/Form/Khảo sát
### Giai đoạn 7 — BQL-08 An ninh/Khách/Tuần tra/SOS (build mới nhiều nhất)
### Giai đoạn 8 — BQL-09 Báo cáo/Phê duyệt/Audit/X2AI
### Giai đoạn 9 — Hardening: audit đầy đủ, policy coverage, realtime (event+queue+WS, fallback polling), read-model/KPI snapshot cho dashboard, seed refresh, test acceptance theo `ACCEPTANCE_CRITERIA.md` từng module.

### Quy ước bắt buộc mỗi màn (từ handoff)
- Không dựng màn tĩnh — mọi KPI/table/badge/timeline đọc từ DB/seed hoặc read-model/cache.
- Mọi query qua `CurrentProjectContext` + policy; `tenant_id`+`project_id` bắt buộc, `building_id` là scope con.
- Empty-state chủ động + seed mẫu cho tab chưa có dữ liệu.
- Action nhạy cảm gọi policy tường minh, `requiresConfirmation`, lý do bắt buộc (approve/reject).
- AI chỉ gợi ý có người duyệt, có audit + confidence + source.
- Đọc theo thứ tự tài liệu mỗi batch: SCREEN_INVENTORY → UI_SCREEN_CONTRACT → WEB_INTERACTION_SPEC → BUSINESS_RULES → WORKFLOW_STATE_MACHINE → DB_ENTITY_MAP → API_CONTRACT → REALTIME_EVENTS → FILAMENT_IMPLEMENTATION_GUIDE → SEED_DATA → TEST_SCENARIOS → ACCEPTANCE_CRITERIA → CLAUDE_TASKS.

---

## 6. Quyết định đã chốt
1. ✅ **Panel:** KHÔNG dựng `/bql`. `/admin` = BQL. Tách platform→`/sa`, AI→`/hq`. (Đã thực thi xong.)
2. **Thứ tự ưu tiên module** sau Giai đoạn 0: theo dependency 01→02→03→04… (mặc định).
3. **Realtime:** polling nhẹ cho MVP, bật WebSocket đầy đủ ở giai đoạn Hardening.
