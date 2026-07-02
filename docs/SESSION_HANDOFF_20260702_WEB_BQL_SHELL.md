# X2-BMS — Session Handoff (2026-07-02 · tối) · Tách 3 panel + Shell + Web BQL Slice 0/1/2 + Mobile/Search/Profile/Context-Switcher

Bàn giao phiên 2026-07-02 (buổi 2, nối tiếp phiên HQ Portal). Đọc kèm `docs/DEV_JOURNAL.md` (entry mới nhất ở đầu = chi tiết phiên này).

## 0. Đọc trước (reading order phiên sau)
1. `docs/DEV_JOURNAL.md` — entry đầu tiên.
2. File này + `SESSION_HANDOFF_20260702_HQ_PORTAL.md`.
3. Memory tự nạp: `x2bms-web-admin-architecture` (đã cập nhật nhiều — panels/shell/mobile/search/profile/context-switcher), `x2bms-build-roadmap` (Slice progress), `x2bms-handoff-image-mislabels`, `x2bms-dev-env`.
4. Kế hoạch: `docs/WEB_BQL_EXECUTION_PLAN.md` (lộ trình 10 slice), `docs/WEB_BQL_BUILD_PLAN_20260702.md`.
5. Handoff nguồn: `D:\Chinh\x2\handoff\X2_BMS_WEB_BQL_CLAUDE_CODE_HANDOFF_20260702_FULL_00_09\` (ảnh 00–04 lệch — xem §6) + bộ re-map `D:\Chinh\x2\handoff\01-04\` + `WEB-UX-MOBILE-RESPONSIVE-HEADER_HANDOFF` + `X2_BMS_WEB_UX_Header_Profile_20260630\batch02`.

## 1. Trạng thái git
- Branch `main`; commit cuối `7508eaf "Cty qltn"`. **Toàn bộ phiên CHƯA commit** (dirty): ~19 sửa + 47 mới + 39 rename (tách panel). Chưa commit theo quy ước (chờ chủ dự án).

## 2. Kiến trúc 3 panel (đã tách)
```
/sa    SuperAdmin/Platform  (SaPanelProvider, EnsurePlatformAdmin)  — 35 page
/hq    Công ty vận hành     (HqPanelProvider, EnsureHqAccess)       — 55 page (51 + 4 AI)
/admin Ban Quản lý (BQL)    (AdminPanelProvider, default workspace=bql) — 13 page gốc + màn mới slice 0/1/2 + profile
/fila  stock CRUD Resources (chưa tách tầng)
```
- Page platform ở `app/Filament/Sa/Pages`; AI ở `app/Filament/Hq/Pages`; BQL ở `app/Filament/Pages`.
- Nav groups /admin: Cư dân & Căn hộ · **An ninh & Kiểm soát** · Vận hành · Tài chính – Phí · Hệ thống.

## 3. Shell dùng chung (4 quyết định chủ dự án — DONE)
- Tiêu đề trên header (`resources/views/filament/hooks/topbar-start.blade.php`, JS clone `.fi-header-heading`).
- Global search **căn giữa** header (flex-1 gap), bấm mở `<livewire:global-search>`.
- **Bỏ subtitle**: `x-x2.action-bar` không render subtitle; các trang BQL bỏ title lặp.
- **Giữ số card KPI theo thiết kế**: `<x-x2.kpi-row :cols="6|5|4">` (không tự co 2/3).

## 4. Màn đã dựng phiên này (trên /admin)
| Slice | Route | Class |
|---|---|---|
| 0 | `my-work` | MyWork (inbox đa nguồn + duyệt) |
| 0 | `audit-logs` | AuditLogViewer |
| 0 | `access-denied` | PermissionState (ẩn nav) |
| 0 | `project-settings` | ProjectSettingsPreview |
| 1 | `apartments/tree` | ApartmentTree |
| 1 | `households` | HouseholdRelationships |
| 1 | `move-history` | MoveInOutHistory |
| 1 | `resident-timeline` | ResidentTimeline |
| 1 | `residents/data-quality` | ResidentDataQuality |
| 2 | `access` | AccessControlDashboard |
| 2 | `access/vehicle-requests` | VehicleRequests |
| 2 | `access/cards` | AccessCards |
| 2 | `access/resident-profile` | ResidentAccessProfile |
| 2 | `residents/approvals/{id}` | AccountApprovalDetail (ẩn nav) |
| profile | `my-profile` `security` `sessions` | MyProfile / SecuritySettings / LoginSessions (ẩn nav, nối avatar menu) |

Model backing gần như đầy đủ sẵn — build = dựng page UI + workflow (không dựng schema).

## 5. Shell nâng cao (3 panel)
- **Mobile shell** `resources/views/components/x2/mobile-shell.blade.php` (BODY_START, <lg): header gọn + context row + drawer (sidebar Filament) + bottom sheet.
- **Global search** `App\Livewire\GlobalSearch` + `resources/views/livewire/global-search.blade.php` (BODY_END): palette dùng chung, event `open-x2-search` + Ctrl/K.
- **Context switcher** `App\Livewire\ContextSwitcher` + `resources/views/livewire/context-switcher.blade.php` (BODY_END): 1 popup Công ty→Dự án→Workspace/Vai trò, **gate quyền**, width 2/3 content / mobile full, event `open-x2-context`. Thay 2 dropdown cũ bằng 1 chip header.
- **Bật cả 3 panel** qua render hook (`<x-x2.ai-fab/> @auth @livewire('global-search') @livewire('context-switcher') @endauth`).

## 6. Ảnh handoff — CẢNH BÁO
`UI_IMAGE_INVENTORY.md` (bộ gốc) **tên file ≠ nội dung ở batch 00–04**. Trước khi bám ảnh 00–04, **mở PNG đọc tiêu đề**. Bảng thật: `UI_IMAGE_INVENTORY_CORRECTED.md` (bộ gốc) + `D:\Chinh\x2\handoff\01-04\REMAP_VERIFICATION_20260702.md` (bộ re-map, còn vài slot lệch: BQL-01 01/06/07, 02 01/09, 03 01/02/03/09, 04 02/09). Batch 05–09 chuẩn.

## 7. Bẫy quan trọng (đã ghi memory)
1. `transition()` = method reserved Livewire — không đặt tên page method vậy.
2. Model cast **BackedEnum** (Vehicle/AccessCard/Resident status+type…) → `enumVal()` về scalar trước khi làm array key / so sánh (PHP + blade).
3. `@php use ... @endphp` trong `@auth` = fatal → dùng FQN.
4. **`resources/css/filament/admin/theme.css` `@source` phải liệt kê mọi thư mục có class Tailwind** — thiếu `resources/views/livewire` khiến class chỉ-dùng-ở-livewire không sinh (z-[100], lg:pl-64, calc width). Đã thêm.
5. Tailwind arbitrary `w-[calc(...*2/3)]` vỡ (opacity modifier) → `*0.667`.
6. Preview login/session hay rớt giữa các bước — verify lại sau khi đăng nhập.

## 8. Chạy & tài khoản
- Serve: Herd php `~/.config/herd/bin/php.bat artisan serve` (preview MCP port 8010, launch.json `x2bms`).
- Platform admin (thấy đủ 3 panel + cột Công ty): `x2bms@x2bms.vn` / `Bms@2026!` (Nguyễn Minh Anh).
- HQ operator: `hq@sunshinegroup.vn` / `Bms@2026!`.
- Build asset: `npm run build` (viteTheme `admin/theme.css`). Clear: `php artisan optimize:clear`.

## 9. Còn lại (đề xuất phiên sau)
- **Slice 3 → 9** (BQL-03 Tài chính/Phí/Công nợ → 04 Thanh toán → 05 SLA → 06 Bảo trì → 07 Truyền thông → 08 An ninh → 09 Báo cáo). Model đầy đủ.
- Nối avatar menu profile cho /hq & /sa; HQ cần context-row đa-dự-án trong mobile-shell.
- Global search: wiring kết quả sâu hơn / thêm nhóm entity; guard `EnsureProjectContext` redirect `/access-denied`.
- Slice 1 realign nếu cần bám thiết kế thật BQL-01 (một số màn extra ngoài thiết kế gốc).
