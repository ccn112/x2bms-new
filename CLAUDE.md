# X2-BMS Backend (Laravel 13 + Filament) — CLAUDE.md

SaaS quản lý vận hành chung cư. Backend + web BQL/HQ/SuperAdmin (Filament, panel `/admin` `/hq` `/sa` `/fila`) + API cho app cư dân/BQL.

## Nguồn phạm vi nghiệp vụ (đọc trước khi build)
- Bản đồ nghiệp vụ tổng: `D:/Code/handoff/x2bms/_BUSINESS_MAP_20260725/` → mở `00_MASTER_INDEX.md`.
  - Web BQL: `03_WEB_BQL_business_map.md` · SuperAdmin+HQ: `05_SAAS_PLATFORM_HQ_map.md` · Kiến trúc: `00_FOUNDATION_ARCHITECTURE_map.md`.
- Mô hình **4 tầng**: T1 SuperAdmin (nhà cung cấp) › T2a HQ (công ty khách hàng) › T2b BQL (dự án) › T3 Cư dân.

## Tài liệu phát triển — BẮT BUỘC cập nhật
Khi hoàn tất/sửa một module/màn/endpoint, **dùng skill `cap-nhat-tai-lieu`** (`.claude/skills/cap-nhat-tai-lieu/SKILL.md`) và theo đúng trình tự:
1. Track 3 (DB/kiến trúc/seed) → `docs/dev/03_data_arch/`
2. Track 2 (tính năng) → `docs/dev/02_features/`
3. Track 1 (UI/UX) → `docs/dev/01_ui_ux/`
4. Cập nhật `docs/PROGRESS_TRACKER.md` + đồng bộ bản hợp nhất ở handoff
5. Test → ✅ → Track 4 hướng dẫn sử dụng GitBook `docs/guide/{bql|hq|sa}/` + ảnh
6. Ghi `docs/DEV_JOURNAL.md` cuối phiên (độc lập)

Tài liệu là **1 GitBook** (`docs/guide/SUMMARY.md`) phân quyền theo chương; nhật ký phát triển ghi độc lập.

## Trạng thái tiến độ
`docs/PROGRESS_TRACKER.md` là nguồn theo dõi chính thức. Ký hiệu: ✅/🟢/🟡/⬜/❓.
