# Kế hoạch TEST NGHIỆP VỤ — Engine tính phí (C / Phase 2)

> **Đối tượng đọc:** kế toán/BQL nghiệm thu + dev QA. · **Cập nhật:** 2026-08-04 · **Trạng thái engine:** P2.1 (phí quản lý) đã có khung + test; P2.2–P2.5 chưa.
> Đọc kèm: `docs/BILLING_FEE_ENGINE_PHASE2_PLAN.md` (kế hoạch), `docs/BILLING_OWNER_DECISIONS_20260731.md` (D1–D9).

## 1. Nguyên tắc nghiệm thu (đọc trước)
Engine tính tiền hàng nghìn căn — sai một công thức là sai tiền thật. Nên nghiệm thu theo 5 nguyên tắc:
1. **Số kế toán import là CHUẨN VÀNG.** Engine tính lại cùng kỳ, so với số kế toán; lệch ở đâu là bug ở đó. (Không tự đối chiếu engine với chính nó.)
2. **Engine KHÔNG được phát hành.** Kết quả vào `approval_status = pending`; người có quyền mới duyệt→phát hành (D1). Test phải xác nhận điều này.
3. **Chạy song song ≥ 2 kỳ** (engine dry-run + kế toán import) trước khi engine thành nguồn chính. 2 kỳ đó **kế toán vẫn là số chuẩn**.
4. **Bật theo family, không bật cả loạt:** `management` trước (dễ đối chiếu), `electricity`/`water` sau cùng (khó nhất).
5. **Mọi dòng lệch thật phải GIẢI THÍCH được** trước khi bật engine cho kỳ đó — không "gần đúng là được".

## 2. Điều kiện tiên quyết để test được (nếu thiếu, chưa test golden được)
- [ ] **`fee_rates` cấu hình đúng** cho tenant + kỳ (đơn giá/m², hiệu lực `effective_from/to`). ⚠️ Import kế toán (HPO) **chỉ nạp `statement_lines`, KHÔNG tạo `fee_rates`** → phải khai giá cho tenant demo trước, khớp đơn giá kế toán dùng.
- [ ] **`fee_scope_assignments`** (nếu áp giá theo tòa/căn khác nhau).
- [ ] Số kế toán kỳ cần đối chiếu đã import (vd HPO `2026-05`), ≥ 200 căn.
- [ ] `apartments.area_sqm` đầy đủ (thiếu diện tích → engine bỏ qua, không ra dòng).

## 3. Hai lớp test

### 3A. Test TỰ ĐỘNG (dev — đã có)
`tests/Feature/BillingEngineManagementTest.php` (3 ca, chạy: `php artisan test --filter BillingEngineManagementTest`):
| Ca | Kiểm |
|---|---|
| generator thuần | `round_half_up(area×giá)`, VAT 8%, trả `null` khi thiếu giá/diện tích, snapshot có công thức |
| runner dry-run | tổng đúng, **KHÔNG ghi** DB |
| runner commit | ghi `approval_status=pending`, line `source=engine`, chạy lại **không nhân đôi** (idempotent) |

Mỗi generator family mới PHẢI kèm test thuần tương tự (công thức là code có test, không eval chuỗi).

### 3B. Test NGHIỆP VỤ — đối soát "số vàng" (kế toán + QA)
Quy trình cho MỖI family, MỖI kỳ:

**Bước 1 — Dry-run engine (không ghi):**
```bash
php artisan billing:run <building_id> <period_code> --family=management        # dry-run
```
In ra: đơn giá áp dụng · số căn · TỔNG engine tính.

**Bước 2 — So TỔNG với số kế toán** cùng kỳ/tòa/family (từ bảng kê đã import).

**Bước 3 — Phân loại lệch theo 3 mức:**
| Mức | Nghĩa | Xử lý |
|---|---|---|
| Khớp tuyệt đối | engine = kế toán | ✅ |
| Lệch ≤ 1 đồng/căn | do làm tròn | ✅ chấp nhận, ghi chú |
| **Lệch thật** | khác công thức/giá/diện tích/prorate | ❌ **phải tìm nguyên nhân + giải thích** trước khi bật |

**Bước 4 — Ghi biên bản đối soát** (kỳ, family, số căn, tổng engine, tổng kế toán, số căn lệch, nguyên nhân từng căn lệch).

**Bước 5 — Chỉ khi đạt tối thiểu 2 kỳ liên tiếp** (lệch thật = 0 hoặc đã giải thích + owner duyệt) → mới `--commit` cho kỳ mới và để engine thành nguồn.

> ✅ **Công cụ đối soát ĐÃ CÓ:** `billing:reconcile-engine <building> <period> [--family=]` — engine
> dry-run so **từng dòng** với `statement_lines` kế toán (source≠engine) theo khóa (fee_type, subject,
> service_period_start), xuất đúng 3 mức: khớp tuyệt đối / lệch ≤1đ / lệch thật (+ liệt kê căn lệch).
> Đây là công cụ thực thi Bước 1–3.

