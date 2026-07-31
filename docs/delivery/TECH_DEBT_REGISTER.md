# X2-BMS — TECH DEBT REGISTER

> Lập 2026-07-31 khi cài gói AI-First Delivery. Mỗi mục có **bằng chứng code**, không phải
> cảm nhận. Cột "Gate" = gate nào ở `03_VERTICAL_SLICE_GATES.md` bị vi phạm.

## Nhóm 1 — TIỀN (hậu quả cao nhất)

| # | Nợ | Bằng chứng | Gate | Trạng thái |
|---|---|---|---|---|
| M1 | **`/fila/payments` cho sửa `status` bằng `TextInput` tự do + sửa `amount`.** Set `confirmed` ở đây không sinh allocation/receipt; sửa `amount` sau khi phân bổ không đồng bộ `statements.paid_amount` | `app/Filament/Resources/Payments/Schemas/PaymentForm.php:33` | G9, G10 | ✅ **Đóng 31/07**: `PaymentResource` đổi CHỈ ĐỌC, xoá `PaymentForm.php`/create/edit page |
| M2 | **Đường duyệt bảng kê thứ hai, không kiểm soát** — mass-update, không lock, không guard trạng thái, không audit đủ | `app/Filament/Pages/MyWork.php:338` | G9, G10 | ✅ **Đóng 31/07**: loại `statement` nay gọi `StatementApprovalService::approve/reject` |
| M3 | **Duyệt billing run không transaction, không maker-checker** — `$eligible->each->update(...)`; không chặn `created_by_id` tự duyệt | `app/Filament/Pages/StatementApprovalQueue.php:186` | G10 | ⬜ **Chưa đóng** — đây là trục `BillingRun` (khác `Statement`), `approve()` đã lọc trạng thái từ trước nhưng vẫn thiếu transaction + chặn tự duyệt |
| M4 | **`transitionRuns()` không lọc trạng thái hợp lệ** → từ chối được cả bản ghi đã `approved`/`published` | `StatementApprovalQueue.php:194` | G9 | ✅ **Đóng 31/07**: lọc `validFrom` theo `$status` đích trước khi transition |
| M5 | **Không có bất biến tầng DB** cho `Σ payment_allocations.amount ≤ payments.amount` và `statements.paid_amount = Σ allocations`. Đúng chỉ nhờ MỘT code path — mà có 4 đường vòng | — | G10 | 🟡 **Một phần 31/07**: `statements.paid_amount` nay là phép chiếu từ `SUM(lines.paid_amount)` (`Statement::recomputePaidAmount()`) + lệnh đối chiếu `billing:reconcile-statement-balances` phát hiện/sửa lệch, báo dòng phí trả quá tiền (không tự sửa). Vẫn CHƯA có ràng buộc ở tầng DB (chỉ ở tầng service) — 3/4 đường vòng tiền của M1-M4 đã đóng, còn M3 (BillingRun) |
| M6 | **`receipts.code` không unique** → hai lượt duyệt đồng thời cùng tháng có thể trùng mã | tự ghi nhận ở `app/Services/Billing/ResidentPaymentClaimReviewer.php:184` | G10 | ⬜ Chưa đóng |
| M7 | **`Payment::STATUS_REVERSED` khai báo nhưng không code path nào set** — không có nghiệp vụ đảo/hoàn, không `reversal_of_id` | `app/Models/Payment.php:32` | G10 | ⬜ Chưa đóng |
| M8 | **`ApartmentWalletService::autoSettleOutstanding()` là dead code và sẽ phá bất biến nếu bật** — ghi `statement_lines.paid_amount` mà bỏ qua `payment_allocations` + `statements.paid_amount` | `app/Services/Resident/ApartmentWalletService.php:107` | G10 | ✅ **Đóng 31/07**: gọi `Statement::recomputePaidAmount()` cho từng bảng kê CHẠM TỚI sau khi hạch toán ví. Vẫn 0 caller thật (dead code an toàn hơn, chưa phải code đang chạy) |
| M9 | **Không có code nào set `approval_status='published'`** (chỉ seeder); `statement_publish_logs` có bảng + model, không write-path | — | G10 | ✅ **Đóng 31/07**: `StatementApprovalService::publish()` — chỗ DUY NHẤT set `published`, ghi `StatementPublishLog` |
| M10 | **`GET resident/statements` không lọc `published`** — 130 bảng kê `pending` đang có thể lộ cho cư dân | `StatementController::index` | G10 | ✅ **Đã đóng cùng ngày lập register này** (D1, `Statement::scopeVisibleToResident()` — chốt trước khi mục này được ghi, entry lạc hậu ngay từ lúc viết) |
| M11 | **Tiền dùng `double` ở app** — 3 hàm `_money()` ép `double.tryParse`, so sánh bằng epsilon `0.009`; cache ghi `"5000000.0"` mất hình dạng gốc | `x2mobile` `statement_dto.dart:131` · `payment_dto.dart:99` · `wallet_dto.dart:132` | G10 | ⬜ Chưa đóng — Phase B7 |
| M12 | **`debts` không có nguồn tính** — buckets/risk/recovery chỉ seeder, không job tính lại → `/admin/debts` và HQ DebtAging không đảm bảo khớp `statements` | — | G10 |
| M13 | **Migration data-fix không có `down()`** — vi phạm "migration reversible" ngay ở code đã merge | `database/migrations/2026_07_30_170001_*` | G3 |
| M14 | **Hai số tiền khác nhau cùng luồng** — sheet hiện `totalAmount`, intent tạo theo `outstanding`; thẻ hóa đơn `totalAmount` vs hàng tổng `remaining` | `payment_method_sheet.dart:100` · `statements_screen.dart:384` | G10 |
| M15 | **`statements.currency` không có trong migration** nhưng code đọc `$this->currency` — DB dev từng sync tay ⇒ schema drift | `StatementResource.php:38` | G3 |

