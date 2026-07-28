# ĐỊA CHỈ MỚI 2025 — Bảng tra cứu & Kiến trúc dữ liệu

> Cải cách hành chính 2025 (Nghị quyết 202/2025/QH15, hiệu lực 01/07/2025): **bỏ cấp huyện**, sáp nhập **63 → 34 tỉnh/thành**. Tài liệu này mô tả bộ bảng tra cứu `admin_*_2025` và pipeline suy diễn địa chỉ mới cho `public_projects`.

## 1. Nguồn dữ liệu

| Thành phần | Nguồn | Đường dẫn thô lưu trong repo |
|---|---|---|
| 34 tỉnh/thành mới | `truongqv12/tinh-xa-sapnhap` (`provinces.json`) | `database/seeders/data/admin_2025/new_provinces.json` |
| Xã/phường mới (3.321) thuộc tỉnh mới | `truongqv12/tinh-xa-sapnhap` (`wards.json`) | `database/seeders/data/admin_2025/new_wards.json` |
| Quận/huyện CŨ → xã/phường mới + tỉnh mới (3.321 dòng, trích Nghị quyết) | `tuongnguyen913/API_SapNhapTinhThanh_VietNam` (`sap_nhap_2025/file1_full_content.json`) | `database/seeders/data/admin_2025/old_district_to_new_ward.json` |
| Map tỉnh CŨ (63) → tỉnh mới (34) | Tổng hợp thủ công theo NQ202/2025 | `database/seeders/data/admin_2025/old_province_to_new.json` |

> Lưu ý: repo `phamhongduc-dev/dvhcvn` chỉ là web tra cứu (không có file dữ liệu tải được); `thanglequoc/vietnamese-provinces-database` chỉ có đơn vị MỚI, không có ánh xạ cũ→mới. Do đó chốt bộ 3 nguồn trên.
>
> **Caveat quan trọng:** trường `Mã tỉnh (BNV)` trong `file1` thực chất là **số thứ tự 01..34**, KHÔNG phải mã BNV thật. Seeder canonical hoá lại theo TÊN tỉnh (đã chuẩn hoá) về `admin_provinces_2025` để mọi bảng dùng chung mã BNV thật (vd Hà Nội=01, Nghệ An=40, Đà Nẵng=48, HCM=79).

## 2. Bảng tra cứu (migration `2026_07_28_000001_create_admin_2025_lookup_tables`)

- **`admin_provinces_2025`** — 34 tỉnh/thành mới: `code` (BNV, unique), `full_name`, `name_norm`.
- **`admin_wards_2025`** — 3.321 xã/phường mới: `code` (unique), `full_name`, `name_norm`, `province_code`, `province_name`. Không còn cấp huyện.
- **`admin_old_provinces_2025`** — tỉnh cũ (63 + vài alias như HCM/TPHCM/Sài Gòn): `old_name`, `old_name_norm`, `new_province_code`, `new_province_name`.
- **`admin_old_to_new`** — 3.321 dòng: `old_district_name`, `old_district_norm`, `new_province_code`, `new_province_name`, `new_ward_code`, `new_ward_name`, `new_ward_norm`.

Migration idempotent (`Schema::hasTable`). Seeder `Admin2025Seeder` idempotent (upsert theo `code` cho tỉnh/xã; truncate-insert cho 2 bảng ánh xạ).

## 3. Chuẩn hoá so khớp (`AddressResolver::normalize`)

Bỏ dấu tiếng Việt → hạ thường → thay ký tự không phải chữ/số bằng khoảng trắng → bỏ tiền tố hành chính ở đầu (`thành phố/thị xã/thị trấn/tỉnh/quận/huyện/phường/xã` + viết tắt `tp/tx/tt/q/h/p/t`). Ví dụ: `"Q.7" → "7"`, `"TP. Hồ Chí Minh" → "ho chi minh"`, `"Phường Ba Đình" → "ba dinh"`.

## 4. Ghi kết quả

Command `projects:resolve-new-address` ghi vào `public_projects.metadata_json` (KHÔNG sửa cột địa chỉ gốc):

```json
{
  "address_new": {
    "province_new": "Thành phố Hà Nội",
    "ward_new": "Phường Hoàng Mai",
    "full_new": "Phường Hoàng Mai, Thành phố Hà Nội",
    "matched_by": "district_then_name"
  },
  "address_new_confidence": "high"
}
```

## 5. Cập nhật lại dữ liệu

```bash
php artisan migrate
php artisan db:seed --class=Admin2025Seeder
php artisan projects:resolve-new-address --all
```
