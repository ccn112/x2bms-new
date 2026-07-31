# SKILL INSTALL REPORT — X2BMS AI-First Delivery

**Ngày cài:** 2026-07-31 · **Nguồn:** `D:/Code/handoff/x2bms/X2BMS_AI_FIRST_DELIVERY_SKILL_20260731`
**Kết quả `scripts/validate-handoff.sh`:** `VALIDATION: PASS`

## 1. File đã thêm

| Đích trong repo | Nguồn | Ghi chú |
|---|---|---|
| `.claude/skills/x2bms-domain-seed-contract-delivery/SKILL.md` | như nguồn | **có sửa** — xem §3.5 |
| `.claude/rules/x2bms-{laravel-domain,filament,api-flutter,seed-testing}.md` | như nguồn | nguyên văn |
| `.claude/commands/x2bms-{audit-and-plan,deliver-slice,verify-slice}.md` | như nguồn | nguyên văn |
| `docs/delivery/01_OPERATING_MODEL.md` | `docs/` | nguyên văn |
| `docs/delivery/02_FILAMENT_DECISION_MATRIX.md` | `docs/` | **có sửa** — §3.2 |
| `docs/delivery/03_VERTICAL_SLICE_GATES.md` | `docs/` | **có thêm** — §3.1 |
| `docs/delivery/04_INITIAL_PHASE_PLAN.md` | — | **viết lại** — §3.3 |
| `docs/delivery/04_INITIAL_PHASE_PLAN_ORIGINAL.md` | `docs/04_INITIAL_PHASE_PLAN.md` | bản gốc giữ nguyên văn để tham chiếu |
| `docs/delivery/05_MIGRATION_FROM_SCREEN_FIRST.md` | `docs/` | nguyên văn |
| `docs/delivery/templates/*.md` (9 file) | `templates/` | nguyên văn |
| `docs/delivery/TECH_DEBT_REGISTER.md` | — | mới, 60 mục có bằng chứng code |
| `scripts/validate-handoff.sh` | như nguồn | nguyên văn |
| `CLAUDE.md` | merge từ `CLAUDE_X2BMS_DELTA.md` | **không ghi đè** — thêm mục mới ở đầu |

**Không cài:** `README.md`, `START-HERE.md`, `INSTALL_PROMPT_FOR_CLAUDE.md`,
`CLAUDE_X2BMS_DELTA.md` — là tài liệu của gói handoff, không phải của repo. Nội dung
non-negotiable của DELTA đã merge vào `CLAUDE.md`.

## 2. Phiên bản thực tế (từ lockfile, không từ tài liệu)

| | Khai trong `composer.json` | **Khóa trong `composer.lock`** |
|---|---|---|
| Laravel | `^13.8` | **v13.17.0** |
| Filament | `5.*` | **v5.6.7** |
| PHP | `^8.3` | — (`php` không có trong PATH của shell hiện tại) |
| DB | | **MySQL**, database `x2bms` |
| Flutter | | Dart SDK `^3.11.0`, app `resident_mobile` v0.1.0+1 |

⚠️ `php` không gọi được từ shell hiện tại (Herd). `DEV_JOURNAL.md:140` xác nhận máy dev
**chạy được** PHP — nên đây là vấn đề PATH của shell, không phải môi trường. Command
`migrate`/`seed`/`test` thực tế **chưa xác minh lại trong phiên này**.

## 3. Năm điểm sửa so với gói gốc

### 3.1 Thêm G9 + G10 vào gate (`03_VERTICAL_SLICE_GATES.md`)
Gói gốc có G0–G8, **không có gate nào về bất biến tài chính** — với SaaS billing đây là
chỗ hậu quả nặng nhất. Và thiếu hẳn gate kiểm "còn cửa sau nào không", đúng cái khoảng
trống sinh ra `MyWork.php:338` và form `/fila/payments`.

- **G9 anti-bypass** — liệt kê MỌI code path mutate được state; grep chứng minh; test gọi
  thẳng đường vòng phải bị từ chối.
- **G10 money & authority** — một đường ghi duy nhất · bất biến tầng DB · số nguyên đồng ·
  làm tròn từng dòng · idempotent + lock · reversal · maker-checker · audit đủ
  `subject_type`/`subject_id` · `/sa` không bao giờ duyệt tiền.

### 3.2 Filament matrix — khóa Resource thô cho bảng tiền
Bảng gốc ghi "Thu phí/công nợ → **Resource** + custom actions/page". Đã sửa thành
**Custom Page bắt buộc**, vì chính Resource thô là chỗ nguy hiểm nhất đang tồn tại:
`PaymentForm.php:33` cho sửa `status` tự do, vô hiệu hóa toàn bộ
`ResidentPaymentClaimReviewer` (transaction + 2 lớp lock + 11 test) bằng một form CRUD
sinh tự động.

