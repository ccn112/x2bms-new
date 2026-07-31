# X2-BMS · MẪU IMPORT KHOẢN PHÍ (kế toán) — spec chốt 2026-07-31

> Quyết định: **engine tính phí để Phase 2** (`BILLING_FEE_ENGINE_PHASE2_PLAN.md`).
> Thời gian đầu **kế toán import** khoản phí. File này là hợp đồng của mẫu import đó.
> Bám `BILLING_OWNER_DECISIONS_20260731.md` (D1–D9).

## 1. Đặt trên hạ tầng import đã có, không làm mới

Repo đã có engine import staging dùng chung — **phải dùng lại**:

| Thành phần | Path |
|---|---|
| Hợp đồng profile | `app/Support/Import/ImportProfile.php` |
| Mô tả cột (có `example` để sinh file mẫu) | `app/Support/Import/ImportColumnSpec.php` |
| Engine staging + validate | `app/Support/Import/StagingImporter.php` |
| Chuẩn hóa giá trị | `app/Support/Import/RowNormalizers.php` |
| Đăng ký loại import | `app/Support/Import/ImportProfileRegistry.php` |
| Ghi nền bất đồng bộ | `app/Jobs/CommitImportBatchJob.php` |
| Bảng staging | `import_batches` · `import_batch_rows` |
| Màn lịch sử | `app/Filament/Pages/ImportHistory.php` |
| Mẫu để noi theo | `app/Support/Import/Profiles/ResidentImportProfile.php` + `app/Filament/Concerns/ImportsResidentsFromExcel.php` |

**Việc cần làm:**

1. `app/Support/Import/Profiles/BillingChargeImportProfile.php` — `importType() = 'billing_charges'`, `rowType() = 'billing_charge'`.
2. Đăng ký `'billing_charges'` trong `ImportProfileRegistry::for()`.
3. Trait `ImportsBillingChargesFromExcel` (theo khuôn `ImportsResidentsFromExcel`) — nút **Tải file mẫu (.xlsx)** sinh từ đúng `columns()`, nên **mẫu luôn khớp code**, không có file mẫu chết trong repo.
4. `RowNormalizers::money()` — **mới**, xem §4.
5. Gắn vào màn kế toán ở `/admin` (đề xuất: `Pages/BillingChargeImport.php`, nhóm "Hóa đơn & thanh toán").

File CSV tham khảo cho anh xem trước: `docs/templates/mau_import_khoan_phi.csv`. Nó **chỉ để hình dung** — bản chính thức là `.xlsx` sinh từ profile.

## 2. Cột của mẫu

Ba cấp phí theo D6: **Fee-family › Fee-type › Tài sản**. Family **không có trong file** —
suy ra từ `fee_type_code`, để kế toán không phải nhớ.

| # | Nhãn cột | key | Bắt buộc | Ví dụ | Ghi chú |
|---|---|---|---|---|---|
| 1 | Mã căn hộ | `apartment_code` | ✔ | `A-0205` | Phải thuộc dự án đang chọn |
| 2 | Kỳ phí | `period_code` | ✔ | `2026-07` | Bảng kê thuộc kỳ này |
| 3 | Mã loại phí | `fee_type_code` | ✔ | `OTO` | `fee_types.code`; suy ra family |
| 4 | Tài sản | `subject_ref` | điều kiện | `51K-838888` | BKS xe / mã đồng hồ — xem §3 |
| 5 | Tên khoản hiện cho cư dân | `label` | | `Phí gửi ô tô 51K-838888` | Trống → lấy `fee_types.name` |
| 6 | Kỳ dịch vụ từ | `service_period_start` | | `2026-04-01` | Trống → đầu kỳ ở cột 2 |
| 7 | Kỳ dịch vụ đến | `service_period_end` | | `2026-04-30` | Trống → cuối kỳ ở cột 2 |
| 8 | Chỉ số đầu | `previous_reading` | | `1250` | Chỉ điện/nước |
| 9 | Chỉ số cuối | `current_reading` | | `1398` | Chỉ điện/nước |
| 10 | Số lượng | `quantity` | | `148` | m² · m³ · kWh · số xe |
| 11 | Đơn giá | `unit_price` | | `3500` | Số nguyên đồng |
| 12 | Thành tiền | `amount` | ✔ | `518000` | **Số nguyên đồng** — số của kế toán là số chuẩn |
| 13 | VAT % | `vat_percent` | | `8` | Trống → `fee_types.vat_percent` |
| 14 | Miễn giảm | `discount` | | `0` | Số nguyên đồng |
| 15 | Hạn thanh toán | `due_date` | | `2026-07-15` | Trống → `billing_periods.due_date` |
| 16 | Ghi chú | `note` | | | |

