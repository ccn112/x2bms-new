---
name: cap-nhat-tai-lieu
description: >-
  Cập nhật tài liệu phát triển & hướng dẫn X2-BMS backend theo mô hình 1 GitBook
  (phân quyền theo chương) + nhật ký phát triển độc lập. Dùng MỖI KHI hoàn tất
  (hoặc thay đổi đáng kể) một module/màn/endpoint web BQL/HQ/SuperAdmin, thêm
  migration/model/seed, sửa giao diện Filament, hoặc chuẩn bị "chốt" để viết
  hướng dẫn sử dụng. Đảm bảo 4 loại nội dung (UI/UX, Tính năng, DB/Kiến trúc/Seeding,
  Hướng dẫn sử dụng) + PROGRESS_TRACKER khớp code, và ghi DEV_JOURNAL sau mỗi phiên.
---

# cap-nhat-tai-lieu (X2-BMS backend)

Nguồn phạm vi chuẩn: bộ **bản đồ nghiệp vụ** `D:/Code/handoff/x2bms/_BUSINESS_MAP_20260725/`
(đọc `00_MASTER_INDEX.md` trước).

## Mô hình tài liệu (thống nhất theo yêu cầu chủ dự án)

**Một GitBook duy nhất**, book root = `docs/guide/`, điều hướng ở `docs/guide/SUMMARY.md`,
chia chương theo **phân quyền đối tượng đọc** (không tách thành nhiều bộ rời):

| Nhóm chương | Track | Thư mục nguồn | Quyền đọc | Cập nhật khi |
|---|---|---|---|---|
| Phát triển · UI/UX | 1 | `docs/dev/01_ui_ux/` | nội bộ dev | sửa giao diện Filament |
| Phát triển · Tính năng | 2 | `docs/dev/02_features/` | nội bộ dev | xong logic tính năng |
| Phát triển · DB/Kiến trúc/Seed | 3 | `docs/dev/03_data_arch/` | nội bộ dev | có migration/model/seed |
| Hướng dẫn sử dụng | 4 | `docs/guide/bql|hq|sa/` | BQL / HQ / SuperAdmin | **CHỈ sau khi ✅ chốt+test**, kèm ảnh thật |

**Nhật ký phát triển GHI ĐỘC LẬP** ở `docs/DEV_JOURNAL.md` — KHÔNG đưa vào GitBook,
KHÔNG theo track. Ghi theo phiên (ngày + việc đã làm + quyết định + gotcha).

## Trình tự BẮT BUỘC khi hoàn tất một module/màn

1. **Đọc scope**: `00_MASTER_INDEX.md` → module (BQL-xx / WEB-UX-2x / HQ-0x) + quyết định đã chốt. Scope còn ❓ → hỏi, KHÔNG tự đoán.
2. **Track 3** (nếu có migration/model/seed): `docs/dev/03_data_arch/<module>.md` — bảng, cột, quan hệ, seed, tác động ERD; đồng bộ `docs/ERD_DRAFT.md`.
3. **Track 2**: `docs/dev/02_features/<module>.md` — hành vi thật, business rule, RBAC, acceptance, edge case.
4. **Track 1**: `docs/dev/01_ui_ux/<module>.md` — giao diện cuối, cột/form/action, token/theme.
5. **Tiến độ**: `docs/PROGRESS_TRACKER.md` (🟡→🟢) + đồng bộ dòng ở `handoff/x2bms/_BUSINESS_MAP_20260725/PROGRESS_TRACKER.md`, kèm ngày.
6. **Test** (test/HTTP/thủ công). Đạt → PROGRESS 🟢→✅, ghi bằng chứng vào track 2.
7. **Track 4 (chỉ khi ✅)**: viết/refresh chương hướng dẫn sử dụng trong `docs/guide/{bql|hq|sa}/` + chụp ảnh giao diện tại panel `/admin` `/hq` `/sa`, lưu `docs/guide/images/<module>/`. Thêm trang vào `SUMMARY.md`.
8. **XUẤT BẢN vào Docs CMS (BẮT BUỘC sau khi chốt)** — Docs CMS (`/docs`, publish `doc.x2.fino.vn`) là **nơi chính thức** đăng tài liệu của CẢ x2bms + x2mobile:
   - **Đồng bộ markdown → CMS**: chạy `php artisan docs:import` (idempotent, đa nguồn theo `config/docs.php`). Trang mới trong `docs/dev` `docs/guide` (và `../x2mobile/docs/*` nếu có) tự vào đúng space. Hoặc **soạn trực tiếp** trong Filament panel `/sa` → nhóm **"Tài liệu"** (DocSpace/DocPage).
   - **Gán space + version**: đặt trang đúng space (dev / mobile-dev / ops / cu-dan / bql / hq / sa) và `version_id` = phiên bản hiện hành (hoặc để trống = chung).
   - **Thêm 1 mục BACKLOG**: vào phiên bản sản phẩm hiện hành (`/sa` → "Phiên bản & Backlog" → DocVersion đang `is_current`), tạo `DocVersionItem` mô tả thay đổi (category feature/improvement/fix/change + status), trỏ `ref_page` tới trang liên quan nếu có.
9. **Cuối phiên**: ghi `docs/DEV_JOURNAL.md` (độc lập).

## Quy ước
- Trạng thái: ✅ xong&verify · 🟢 xong chưa verify · 🟡 đang làm · ⬜ chưa làm · ❓ chưa rõ scope.
- Mỗi trang GitBook nên có nhãn quyền ở đầu: `> 🔒 Đối tượng: dev nội bộ | BQL | HQ | SuperAdmin`.
- KHÔNG viết hướng dẫn sử dụng (track 4) khi tính năng chưa ✅ — tránh chụp lại ảnh nhiều lần.
- Mọi số liệu/màn phải khớp code thực; lệch bản đồ → ghi chú "lệch bản đồ" + lý do.

## Template
`docs/dev/02_features/<module>.md`:
```markdown
# <Mã module> — <Tên>
> 🔒 dev nội bộ · Ngày: YYYY-MM-DD · Trạng thái: 🟢 · Commit: <hash>
## Người dùng làm gì
## Business rule
## Phân quyền hiển thị (RBAC)
## Acceptance criteria
## Edge case / trạng thái lỗi
## Bằng chứng test
```
Xem `docs/dev/README.md` để biết bố cục đầy đủ.
