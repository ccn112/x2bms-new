# X2-BMS · ENGINE TÍNH PHÍ — KẾ HOẠCH PHASE 2

> Chốt 2026-07-31 (D9): **engine tính phí hoãn sang Phase 2**. Giai đoạn đầu kế toán
> import (`BILLING_IMPORT_SPEC_20260731.md`). File này lưu kế hoạch để không mất ngữ cảnh.
> Điều kiện tiên quyết: D1–D9 ở `BILLING_OWNER_DECISIONS_20260731.md` đã chạy được.

## 1. Vì sao hoãn là quyết định đúng

Engine tính phí là chỗ **đắt nhất và dễ sai nhất** của billing: sai một công thức là sai
tiền hàng nghìn căn, và sai tiền thì không có cách "sửa UI" nào cứu được. Import trước
cho ba lợi ích thật:

1. **Có dữ liệu thật để đối chiếu.** Khi viết engine, bộ số kế toán đã import trở thành
   **bộ test vàng**: engine tính lại cùng kỳ, so với số kế toán, lệch ở đâu là bug ở đó.
   Không có bộ này thì engine chỉ tự đối chiếu với chính nó.
2. **Chốt được vốn từ trước khi tự động hóa.** Family, tài sản, kỳ dịch vụ, thứ tự ưu
   tiên — import ép phải chốt hết. Engine viết sau thì các khái niệm đã ổn định.
3. **Vận hành không bị chặn.** BQL thu được tiền ngay, không chờ engine.

## 2. Hiện trạng — những gì đã có sẵn cho engine

| Có | Path / bảng | Dùng được ngay? |
|---|---|---|
| Danh mục phí + đơn vị + VAT + công thức dạng chữ | `fee_types` (`unit`, `vat_percent`, `formula_text`, `is_complex`) | ✔ đọc được |
| Biểu giá + hiệu lực theo thời gian | `fee_rates` (`amount`, `effective_from/to`) | ✔ |
| Phạm vi áp giá | `fee_scope_assignments` (`project|building|apartment`) | ✔ |
| Công thức + version | `fee_formulas.expression`, `fee_formula_versions` | ⚠️ **chỉ là chuỗi, không có evaluator** |
| Chỉ số đồng hồ | `meters`, `meter_readings` (`previous/current/consumption`) | ✔ nhưng **chưa nối vào dòng phí** |
| Xe + phí tháng | `vehicles.monthly_fee`, `valid_to`, `status` | ✔ nhưng **chưa nối** |
| Vòng đời chạy kỳ | `billing_runs` (+ `approval_status`), `billing_run_items` | ⚠️ **có bảng, không có runner** |
| Diện tích căn | `apartments` | ✔ |

**Kết luận:** phần khó nhất còn thiếu là **evaluator** và **runner**. Dữ liệu đầu vào gần
như đủ.

## 3. Phạm vi Phase 2 — 5 bài toán tính phí

Xếp theo độ khó tăng dần, làm đúng thứ tự này:

### P2.1 — Phí quản lý theo diện tích (dễ nhất, làm trước)
`amount = round_half_up(area × unit_price)` + VAT. Cần: version giá theo thời gian,
**prorate** khi nhận nhà/chuyển đi giữa kỳ, miễn giảm, phân biệt loại căn
(căn hộ/văn phòng/thương mại). Đây là bài toán chuẩn để dựng khung engine.

### P2.2 — Phí phương tiện
`amount = monthly_fee × số tháng` theo `vehicles`. Cần: chu kỳ, `valid_to`,
suspension (xe tạm ngưng), prorate khi đăng ký/hủy giữa kỳ, **bậc số lượng xe**
(`formula_text` hiện ghi "Theo bậc số lượng xe" — xe thứ 2 giá khác xe thứ 1).
Ràng buộc D6: mỗi xe ra **một dòng phí riêng** có `subject_id` trỏ tới xe.

### P2.3 — Điện / nước theo chỉ số
`consumption = current − previous`, rồi × biểu giá. Cần: **bậc thang** (lũy tiến),
`actual` vs `estimated` (đồng hồ không đọc được), điều chỉnh khi đọc sai kỳ trước,
đổi đồng hồ giữa kỳ, VAT + phí BVMT nước. Mỗi đồng hồ ra một dòng có `subject_id`.
**Đây là chỗ dễ sai nhất** — bậc thang + kỳ lệch ngày.

### P2.4 — Phí theo lần dùng / phát sinh
`frequency = per_use` (9 fee type hiện có). Nguồn là bản ghi sử dụng thật (đặt tiện ích,
gửi xe vãng lai). Cần định nghĩa nguồn đếm trước khi tính.

### P2.5 — Phạt / lãi chậm nộp
Chưa có bất kỳ thứ gì trong DB. **Cần chốt nghiệp vụ trước khi thiết kế**: tính trên gốc
nào, từ ngày nào, lãi suất, có trần không, có miễn lần đầu không. Là bài toán **chính
sách**, không phải bài toán code — để cuối.