**Vì sao `amount` bắt buộc mà `quantity`/`unit_price` không:** engine tính phí là Phase 2.
Giai đoạn này **số của kế toán là số chuẩn**, hệ thống không tự tính lại.
`quantity`/`unit_price`/chỉ số đầu-cuối là để **hiện cho cư dân hiểu vì sao** (màn "vì sao
hóa đơn tháng này cao") — có thì tốt, thiếu không chặn.

Nếu có đủ `quantity` + `unit_price` mà `round_half_up(quantity × unit_price) ≠ amount`:
**cảnh báo, không chặn** — kế toán có thể đã trừ miễn giảm hoặc làm tròn theo hợp đồng.
Ghi cảnh báo vào `RowIssue` để trưởng ban thấy khi duyệt.

**Nợ cũ dồn sang kỳ sau** (ví dụ D3: bảng kê tháng 4 chứa nợ điện tháng 3) → nhập thành
**dòng riêng**, cột 2 = `2026-04`, cột 6-7 = `2026-03-01`/`2026-03-31`. Đây là lý do
`service_period_start/end` phải tách khỏi `period_code`.

## 3. Cột "Tài sản" — khi nào bắt buộc

Đây là chiều D6 yêu cầu, hiện `statement_lines` **chưa có** (cần thêm
`subject_type`/`subject_id` — xem §6).

| `fee_types.unit` / family | Tài sản | Bắt buộc? | Khớp vào |
|---|---|---|---|
| `per_vehicle` (family `vehicle`) | Biển số xe | **✔ bắt buộc** | `vehicles.plate_no` trong đúng căn hộ |
| family `electricity` / `water` có đồng hồ | Mã đồng hồ | ✔ nếu căn có >1 đồng hồ cùng loại | `meters.code` trong đúng căn hộ |
| `per_sqm` / `per_unit` / `fixed` (family `management`, `other`) | — | không | Tài sản = chính căn hộ |

**Không khớp được thì CHẶN dòng, không đoán.** Thông báo phải cụ thể: *"BKS 51K-838888
không thuộc căn A-0205"*. Lý do: tài sản là thứ quyết định **tiền thừa vào ngăn nào**
(D6.3). Đoán sai = tiền vào ngăn của xe khác — sai tiền, không phải sai hiển thị.

Chuẩn hóa biển số: bỏ khoảng trắng, bỏ dấu `.`/`-`, in hoa (`51K-838888` ·
`51K 838888` · `51k838888` → `51K838888`) rồi mới so khớp.

## 4. Tiền là SỐ NGUYÊN ĐỒNG (D7)

`RowNormalizers::money()` — **cần viết mới**:

- Nhận: `518000` · `518.000` · `518,000` · `"518 000"` · `518000 đ` → **`518000` (int)**
- **Từ chối** mọi giá trị có phần lẻ khác 0: `518000.5` · `518.000,50` → `RowIssue` chặn
  dòng, thông báo *"Tiền đồng không có số lẻ"*
- Chấp nhận `.00`/`,00` (Excel hay xuất vậy) → cắt về nguyên
- Âm: chỉ cho ở `discount` và ở dòng điều chỉnh (`label` có tiền tố `[ĐC]`); `amount` âm ở
  dòng thường → chặn
- Trần: `amount ≤ 5.000.000.000` mỗi dòng (đồng ngưỡng với `payments/claim`)

Áp `money()` cho: `amount`, `unit_price`, `discount`.
**Không** áp cho: `quantity` (m²/m³/kWh có số lẻ), `vat_percent`, `previous_reading`,
`current_reading`.

Làm tròn: **từng dòng phí, half-up, tới đồng** (D7 đã chốt). Tổng bảng kê = cộng các dòng
đã tròn, để cư dân cộng tay luôn khớp.

## 5. Hành vi khi ghi (commitRow)

1. **Tìm/tạo `statements`** theo `(apartment_id, billing_period_id)`.
   `approval_status = 'pending'`, `published_at = NULL`. **Import KHÔNG BAO GIỜ tạo bảng
   kê đã phát hành** — D1 bắt buộc đi qua trưởng ban duyệt rồi mới phát hành.
2. **Tạo/cập nhật `statement_lines`**: `fee_type_id`, `fee_category` = **family** (D2),
   `subject_type`/`subject_id`, `service_period_start/end`, `quantity`, `unit_price`,
   `amount`, `paid_amount = 0`, `status = 'issued'`, `due_date`.
3. **`statements.total_amount` = Σ dòng** (projection, D3). Không nhập tổng bằng tay.
4. **Không chạm** `paid_amount`, `payment_allocations`, `receipts`. Import là nghĩa vụ,
   không phải tiền.
5. **Idempotent** — khóa upsert:
   `(statement_id, fee_type_id, subject_type, subject_id, service_period_start)`.
   Import lại cùng file → **cập nhật**, không nhân đôi. Đây là điều kiện để kế toán dám
   import lại khi sửa sai.
6. **Audit** mỗi dòng vào `audit_logs` với `subject_type`/`subject_id` đầy đủ (đừng lặp lại
   lỗi của `StatementApprovalQueue::audit()` — nó thiếu hai trường này nên không truy được
   bản ghi nào bị tác động).
7. **Hoàn tác theo lô**: cho phép **chỉ khi bảng kê còn `pending`**. Đã `published` thì
   không hoàn tác — phải dùng điều chỉnh/đảo khoản.

## 6. Điều kiện schema — CHẶN import

Ba thứ phải có trước khi profile này chạy đúng:

1. **`statement_lines.subject_type` / `subject_id`** (morph → `vehicles`, `meters`) — §3.
   Không có nó thì cột "Tài sản" không lưu được đi đâu.
2. **`statement_lines.service_period_start` / `service_period_end`** — cột 6-7. Không có
   thì nợ cũ dồn kỳ (ví dụ D3) không phân biệt được với phí kỳ hiện tại.
3. **Backfill `fee_category` sang 5 family** (D2) — vì bước 2 của §5 ghi family vào đó.
   Làm trước, nếu không import sẽ ghi lẫn hai bộ vốn từ.

Cả ba đều là **thêm cột / thêm dữ liệu**, không phá cột cũ, reversible.

## 7. Kiểm thử tối thiểu trước khi cho kế toán dùng

- Import 2 lần cùng file → số dòng và tổng **không đổi** (idempotency).
- `518000.50` → chặn, thông báo đúng.
- `518.000` và `518,000` → cùng ra `518000`.
- BKS không thuộc căn → chặn, thông báo nêu đúng BKS và mã căn.
- Căn có 2 đồng hồ điện, thiếu cột Tài sản → chặn.
- Dòng nợ cũ (`period_code=2026-04`, kỳ dịch vụ tháng 3) → lưu đúng, không gộp vào dòng
  tháng 4 cùng loại phí.
- Bảng kê sinh ra `approval_status = pending`, **cư dân gọi API không thấy** (D1).
- `statements.total_amount` = Σ `statement_lines.amount` sau mỗi lần import.
- Hoàn tác lô khi `pending` → sạch; khi `published` → bị từ chối.
