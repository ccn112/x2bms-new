# SESSION HANDOFF — 2026-07-03 · BQL 3 luồng: kế hoạch + màn Căn hộ + Density fix

> Phiên này: đọc 3 gói handoff BQL → lập kế hoạch tổng → chốt "0 bảng mới" → dựng màn
> **BQL-01-05 Danh sách căn hộ** đúng khuôn DS-01 → áp **lớp density** (handoff 0307).
> **Toàn bộ code nằm ở repo CHÍNH `D:\Chinh\x2\x2bms` (KHÔNG dùng worktree), hiện UNCOMMITTED.**

---

## 0. Quy ước làm việc (ĐỌC TRƯỚC)
- **Solo dev, làm thẳng trên `main`**, KHÔNG worktree (worktree thiếu `vendor/`+`.env` → không boot/test/preview được). Nếu bị mở trong worktree, edit file ở đường dẫn repo chính `D:/Chinh/x2/x2bms`.
- **Commit:** chỉ khi update lớn/đáng lưu tâm và **hỏi chủ dự án trước** — không auto-commit.
- PHP: `~/.config/herd/bin/php84.bat`. DB: MySQL `x2bms` (root / `congchinh`) @127.0.0.1:3306.
- **3 tầng ↔ 3 route (bất di bất dịch):** SuperAdmin=`/sa` · Công ty vận hành Tenant=`/hq` · **Ban QL dự án (project_id)=`/admin`**. Cả 3 luồng BQL-01/02/03 ở `/admin`, scope `CurrentContext::buildingIds()`.

## 1. Kế hoạch & quyết định đã chốt
- Kế hoạch tổng: `docs/BQL_MASTER_BUILD_PLAN_20260703.md`. Ánh xạ tên entity: `docs/BQL_ENTITY_NAME_MAP.md`.
- **AI:** rule-based inline (badge kiểm tra/rủi ro tất định, 4 mức info/warning/high_risk/policy_block, `policy_block` chặn nút) **+ FAB chung** cho hỏi-đáp sinh sinh **+ Suggested Prompts click-được** (đẩy vào FAB qua `ProvidesAiContext::shareAiContext(['suggestions'=>...])`). KHÔNG panel AI inline, KHÔNG LLM per-màn.
- **Phạm vi:** phân đợt, DS-01 trước — BQL-01 (đủ) → 02 → 03; nâng cấp màn cũ + bù màn thiếu.
- **Schema: 0 BẢNG MỚI.** Live DB đã 326 bảng, phủ hết nhu cầu. Delta cột duy nhất đã chốt: `areas.access_config` (json) cho màn BQL-03-06 — thêm khi tới màn đó. households/residency_events/approval_conflict = suy ra. Chi tiết trong ENTITY_NAME_MAP.
- **Kiến trúc UI:** giữ **bespoke `/admin` Page + DS-01 Blade component**; handoff `*Resource` chỉ tham chiếu. Màn list = **Filament `Page implements HasTable`** (khuôn `app/Filament/Pages/ResidentDirectory.php`) + `<x-x2.page.tabs>` + `<x-x2.kpi-row>`/`<x-x2.card.kpi>` + `{{ $this->table }}`. Tiêu đề CHỈ ở topbar.

