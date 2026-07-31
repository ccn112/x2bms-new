# X2-BMS · Công nợ — QUYẾT ĐỊNH CỦA CHỦ DỰ ÁN (chốt 2026-07-31)

> Văn bản chốt 6 câu hỏi treo ở `handoff/x2bms/X2_BMS_BILLING_DOMAIN_AND_PACKAGING_HANDOFF_20260730`
> (§7 của `09_REFERENCE/HANDOFF_HOA_DON_CONG_NO_20260730.md`) + 2 quyết định mới.
> Mọi thiết kế sau ngày này phải bám văn bản này; chỗ nào handoff 30/07 nói khác thì
> văn bản này thắng.

---

## D1. Cư dân chỉ thấy bảng kê ĐÃ PHÁT HÀNH

Chuỗi bắt buộc: **kế toán nhập → trưởng ban QL duyệt → phát hành**. Cư dân chỉ thấy
khoản phí đã đi hết chuỗi này.

Kéo theo (đều là việc CHƯA CÓ trong code hôm nay):

1. `GET /resident/statements` phải lọc `approval_status = published` **và**
   `published_at IS NOT NULL`. Hiện **không lọc gì** → 130 bảng kê `pending` đang có
   thể lộ. Sửa `StatementController::index` + `show`.
2. **Maker-checker là bắt buộc**, không còn tuỳ chọn. `StatementApprovalQueue.php:186`
   hiện `$eligible->each->update(...)` — ngoài transaction, **không chặn
   `created_by_id` tự duyệt**. Phải chặn người nhập tự duyệt.
3. **Phải có nghiệp vụ phát hành.** Hôm nay **không dòng code nào** set
   `approval_status = 'published'` (chỉ seeder), `statement_publish_logs` có bảng +
   model nhưng **không có write-path**. Cần: chỉ `approved` mới `publish` được,
   publish một lần, ghi `statement_publish_logs`.
4. **Đóng đường vòng.** Đang có 3 lối đi qua chuỗi duyệt:
   - `MyWork.php:338` — `Statement::whereKey($id)->update(['approval_status'=>…])`
     mass-update, không lock, không guard trạng thái, không audit.
   - `/fila/payments` — `PaymentForm.php:33` cho sửa `status` bằng `TextInput` tự do
     và sửa `amount`. Set `confirmed` ở đây **không sinh allocation/receipt**.
   - `StatementApprovalQueue::transitionRuns()` không lọc trạng thái hợp lệ → từ chối
     được cả bản ghi đã `published`.

---

## D2. Backfill `fee_category` — LÀM

Hiện NULL 4.792/7.212 dòng (66%).

**Nhưng backfill vào bộ mới, không vào bộ cũ** — xem D4: bộ `fee_types.category`
hiện tại (`management|parking|utility|service|surcharge|reserve|other`) **không biểu
diễn được** thứ tự ưu tiên mà D4 yêu cầu. Backfill hai lần là lãng phí.

Đích backfill = **5 billing family**: `management` · `water` · `electricity` ·
`vehicle` · `other`.

Bảng ánh xạ từ dữ liệu thật:

| `fee_types.category` | → family | Ghi chú |
|---|---|---|
| `management` | `management` | |
| `utility` | **phải tách `water` / `electricity`** | 9 fee type đang trộn cả hai |
| `parking` | `vehicle` | |
| `service`, `surcharge`, `reserve`, `other` | `other` | |

Tách `utility` theo `code`/`name` (dữ liệu thật đã có tiền tố rõ):
- → `water`: code `NUOC*`, tên chứa "nước" (`Phí nước sinh hoạt`, `Tiền nước`,
  `Nước nóng trung tâm`)
- → `electricity`: code `DIEN*`, tên chứa "điện" (`Tiền điện`, `Tiền điện chỉ số`,
  `Điện theo khung giờ`, `Điện năng lượng mặt trời`, `Phí điện theo khung giờ (cũ)`)
- ⚠️ Ba trường hợp KHÔNG tự động được, phải BQL gán tay:
  `Phí điều hòa trung tâm` · `Phí sạc xe điện` · các fee type `utility` mới phát sinh.
  Không đoán — để `other` và đưa vào danh sách chờ gán.

Backfill phải reversible (lưu giá trị cũ) và có báo cáo exception cho dòng không map được.

---

## D3. Theo dõi thanh toán TỪNG DÒNG PHÍ — LÀM

Ví dụ chuẩn của chủ dự án:

```
Tháng 4:  tiền điện 400.000
          nợ cũ     200.000
          → thanh toán 500.000
          → còn nợ  100.000
```

