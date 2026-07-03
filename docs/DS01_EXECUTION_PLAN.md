# Kế hoạch thực thi DS-01 — App Shell · Nav · Header · Layout · Approval

_Lập 2026-07-03. Nguồn: `handoff/0307/X2_BMS_DS01_APP_SHELL_NAV_HEADER_UI_APPROVAL_HANDOFF_20260703`._

## Bối cảnh & quyết định chủ dự án (đã chốt 2026-07-03)

DS-01 là **bộ Design System chính thức** (không phải module nghiệp vụ mới) — dùng module Cư dân làm ví dụ tham chiếu cho shell/nav/header/layout/action/approval/responsive. Đây là hiện thực hoá "bộ component dùng chung" đã thống nhất ở phiên trước.

**Hai quyết định chốt:**
1. **Kiến trúc = giữ bespoke `/admin` Page toàn bộ**, chỉ áp bộ component + font DS-01 lên trên. → Bộ component DS-01 là **Blade component** (không chuyển sang Filament Resource). Các mapping "ResidentResource::table()/view()" trong `COMPONENT_MAPPING.md`/`ROUTE_RESOURCE_MAPPING.md` chỉ coi là **tham chiếu**, KHÔNG áp dụng (deviation có chủ đích so với khuyến nghị Filament-native của DS-01).
2. **Thứ tự = làm nền DS-01 trước** (Phase 0→1→2), rồi mới roll-out sang finance & các slice khác.

## Locked decisions (từ handoff, áp cho mọi component)

- Font tiêu đề/heading/số KPI = **Plus Jakarta Sans**; body/table/form = **Inter**.
- Body KHÔNG lặp title/subtitle — title chỉ ở topbar.
- Trang có tab → **action page-level nằm cùng hàng tab, canh phải**; >3 action thì gộp dropdown.
- KPI = tổng theo context (tenant/company/project/building), **KHÔNG đổi theo filter bảng**.
- Filter nằm ngay trên bảng, chỉ ảnh hưởng bảng; chip filter nằm dưới hàng filter; `Xóa tất cả` chỉ xoá filter bảng.
- Action model 5 lớp: header `+ Tạo mới` (quick-create toàn cục) · page `+ Thêm mới` (gold, tạo theo module) · `Nhập/Xuất dữ liệu` (outline phụ, export có scope) · toolbar bảng · row actions · **approval: nút quyết định sticky đáy panel**.
- Sidebar navy + active gold + badge đỏ; export scope (context/filtered/selected/template); import wizard 6 bước.

## Đối chiếu hiện trạng ↔ DS-01 (tóm tắt)

- **Đã có:** navy+gold shell, topbar (title/search Ctrl+K/context/+Tạo mới/noti/profile), `kpi-row` 5–6 thẻ, Inter, AI FAB, scope 3 tầng, đủ trang Cư dân bespoke, `mobile-shell` cơ bản.
- **Cần đổi:** font tiêu đề Manrope→Plus Jakarta Sans; đặt tên component flat→dotted namespace; action inline tab-row; filter-bar chuẩn + chips + saved-view/column-config/density; KPI bất biến theo filter; export scoping; import wizard; workspace switcher modal giàu hơn; 13 state bắt buộc; responsive table→card; formalize tokens.
- **Màn mới:** `NavigationGuidePage` (02), `ResidentCommunityOverviewPage` (03). Dashboard 01 = đối chiếu `OperationalDashboard` với ảnh.

---

## Phase 0 — Nền: Token & Font

1. **Font:** đổi link bunny.net ở `AdminPanelProvider` + `HqPanelProvider` + `SaPanelProvider`: `manrope:...` → `plus-jakarta-sans:400,500,600,700,800`. Cập nhật `--font-title` trong `resources/css/filament/admin/theme.css` thành `'Plus Jakarta Sans','Inter',...`. Rà các chỗ hardcode `font-family:'Manrope'` (header-cluster.blade…).
2. **Design tokens:** đưa `tokens/DESIGN_TOKENS.json` vào `theme.css` dạng CSS var: màu (`--x2-navy-950 #071A3A`, `--x2-navy-900 #0B2146`, `--x2-gold-600 #D5A331`, `--x2-blue-600 #2563EB`, success/warning/danger/ai…), layout (`--x2-sidebar-width 20rem`, `--x2-sidebar-collapsed-width 5rem`, `--x2-topbar-height 4.25rem`, `--x2-content-padding 1.5rem`, `--x2-card-radius 12px`, `--x2-button-height 40px`, `--x2-table-row-height 56px`).
3. **Sidebar:** thêm `->sidebarWidth('20rem')->collapsedSidebarWidth('5rem')` (hiện chỉ có `sidebarCollapsibleOnDesktop`).
4. **Tailwind `@source`:** đảm bảo phủ thư mục component mới `components/x2/**`.
5. Verify: `npm run build`, render 200, đối chiếu topbar/sidebar với ảnh 01.

## Phase 1 — Bộ component DS-01 (Blade, dotted namespace)

Xây trong `resources/views/components/x2/<group>/<name>.blade.php`. Mỗi component hỗ trợ 13 state khi có nghĩa: `default/hover/active/focus/disabled/loading/empty/error/success/warning/danger/readonly/permission-denied`.

