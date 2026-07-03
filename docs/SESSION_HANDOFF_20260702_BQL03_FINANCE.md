# X2-BMS — Session Handoff (2026-07-02) · Slice 3 BQL-03 Tài chính – Phí – Công nợ (6/10 màn) + nền dữ liệu 1.248 căn

Bàn giao phiên làm **Slice 3 (BQL-03)** trên panel `/admin`. Đọc kèm `docs/DEV_JOURNAL.md` (các entry đầu = chi tiết phiên này, mới nhất ở trên).

## 0. Đọc trước (reading order phiên sau)
1. `docs/DEV_JOURNAL.md` — 6 entry đầu (03-02 / 03-04+06 / backbone+03-05 / 3 tầng scope / 03-01 / 03-09).
2. File này + `docs/WEB_BQL_EXECUTION_PLAN.md` (lộ trình 10 slice).
3. Memory tự nạp: `x2bms-three-tier-scope`, `dev-env-handoff-paths`, `x2bms-render-helper`, `x2bms-next-shared-components`.
4. Handoff nguồn BQL-03 (máy này): `C:\app\x2-bms\handoff\WEB-BQL-03_FEE_CYCLE_STATEMENT_DEBT_BILLING_HANDOFF_20260702\` (ảnh + contract). **Tên file ảnh lệch nội dung** → mở PNG đọc tiêu đề trước khi bám.

## 1. Trạng thái git
- Branch `main`; commit cuối `72a12e6` (merge PR#1). **Toàn bộ phiên CHƯA commit** (dirty): 8 sửa + 16 mới (6 Page + 6 blade + 1 concern + 3 migration). Chưa commit theo quy ước (chờ chủ dự án).

## 2. Đã build (6/10 màn BQL-03, tất cả verify render 200 + số khớp ảnh)
| Màn | Route | Class | Ghi chú |
|---|---|---|---|
| 03-01 | `/admin/fees/catalog` | `FeeCatalog` | Biểu phí & quy tắc tính phí — KPI 28/6/4/9/12 |
| 03-02 | `/admin/fees/cycles` | `FeeCycleList` | Chu kỳ phí & đợt thu + drawer "Thiết lập kỳ phí" 5 bước (verify preview thật) |
| 03-04 | `/admin/statements` | `StatementList` | Bảng kê phí cư dân — KPI 124/1.086/732/148/8,42 tỷ, phân trang thật |
| 03-05 | `/admin/debts` | `DebtAgingList` | Danh sách công nợ & tuổi nợ — aging 1,02/0,65/0,32/0,21 tỷ |
| 03-06 | `/admin/debts/{record}` | `DebtLedger` | Sổ công nợ cư dân (detail) |
| 03-09 | `/admin/statements/{record}` | `StatementDetail` | Chi tiết bảng kê + VAT + timeline + checklist |

- Nav 'Tài chính – Phí' đúng 4 mục như ảnh: **Khoản thu** (FeeCatalog, có pill sang Chu kỳ phí) · **Hóa đơn & thanh toán** (StatementList) · **Công nợ** (DebtAgingList) · Báo cáo tài chính (chưa). Ẩn `StatementApprovalQueue` cũ khỏi nav.
- Shared: trait `App\Filament\Concerns\FinanceScope` (financeBuildingId = toà chính dự án SG-A, currentPeriod, money/moneyCompact). Quan hệ mới `Apartment::residents()/statements()`.

## 3. Nền dữ liệu (backbone) — quyết định chủ dự án: "phình 1.248 căn, mọi số khớp ảnh, 100% bản ghi thật"
`DemoDataSeeder::seedBql03Receivables` + `seedBql0302Cycles` + `seedBql03CatalogExtra` (bulk-insert, helper `distribute()` exact-sum). Trên Tòa A (SG-A):
- **1.248 căn** thật (+1.128 căn A/B/C + cư dân `CDX-*` + relation owner).
- Bảng kê T7/2026: **1.210** = published 1.086 / pending 124; viewed 732; overdue 148; **tổng phải thu 8,42 tỷ** (+ dòng phí 5-7/bảng kê; + lịch sử 6 kỳ cho 24 debtor).
- Công nợ: **24 sổ** (AR-2026-*), aging **1,02/0,65/0,32/0,21 tỷ**, risk 4/6/8/6.
- Kỳ phí 03-02: **10 kỳ CP-*** (6 mở/3 chờ chốt/1 phát hành).
- Migration add-only: `..._000006` (fee_types display), `..._000007` (statements/debts columns), `..._000008` (billing_periods cycle columns).
- Verify số: dùng script bootstrap `_chk.php`-style ở project root (tinker --execute KHÔNG in ra trong env này).

## 4. ⚠️ Việc CẦN CHỐT với chủ dự án
- **Side-effect dashboard WEB-01-01:** backbone 1.248 căn làm OperationalDashboard đổi số (Tỷ lệ thu **95.9%**, Đã thu **3,21 tỷ**, Công nợ đến hạn **2.220 tr**) — dữ liệu thật nhưng lệch ảnh gốc 96.2%/2.45 tỷ. Chọn: (a) giữ nguyên (ưu tiên BQL-03) — khuyến nghị; hoặc (b) cô lập OperationalDashboard đọc số chốt riêng.

## 5. Còn lại Slice 3 (4 màn — 3 màn cần seed bổ sung cho tenant demo)
- `03-03` Chi tiết kỳ phí (BillingRun — tổng hợp kết quả tính phí + nhật ký xử lý + Phê duyệt/Chạy lại/Phát hành). Model BillingRun/Item có sẵn; cần seed run cho các kỳ CP-*.
- `03-07` Duyệt điều chỉnh công nợ — **cần seed BillingAdjustment cho tenant demo** (hiện chỉ có ở Batch07).
- `03-08` Nhắc nợ & chiến dịch — **cần seed DebtReminderCampaign cho tenant demo** (hiện chỉ ở HQ tenant T-SSG).
- `03-10` Nhật ký thao tác (audit tài chính) — **cần seed AuditLog tài chính** (run/approve/publish/adjust) + drawer before/after.

## 6. Chạy & verify
- Serve/preview: `.claude/launch.json` đã đổi `runtimeExecutable: "php"` (portable 2 máy); preview MCP port 8010, name `x2bms`.
- Render headless: `_render_admin.php "slug1,slug2"` (gitignore, mặc định login `x2bms@x2bms.vn`) → in HTTP status. `_chk.php` để đếm DB.
- `php artisan migrate:fresh --seed` (~48s) · `npm run build` (Node 22, cho Tailwind class mới) · `php artisan optimize:clear`.
- Tài khoản: platform admin `x2bms@x2bms.vn` / `Bms@2026!` (tenant demo, building SG-A → context Sunshine Garden).

## 7. Bẫy phiên này (đã trả giá)
1. **Cột mới không có trong `$casts`** → `Statement::due_date` là string → `->format()` lỗi. Nhớ thêm casts cho mọi cột date mới.
2. **Danh sách lớn (1.210 bảng kê)** phải phân trang (WithPagination) — không render toàn bộ.
3. **Sort mặc định** dễ khiến 1 trang toàn 1 trạng thái → dùng hash-shuffle `orderByRaw('(id*2654435761)%100003')` cho mix như ảnh.
4. **Scope tài chính**: dự án có 2 toà (SG-A 1.248 / SG-B phụ) → KPI phải scope **toà chính SG-A** (`FinanceScope::financeBuildingId`) mới khớp ảnh.
5. **launch.json / docs path** hardcode user máy kia (`C:\Users\ADMIN`, `D:\Chinh`) — luôn kiểm tra path theo máy hiện tại (xem memory `dev-env-handoff-paths`).
6. **Ảnh handoff tên file ≠ nội dung** (03-01=Biểu phí, 03-09=Chi tiết bảng kê…).

## 8. PHIÊN SAU — thiết kế bộ COMPONENT dùng chung toàn dự án (chủ dự án yêu cầu)
Mục tiêu: cùng thiết kế 1 bộ component chuẩn hoá và refactor toàn bộ màn về dùng chung. Bối cảnh hiện tại để chuẩn bị:
- Đã có `resources/views/components/x2/*` (kpi-row, kpi-card, section-card, status-badge, data-table, action-bar, page-shell, admin-shell, ai-fab, mobile-shell…) nhưng **các màn tự viết lại nhiều pattern lặp**: khối top-actions (nút Tạo/Nhập/Xuất), thanh filter (grid select + search), bảng danh sách (thead/tbody/hover/empty/bulk-bar), pill sub-nav, drawer/slide-over (Alpine), detail 2-3 cột, timeline, checklist, mini-stat cards (Xem trước).
- **Ứng viên component nên chuẩn hoá:** `x2.page-actions` (nút hành động header), `x2.filter-bar` (dãy select + search), `x2.table` (bảng list chuẩn: columns config + row/bulk actions + pagination + empty-state), `x2.bulk-bar`, `x2.pill-nav` (sub-nav), `x2.drawer` (slide-over Alpine + step-indicator), `x2.detail-panel` / `x2.field-list` (dl), `x2.timeline`, `x2.checklist`, `x2.stat-tiles`, `x2.money` (format VND: đã có `FinanceScope::money/moneyCompact` — nên nâng thành helper/component chung).
- Các màn BQL-03 phiên này là **nguồn tham chiếu tốt** để rút component (nhiều pattern lặp rõ ràng). Sau khi có bộ component, refactor 03-01→09 + các slice trước về dùng chung, rồi mới làm tiếp 03-03/07/08/10 + Slice 4→9 trên nền component mới.
- Nhớ: `theme.css` `@source` phải phủ mọi thư mục chứa class Tailwind (đã có `resources/views/filament`, `components/x2`, `livewire`).

## 9. Quy ước
- Mỗi lần đổi code ⇒ append 1 entry `docs/DEV_JOURNAL.md` trước khi báo cáo.
- Không hardcode số — mọi số từ seed/DB. Scope tenant/project/building bắt buộc.