## 2. ĐÃ LÀM — Màn BQL-01-05 Danh sách căn hộ (`/admin/apartments`)
Files (repo chính, uncommitted):
- `app/Filament/Pages/ApartmentDirectory.php` — viết lại thành `HasTable`: scope `buildingIds()` (đã sửa lỗi rò chéo tenant), KPI 5 card đúng thiết kế (Tổng căn/Đã ở/Trống/Đang duyệt gắn/Nợ phí + %), tab trang (Danh sách/Cây/Duyệt gắn) + action (Nhập/Xuất/Thêm), cột đúng contract (Mã căn·Tòa·Tầng·DT·Loại·Trạng thái ở·Chủ thể hiện tại·Số cư dân·Công nợ VND·Cập nhật·Hành động), badge trạng thái, 5 SelectFilter, row-action eye/edit/kebab, bulk, export CSV+audit, phân trang [10,25,50,100].
- `resources/views/filament/pages/apartment-directory.blade.php` — bọc `<div class="x2-bql-page">`, `x-x2.page.tabs` + `x-x2.kpi-row cols=5` (card `class="x2-kpi"`) + bảng.
- `resources/css/filament/admin/theme.css` — thêm **lớp density scoped `.x2-bql-page`** (handoff 0307): KPI compact 88–96px, table dense th44/td8px, gap 16px, triệt margin child. TUYỆT ĐỐI không sửa `.fi-*` global.
- `_render_admin.php` — **script verify tái dùng**: `php _render_admin.php "<slug>"` (login BQL user `nv1@x2bms.vn`) → render HTTP 200. (login pass staff = `Bms@2026!`).

**Verify đã đạt:** `php -l` ✓ · `npm run build` ✓ (theme 678KB, `.x2-bql-page` đã compile) · render `/admin/apartments` = **200** · grep marker khớp thiết kế (tab/KPI/cột). Preview pixel KHÔNG chạy từ worktree — chủ dự án tự xem trên app đang chạy.

## 3. ĐANG DỞ / TIẾP THEO
1. **Refined tab "active tab nối với content"** (thiết kế mới nhất chủ dự án gửi, footer "Phương án refined · v1.2.0"): tab active nối liền vào vùng nội dung. CẦN: phân tích component `resources/views/components/x2/page/tabs.blade.php` rồi tinh chỉnh (active = chữ xanh + gạch xanh bo tròn flush đường kẻ chân full-width; vùng content nối với tab active). Là bước đang làm dở khi tạo handoff này.
2. **Density handoff — phase sau (Task 4/5/7):** custom `X2FilterBar` (Livewire, filter nghiệp vụ nhiều trường + chip + reset + drawer nâng cao, phải tác động query thật) thay filter mặc định Filament; **mobile card/list mode**. Ref: `handoff/0307/X2_BMS_BQL_UI_DENSITY_RESPONSIVE_FIX_HANDOFF_20260703_FLAT/` (examples/ + specs/).
3. **Nghiệm thu màn 05** → nhân bản khuôn + lớp `.x2-bql-page` cho các màn list còn lại.
4. **Roadmap màn:** BQL-01 còn 06 (Chi tiết căn hộ 360) · 07 (Cây căn hộ) · 01–04 (cụm cư dân: list/timeline/wizard/detail) · 08 (Households) · 09 (Residency events) · 10 (Data quality) → rồi BQL-02 (10 màn duyệt/tài khoản) → BQL-03 (10 màn xe/thẻ/ra vào). Ảnh + contract trong 3 gói `handoff/BQL/X2_BMS_BQL0{1,2,3}_*`.
5. **Vá audit:** `audit_logs` đang gần trống (ERD §10.1) — xác nhận write-path ghi thật khi build.

## 4. Bẫy đã biết
- Filament column closure param **PHẢI tên `$state`**. KHÔNG đặt method Page trùng tên Livewire (mount/render/dispatch…). Enum BackedEnum → `enumVal()` trước khi so sánh.
- Đừng thêm bảng preemptive — luôn tra `docs/BQL_ENTITY_NAME_MAP.md` + introspect live DB trước.
- CSS density: chỉ scope trong `.x2-bql-page`, build lại bằng `npm run build` (main).

## 5. Con trỏ
- Memory: `x2bms-bql3-master-plan`, `x2bms-dev-workflow`, `x2bms-web-admin-architecture`, `x2bms-erd-current`, `x2bms-ds01-track`.
- Docs: `BQL_MASTER_BUILD_PLAN_20260703.md`, `BQL_ENTITY_NAME_MAP.md`, `ERD_CURRENT_20260703.md`, `DS01_EXECUTION_PLAN.md`, `WEB_BQL_EXECUTION_PLAN.md`.
