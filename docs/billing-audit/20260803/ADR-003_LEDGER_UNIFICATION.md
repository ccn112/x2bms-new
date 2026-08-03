# ADR-003 — Hợp nhất ledger: `paid_amount` là projection từ ledger

- **Ngày:** 2026-08-03
- **Trạng thái:** Accepted
- **Liên quan:** ADR-001 (transitional charge trên `statement_lines`), handoff canonical §invariant #6/#7, Gate B "đóng đường vòng status/paid amount trước khi mở API mới".

## Bối cảnh (audit code thật)

Có **hai đường ghi tiền** chạm `statement_lines.paid_amount`, hiện đều **ghi tăng dần bằng tay**:

1. `ResidentPaymentClaimReviewer` (chứng từ chuyển khoản): tạo `payment_allocations(statement_line_id)` **và** `line.paid_amount += take`.
2. Đường ví/D6 — `ApartmentWalletService::autoSettleOutstanding` / `settleAssetLines`, gọi từ `DebtByAssetPaymentController`: ghi `apartment_wallet_transactions(direction=out, type=debt_settlement, reference=StatementLine)` **và** `line.paid_amount += take`, **KHÔNG** tạo `payment_allocations`.

Hệ quả: `line.paid_amount` là trường **denormalized** do hai nguồn cùng cộng vào, không tái dựng được từ một ledger → vi phạm bất biến "nguồn sự thật tài chính = ledger, `paid_amount`/`balance` là projection".

## Quyết định

`statement_lines.paid_amount` (và qua đó `statements.paid_amount`) trở thành **projection thuần**, tái dựng từ **ledger hợp nhất**:

```
paid(line) =  Σ payment_allocations.amount        WHERE statement_line_id = line
            + Σ apartment_wallet_transactions.amount WHERE reference = line
                                                       AND direction = 'out'
                                                       AND status    = 'confirmed'
```

- Thêm `StatementLine::recomputePaidFromLedger()` là **nơi DUY NHẤT** tính `paid_amount`/`status` của dòng.
- **Mọi writer chỉ append ledger row rồi gọi recompute** — cấm `paid_amount += …` bằng tay.
  - Claim reviewer: sau khi tạo allocation → `recomputePaidFromLedger()` (bỏ `forceFill(paid_amount+=)`).
  - Ví/D6: sau khi ghi `apartment_wallet_transactions(out, ref=line)` → `recomputePaidFromLedger()` (bỏ ghi tay).
- `Statement::recomputePaidAmount()` giữ nguyên (đã là `SUM(lines.paid_amount)`).

`apartment_wallet_transactions(out)` đóng vai **"credit application tạm thời"**. Khi Phase 5 dựng `prepaid_credit_lots` + `credit_applications`, chỉ cần đổi nguồn thứ hai trong `recomputePaidFromLedger()` sang `credit_applications` và migrate các out-transaction — không đụng call site.

### Xử lý dữ liệu legacy chưa có ledger backing (QUAN TRỌNG)

Dữ liệu seed hiện tại: ~1.088 bảng kê paid/partial nhưng chỉ **13 `payment_allocations`** → phần lớn `paid_amount` **không có ledger backing**. Flip sang projection thuần sẽ **xoá trắng** paid_amount seed. Do `payment_allocations.payment_id` là NOT NULL (không tạo được allocation legacy không kèm payment), ta dùng cột chốt legacy:

- Thêm `statement_lines.legacy_paid_amount` (nullable, additive, reversible).
- **Định nghĩa:** `paid(line) = legacy_paid_amount + ledgerPaidAmount(line)`.
- `legacy_paid_amount` = `max(paid_amount − ledgerPaidAmount(), 0)` **chốt một lần** (backfill eager qua command, hoặc lazy `ensureLegacyBase()` TRƯỚC khi writer thêm ledger row — không double-count).
- Non-destructive: `paid_amount` không đổi tại thời điểm migrate; writer mới chỉ cộng thêm phần ledger mới.
- Phase 5: chuyển `legacy_paid_amount` thành credit legacy record rồi bỏ cột.

## Phạm vi & không thuộc phạm vi

- **Trong P1a:** hợp nhất projection cho dòng phí; reconcile command; test invariant/concurrency. **Bảo toàn hành vi** (cùng con số, chỉ khác cách tính).
- **Ngoại lệ transitional được ghi nhận:** nhánh "bảng kê legacy KHÔNG có dòng phí" trong claim reviewer vẫn tạo `payment_allocations(statement_id, no line)` + ghi `statements.paid_amount` trực tiếp ở cấp bảng kê. Reconcile phải cộng thêm phần allocation cấp-bảng-kê (không gắn dòng) khi kiểm `statements.paid_amount`. Không refactor nhánh này trong P1a.
- **Không** tạo bảng `charges`/`credit_applications` mới (thuộc Phase 1b+/Phase 5).

## Hệ quả / rủi ro

- `recomputePaidFromLedger()` phải đọc `apartment_wallet_transactions` theo `reference_type = StatementLine::class` (morph class), `reference_id = line.id`, `direction='out'`, `status='confirmed'`.
- Reversal của out-transaction (nếu có sau này) phải hoặc đổi `status` khỏi `confirmed`, hoặc ghi out-transaction âm — recompute tự phản ánh.
- Không có DB CHECK cross-table cho "paid = Σ ledger" (MySQL không hỗ trợ); bất biến được bảo vệ bằng: (1) một hàm recompute duy nhất, (2) reconcile command, (3) test. Bổ sung CHECK trong tầm bảng: `paid_amount >= 0` và `paid_amount <= amount`.

## Nghiệm thu

- `line.paid_amount == recomputePaidFromLedger()` cho mọi dòng (reconcile dry-run 0 mismatch trên seed hiện tại).
- Đường ví/D6 sau sửa: settle 1 dòng → có đúng 1 out-transaction + `paid_amount` khớp; **không còn** lệnh ghi `paid_amount` trực tiếp ngoài `recompute*`.
- Test concurrency: hai luồng cùng settle không nhân đôi.
