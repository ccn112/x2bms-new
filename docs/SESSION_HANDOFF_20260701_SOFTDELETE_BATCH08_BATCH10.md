# X2-BMS — Session Handoff (2026-07-01) · Soft Delete + Batch 08 + Batch 10

Bàn giao phiên làm việc 2026-07-01 (phiên nối tiếp). Phiên này làm **3 khối lớn**:
1. **Soft Delete toàn hệ + Global scope tầng Project + Archive log** (nền tảng ORM).
2. **Batch 08 — Integration Center, API Key & Webhook** (10 màn platform).
3. **Batch 10 — Support Center, Ticket & Data Correction** (10 màn platform).

Đọc kèm `docs/DEV_JOURNAL.md` (3 entry mới nhất ở đầu = chi tiết từng thay đổi của phiên này).

## 0. Đọc trước (reading order phiên sau)
1. `docs/DEV_JOURNAL.md` — nhật ký code (mới nhất ở đầu).
2. File này + `docs/SESSION_HANDOFF_20260701_SUPERADMIN_BILLING.md` (Batch 07/SuperAdmin).
3. Handoff nguồn: `C:\app\x2-bms\handoff\X2_BMS_BATCH_08_INTEGRATION_CENTER_HANDOFF_20260701\` và `..._BATCH_10_SUPPORT_CENTER_HANDOFF_20260701\`.

---

## 1. Khối 1 — Soft Delete + BelongsToProject + Archive (migration `..._000025`)

- **Soft delete** cho **mọi bảng nghiệp vụ TRỪ** framework/log/append-only/pivot (DENY set trong migration + codemod). 156 model `+SoftDeletes`.
- **Trait `App\Models\Concerns\BelongsToProject`** (opt-in, 17 model vận hành lõi): auto-detect cột — `project_id` nếu có, else `building_id ∈ buildings của dự án user được phép`. No-op ở console + platform admin + tenant operator (HQ). Bypass `withoutGlobalScope('project')`.
- **Unique + deleted_at**: rebuild composite `[col, deleted_at]` cho `buildings.code`, `projects.code`, `tenants.code`, `users.email`.
- **Archive**: bảng `*_archive` + lệnh `logs:archive` (`App\Console\Commands\ArchiveStaleLogs`, schedule 02:30) + `config/archive.php`.
- **`/fila`**: trait `App\Filament\Concerns\SoftDeletableResource` + TrashedFilter/Restore/ForceDelete (82 resource).
- **Bẫy**: `Schema::getTableListing()` (Laravel 13/MySQL) trả tên schema-qualified `db.table` ⇒ strip prefix trước khi so DENY.

## 2. Khối 2 — Batch 08 Integration Center (migration `..._000026`, 18 bảng)

- Reconcile: **drop** `integration_connections` per-tenant sơ khai → rebuild platform-level canonical.
- 18 model (không tenant scope), service `App\Support\Integration\IntegrationSecret` (Crypt encrypt / sha256 hash / mask), trait `WritesIntegrationAudit`.
- 17 resource `/fila` (nav 'Integration Center', **đã strip secret fields**, soft-delete UX).
- 7 màn bespoke `/admin` (nav 'Integration Center', gate `PlatformScreen`): Overview · Connection Mgmt+Detail · API Key Mgmt+Create · Webhook Mgmt+Test · Event Monitor · Health & Retry Queue · Security Settings (enforce HMAC, emergency disable).
- API `platform/integrations` (7 controller, `platform.admin`). **Secret hiện MỘT LẦN** khi create/rotate.
- Test `Batch08IntegrationApiTest` — **11/11 PASS**.

## 3. Khối 3 — Batch 10 Support Center (migration `..._000027`, 27 bảng)

- Reconcile: **drop** `support_tickets`/`support_ticket_comments`/`data_fix_requests` cũ → canonical Support Center.
- 27 model platform-level, trait `WritesSupportAudit`.
- **3 yêu cầu UI chủ dự án (đã áp đủ):**
  1. **Số thống kê đúng ảnh** — priority Critical 12 / High 46 / Medium 132 / Low 120 (tổng 310), 28 escalated, 37 near-breach (từ ticket seed); % SLA 88.4 / breach 11.6 / CSAT 4.6 từ `support_reports` snapshot `DASH-CURRENT`; report tháng 06 (1248 / 14h36m / 96.8% / 312 / 24 / 4.7) từ `support_reports` type=resolution. Tất cả từ DB.
  2. **Listing luôn có title click → chi tiết** (`->action($this->detailAction())` trên cột tiêu đề).
  3. **Textarea → RichEditor** (HTML editor mặc định) ở mọi form thêm mới.
- 11 resource `/fila` + 8 màn bespoke `/admin` (nav 'Support Center'): Dashboard · Ticket Queue+SLA (detail modal timeline + create + bulk assign + escalate/close/reopen) · Tenant Profile · Data Correction (duyệt 2 người high/critical) · Data Fix Wizard (bắt buộc snapshot trước execute → rollback) · KB · Escalation & Assignment · Audit Report.
- API `platform/support` (3 controller). Test `Batch10SupportApiTest` — **10/10 PASS**.
- **Bẫy**: (a) `fn (string $s)` → 500, đổi `$state`; (b) FK auto-name >64 ký tự (`data_correction_affected_records`) → đặt tên FK ngắn `dcar_request_fk`.

---

## 4. Cách chạy & verify (Windows + Herd)

```powershell
php artisan migrate:fresh --seed            # sạch; 000025/000026/000027 chạy OK
php artisan test --filter Batch08IntegrationApiTest   # 11/11
php artisan test --filter Batch10SupportApiTest       # 10/10
php artisan test --filter Batch07BillingApiTest       # 10/10 (không hồi quy)
php artisan logs:archive --dry-run          # kiểm tra archive
```
- Bash dùng `~/.config/herd/bin/php.bat`. Render headless: script `scratchpad/render_*.php` (bootstrap console kernel → HTTP kernel → `auth()->guard('web')->setUser($admin)`).
- Dev admin: `x2bms@x2bms.vn` / `Bms@2026!` (is_platform_admin).
- **Nav groups mới:** 'Integration Center' (icon bolt), 'Support Center' (icon lifebuoy) trong `AdminPanelProvider`.

## 5. Trạng thái tổng & việc còn lại

**DONE phiên này:** soft delete + project scope + archive; Batch 08 (DB+UI+API+test 11/11); Batch 10 (DB+UI+API+test 10/10). `migrate:fresh --seed` sạch; toàn bộ màn render 200; không hồi quy.

**Còn lại / tùy chọn:**
- **Sanctum** cho API platform (Batch 07/08/10 hiện auth qua phiên Filament/actingAs, chưa stateless).
- Batch 08: connector provider thật (test/health đang mô phỏng); StaffProfilesTable chưa gắn trashed UX.
- Batch 10: Escalation Kanban kéo-thả (đang bảng + workload cards); AI-suggest KB theo ngữ cảnh ticket (chưa nối X2AI); wizard stepper trang riêng đầy đủ.
- **BQL-4 Tài chính** (WEB-FORM-08 công nợ + duyệt chi + biên lai) — roadmap chính chưa làm.
- Chạy full `php artisan test` (toàn bộ suite) sau loạt đổi model soft-delete.

## 6. Memory liên quan
- `x2-bms-backend-runbook`, `x2-bms-build-decisions`, `x2bms-dev-journal-rule` (thư mục `C--app-x2-bms/memory`).
- Bẫy Filament tái phát: **cột closure param PHẢI `$state`** (không `$s`); **FK name ≤ 64 ký tự** (MySQL); `Schema::getTableListing()` trả tên schema-qualified.
