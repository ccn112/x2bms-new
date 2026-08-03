# Chiến lược di trú Hệ CŨ → X2 (cụm Hoá đơn/Thông báo phí)

- **Ngày:** 2026-08-03 · **Phạm vi:** chuyển vận hành thu phí từ phần mềm cũ sang X2.
- **Căn cứ thực nghiệm:** đã nạp thật file `import_thong_bao_phi-HPO-05.2026.xlsx` (6.570 dòng, 1.314 căn) vào X2 và đối soát **khớp tuyệt đối tới từng đồng** (tổng 1.376.873.221đ; đối soát cấp dòng 6.570/6.570, 0 lệch).

## 1. Nguyên tắc nền

File "thông báo phí" **không phải export của hệ cũ** — nó là **file kế toán đang dùng để nạp phí vào phần mềm cũ**. Vì vậy di trú thuận lợi nhất là: **X2 nhận đúng file đó (drop-in)** → kế toán **giữ nguyên quy trình & công cụ**, chỉ đổi phần mềm nhận. Cách tính thành tiền của X2 đã được port khớp hệ cũ (bằng chứng: tổng trùng tuyệt đối).

## 2. Hai lựa chọn nhập liệu (cho kế toán)

| | **Option A — MẪU CŨ** (`fee_notification`) | **Option B — MẪU MỚI** (`billing_charges`, canonical) |
|---|---|---|
| File | Đúng file hệ cũ, 24 cột tiếng Việt, KHÔNG có "Thành tiền" | 16 cột chuẩn hoá, có "Thành tiền" kế toán chốt |
| Tính tiền | **X2 tự tính** (cố định qty×đơn giá; lũy tiến Σ định mức×đơn giá) | Kế toán nhập số đã chốt, X2 không tính lại |
| Tài sản (xe/đồng hồ) | Gộp theo số lượng (không biển số) | Tách theo biển số/mã đồng hồ (subject cấp 3) |
| Ưu | Di trú không ma sát, giữ nguyên quy trình | Rõ ràng, đối soát mạnh, hỗ trợ trả trước theo tài sản (D6) |
| Dùng khi | **Cutover ban đầu** | **Sau cutover**, tối ưu dần |
| File mẫu | `storage/app/templates/import_template_fee_notification.xlsx` | `storage/app/templates/import_template_billing_charges.xlsx` |

Cả hai chạy trên **cùng khung** `StagingImporter` (staging → preview → commit), ra cùng `statements`/`statement_lines`, cùng bất biến (bảng kê sinh ra `pending`; `total_amount` là phép chiếu; không chạm `paid_amount`).

## 3. Ba phương án chuyển đổi

1. **Bulk một lần** — nạp toàn bộ kỳ hiện hành (và/hoặc lịch sử) rồi cutover. Nhanh; cần cửa sổ dừng ghi + backup + đối soát tổng từng kỳ trước khi phát hành.
2. **Chạy song song (khuyến nghị)** — trong N kỳ, kế toán nạp CẢ hệ cũ và X2; sau mỗi kỳ **đối soát tổng + cấp dòng** (`billing:reconcile-fee-notification`). Khớp liên tục → tự tin cutover, rủi ro thấp nhất.
3. **Nạp định kỳ tới ngày cutover** — X2 nhận file mỗi kỳ (chưa phát hành cho cư dân) cho tới khi chốt chuyển hẳn.

## 4. Truy vết & audit (bắt buộc cho tiền)

- `statement_lines.source = 'legacy_import'` + `calculation_snapshot` = **toàn bộ đầu vào gốc** (loại giá, đơn giá/định mức/chỉ số/giảm giá, mức tiêu thụ, cách tính) → đối soát & giải thích cho cư dân.
- `AuditLog` mỗi dòng (`fee_notification.import`), gồm căn/kỳ/tiền/phương pháp.
- `import_batch_rows.committed_entity_id` → liên kết dòng file ↔ `statement_line` (truy ngược).
- **Reconciliation oracle:** `billing:reconcile-fee-notification {file}` — tính lại từng dòng từ file, so `amount` DB; báo khớp/lệch/thiếu + chênh tổng. Đây là cổng nghiệm thu di trú (phải 0 lệch).
- Idempotent theo natural key `(statement, fee_type, subject, service_period_start)` → nạp lại/nạp bù không nhân đôi. Rollback lô chỉ khi bảng kê còn `pending`.

## 5. Ánh xạ thực thể legacy → X2

| Legacy (file) | X2 |
|---|---|
| Mã căn hộ (A-0101) | `apartments.code` (phải tồn tại — không tự tạo) |
| Kỳ (202605) | `billing_periods.code` |
| Mã dịch vụ (PQL/NUOC/XEMAY/XEDAPDIEN/XEDAP) | `fee_types.code` → `BillingFamily` (management/water/vehicle) |
| Ngày bắt đầu/kết thúc | `statement_lines.service_period_start/end` |
| Đơn giá/định mức/chỉ số | `calculation_snapshot` (để giải thích, không tính lại) |
| Thành tiền (tính ra) | `statement_lines.amount` |

**Điều kiện tiên quyết dữ liệu chủ:** căn hộ, kỳ phí, loại phí phải có sẵn trong X2 trước khi nạp (script scaffolding demo `billing:import-fee-notification-demo` minh hoạ cách dựng từ chính file). Với Option B còn cần master **xe/đồng hồ** (subject) để tách tài sản.

## 6. Checklist cutover
1. Master data (căn/kỳ/loại phí, và xe/đồng hồ nếu dùng Option B) đã nạp.
2. Nạp file kỳ hiện hành (chưa phát hành).
3. `billing:reconcile-fee-notification` = 0 lệch, chênh tổng = 0.
4. Maker-checker duyệt → phát hành (D1) → cư dân thấy.
5. (Song song) đối soát với hệ cũ ≥ 1–2 kỳ trước khi tắt hệ cũ.

## 7. Tồn đọng / cần chốt
- Có nạp **lịch sử** nhiều kỳ không, hay chỉ từ kỳ cutover? (ảnh hưởng khối lượng + công nợ đầu kỳ)
- Công nợ/đã thu của hệ cũ có mang sang không (số dư đầu kỳ, đã thanh toán)? — nếu có, cần thêm luồng nạp "đã thu" (payment/allocation) tách khỏi nạp phí.
- Khi nào chuyển kế toán từ Option A sang Option B (hoặc dùng lẫn).
- Phí phạt/lãi, VAT hiển thị: hiện snapshot giữ nguyên đầu vào; nếu hệ cũ có VAT/phạt cần bổ sung cột/logic.