### 3.3 Viết lại phase plan theo trạng thái thật
Bản gốc (A0→A6) viết như repo là greenfield. Thực tế: A1 `resident-identity` **phần lớn đã
xong** (`ResidentImportProfile`, `ResidentIdentityMatcher`, `GlobalUserAccount` activation);
A5 fee/debt **đã qua read-only từ lâu** (claim + review + allocation + receipt + 11 test);
A6 community **~90% xong** (20 route, 7.744 dòng app, 54 test). Đi theo A1–A6 nguyên văn sẽ
làm lại việc đã xong và để hồi quy mất trả lời bình luận sống thêm nhiều tháng.

Bản mới: **B0 sửa 5 lỗi đang sống** → **B1 reference slice = Billing Charge Import** →
B2 duyệt/phát hành maker-checker → B3 phân bổ theo dòng → B4 thứ tự ưu tiên → B5 ngăn theo
tài sản + màn công nợ theo dịch vụ → B6 kiểm duyệt cộng đồng → B7 số nguyên đồng →
B8 phản ánh → **C engine tính phí**.

### 3.4 `docs/` → `docs/delivery/`
Gói gốc hướng dẫn `cp -R docs <REPO>/`. `x2bms/docs/` đã có `PROGRESS_TRACKER.md`,
`DEV_JOURNAL.md`, `api/`, `contracts/`, `guide/`, `dev/`, 6 file `COMMUNITY_*.md`,
`ERD_CURRENT_*`, 3 file billing mới — đổ `01_…05_` vào cùng cấp là trộn tài liệu **phương
pháp** với tài liệu **sản phẩm**. Đã tách sang `docs/delivery/` (đúng namespace mà
`START-HERE.md` vốn dùng cho output).

### 3.5 Artifact theo tầng rủi ro, không phải 10 cho mọi module
Repo có ~100 màn. Bắt đủ 10 artifact cho mọi CRUD danh mục thì không ai duy trì nổi — và
repo **đã có drift tài liệu** (8 mục nhóm D6 trong TECH_DEBT_REGISTER). Đã chia 3 mức:
tiền/quyền/danh tính/kiểm duyệt = đủ 10 + G9 + G10; có state machine = 8 artifact;
CRUD master data = 4 artifact.

## 4. Xung đột với rule/quy trình hiện có — đã giải quyết

**Xung đột chính:** `CLAUDE.md` đang bắt buộc skill `cap-nhat-tai-lieu` (Track 1–4 →
`PROGRESS_TRACKER` → GitBook → `DEV_JOURNAL` → Docs CMS). Gói mới bắt buộc
`docs/modules/<key>/` với 10 artifact. Đây là **hai hệ tài liệu song song** trong một repo
đã không giữ nổi một hệ.

**Đã chốt trong `CLAUDE.md`:** artifact `docs/modules/` là **đầu vào thiết kế** (trước khi
code); Track 1–4 là **đầu ra vận hành** (sau khi code); `docs/PROGRESS_TRACKER.md` là
**nguồn duy nhất về trạng thái**. Không đánh trạng thái ở chỗ khác.

**Không có xung đột** giữa 4 file `.claude/rules/` mới và code hiện có ở mức cú pháp —
nhưng chúng **tố giác nợ có thật**, đã ghi hết vào `TECH_DEBT_REGISTER.md`:
- "Add database constraints for invariants that must survive every code path" → M5
- "Do not hardcode counts, amounts, trend data" → H1–H8
- "Không dùng `tenant_id` đơn thuần làm bằng chứng isolation" → T1 (bảng `comments`
  **không có** cột `tenant_id`)
- "Migration reversible" → M13 (`2026_07_30_170001` không có `down()`)

Không có skill/rule nào bị ghi đè. `cap-nhat-tai-lieu` giữ nguyên.

## 5. Module đề xuất làm reference slice

**`billing-charge-import`** — thay cho `resident-identity` của gói gốc.

Lý do: là việc đang cần thật (chủ dự án chốt D9 hôm nay: engine tính phí sang Phase 2,
giai đoạn đầu kế toán import); đi trọn vòng migration → service → seed → Filament → test →
evidence; **buộc đi qua G9 + G10** vì là tiền; đặt trên `StagingImporter` đã có nên không
phải dựng hạ tầng; và tạo ra **bộ test vàng** để nghiệm thu engine ở Phase C.

Chuẩn: `docs/BILLING_IMPORT_SPEC_20260731.md` · `docs/BILLING_OWNER_DECISIONS_20260731.md`

## 6. Việc còn lại trước khi chạy `/x2bms-deliver-slice`

- [ ] Xác minh lại command `migrate` / `seed` / `test` thực tế (PHP chưa gọi được từ shell
      phiên này)
- [ ] Tạo `docs/modules/billing-charge-import/` với 10 artifact theo template
- [ ] Chốt: B0 (5 lỗi đang sống) làm trước hay làm song song với B1
