# X2-BMS — Nguyên tắc phát triển (bắt buộc áp dụng)

Ghi lại các nguyên tắc làm việc cốt lõi. Cập nhật khi có nguyên tắc mới.

## 1. Dữ liệu màn hình phải thật
Khi dựng bất kỳ màn UI nào (web admin/BQL, app), nếu màn cần dữ liệu:
- **PHẢI seed dữ liệu thật** vào DB (seeder) **hoặc lấy từ API** — không dùng placeholder giả/để trống.
- Web Filament: đảm bảo có seed đủ để màn hiển thị đúng nghiệp vụ.

## 2. Config phải lưu về backend
Mọi cấu hình cần bền vững (branding, feature flag, tham số nghiệp vụ, tuỳ chọn người dùng...) **phải lưu ở backend** — không hardcode ở client, không chỉ giữ trong local.
- Client được cache lại để hiển thị nhanh, nhưng **nguồn sự thật là backend**.

## 3. App: cache local đầy đủ (offline-first)
App mobile phải **cache đầy đủ trên local storage** để tăng trải nghiệm, theo Foundation:
- **Drift/SQLite** cho dữ liệu có cấu trúc (public content, project, notification inbox, snapshot user...).
- **SharedPreferences** cho tuỳ chọn nhẹ (theme, locale, last context...).
- **Secure Storage** cho token/secret.
- Repository luôn **emit cache trước → refresh nền** (SWR); màn public render < 1s từ cache; giữ `lastUpdatedAt`.
- KHÔNG cache PII nhạy cảm sai chỗ (KYC, CCCD) — theo `LOCAL_STORAGE_OFFLINE_STRATEGY`.

## 4. Hiệu năng danh sách — tránh N+1
Với mọi bảng/trang danh sách (Filament hoặc API):
- **Không** query trong vòng lặp/`->state()` từng dòng. Dùng `withCount`, `addSelect(subquery)`, eager-load (`with`) để mỗi trang chỉ vài query.
- KPI/thống kê: gộp `groupBy` thay vì nhiều `count()`; dùng subquery `whereIn(select id)` thay vì `pluck` toàn bộ id về PHP.
- Thêm index cho cột lọc/join nóng.
- Đo bằng số **query/lần tải** (không đo wall-clock trên `php artisan serve` — đơn luồng gây sai lệch). Xem `x2/x2web/docs/PERF_LIST_PAGES_OPTIMIZATION.md`.

## Tài liệu liên quan
- Kiến trúc: `ARCHITECTURE_X2_PLATFORM_V1.md`
- Hướng dẫn API/app: `guide/mobile-api-usage.md`
- Vận hành scale: `guide/scale-ops.md`

> Quy ước: điểm quan trọng phát sinh → ghi ngay vào tài liệu phát triển + hướng dẫn sử dụng.
