# Tính năng: Suy diễn ĐỊA CHỈ MỚI 2025 cho danh mục dự án

## Mục tiêu
Chuyển địa chỉ CŨ (ward/district/province theo mô hình 63 tỉnh + cấp huyện) của `public_projects` sang **ĐỊA CHỈ MỚI 2025** (34 tỉnh, không cấp huyện) và lưu song song trong `metadata_json` — phục vụ hiển thị/tìm kiếm theo địa giới mới mà không phá dữ liệu gốc.

## Thành phần
- **Service** `App\Services\Address\AddressResolver::resolveNew($ward, $district, $province)` → `{province_new, ward_new, matched_by, confidence}`.
- **Command** `php artisan projects:resolve-new-address {--all} {--limit=}` → duyệt dự án có địa chỉ, ghi `metadata_json.address_new` + `address_new_confidence`. Idempotent, dùng `saveQuietly()`.
- Bảng tra cứu `admin_*_2025` (xem `03_data_arch/ADDRESS-2025.md`).

## Thuật toán khớp (ưu tiên từ cao xuống thấp)
1. **Chốt khung tỉnh** từ tỉnh cũ (map 63→34) — dùng để loại trùng tên quận/huyện giữa các tỉnh.
2. **`ward_name_exact`** (high): tên xã cũ khớp trực tiếp một xã/phường mới trong tỉnh mới.
3. **`district_unique`** (high): quận/huyện cũ chỉ ánh xạ tới đúng 1 xã mới.
4. **`district_then_name`** (high): quận/huyện cũ ra nhiều xã mới, nhưng khớp thêm được theo tên ward/district cũ.
5. **`district_ambiguous`** / **`province_only`** (medium): chỉ chốt được tỉnh mới, chưa chốt được xã.
6. **`none`** (low): không khớp.

Xử lý ca dữ liệu bẩn: nhiều dự án để cấp quận/huyện cũ trong cột `province` (vd `"Q.7"`, `"TP. Thủ Đức"`) — resolver có bước fallback coi `province` như quận/huyện cũ.

## Kết quả thực tế (chạy trên DB `x2bms`)
- Nạp: **34 tỉnh**, **3.321 xã/phường mới**, **74 dòng** map tỉnh cũ→mới, **3.321 dòng** quận/huyện cũ→xã mới.
- `projects:resolve-new-address --all` trên **1.023 dự án**: **high 868 · medium 155 · low 0**.

Ví dụ:
| Cũ | Mới | matched_by |
|---|---|---|
| Phường Đại Kim, Quận Hoàng Mai, Hà Nội | Phường Hoàng Mai, Thành phố Hà Nội | district_then_name |
| Phường Lái Thiêu, TP Thuận An, Bình Dương | Phường Lái Thiêu, Thành phố Hồ Chí Minh | ward_name_exact |
| Phường Mỹ Xuân, TP Phú Mỹ, Bà Rịa Vũng Tàu | Phường Phú Mỹ, Thành phố Hồ Chí Minh | district_then_name |
| Xã Bình Minh, Huyện Kiến Xương, Thái Bình | Xã Kiến Xương, Tỉnh Hưng Yên | district_then_name |
| (trống), Q.7 | Thành phố Hồ Chí Minh (chưa rõ phường) | district_ambiguous |

## Giới hạn / cần chủ dự án quyết
- Dataset `old_district_to_new_ward` chỉ ánh xạ **quận/huyện cũ → xã mới** (không có bảng xã-cũ → xã-mới đầy đủ). Khi một quận/huyện tách thành nhiều xã mới mà tên xã cũ không trùng tên xã mới → chỉ ra được **tỉnh** (medium). Các ca này cần đối chiếu thủ công.
- Alias tỉnh (HCM/TPHCM/Sài Gòn…) đã thêm; nếu phát sinh biến thể mới của cột địa chỉ gốc thì bổ sung vào `old_province_to_new.json`.