Nghĩa là phân bổ ở **cấp nghĩa vụ**, không phải cấp bảng kê. Đây đúng hướng `Charge`
của handoff 30/07.

**Đường đi rẻ nhất — không dựng bảng `charges` mới ngay:**

`statement_lines` đã có sẵn `paid_amount`, `fee_category`, `status`, `fee_type_id`
(migration `2026_07_26_000001`), và `payment_allocations.statement_line_id` **đã có
cột** nhưng **code chưa bao giờ ghi**. Việc cần làm là bắt đầu ghi
`statement_line_id` và duy trì `statement_lines.paid_amount`/`status`, rồi
`statements.paid_amount` trở thành **projection** cộng lên từ dòng.

**Thiếu về schema — phải bổ sung:**

1. `statement_lines` **không có kỳ dịch vụ riêng** (`service_period_start/end`) và
   **không có hạn riêng** (`due_date`) → không nói được "dòng này là phí tháng 4".
   Hiện chỉ suy được qua `statements.billing_period_id`, không đủ khi một bảng kê
   gánh nhiều kỳ (trường hợp nợ cũ trong ví dụ trên).
2. `statement_lines` **không có chiều tài sản** — xem D6, bắt buộc cho phí xe.
3. Không có invariant DB nào bảo vệ
   `statements.paid_amount = Σ statement_lines.paid_amount` và
   `Σ payment_allocations.amount ≤ payments.amount`. Cần command đối chiếu + test.
4. `ApartmentWalletService::autoSettleOutstanding()` hiện là **dead code** và nếu bật
   lên sẽ ghi `statement_lines.paid_amount` mà **bỏ qua `payment_allocations` và
   `statements.paid_amount`** → hai nguồn sự thật lệch nhau. Phải viết lại theo
   ledger, không dùng nguyên trạng.

---

## D4. Thứ tự phân bổ — BQL từng dự án tự sắp, có mặc định

**Mặc định khi BQL không sắp:**

```
1. Phí quản lý   (management)
2. Nước          (water)
3. Điện          (electricity)
4. Phương tiện   (vehicle)
5. Phí khác      (other)
```

Trong cùng một family: **cũ nhất trước** (xem D6).

**Kéo theo:**

- Bộ `fee_types.category` hiện tại **không biểu diễn được thứ tự này**: "Nước" và
  "Điện" đều nằm trong `utility`, nên không có cách nào xếp Nước trước Điện. ⇒ **5
  family của handoff 30/07 không còn là tuỳ chọn kiến trúc, nó là điều kiện để D4 chạy
  được.** Đây là lý do D2 phải backfill vào family.
- `fee_types.payment_priority` (usmallint, default 100) đã có nhưng là **theo tenant,
  không theo dự án**. D4 yêu cầu **mỗi dự án tự sắp** ⇒ cần lớp override theo project.
- Thứ tự này chỉ áp dụng khi cư dân **không chỉ định**. Cư dân chỉ định rõ thì lựa
  chọn của cư dân thắng (xem D6).

### D4-bis — Cách BQL sắp xếp (chốt bổ sung 2026-07-31)

**BQL từng dự án được sắp xếp**; mặc định **sort theo thứ tự của loại phí, tăng dần**.

Hiện thực:

1. Bảng override theo dự án (đề xuất `project_fee_priorities`:
   `project_id` + `fee_type_id` + `sort_order`), hoặc cột `sort_order` trên
   `fee_scope_assignments` đã có sẵn scope `project|building|apartment`.
2. **Giá trị mặc định phải được seed sao cho sắp tăng dần ra đúng dãy D4**:
   Phí quản lý (100) → Nước (200) → Điện (300) → Phương tiện (400) → Khác (900).
   Chừa khoảng giữa để BQL chèn loại phí mới không phải đánh số lại toàn bộ.
3. UI BQL: danh sách kéo-thả, sắp tăng dần, hiện số thứ tự. Không bắt BQL tự nhập số.
4. Thứ tự phân bổ đầy đủ = **lựa chọn của cư dân** → override dự án (tăng dần) →
   `fee_types.payment_priority` → dãy family mặc định → **cũ nhất trước** → `due_date`
   → `id`.

**Loại phí chưa rõ family thì vào `other` (Phí khác)**, không đoán. BQL gán tay sau —
và vì `other` xếp cuối (900), khoản chưa gán không bao giờ chen lên trước phí quản lý.

---

## D5. SLA duyệt chứng từ 24h + HQ xử lý ngành dọc

- Quá **24 giờ** → escalate.
- HQ có bộ phận ngành dọc (kế toán, chăm sóc khách hàng) **được xử lý dọc từ HQ**.

