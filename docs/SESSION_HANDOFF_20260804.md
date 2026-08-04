# SESSION HANDOFF — 2026-08-04 · Thông báo đa kênh + Hard-lock tenant + Chuẩn bảo mật

> Tất cả đã **commit + push `main`** (`ccn112/x2bms-new`). Migration MySQL additive, guard theo driver.
> Deploy server: `git pull origin main && php artisan migrate --force` (+ re-seed nếu cần).

## 1. ĐÃ LÀM (đã test + push)

### A. Thông báo đa kênh (ADR-002 `docs/modules/notifications-multichannel/`)
- **Mail THẬT** qua Elastic Email; from/reply-to override theo tòa.
- Zalo/WhatsApp/Telegram/**X.Space (xhub)** = **cổng chờ**, cấu hình tham số **theo tòa**: bảng
  `building_notification_channels` + model + trang `/admin/notifications/channel-settings`.
  `MultiChannelNotifier` building-aware (provider_pending / provider_not_configured / channel_disabled).
- **Fix push:** form soạn trước thiếu kênh `push` → thêm "Đẩy về máy (Push)"; `NotificationExternalChannelDispatcher`
  gửi kênh ngoài cho audience **targeted** khi phát hành (broadcast không auto-gửi — tránh phí).
- **Bỏ "Toàn hệ thống"** khỏi form (không có FCM topic toàn hệ → push không đi đâu); target **bó theo quyền**
  người soạn + **validate phía server** (G9); guard `canManageBy` ở Phát hành/Lưu trữ.
- Seeder `HpoDemoSeeder` (tòa HPO/Happy One = tenant riêng HPO-DEMO + Company "Công ty vận hành" +
  TK `hq.hpo@`/`bql.hpo@x2bms.vn` mk `Bms@2026!`; file phí committed trong `database/seeders/data/`).
- Hướng dẫn test: `docs/test0408.md`. Cư dân test: `chtchinh@gmail.com`(căn #2628)/`chatto.vn@gmail.com`(#2629) mk `Test@2026!`.

### B. Hard-lock tenant tầng DB — ①③② trên MySQL (Postgres RLS: đã đánh giá 4–8 tuần → HOÃN)
- **③** `TenantScopeRatchetTest` + `tests/Architecture/tenant_scope_baseline.json`: khóa 83 chỗ bỏ-tenant-scope
  trên web, chặn sinh cửa-sau mới.
- **①** composite FK: notifications↔buildings (`..._000002`) + **NHÓM TIỀN 14 quan hệ** (`..._000003`).
  DB chặn ghi lai-tenant (SQLSTATE 1452).
- **②** trigger `payment_allocations` (`..._000004`): junction không có tenant_id → reject lai-tenant (45000).
- Chuẩn: `docs/adr/ADR-001-tenant-scope-discipline.md`, rule `.claude/rules/x2bms-laravel-domain.md`, TECH_DEBT T9.
- **Tài liệu chuẩn bảo mật (audit + cam kết KH):** `docs/security/SECURITY_CONTROLS_AND_STANDARDS.md`,
  `docs/security/OWASP_ASVS_V4_ACCESS_CONTROL_CHECKLIST.md` (ánh xạ OWASP A01/ASVS V4/Saltzer-Schroeder/
  AWS SaaS Lens/G9-G10; **tự đánh giá, chưa chứng nhận bên 3**).

## 2. Test / môi trường
- Test nặng (Livewire, full suite): chạy `php -d memory_limit=2G vendor/bin/phpunit` (Herd Windows mặc định 128MB).
- Test tenant-isolation: `NotificationAudienceScopeTest`, `TenantScopeRatchetTest`, `TenantCompositeFkTest` (mysql-only → skip sqlite).
- Local đã bật FCM (service-account `x2bms-a37d2` + `kreait/firebase-php` — vendor local trước thiếu, chính là lý do push từng không gửi). App debug qua `php artisan serve :8000` + `adb reverse tcp:8000`.

## 3. Batch feature 04/08 (sau bảo mật) — ĐÃ XONG
- **07-10** Analytics thông báo: `NotificationAnalyticsService` + page `/admin/notifications/analytics` (open-rate + phễu kênh + chi phí). Test 3/3.
- **BOLA/IDOR API cư dân** (audit): vá poll-vote + @mention xuyên tenant. `docs/security/DATA_LEAK_AUDIT_20260804.md`. Test CommunityPollScopeTest + CommunityCommentMentionTest.
- **B8** Phản ánh: cư dân **chấm sao** (`POST feedback/{id}/rating`). Test 4/4.
- **B5** Công nợ theo dịch vụ + ngăn tiền thừa theo tài sản (D6): xác minh đã xong + test cross-asset.
- **B6** Kiểm duyệt cộng đồng + đóng vòng report: xác minh đã xong (9 test).
- **C (P2.1)** Engine tính phí: khung + `ManagementFeeGenerator` + `BillingRunner` + `billing:run` + `billing:reconcile-engine` + `docs/BILLING_FEE_ENGINE_TEST_PLAN.md` + `FeeEngineDemoSeeder` (test trên tòa HPO). Test 3+1.

## 4. LÀM TIẾP (điểm dừng, ưu tiên)
1. **Engine tính phí P2.2–P2.5**: xe · điện/nước bậc thang · per-use · phạt/lãi (mỗi cái test thuần + đối soát vàng). Điền `area_sqm` cho căn import HPO + giá khớp để đối chiếu kỳ 2026-05 thật.
2. **Audit data-leak RỘNG (còn)**: BOLA API panel BQL/HQ/SA · over-exposure API · PII trong log · RBAC trong-tenant · MFA admin. (SECURITY_CONTROLS §5)
3. **N4 provider THẬT** (Zalo ZNS/WhatsApp/Telegram/X.Space) — **chờ owner chốt** nhà cung cấp + template + phí.
4. **iOS build** trên Mac (`flutter build ipa ... -t lib/main_prod.dart`); APK release đã cài Samsung.
5. Deploy server: `git pull && php artisan migrate --force` (migration tenant-hardlock 000002/03/04).
