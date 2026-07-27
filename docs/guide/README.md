# X2-BMS — Tài liệu hướng dẫn sử dụng

Bộ tài liệu này hướng dẫn **sử dụng** hệ thống X2 (API, luồng nghiệp vụ, cách vận hành) — tách khỏi tài liệu **phát triển/kiến trúc**.

| Loại | Vị trí |
|---|---|
| Kiến trúc & quyết định (dev) | `x2/docs/ARCHITECTURE_X2_PLATFORM_V1.md` |
| Ghi chú triển khai backend (dev) | `x2/x2web/docs/PHASE0_MOBILE_API_IMPLEMENTATION.md` |
| **Hướng dẫn sử dụng (guide)** | `x2/docs/guide/` ← bạn đang ở đây |

## Mục lục hướng dẫn
- [Sử dụng Mobile API `/api/v1`](mobile-api-usage.md) — luồng đăng nhập, headers, envelope, mã lỗi (cho lập trình viên app & tích hợp).
- [Chạy & kiểm thử backend cục bộ](backend-run-local.md) — dựng môi trường, migrate, smoke test.

> **Quy ước:** mọi điểm quan trọng (quyết định, gotcha, security) được ghi ngay vào tài liệu tương ứng khi phát sinh.

---

## GitBook thống nhất — phân quyền theo chương

Toàn bộ tài liệu (phát triển + hướng dẫn sử dụng) là **một GitBook**, điều hướng ở [`SUMMARY.md`](SUMMARY.md), chia chương theo đối tượng đọc:

| Phần | Chương | Quyền đọc |
|---|---|---|
| A | Tài liệu phát triển (UI/UX · Tính năng · DB/Kiến trúc) — `../dev/` | 🔒 nội bộ dev |
| B | Vận hành & tích hợp (API, run-local, deploy, scale) | 🔧 dev/ops |
| C | Hướng dẫn Ban Quản Lý | 🏢 BQL |
| D | Hướng dẫn Cổng Công ty | 🏬 HQ |
| E | Hướng dẫn SuperAdmin | 🛡️ nhà cung cấp |

Mỗi trang gắn nhãn quyền ở đầu: `> 🔒 Đối tượng: …`. Khi publish GitBook, dùng phân quyền space/collection để ẩn Phần A/B với người dùng cuối.

📓 **Nhật ký phát triển** ghi độc lập ở `../DEV_JOURNAL.md` — KHÔNG thuộc GitBook.
Quy trình cập nhật: skill `cap-nhat-tai-lieu` (`.claude/skills/`).
