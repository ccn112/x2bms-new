# Phase Plan for X2-BMS — bản viết lại theo trạng thái THẬT (2026-07-31)

> **Bản gốc của gói handoff đã được thay thế.** Lý do: bản gốc (A0 audit → A1
> resident-identity → A2 home → A3 feedback → A4 amenity → A5 fee/debt read-only →
> A6 community) viết như thể repo là greenfield. Repo đang ở giữa chặng, và đi theo
> A1–A6 nguyên văn sẽ **làm lại việc đã xong** và **để bug đang sống tiếp tục sống**.
>
> Bản gốc giữ nguyên văn ở `04_INITIAL_PHASE_PLAN_ORIGINAL.md`.
> File dẫn đường đúng cho repo này là `05_MIGRATION_FROM_SCREEN_FIRST.md`.

## Trạng thái thật, đo bằng audit 2026-07-31

| Miền | Bản gốc xếp | Thực tế |
|---|---|---|
| Resident identity | A1 — reference slice | **Phần lớn đã xong**: `ResidentImportProfile` (import gộp căn hộ + cư dân + quan hệ, quyết định owner 2026-07-20), `ResidentIdentityMatcher`, `resident_apartment_relations`, activation qua `GlobalUserAccount` + `MobileDevice` |
| Home read model | A2 | Đã có: `billing/summary`, thẻ công nợ, thông báo, quick action |
| Feedback | A3 | BQL đủ (`FeedbackQueue.php`, 244 dòng); **cư dân chỉ đọc** — không trả lời, không đính ảnh, không chấm sao |
| Amenity booking | A4 | Đã có cả 2 phía + bình luận phiếu |
| Fee & debt | A5 — "read-only trước" | **Đã qua read-only từ lâu**: claim + review + allocation + receipt + 11 test. Owner vừa chốt D1–D9 đi sâu hơn hẳn A5 |
| Community | A6 — "làm sau cùng" | **~90% xong**: 20 route, 7.744 dòng app, 54 test. Nhưng đang có **hồi quy mất trả lời bình luận** |

## Nguyên tắc của bản viết lại

1. **Sửa cái đang sai trước khi xây cái mới.** Bug người dùng thấy được đứng trước tính năng.
2. **Reference slice phải là việc đang cần thật**, không phải việc làm lại để trình diễn phương pháp.
3. **Chỉ một slice implementation chính** — giữ nguyên quy tắc WIP của gói gốc.
4. Mỗi phase đi qua gate, kể cả phase sửa lỗi (tối thiểu G7 test + G8 evidence).

---

## Phase B0 — Sửa lỗi đang sống (không phải slice, làm ngay)

Năm lỗi đã xác minh bằng code, đều nhỏ, đều đang gây hậu quả thấy được:

| # | Lỗi | Vị trí | Gate |
|---|---|---|---|
| 1 | **Trả lời bình luận cộng đồng mất sau khi tải lại.** Backend mặc định `whereNull('parent_id')` (`CommunityPostController.php:212`) và trả `reply_count`; app không gửi `parent_id`, không parse `reply_count`, vẫn gom cây từ danh sách phẳng | `x2mobile` `features/comments/data/remote_comment_repository.dart` · `comment_dto.dart` · `presentation/comment_thread.dart` | G7 |
| 2 | **Cư dân có thể thấy bảng kê chưa phát hành** — `StatementController` không lọc `approval_status`/`published_at`; 130 bản ghi `pending` đang tồn tại (D1) | `StatementController::index` + `show` | G10 |
| 3 | **App nói sai sự thật với cư dân**: "hệ thống sẽ tự động đối soát và cập nhật trạng thái hóa đơn" — không có webhook/IPN nào trong `routes/` | `x2mobile` `vietqr_screen.dart:72` | G8 |
| 4 | **Hai số tiền khác nhau cùng một luồng**: sheet hiện `totalAmount`, backend tạo intent theo `outstanding` (`bcsub`); thẻ hóa đơn hiện `totalAmount` nhưng hàng tổng cộng `remaining` | `payment_method_sheet.dart:100` · `statements_screen.dart:384` | G10 |
| 5 | **Đăng bài trong nhóm không vào nhóm** — `createPost()` không truyền `community_group_id` → bài lưu `null` | `remote_community_repository.dart` | G7 |

## Phase B1 — Reference slice: **Billing Charge Import** (kế toán nhập khoản phí)

Slice mẫu, thay cho `resident-identity`.

**Vì sao slice này:** là việc đang cần thật (owner chốt D9 hôm nay); đi trọn vòng
migration → service → seed → Filament → test → evidence; **buộc đi qua G9 + G10** vì là
tiền; và có **bộ test vàng** để nghiệm thu về sau (số kế toán nhập vs số engine tính).