**Làm rõ một chỗ dễ hiểu sai:** handoff 30/07 §3.2 chốt màn duyệt chứng từ *chỉ ở
`/admin`, cố ý không đưa lên `/sa`*. Điều đó **không mâu thuẫn** với D5:

| Tầng | Duyệt chứng từ tiền? | Lý do |
|---|---|---|
| T1 SuperAdmin (`/sa`) — nhà cung cấp phần mềm | **KHÔNG, tuyệt đối** | Chủ dự án chốt nguyên văn: *"sa không biết được sao kê gì đâu"*. Không xem được sao kê tài khoản của công ty vận hành ⇒ duyệt là xác nhận việc mình không có cách nào biết. Không có cả nút. |
| T2a HQ (`/hq`) — công ty vận hành | **CÓ** (kế toán/CSKH ngành dọc) | Tài khoản ngân hàng là của chính công ty này ⇒ có quyền và có cơ sở xác nhận. |
| T2b BQL (`/admin`) — dự án | **CÓ** (mặc định) | |

⇒ Màn duyệt chứng từ chỉ tồn tại ở **`/admin` và `/hq`**. Không đưa lên `/sa` dưới bất
kỳ hình thức nào — kể cả "chỉ để xem" cũng không nên có nút duyệt trên màn đó.

Cần: role kế toán/CSKH ở `/hq` scope theo dự án của công ty đó, audit ghi rõ **tầng
nào** đã duyệt, và đường escalate BQL → HQ khi quá 24h.

---

## D6. Trả trước nhiều tháng & thanh toán theo dịch vụ

**Đơn vị chọn để trả là DỊCH VỤ / TÀI SẢN, không phải bảng kê.**

Ví dụ chuẩn của chủ dự án:

```
Phương tiện > Ô tô BKS 838888 — đang nợ 3 tháng, 4.500.000
  → cư dân trả đúng 4.500.000, hoặc trả số khác
  → thiếu  : hạch toán ưu tiên CŨ NHẤT trước
  → thừa   : vào ngăn tiền thừa CỦA CHÍNH BKS ĐÓ
```

**Quy tắc chốt:**

1. Cư dân tick được **nhiều dịch vụ** hoặc **nhiều bảng kê**, hoặc chọn **một khoản
   cụ thể** (một tài sản, nhiều tháng).
2. Trả **thiếu** so với khoản đã chọn → phân bổ **cũ nhất trước** trong đúng phạm vi
   đã chọn.
3. Trả **thừa** → tiền thừa vào **ngăn của chính tài sản đó** (ngăn của BKS 838888),
   không rơi vào quỹ chung, không dùng chéo sang dịch vụ khác.
4. App phải cho chọn nhiều tháng thuận tiện — không bắt cư dân mở từng bảng kê.

### D6-bis — Ba cấp phí (chốt bổ sung 2026-07-31)

Chủ dự án đặt câu hỏi: phí đang thiết kế 3 cấp **Fee-family › Fee-category › Fee-title**
tương ứng **Phương tiện › Ô tô › BKS 838888** — đúng không?

**Hình dạng 3 cấp là ĐÚNG, nhưng cấp 3 không phải một dòng trong danh mục phí.**

Ánh xạ vào code thật:

| Cấp | Tên trong quyết định | Hiện thực | Ví dụ | Số lượng |
|---|---|---|---|---|
| 1 | Fee-family | `statement_lines.fee_category` (5 family, D2) | `vehicle` — "Phương tiện" | 5, cố định |
| 2 | Fee-type | `fee_types` (**danh mục**, theo tenant) | `OTO` — "Phí gửi ô tô" | ~39 dòng |
| 3 | Tài sản | `subject_type`/`subject_id` → **`vehicles`** | xe BKS 51K-838888 | theo từng căn |

**Vì sao cấp 3 KHÔNG nên là dòng danh mục phí:**

- `fee_types` là **danh mục dùng chung của cả tenant** (39 dòng). Nếu mỗi biển số là một
  dòng danh mục, danh mục nổ thành hàng nghìn dòng — mỗi xe của mỗi căn một dòng, và mỗi
  lần cư dân mua xe mới là **sửa danh mục phí**.
- Biển số đã là thuộc tính của một bản ghi có thật: `vehicles` đã có `plate_no`,
  `parking_card_no`, `monthly_fee`, `valid_to`, `status`. Đưa biển số vào danh mục phí là
  lưu **hai nơi**, chắc chắn lệch.
