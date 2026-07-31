# Vertical Slice Gates

## G0 — Boundary

- User job rõ.
- In/out scope rõ.
- Không trùng domain hiện có.

## G1 — Domain

- Entity/relationship/invariant rõ.
- State machine rõ nếu có.
- Source of truth xác định.

## G2 — Security and scope

- Role matrix hoàn thành.
- Tenant/project/building/apartment scope rõ.
- Negative scenarios đã định nghĩa.

## G3 — Persistence

- Migration reversible.
- Constraint/index phù hợp.
- Model relation/cast chuẩn.

## G4 — Seed

- Seed deterministic.
- Có happy/pending/error/forbidden.
- Có `MUST_NOT_LEAK`.
- Dashboard expected values được ghi.

## G5 — Application/API

- Business rules ở service/action.
- API typed/versioned.
- Error and idempotency rõ.

## G6 — Surface

- Filament decision phù hợp.
- Mobile contract có đủ states nếu cần.
- Không hardcode data.

## G7 — Test

- Domain, policy, API và isolation tests pass.
- Clean migrate + seed + test pass.

## G8 — Evidence

- Demo account/route/API được ghi.
- Known limitation và rollback rõ.

---

## G9 — Anti-bypass (áp cho MỌI slice có state chuyển được)

> Thêm khi cài vào repo x2bms, 2026-07-31. Lý do: gate G0–G8 kiểm "làm đúng chưa",
> không kiểm "còn cửa sau nào không". Chính khoảng trống này sinh ra
> `MyWork.php:338` và form `/fila/payments` — trong khi
> `ResidentPaymentClaimReviewer` làm rất đúng nhưng có 4 đường vòng qua nó.

- **Liệt kê MỌI code path** mutate được state này: Controller, Filament Page, Filament
  Resource form/table action, Job, Command, seeder, migration data-fix. Không phải "đường
  chính đúng là đủ".
- Mỗi đường trong danh sách: đi qua service chuẩn, hay bị **khóa/bỏ**.
- Filament Resource thô (`/fila`) **không được** cho sửa trực tiếp cột trạng thái hoặc
  cột tiền của bảng có invariant. Không khóa được thì bỏ Resource đó.
- Grep chứng minh: không còn `->update([...'status'...])` / `whereKey()->update()` nào
  ngoài service chuẩn.
- Test: gọi thẳng đường vòng → phải bị từ chối hoặc không tồn tại.

## G10 — Money & Authority (chỉ áp cho slice có tiền)

> Thêm khi cài vào repo x2bms, 2026-07-31. Gói gốc không có gate nào về bất biến tài
> chính — với SaaS billing đây là chỗ hậu quả nặng nhất. Chuẩn tham chiếu:
> `docs/BILLING_OWNER_DECISIONS_20260731.md`.

- **Một đường ghi tiền duy nhất.** UI/API chỉ orchestrate, không tự viết lại logic.
- **Bất biến ở tầng DB, không chỉ tầng code**: unique/CHECK/FK cho những thứ phải đúng
  qua mọi code path. Kèm command đối chiếu chạy được độc lập:
  - `Σ payment_allocations.amount ≤ payments.amount`
  - `statements.paid_amount = Σ statement_lines.paid_amount`
  - `statement_lines.paid_amount ≤ statement_lines.amount`
- **Không float.** Tiền VND là **số nguyên đồng** (D7). Không `double`, không decimal
  string, không so sánh bằng epsilon.
- **Làm tròn từng dòng phí**, half-up. Tổng = cộng các dòng đã tròn.
- **Write tiền phải idempotent + transaction + `lockForUpdate`.** Duyệt hai lần không
  ghi tiền hai lần; hai người bấm đồng thời → người thứ hai là no-op.
- **Không phân bổ vượt outstanding.** Phần vượt thành tiền dư có chủ, không overpay.
- **Reversal phục hồi được** và audit được. Trạng thái khai báo trong enum mà không có
  code path (như `Payment::STATUS_REVERSED` hiện nay) là **nợ, không phải tính năng**.
- **Maker-checker** ở nơi nghiệp vụ yêu cầu (D1: kế toán nhập → trưởng ban duyệt →
  phát hành). Người tạo không được tự duyệt.
- **Audit đủ `subject_type` + `subject_id`.** Ghi audit mà không truy được bản ghi nào
  bị tác động thì coi như chưa có audit.
- **Phân quyền theo tầng đúng** (D5): `/sa` **không bao giờ** duyệt tiền — nhà cung cấp
  phần mềm không xem được sao kê của công ty vận hành. Chỉ `/admin` và `/hq`.
- **Cư dân chỉ thấy chứng từ đã phát hành** (D1).