## Nhóm 2 — HARDCODE / MÀN KHÔNG TRUY VẾT ĐƯỢC

| # | Nợ | Bằng chứng | Gate |
|---|---|---|---|
| H1 | Hardcode ngày `$today='2026-07-02'` trong màn nghiệp vụ | `app/Filament/Pages/StatementList.php:47` | G6 |
| H2 | Sort danh sách bằng **hash-shuffle giả lập** | `StatementList.php:58` | G6 |
| H3 | VAT hardcode `/1.08` thay vì đọc `fee_types.vat_percent` | `app/Filament/Pages/StatementDetail.php:54` | G6 |
| H4 | "X2 AI giải thích bảng kê" là **chuỗi cứng một câu** cho mọi hóa đơn | `x2mobile` `statement_detail_screen.dart:287` | G6 |
| H5 | `_MethodPicker` 4 ô (VietQR/Thẻ/Ví/AutoPay) — set state nhưng **không truyền vào sheet**; 3/4 lựa chọn không tồn tại ở backend | `statement_detail_screen.dart:299-350` | G6 |
| H6 | **App nói sai sự thật với cư dân**: "hệ thống sẽ tự động đối soát và cập nhật trạng thái hóa đơn" — không có webhook/IPN nào | `vietqr_screen.dart:72` | G6, G8 |
| H7 | 4 icon/nút no-op (`onPressed: () {}`) | `statements_screen.dart:35,114` · `statement_detail_screen.dart:36` · `payments_screen.dart:29` | G6 |
| H8 | Mock dùng vốn từ đã bị dọn (`status: 'completed'`) | `mock_payments_repository.dart:16,33,48` | G4 |

## Nhóm 3 — BẢNG/CỘT CÓ MÀ KHÔNG CÓ WRITE-PATH

| # | Nợ | Bằng chứng |
|---|---|---|
| W1 | `statement_approvals` — bảng + model, **không code nào ghi** (hạ tầng duyệt nhiều cấp bỏ không) |
| W2 | `statement_publish_logs` — như trên |
| W3 | `qr_payment_tokens` — chỉ CRUD, không write-path |
| W4 | `payment_allocations.statement_line_id` — **có cột, code chưa bao giờ ghi** |
| W5 | `community_post_reports.status` / `resolved_by_user_id` / `resolved_at` — **không một dòng code nào ghi**; `moderate()` không đóng report ⇒ hậu kiểm không có vòng đóng |
| W6 | 3 bảng đối soát ngân hàng (`bank_statement_imports`, `bank_transactions`, `reconciliation_matches`) — chưa có importer/matcher |
| W7 | `meter_readings` + `vehicles.monthly_fee` — **không dòng code nào nối vào `statement_lines`** |
| W8 | `fee_formulas.expression` — chuỗi, **không có evaluator** |
| W9 | `debt_reminder_campaigns` / `_logs` — có model, không thấy Page |

## Nhóm 4 — ISOLATION / TEST

| # | Nợ | Bằng chứng | Gate |
|---|---|---|---|
| T1 | **Bảng `comments` không có `tenant_id`** — cô lập hoàn toàn dựa vào scope `commentable`. Đúng thứ rule "không dùng `tenant_id` đơn thuần làm bằng chứng" cảnh báo, ở dạng nặng hơn: không có cả cột | `2026_07_25_100000_create_comments_table.php` | G2 |
| T2 | **0 test backend cho cộng đồng** (app có 54) — không test cô lập tenant, không access matrix, không snapshot payload | — | G7 |
| T3 | **0 test** cho `ApartmentWalletService`, `StatementApprovalQueue`, `BillingSummaryController`, `StatementController`, đối soát ngân hàng | — | G7 |
| T4 | `audit_logs` chỉ **1 dòng** trên DB dù C9 bắt buộc audit tài chính ⇒ write-path audit chưa kiểm chứng được | `docs/ERD_CURRENT_20260703.md:307` | G7 |
| T5 | **Ba hệ audit song song** (`audit_logs` / `activity_logs` / `activity_log` spatie) + các `*_audit_logs` | | G1 |
| T6 | `StatementApprovalQueue::audit()` **không ghi `subject_type`/`subject_id`** → không truy được bản ghi nào bị duyệt | `StatementApprovalQueue.php:200` | G10 |
| T7 | Lệch seed cực lớn: FEE_BILLING 8.720 rows vs domain khác < 50; 23 bảng `_archive` rỗng | `ERD_CURRENT_20260703.md:307` | G4 |
| T8 | DB chỉ có **7 bình luận thật** ⇒ chưa thể chứng minh migration tách bảng bình luận đúng. Gói mass seed có ở `handoff/` nhưng **chưa copy vào repo** | | G4 |