- Xe bán/chuyển chủ, đổi thẻ, hết hạn — là vòng đời của **tài sản**, không phải của
  danh mục phí.

`fee_types.unit = 'per_vehicle'` (đã có sẵn ở `OTO`, `XEMAY`) chính là tín hiệu
"loại phí này sinh ra theo từng tài sản" ⇒ dùng nó để biết khi nào cấp 3 là bắt buộc.

**Mô hình này tổng quát cho cả 5 family** — dấu hiệu cho thấy nó đúng:

| Family | Cấp 2 (fee_type) | Cấp 3 (tài sản) |
|---|---|---|
| Phương tiện | Phí gửi ô tô / xe máy | xe (`vehicles`) |
| Điện | Tiền điện chỉ số | đồng hồ (`meters`) |
| Nước | Phí nước sinh hoạt | đồng hồ (`meters`) |
| Phí quản lý | Phí quản lý | chính căn hộ (không cần cấp 3) |
| Khác | Phí vệ sinh… | không có |

**Hiển thị cho cư dân** đúng như chủ dự án hình dung — `Phương tiện › Ô tô › 51K-838888` —
chỉ là cấp 3 được **resolve từ `vehicles`**, không phải đọc từ danh mục phí.

### D6-ter — Màn công nợ theo dịch vụ (chốt bổ sung 2026-07-31)

Hai lối xem song song, cư dân chọn:

1. **Theo bảng kê từng kỳ** — đã có (`CD-PAY-01`/`CD-PAY-02`), giữ nguyên.
2. **Theo dịch vụ / loại phí** — **MỚI**. Chọn loại phí → dropdown **3 cấp** (family →
   fee_type → tài sản) → hiện **từng tháng đang nợ** của đúng tài sản đó → **tick chọn
   từng tháng**.

Ví dụ màn: `Phương tiện › Ô tô › 51K-838888` → 3 dòng tháng 5, 6, 7 mỗi dòng 1.500.000,
tick cả 3 → tổng 4.500.000 → cho sửa số tiền → gửi.

Đây là màn làm cho D6 dùng được. Không có nó thì "trả trước nhiều tháng cho một xe" vẫn
phải mở từng bảng kê, đúng thứ chủ dự án muốn tránh.

**Thiếu về schema — chặn D6:**

1. **`statement_lines` không có chiều tài sản.** Dòng "Phí gửi xe ô tô" hôm nay không
   nói được là xe nào. Cần `subject_type`/`subject_id` (morph → `vehicles`,
   `meters`, …) trên dòng phí. Không có nó thì "nợ của BKS 838888" là câu **không
   truy vấn được**.
2. **`apartment_wallet_buckets` không đủ mịn.** Unique hiện tại là
   `(wallet_id, fee_category, fee_type_id)` — mịn nhất tới *loại phí*, không tới *tài
   sản*. D6 yêu cầu ngăn **theo từng BKS** ⇒ cần thêm chiều tài sản vào bucket và đổi
   unique tương ứng.
3. **Không có cách gom nhiều kỳ của một tài sản.** "Nợ 3 tháng của một xe" = 3 dòng ở
   3 bảng kê khác nhau; cần view/truy vấn theo tài sản xuyên kỳ (khái niệm
   `service_subscription` của handoff).

---

## D7. TIỀN VIỆT KHÔNG CÓ SỐ THẬP PHÂN (mới)

Đồng Việt Nam không có phần lẻ. **Mọi số tiền là số nguyên đồng.**

Đây là **thay đổi so với handoff 30/07** (handoff ghi `DECIMAL(20,2)` + decimal string
`"1234000.00"`). Văn bản này thắng.

**Hệ quả:**

| Tầng | Hôm nay | Chốt |
|---|---|---|
| DB | `decimal(16,2)` / `decimal(18,2)` | Giữ cột (không migration phá), nhưng **ràng buộc phần lẻ = 0**. Cân nhắc chuyển `bigint` ở bảng mới. |
| PHP | bcmath trên chuỗi | Số nguyên; bỏ được phần lớn bcmath vì VND nguyên nằm gọn trong `int64` |
| API | `"1234000.00"` | `"1234000"` — chuỗi số nguyên, không phần lẻ |
| App Flutter | `double.tryParse` ở 3 hàm `_money()` | **`int`** — chính xác tuyệt đối, bỏ được epsilon `0.009` đang phải dùng ở `statement.dart:61` và `wallet.dart:107` |

Điều này **giải quyết luôn** vi phạm "tiền không dùng float" mà app đang mắc: `int`
đúng hơn cả decimal string, và VND lớn nhất còn cách xa giới hạn an toàn của Dart.

