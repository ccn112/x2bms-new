# Rà & backlog chuẩn hóa trang listing /admin

> 2026-08-05. Đối chiếu 19 trang có bảng (`InteractsWithTable`) với chuẩn
> **`.claude/skills/x2bms-admin-listing-page`** (mẫu: ResidentDirectory `/admin/residents`).

## Trạng thái tuân thủ
| Trang | KPI | Filter-bar `f*`+flush | Breadcrumb | Header (imp/exp/create) | Column toggle | Query closure | Kết luận |
|---|---|---|---|---|---|---|---|
| **ResidentDirectory** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ CHUẨN (mẫu) |
| **ApartmentDirectory** | ✅ | ✅ | ✅ | 🟡 | ✅ | ✅ | ✅ gần chuẩn |
| FeedbackQueue | ✅ | ✗ (native filter) | ✗ | 🟡 | ✗ | ✗ | 🟡 cần chuyển |
| PaymentClaimQueue | ? | ✗ | ✗ | 🟡 | ✗ | ? | 🟡 |
| StatementApprovalQueue | ? | ✗ | ✗ | 🟡 | ✗ | ? | 🟡 |
| NotificationCenter · NotificationDeliveryAudit · NotificationAnalytics | 🟡 | ✗ | ✗ | 🟡 | ✗ | ? | 🟡 |
| BuildingChannelSettings · FeePriorityOrder | 🟡 | ✗ | ✗ | 🟡 | ✗ | ? | 🟡 |
| ListingApprovalQueue · ListingPostingGrants | 🟡 | ✗ | ✗ | 🟡 | ✗ | ? | 🟡 |
| ResidentBindingQueue · VehicleRequests · AccessCards | 🟡 | ✗ | ✗ | 🟡 | ✗ | ? | 🟡 |
| CommunityModeration | 🟡 | ✗ (có flush) | ✗ | 🟡 | ✗ | ? | 🟡 |
| AuditLogViewer · ImportHistory · BillingChargeImport | 🟡 | ✗ | ✗ | 🟡 | ✗ | ? | 🟡 |

→ **2/19 chuẩn**; 17 trang cần chuyển. Mỗi trang ≈ 1 rewrite (Page + blade view) → làm theo **batch** dùng skill.

## Lỗi phổ biến cần sửa (theo skill mục 10)
1. `->query($eval)` thay vì `->query(fn () => …)` → filter có thể stale (bug 2.2). **Sửa cơ học, ưu tiên.**
2. Dùng native `->filters([...])`/tab thay vì filter-bar `f*` + `flushCachedTableRecords` (bug 2.1).
3. Thiếu `getBreadcrumbs()` + `getHeaderActions()` (màu export gray / create gold).
4. Thiếu column toggle (COLS + `$cols` + `->visible`).
5. KPI không tính lại theo filter.

## Thứ tự chuyển đổi đề xuất (batch)
**Batch 1 — Tài chính (rủi ro cao, hay dùng):** StatementApprovalQueue · StatementList · PaymentClaimQueue · DebtAgingList · FeeCatalog · FeePriorityOrder.
**Batch 2 — Vận hành/tương tác:** FeedbackQueue · NotificationCenter · NotificationDeliveryAudit · NotificationAnalytics · CommunityModeration.
**Batch 3 — An ninh/duyệt:** VehicleRequests · AccessCards · ResidentBindingQueue · ListingApprovalQueue · ListingPostingGrants.
**Batch 4 — Hệ thống:** AuditLogViewer · ImportHistory · BillingChargeImport · BuildingChannelSettings.

Mỗi trang khi chuyển: bám **checklist skill mục 9**; tái dùng component `x-x2.*` + tách blade `filament.pages.<x>-directory`.

## Việc đã làm hôm nay
- Skill `x2bms-admin-listing-page` (chuẩn hóa từ ResidentDirectory) — commit `51ddbb9`.
- Menu /admin sắp lại theo handoff (nhóm + sort duy nhất) — commit `355ba57`.

## Cần owner quyết
- Chuyển đổi 17 trang là khối lượng lớn → chạy theo batch (mỗi batch vài trang/lượt). Xác nhận thứ tự batch trên.