## 4. Kiến trúc engine đề xuất

```text
BillingRunner (orchestrator, theo billing_run)
  → ChargeGenerator theo family (5 generator, mỗi cái một class)
      ManagementFeeGenerator
      VehicleFeeGenerator
      ElectricityFeeGenerator
      WaterFeeGenerator
      OtherFeeGenerator
  → mỗi generator trả về list ChargeDraft (không ghi DB)
  → BillingRunner ghi statement + statement_lines trong 1 transaction
```

**Nguyên tắc bắt buộc:**

1. **Generator là hàm thuần** — nhận input đã đọc sẵn, trả draft, **không tự query, không
   tự ghi**. Có vậy mới test được từng công thức mà không cần dựng cả DB.
2. **Snapshot công thức vào dòng phí.** Mỗi dòng lưu `rate_version_id` +
   `calculation_snapshot` (json: input, công thức, từng bước). Giá đổi về sau **không được
   làm đổi số của kỳ đã tính**. Đây cũng chính là dữ liệu cho màn "vì sao hóa đơn cao".
3. **Không tự ý evaluate chuỗi.** `fee_formulas.expression` là chuỗi tự do — **không
   dùng `eval`, không dùng expression-parser tổng quát**. Mỗi công thức là một class có
   test. `formula_text` chỉ để **hiển thị cho người đọc**. Đây là ranh giới an toàn:
   công thức là code có review, không phải dữ liệu người dùng nhập.
4. **Chạy kỳ không được ghi thẳng ra `published`.** Kết quả engine đi vào
   `approval_status = pending`, y như import (D1). Engine không có quyền phát hành.
5. **Idempotent theo `billing_run`.** Chạy lại cùng kỳ → cập nhật, không nhân đôi. Cùng
   khóa upsert với import: `(statement_id, fee_type_id, subject_type, subject_id,
   service_period_start)`.
6. **Làm tròn từng dòng, half-up, số nguyên đồng** (D7). Không giữ số lẻ trung gian.

## 5. Cách nghiệm thu — dùng số kế toán làm bộ test vàng

Đây là lý do chính để import trước:

1. Chọn 1 kỳ đã có số kế toán import (vd `2026-07`), ≥ 200 căn.
2. Chạy engine ở **chế độ dry-run** trên cùng kỳ, ghi ra bảng đối chiếu, **không ghi vào
   `statement_lines`**.
3. Báo cáo lệch theo 3 mức: khớp tuyệt đối / lệch ≤ 1 đồng (làm tròn) / lệch thật.
4. **Mọi dòng lệch thật phải giải thích được** trước khi bật engine cho kỳ nào.
5. Chạy song song (engine dry-run + kế toán import) **tối thiểu 2 kỳ** trước khi để engine
   thành nguồn chính. Kế toán vẫn là nguồn chuẩn trong 2 kỳ đó.
6. Chuyển đổi **theo family, không theo toàn bộ**: bật `management` trước (dễ nhất, dễ
   đối chiếu), `electricity`/`water` sau cùng.

## 6. Điều kiện tiên quyết — không được bắt đầu Phase 2 khi còn thiếu

- [ ] Backfill `fee_category` → 5 family xong (D2)
- [ ] `statement_lines` có `subject_type`/`subject_id` + `service_period_start/end` (D3, D6)
- [ ] Import kế toán chạy thật ≥ 2 kỳ, có dữ liệu để đối chiếu (D9)
- [ ] Duyệt + phát hành có maker-checker, 3 đường vòng đã đóng (D1)
- [ ] Phân bổ theo dòng phí đã chạy đúng (D3)
- [ ] Tiền đã là số nguyên đồng toàn tuyến (D7)
- [ ] Có bộ test cho invariant tiền (`Σ allocations ≤ payment.amount`,
      `statements.paid_amount = Σ lines.paid_amount`)

Thiếu ô cuối là nguy hiểm nhất: engine sinh khối lượng dòng phí lớn hơn import nhiều lần,
nên **mọi lỗ hổng invariant sẽ được nhân lên theo quy mô**.

## 7. Việc phải chốt nghiệp vụ trước khi code Phase 2

1. **Prorate** — nhận nhà/chuyển đi giữa kỳ tính theo ngày hay theo tháng tròn?
2. **Bậc số lượng xe** — bậc theo căn hộ hay theo hộ gia đình? Xe của khách thuê tính vào
   bậc của căn không?
3. **Bậc thang điện/nước** — theo biểu giá nhà nước hay biểu riêng của dự án? Kỳ lệch
   ngày (đọc số ngày 25) thì bậc chia theo tỷ lệ ngày không?
4. **Đồng hồ không đọc được** — tính tạm theo bình quân mấy kỳ? Kỳ sau bù trừ thế nào?
5. **Phạt/lãi chậm nộp** — toàn bộ P2.5.
6. **VAT** — VAT trên từng dòng hay trên tổng? (D7 đã chốt làm tròn từng dòng ⇒ nên là
   từng dòng, nhưng cần xác nhận đúng quy định kế toán.)