**Quy tắc làm tròn — ĐÃ CHỐT 2026-07-31:** làm tròn **từng dòng phí**, half-up, tới
đồng. Tổng bảng kê = cộng các dòng **đã tròn**. Không làm tròn ở tổng, không giữ số lẻ
trung gian rồi tròn một lần cuối — vì khi đó cư dân cộng tay các dòng sẽ lệch với tổng
in trên bảng kê, và đó là loại lệch làm mất niềm tin vào toàn bộ con số.

**Ngoại lệ giữ số lẻ** (không phải tiền VND của cư dân): `fee_types.vat_percent`
(decimal 5,2 — là phần trăm), `billing_rate_cards.unit_price` (decimal 18,4 — giá
SaaS B2B), `meter_readings.consumption` (chỉ số đồng hồ), `statement_lines.quantity`
(m², m³, kWh).

---

## D8. Múi giờ

Lưu **UTC** ở server là đúng, giữ nguyên. Yêu cầu duy nhất: **hiển thị cho người dùng
theo UTC+7**.

App phải: gửi `paid_at` có offset (`.toUtc()` hoặc `+07:00`) — bẫy đã ghi ở handoff
§2.8; và **hiển thị** mọi mốc thời gian đã chuyển sang UTC+7, không đưa chuỗi UTC thô
ra màn hình.

---

## D9. Engine tính phí → PHASE 2. Giai đoạn đầu KẾ TOÁN IMPORT (mới)

Chốt: **engine tính phí chưa cần ngay.** Thời gian đầu kế toán **import** khoản phí.

- Mẫu import + hợp đồng cột: `BILLING_IMPORT_SPEC_20260731.md`
  (+ CSV tham khảo `docs/templates/mau_import_khoan_phi.csv`)
- Kế hoạch engine Phase 2: `BILLING_FEE_ENGINE_PHASE2_PLAN.md`

Hệ quả tốt: **chặn lớn nhất được gỡ.** Trước đây D3/D4/D6 đều phụ thuộc "phải có gì sinh
ra khoản phí" — nay nguồn sinh là import của kế toán. Engine tính phí thành việc tối ưu
hóa về sau, không còn là điều kiện tiên quyết.

Giai đoạn này **số của kế toán là số chuẩn**, hệ thống không tự tính lại. `quantity` /
`unit_price` / chỉ số đồng hồ nhập kèm chỉ để **giải thích cho cư dân**, không dùng để
tính.

## Việc D1–D9 tạo ra, không có trong handoff 30/07

1. Chiều **tài sản** trên dòng phí + trên ngăn tiền thừa (D6) — handoff chỉ nói tới
   `fee_type`, không tới từng BKS.
2. **Ba cấp phí family › fee_type › tài sản** (D6-bis) — handoff dừng ở `fee_type`.
3. **Màn công nợ theo dịch vụ** với dropdown 3 cấp + tick từng tháng (D6-ter) — handoff
   chỉ mô tả Billing Hub theo kỳ.
4. **Override thứ tự ưu tiên theo dự án, kéo-thả, sắp tăng dần** (D4-bis) — handoff coi
   `payment_priority` theo tenant là đủ.
5. **Số nguyên đồng** (D7) — ngược với `DECIMAL(20,2)` của handoff.
6. **Quyền duyệt tiền cho HQ ngành dọc** (D5) — handoff ghi HQ chỉ quan sát/escalate.
7. **Import kế toán là nguồn sinh khoản phí giai đoạn đầu** (D9) — handoff giả định có
   billing run tính tự động ngay từ Phase 1.

## Thứ tự thi công suy ra từ D1–D9

1. Backfill `fee_category` → 5 family (D2) — nền cho D4 và cho import.
2. Thêm `subject_type`/`subject_id` + `service_period_start`/`_end` trên
   `statement_lines` (D3, D6).
3. `BillingChargeImportProfile` + `RowNormalizers::money()` + màn import kế toán (D9).
4. Duyệt + phát hành có maker-checker, đóng 3 đường vòng (D1).
5. Ghi `payment_allocations.statement_line_id` + `statement_lines.paid_amount`;
   `statements.paid_amount` thành projection (D3).
6. Thứ tự ưu tiên: seed mặc định + override theo dự án + UI kéo-thả (D4-bis).
7. Ngăn tiền thừa theo tài sản (D6.3).
8. Màn công nợ theo dịch vụ trên app + dropdown 3 cấp + tick nhiều tháng (D6-ter).
9. Chuyển tiền sang số nguyên đồng toàn tuyến DB → PHP → API → Flutter (D7).