**Dữ liệu test có sẵn (trên tòa HPO):** `php artisan db:seed --class=FeeEngineDemoSeeder --force` dựng
3 căn ENG-01/02/03 (area 100/75,5/50) + giá 15.000/m² + số kế toán "vàng" kỳ `2026-08` (2 căn khớp,
căn ENG-03 lệch cố ý 150.000). Chạy `billing:reconcile-engine <hpo_building> 2026-08` → thấy **2 khớp
+ 1 lệch thật** (minh hoạ công cụ bắt đúng dòng sai).

## 4. Kịch bản test P2.1 — Phí quản lý (bảng ca cụ thể)
Đơn giá ví dụ 15.000 đ/m², VAT 0%. (Điều chỉnh theo giá thật của tenant.)

| # | Tình huống | Input | Kỳ vọng |
|---|---|---|---|
| 1 | Căn thường | area 100 m² | dòng phí = **1.500.000 đ**, `source=engine`, snapshot đủ |
| 2 | Làm tròn | area 100,5 · giá 15.001 | **1.507.601 đ** (half-up, số nguyên đồng) |
| 3 | VAT | area 100 · giá 10.000 · VAT 8% | base 1.000.000 + VAT 80.000 = **1.080.000 đ** |
| 4 | Thiếu diện tích | area 0/null | **KHÔNG ra dòng** (không ghi 0đ) |
| 5 | Thiếu giá | không có `fee_rate` hiệu lực | runner báo `no_active_rate`, không ghi |
| 6 | Idempotent | chạy `--commit` 2 lần cùng kỳ | **không nhân đôi** dòng/bảng kê |
| 7 | Không phát hành | sau `--commit` | statement `approval_status = pending` (chưa published) |
| 8 | Cô lập tenant | 2 tenant có tòa | engine chỉ tính căn của tòa/tenant chỉ định |
| 9 | Prorate (khung) | nhận nhà giữa kỳ, `factor < 1` | tiền theo tỉ lệ ngày ở trong kỳ *(P2.1 có tham số `factor`; nối nguồn ngày vào-ra là bước sau)* |
| 10 | Đối chiếu vàng | kỳ HPO đã import | TỔNG engine = TỔNG kế toán family management (sau khi khai `fee_rates` đúng) |

Ca 1–8 test tự động được (một phần đã có ở §3A); ca 9–10 là nghiệp vụ/đối soát.

## 5. Kịch bản cho P2.2–P2.5 (khi làm tới — khung nghiệm thu)
- **P2.2 Xe:** mỗi xe MỘT dòng (`subject_id`=xe); bậc số lượng (xe thứ 2 giá khác); prorate đăng ký/hủy giữa kỳ; xe suspended không tính. Đối chiếu số kế toán xe.
- **P2.3 Điện/nước (khó nhất):** `consumption = current − previous`; **bậc thang lũy tiến**; `actual` vs `estimated`; điều chỉnh khi kỳ trước đọc sai; đổi đồng hồ giữa kỳ; VAT + phí BVMT nước. Test bậc thang bằng các mức tiêu thụ ranh giới bậc.
- **P2.4 Per-use:** nguồn đếm là bản ghi sử dụng thật (đặt tiện ích/gửi xe vãng lai) — định nghĩa nguồn trước.
- **P2.5 Phạt/lãi:** **chốt chính sách trước** (gốc tính, từ ngày nào, lãi suất, trần, miễn lần đầu) — bài toán nghiệp vụ, không phải code.

## 6. Checklist BẬT engine cho một family (không bật khi còn ô trống)
- [ ] Generator family có test thuần (công thức) — pass.
- [ ] `fee_rates`/scope khai đúng cho tenant + kỳ.
- [ ] Dry-run ≥ 2 kỳ, đối soát vs kế toán: lệch thật = 0 **hoặc** đã giải thích + owner duyệt.
- [ ] Xác nhận engine ghi `pending`, không tự phát hành; duyệt qua maker-checker (D1).
- [ ] Số nguyên đồng, làm tròn half-up từng dòng (D7).
- [ ] Idempotent: chạy lại không nhân đôi.
- [ ] Biên bản đối soát lưu lại (kỳ, tổng, số căn lệch, nguyên nhân).

## 7. Trạng thái công cụ / việc còn lại
1. ✅ **`billing:reconcile-engine`** — ĐÃ build (§3B). Test `BillingEngineReconcileTest`.
2. ✅ **Seed test trên HPO** — `FeeEngineDemoSeeder` (3 căn có area + fee_rate + số vàng kỳ 2026-08).
3. ⬜ Seed `fee_rates` khớp đơn giá kế toán cho **kỳ import thật** HPO `2026-05` (1314 căn) — cần điền
   `area_sqm` cho căn import + đơn giá khớp, để đối chiếu quy mô thật (hiện căn import chưa có area).
4. ⬜ (Tùy chọn) màn `/admin` xem biên bản đối soát engine vs kế toán theo kỳ.
5. ⬜ Generator P2.2–P2.5 (mỗi cái kèm test thuần + đối soát vàng như P2.1).