| Nhóm | Component | Nguồn refactor |
|---|---|---|
| shell | `x2.shell.app` · `x2.shell.sidebar` · `x2.shell.topbar` | admin-shell / sidebar / topbar |
| nav | `x2.nav.group` · `x2.nav.item` · `x2.nav.child` | (mới, từ brand + Filament nav) |
| header | `x2.header.context-switcher` · `.global-search` · `.quick-create` · `.notification` · `.user-menu` | header-cluster / context-switcher / global-search |
| page | `x2.page.tabs` · `x2.page.action-group` (inline tab+action) | action-bar |
| card | `x2.card.kpi` · `x2.card.info` | kpi-card / kpi-row / section-card |
| filter | `x2.filter.bar` (search+select+saved-view+column-config+density+export) · `x2.filter.chip` | (mới, rút từ các trang finance) |
| table | `x2.table.data` (columns config + row/bulk + pagination + empty/loading) · `x2.table.bulk-actions` | data-table |
| record | `x2.record.highlights` · `x2.record.timeline` | (mới, từ resident-detail) |
| approval | `x2.approval.detail-panel` (drawer phải + sticky footer quyết định) | AccountApprovalDetail |
| ai | `x2.ai.suggestion-card` | ai-panel |

- **Giữ alias tương thích ngược** cho tên flat cũ (`x2.kpi-card`→`x2.card.kpi`…) để các trang đã build không vỡ trong lúc chuyển tiếp; gỡ alias ở Phase 3.
- `x2.money` helper (VND) nâng từ `FinanceScope::money/moneyCompact`.

## Phase 2 — Module Cư dân = bản dựng chuẩn (10 màn DS-01)

Refactor bespoke Page hiện có về bộ component mới, bám ảnh acceptance:
1. **01 Dashboard** — đối chiếu `OperationalDashboard` với ảnh (KPI 5 thẻ + Cảnh báo + Công việc của tôi + Lối tắt nhanh + Hoạt động gần đây).
2. **02 NavigationGuide** (mới) `/admin/navigation-guide` — sidebar expanded + group/child + badge + info banner + module cards.
3. **03 ResidentCommunityOverview** (mới) `/admin/resident-community/overview` — header quick-create + KPI + distribution + community activity + recent residents.
4. **04 Workspace switcher** (nâng cấp) — modal segmented BQL/Công ty/SuperAdmin + workspace cards + panel ngữ cảnh hiện tại + recent + search.
5. **05 Resident list** (`ResidentDirectory`) — tab inline actions, KPI context, filter-bar + chips + saved-view + column-config + density + export scoping.
6. **06 Add wizard** (`ResidentCreate`) — WizardSteps + FormSection + FileUpload + right AI/summary panel + validation.
7. **07 Detail** (`ResidentDetail`) — highlights + record tabs + info blocks + related lists + timeline/audit.
8. **08 Data quality** (`ResidentDataQuality`) — tab+action inline, KPI, advanced filter + chips, bulk action bar, export filtered/selected.
9. **09 Approval** (`ResidentApprovalQueue` + `AccountApprovalDetail`) — split list trái + detail drawer phải + risk/AI panel + attachment + toast + sticky Phê duyệt/Bổ sung/Từ chối + **ghi audit**.
10. **10 Mobile** (`/admin/residents/data-quality` responsive) — topbar compact, tabs + KPI cuộn ngang, table→card list, row actions→kebab, bulk bar sticky đáy, sidebar→drawer.

## Phase 3 — Roll-out toàn hệ

- Refactor các màn đã build (BQL-00 Foundation, BQL-01, BQL-02, BQL-03 finance 6 màn) về bộ component DS-01; gỡ alias flat.
- **Đóng nốt 4 màn BQL-03** (03-03 BillingRun, 03-07 duyệt điều chỉnh, 03-08 nhắc nợ, 03-10 audit tài chính) TRÊN component mới.
- Tiếp Slice 4→9 trên nền DS-01.

## Phase 4 — Responsive & Hardening

- table→card + mobile drawer + horizontal tabs/KPI cho mọi list.
- QA 13-state từng component; checklist `ACCEPTANCE_CRITERIA.md` (visual + functional + Filament).
- Verify headless từng route đối chiếu ảnh; kiểm KPI bất biến theo filter; export/import scope; audit trên approval.

---

## Rủi ro / lưu ý

- **Deviation có chủ đích:** giữ bespoke Page thay vì Filament Resource → tự quản table/filter/bulk (không dùng `Tables\*` Filament). Đánh đổi: nhiều Blade hơn nhưng match pixel dễ hơn + giữ thiết kế WEB-FORM đã chốt.
- **13 state** là khối lượng lớn — ưu tiên empty/loading/permission-denied trước, phần còn lại khi màn cần.
- **KPI bất biến theo filter** phải rà lại các trang đã build (một số đang tính lại theo filter).
- Ảnh handoff batch này (0307) tên file khớp nội dung (khác các batch cũ) — vẫn nên mở PNG khi bám chi tiết.
- Mỗi thay đổi ⇒ append `docs/DEV_JOURNAL.md`.