Chuẩn: `docs/BILLING_IMPORT_SPEC_20260731.md` · `docs/BILLING_OWNER_DECISIONS_20260731.md`

```text
Backfill fee_category → 5 family (D2)
→ thêm subject_type/subject_id + service_period_start/end trên statement_lines (D3, D6)
→ RowNormalizers::money() — số nguyên đồng (D7)
→ BillingChargeImportProfile trên StagingImporter đã có
→ màn import kế toán ở /admin
→ seed: 2 dự án, MUST_NOT_LEAK, căn nhiều xe/nhiều đồng hồ, dòng nợ cũ dồn kỳ
→ test: idempotency · chặn số lẻ · chặn BKS sai căn · statement ra pending KHÔNG lộ cho cư dân
→ evidence
```

Ranh giới: **không** làm engine tính phí (Phase C), **không** làm phát hành (B2),
**không** chạm phân bổ (B3).

## Phase B2 — Duyệt & phát hành có maker-checker (D1)

```text
kế toán nhập → trưởng ban QL duyệt → phát hành
```

Bao gồm **đóng 3 đường vòng** đã xác minh (G9): `MyWork.php:338` mass-update ·
`/fila/payments` form `status` tự do · `StatementApprovalQueue::transitionRuns()` không
lọc trạng thái hợp lệ. Và hiện thực nghiệp vụ publish + ghi `statement_publish_logs`
(hôm nay **không dòng code nào** set `published`).

## Phase B3 — Phân bổ theo từng dòng phí (D3)

Ghi `payment_allocations.statement_line_id` (cột đã có, code chưa bao giờ ghi) + duy trì
`statement_lines.paid_amount`; `statements.paid_amount` thành projection. Kèm command đối
chiếu + test invariant theo G10. Viết lại `ApartmentWalletService::autoSettleOutstanding()`
— hiện là dead code và sẽ phá bất biến nếu bật nguyên trạng.

## Phase B4 — Thứ tự ưu tiên phân bổ (D4-bis)

Seed mặc định QL(100) → Nước(200) → Điện(300) → Xe(400) → Khác(900); override theo dự án;
UI kéo-thả sắp tăng dần.

## Phase B5 — Ngăn tiền thừa theo tài sản + màn công nợ theo dịch vụ (D6)

Backend: chiều tài sản trên bucket. App: dropdown 3 cấp (family → fee_type → tài sản) →
hiện từng tháng đang nợ → tick nhiều tháng. Đây là màn làm cho D6 dùng được.

## Phase B6 — Kiểm duyệt cộng đồng ở `/admin` + đóng vòng report

Spec đã viết đầy đủ ở `docs/COMMUNITY_WRITE_MODERATION_DESIGN.md` §4, chưa 1 dòng code.
Kèm: endpoint đóng/bỏ qua report (`community_post_reports.status`/`resolved_*` hiện
**không có dòng code nào ghi vào**) + snapshot test + test cô lập tenant (hiện **0 test
backend** cho cộng đồng).

## Phase B7 — Số nguyên đồng toàn tuyến (D7)

DB (ràng buộc phần lẻ = 0) → PHP → API (`"1234000"`) → Flutter (`int`; bỏ 3 hàm `_money()`
dùng `double`, bỏ epsilon `0.009`).

## Phase B8 — Phản ánh: cư dân trả lời + đính ảnh + chấm sao

Miền duy nhất trong bản gốc còn đúng là "chưa xong" — và nó nhỏ.

## Phase C — Engine tính phí

`docs/BILLING_FEE_ENGINE_PHASE2_PLAN.md`. Có checklist điều kiện tiên quyết riêng; không
bắt đầu khi còn ô chưa tick.

---

## Việc chạy song song, không nằm trong hàng WIP

- **Deploy live `x2.fino.vn`** — đang chạy code cũ; APK trên máy thật đang test trên
  backend cũ. Mọi kết luận "đã verify" hiện chỉ đúng ở máy dev.
- **Copy gói mass seed** từ `handoff/x2bms/X2_BMS_COMMUNITY_MASS_SEED_HANDOFF_20260726/`.
  Không có nó thì tách bảng bình luận (GĐ7 community) **không được phép bắt đầu** — DB chỉ
  có 7 bình luận thật.
- **Giải nén** `X2_BMS_COMMUNITY_DOMAIN_HANDOFF_20260729.zip` — 6 file docs community đều
  trỏ vào nó, hiện không ai đọc được.
- **Dọn drift tài liệu** — `docs/delivery/TECH_DEBT_REGISTER.md`.
