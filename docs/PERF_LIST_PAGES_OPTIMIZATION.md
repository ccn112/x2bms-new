# Tối ưu hiệu năng — trang danh sách Căn hộ & Cư dân

> Ngày: 2026-07-18. Vấn đề: 2 trang "Hồ sơ căn hộ" và "Danh sách cư dân" tải ~2.8–3.4s.

## Nguyên nhân chính: N+1 query
Trang **Hồ sơ căn hộ** (`app/Filament/Pages/ApartmentDirectory.php`) mỗi dòng chạy 3 query riêng:
- `holderFor()` — chủ thể hiện tại (1 query/dòng)
- `resident_count` — đếm cư dân (1 query/dòng)
- `debt` — tổng công nợ quá hạn (1 query/dòng)

→ 25 dòng ≈ 75 query, cộng KPI (4×count + pluck toàn bộ id + đếm nợ) ≈ **110 query/lần tải**.

## Đã sửa
1. **`tableQuery()`** enrich sẵn (1 query cho cả trang):
   - `withCount('apartmentRelations as resident_count')`
   - `addSelect(['debt_sum' => subquery SUM(amount) where is_overdue])`
   - eager-load `apartmentRelations` (đã sắp theo `FIELD(role,...)`) + `resident:id,full_name`.
2. `holderFor()` / `resident_count` / `debt` / `cardMeta()` đọc dữ liệu đã nạp (không query/dòng).
3. **KPI**: gộp count theo `status` thành 1 query `groupBy`; đếm căn có nợ bằng subquery `whereIn(select id)` thay vì `pluck` toàn bộ id về PHP.
4. **Model `Apartment`**: thêm quan hệ `apartmentRelations()` + `debts()`.
5. Trang **Cư dân** (`ResidentDirectory.php`): bảng vốn đã eager-load (`apartmentRelations.apartment.floor`, `building`) — chỉ gộp KPI 5 count → 2 query.

## Kết quả đo (DB local, 1.268 căn, trang 25 dòng)
| | Query/lần tải | DB time |
|---|---|---|
| Trước | ~110 | 55 ms |
| Sau | **8** | **21 ms** |

Query count giảm mạnh là điểm quan trọng nhất: mỗi query còn kèm overhead round-trip + hydrate Eloquent, nên trên DB thật (có độ trễ mạng) chênh lệch wall-clock lớn hơn con số DB-time.

## Lưu ý đo lường
- **KHÔNG** đo wall-clock bằng `php artisan serve` (đơn luồng — serialize mọi request asset/Livewire song song → TTFB ảo tới hàng chục giây). Đo trên Herd/production đa worker.

## Font — đã self-host (2026-07-18)
Trước: 3 panel provider chèn `<link>` tới `https://fonts.bunny.net/css?family=inter|plus-jakarta-sans` → render-blocking + request ngoài (DNS/TLS).
Đã sửa:
- Tải woff2 về `public/fonts/` + sinh `public/fonts/x2-fonts.css` (đường dẫn tương đối, giữ `unicode-range` để trình duyệt chỉ tải subset cần — latin + vietnamese).
- 3 provider (`Admin/Hq/Sa`) trỏ `<link href="/fonts/x2-fonts.css">` (same-origin, cache được).
- **Verify (browser)**: `bunny.net` requests = **0**; font tải cục bộ; body render đúng `Inter`.
- (Tùy chọn) có thể xoá subset không dùng (greek/cyrillic) trong `public/fonts` để giảm dung lượng repo — hiện giữ đủ, browser không fetch phần thừa.

## Flash shell khi reload (sidebar ẩn 1 lúc) — đã sửa (2026-07-18)
Nguyên nhân: class `fi-sidebar-open` do Alpine gắn (`x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"`, state ở `localStorage.isOpenDesktop`). Trước khi Alpine boot + `theme.css` (viteTheme = stylesheet ngoài) tải xong → shell chưa được layout → chớp trắng, sidebar/nội dung xuất hiện trễ.

Đã sửa (`resources/views/filament/hooks/critical-shell.blade.php`, inject ở `HEAD_START` của Admin panel):
- **Critical CSS inline** dựng sẵn khung shell ngay first paint (sidebar `position:fixed` + nền navy `#0b2146` + full height; body offset theo sidebar width) — không phụ thuộc theme.css tải trễ.
- **Script no-flash**: đọc `localStorage.isOpenDesktop` (mặc định mở), gắn `html.x2-sidebar-preopen` **trước paint** để offset/độ rộng đúng trạng thái đã lưu; **gỡ khi `alpine:initialized`** để nút thu gọn hoạt động bình thường sau đó.
- Verify (browser): critical CSS có trong `<head>`; sidebar fixed, navy, 320px; content offset 320px đúng ngay; class preopen được gỡ sau boot. `[x-cloak]` cũng inline để dropdown không nháy.

> Có thể áp cùng render hook cho panel `/hq` và `/sa` nếu chúng cũng bị flash (hiện mới áp cho `/admin`).

## Tối ưu thứ cấp còn lại
- Bật `config:cache route:cache view:cache` ở production.
- Index đã thêm: `resident_apartment_relations(apartment_id, role)`, `debts(apartment_id, is_overdue)` (migration `2026_07_18_000003`).