## Nhóm 5 — HỢP ĐỒNG APP ↔ BACKEND LỆCH

| # | Nợ | Bằng chứng | Gate |
|---|---|---|---|
| C1 | **Trả lời bình luận cộng đồng mất sau khi tải lại** — backend mặc định `whereNull('parent_id')` + trả `reply_count`; app không gửi `parent_id`, không parse `reply_count` | `CommunityPostController.php:212` vs `remote_comment_repository.dart` | G5 |
| C2 | **Đăng bài trong nhóm không vào nhóm** — `createPost()` không truyền `community_group_id` | `remote_community_repository.dart` | G5 |
| C3 | `sort=ranked\|latest` **không tồn tại ở server** → "Dành cho bạn" và "Mới nhất" là cùng một truy vấn | `CommunityController::posts` | G5 |
| C4 | **Feed + mọi list bỏ cursor** — gửi `per_page: 50`, bỏ `meta.next_cursor`. Căn >50 hóa đơn mất dữ liệu im lặng | `remote_*_repository.dart` | G5 |
| C5 | Bậc thang nhóm + nhãn tab **hardcode ở app** (thiếu `GET community/bootstrap`) — vi phạm chính nguyên tắc server-driven cả hai repo tuyên bố | `resident_community_screen.dart:90` | G5 |
| C6 | "Đã lưu bài" **chỉ sống ở client** — đổi máy là mất | `SavedPostsNotifier` | G5 |
| C7 | App **không parse** `quantity`, `unit_price`, `fee_type_id`, `published_at`, `claimed_statement_id` dù server đã trả | `statement_dto.dart` · `payment_dto.dart` | G5 |
| C8 | `proofImageUrls` parse xong nhưng **không màn nào render** — cư dân không xem lại được ảnh chứng từ đã nộp | `payment.dart:59` | G6 |
| C9 | **Hai đường lưu ảnh song song** — `community_posts.image_paths` (37/40 bài seeder) vs `attachments` polymorphic (bài API) ⇒ test bằng seed không bao giờ chạm nhánh attachments | | G4 |

## Nhóm 6 — DRIFT TÀI LIỆU

| # | Nợ |
|---|---|
| D1 | `PROGRESS_TRACKER.md` đánh 🟢 cho BQL-03-03/04/06 (chạy kỳ, duyệt, phát hành) — thực tế **không có runner, không có code publish** |
| D2 | `PROGRESS_TRACKER.md` đánh 🟢 cho BQL-07-08 (kiểm duyệt cộng đồng) — thực tế là **scaffold auto-gen ở `/fila`**, `/admin` không có màn cộng đồng nào |
| D3 | `DEV_JOURNAL.md` **trễ 3 commit** (chưa có entry nào cho 30/07) |
| D4 | `x2mobile/docs/guide/SUMMARY.md:14` trỏ tới `cu-dan/03-thanh-toan.md` — **cả thư mục `cu-dan/` không tồn tại** |
| D5 | `x2mobile/docs/dev/*/` **không có chương nào** cho cụm tiền và cụm cộng đồng |
| D6 | `X2_BMS_COMMUNITY_DOMAIN_HANDOFF_20260729.zip` **chưa giải nén** — 6 file docs community đều trỏ vào nó |
| D7 | Handoff billing 30/07 §1.5/§6.7 mô tả **sai bảng ví**: nói `wallet_transactions` (ví công ty HQ-02) trong khi app đọc `apartment_wallet_transactions` — bảng này **có** `direction` và đúng bộ `type` app giả định. P0 số 4 của handoff đó là **việc không cần làm** |
| D8 | `docs/PROGRESS_TRACKER.md:227` ghi ví "chưa migrate lần nào (máy dev không có PHP)" nhưng `DEV_JOURNAL.md:140` nói đã chạy — tracker chưa cập nhật |

## Nhóm 7 — VẬN HÀNH

| # | Nợ |
|---|---|
| O1 | **Live `x2.fino.vn` chưa deploy** code mới; APK trên máy thật đang test trên backend cũ ⇒ mọi kết luận "đã verify" chỉ đúng ở máy dev |
| O2 | VNPay/MoMo là stub cả 2 đầu — `TODO(owner-enable)`, `redirect_url: null`. Chỉ VietQR chạy |
| O3 | Không có webhook/IPN nào trong `routes/` (đã grep) — chủ dự án chốt làm sau |
