# X2-BMS Backend (Laravel 13 + Filament) — CLAUDE.md

SaaS quản lý vận hành chung cư. Backend + web BQL/HQ/SuperAdmin (Filament, panel `/admin` `/hq` `/sa` `/fila`) + API cho app cư dân/BQL.

## Nguồn phạm vi nghiệp vụ (đọc trước khi build)
- Bản đồ nghiệp vụ tổng: `D:/Code/handoff/x2bms/_BUSINESS_MAP_20260725/` → mở `00_MASTER_INDEX.md`.
  - Web BQL: `03_WEB_BQL_business_map.md` · SuperAdmin+HQ: `05_SAAS_PLATFORM_HQ_map.md` · Kiến trúc: `00_FOUNDATION_ARCHITECTURE_map.md`.
- Mô hình **4 tầng**: T1 SuperAdmin (nhà cung cấp) › T2a HQ (công ty khách hàng) › T2b BQL (dự án) › T3 Cư dân.

## Phương pháp phát triển — AI-First Delivery (cài 2026-07-31)
Mọi module mới hoặc sửa lớn: **dùng skill `x2bms-domain-seed-contract-delivery`**
(`.claude/skills/…/SKILL.md`) + toàn bộ `.claude/rules/x2bms-*.md`.

Nguyên tắc: `domain contract → data scope → seed → service → API → surface → test → evidence`.
Không bắt đầu từ màn hình. Không hardcode dữ liệu mẫu trong UI. Không đặt business rule
trong Filament Resource / Controller / Flutter widget.

- Gate nghiệm thu: `docs/delivery/03_VERTICAL_SLICE_GATES.md` — **G9 anti-bypass** và
  **G10 money & authority** là bổ sung riêng của repo này, bắt buộc cho mọi slice có tiền.
- Chọn surface: `docs/delivery/02_FILAMENT_DECISION_MATRIX.md` — bảng có bất biến tiền
  (`payments`, `payment_allocations`, `receipts`, `statements`, `statement_lines`,
  `apartment_wallets*`, `debts`) **không được có Filament Resource sửa được**.
- Lộ trình: `docs/delivery/04_INITIAL_PHASE_PLAN.md` (bản viết theo trạng thái thật;
  bản gốc của gói handoff ở `…_ORIGINAL.md`).
- Artifact thiết kế mỗi module: `docs/modules/<module-key>/` theo
  `docs/delivery/templates/`.
- Quyết định nghiệp vụ công nợ đã chốt: `docs/BILLING_OWNER_DECISIONS_20260731.md`
  (**thắng** gói handoff 30/07 ở chỗ hai bên nói khác nhau).

### Quan hệ giữa hai hệ tài liệu — ĐỌC KỸ, đừng làm cả hai một cách trùng lặp
| | `docs/modules/<key>/` (10 artifact) | `docs/dev/` Track 1–4 + GitBook |
|---|---|---|
| Khi nào | **TRƯỚC** khi code | **SAU** khi code xong |
| Vai trò | Đầu vào thiết kế: contract, scope, seed, test matrix | Đầu ra vận hành: tính năng, UI/UX, DB, hướng dẫn dùng |
| Ai đọc | Người triển khai | Người dùng + người bảo trì |

**`docs/PROGRESS_TRACKER.md` là nguồn DUY NHẤT về trạng thái** — không đánh trạng thái ở
chỗ khác. Trạng thái phải khớp code: đánh 🟢 cho màn chỉ có migration + scaffold là lỗi
tài liệu (đã xảy ra với BQL-03-03/04/06 và BQL-07-08).

## Tài liệu phát triển — BẮT BUỘC cập nhật
Khi hoàn tất/sửa một module/màn/endpoint, **dùng skill `cap-nhat-tai-lieu`** (`.claude/skills/cap-nhat-tai-lieu/SKILL.md`) và theo đúng trình tự:
1. Track 3 (DB/kiến trúc/seed) → `docs/dev/03_data_arch/`
2. Track 2 (tính năng) → `docs/dev/02_features/`
3. Track 1 (UI/UX) → `docs/dev/01_ui_ux/`
4. Cập nhật `docs/PROGRESS_TRACKER.md` + đồng bộ bản hợp nhất ở handoff
5. Test → ✅ → Track 4 hướng dẫn sử dụng GitBook `docs/guide/{bql|hq|sa}/` + ảnh
6. Ghi `docs/DEV_JOURNAL.md` cuối phiên (độc lập)

Tài liệu là **1 GitBook** (`docs/guide/SUMMARY.md`) phân quyền theo chương; nhật ký phát triển ghi độc lập.

**Tài liệu CHÍNH THỨC nằm ở Docs CMS** — reader `/docs`, publish tại `doc.x2.fino.vn` — gom tài liệu dev + hướng dẫn của CẢ 2 dự án (x2bms + x2mobile). Quy trình khi chốt/hoàn tất: cập nhật markdown track 1–4 → `php artisan docs:import` (đồng bộ đa nguồn theo `config/docs.php`) hoặc soạn trực tiếp Filament `/sa` nhóm "Tài liệu" → thêm 1 mục **backlog** vào phiên bản hiện hành (DocVersion) → gán trang đúng space + version. Chi tiết ở skill `cap-nhat-tai-lieu`.

## Trạng thái tiến độ
`docs/PROGRESS_TRACKER.md` là nguồn theo dõi chính thức. Ký hiệu: ✅/🟢/🟡/⬜/❓.
