# IMPLEMENTATION REPORT — BQL Communication (BQL-NOTI) · 2026-08-07

Branch `feat/bql-notification-communication-wizard` (off `main`). Gói handoff
`X2_BMS_BQL_NOTIFICATION_HANDOFF_20260807`. Additive, feature-flagged (`x2.bql_wizard_enabled`), reversible.

## Test acceptance
`php artisan test` → **365 tests · 361 passed · 3 skipped · 1 failed · 1463 assertions** (~72s).
- +21 tests mới (suite `tests/Feature/Communication/*`), **tất cả xanh** (99 assertions).
- Fail duy nhất = **pre-existing** `TenantScopeRatchetTest` (BillingRunner 7 chỗ, từ billing engine 2026-08-04 —
  KHÔNG thuộc module này). Module này KHÔNG thêm vi phạm ratchet mới (AudienceResolver đã baseline, re-scope tường minh).

## Trạng thái theo màn / năng lực

| Mã | Màn / năng lực | Trạng thái | Ghi chú |
|---|---|---|---|
| BQL-NOTI-02 | Tạo nội dung (4 loại + field động) | **DONE** | Wizard bước 1; subtype news/event/poll |
| BQL-NOTI-03 | Chọn đối tượng | **DONE** | Scope theo quyền + nhóm đã lưu + ước tính live; DSL whitelist; dedupe + snapshot |
| BQL-NOTI-04 | Cấu hình kênh | **PARTIAL** | Chọn kênh + chiến lược + ước tính chi phí; cấu hình chi tiết per-kênh (subject/deep-link/brandname) = follow-up |
| BQL-NOTI-05 | Hẹn giờ & duyệt | **DONE** | send_now không bypass duyệt; tuyến duyệt theo config; quiet-hours config sẵn |
| BQL-NOTI-06 | Xem lại & phát hành | **PARTIAL** | Preflight + gửi duyệt; preview đa kênh dạng projection đầy đủ = follow-up |
| BQL-NOTI-07 | Chi tiết | **DONE** | Highlights + KPI + nội dung + kênh + tuyến duyệt + snapshot + actions vòng đời |
| BQL-NOTI-08 | Người nhận & trạng thái | **PARTIAL** | Bảng người nhận PII-mask + filter trong trang chi tiết; trang riêng 2/3+1/3 + bulk resend/remind/export-job = follow-up |
| — | Máy trạng thái campaign | **DONE** | 12 trạng thái, guard chuyển, map status cư dân |
| — | Duyệt maker-checker | **DONE** | Người tạo không tự duyệt; đa bước |
| — | Snapshot bất biến | **DONE** | Hash + version; phát hiện thay đổi sau duyệt |
| — | Audience resolve + dedupe + tenant scope | **DONE** | MUST_NOT_LEAK có test |
| — | Publish → delivery | **DONE** (đồng bộ) | App-inbox + push/email dispatcher sẵn có; job-hoá cho audience lớn = follow-up |
| — | Event/Poll link (không nhân đôi) | **DONE** | entity_type/entity_id → Event/Poll canonical |
| — | API cư dân additive | **DONE** | content_type + event/poll summary; field cũ khoá bằng test |
| — | Seeder demo | **DONE** | 12/8/6/6 + nhóm + template + delivery; idempotent |
| — | Provider Zalo/SMS thật | **NOT_IMPLEMENTED** (by design) | Cổng chờ; chờ owner chọn nhà cung cấp (ADR-002) |
| — | Recurring schedule | **NOT_IMPLEMENTED** | Ngoài phạm vi đợt này |

## Locked decisions đã tuân thủ
Campaign = `notifications` (không dựng song song) · delivery ledger = `notification_delivery_logs` · không nhân đôi
event/poll/comment · API cư dân không breaking · không "toàn hệ thống" · AI suggestion-only (chưa wire AI ở wizard) ·
snapshot bất biến sau gửi · seed/test không gửi thật · Zalo/SMS cổng chờ · CSS scope `.x2-bql-page`.

## Follow-up backlog (đã ghi DEV_JOURNAL)
- T3.1 bố cục 2/3 form + 1/3 preview sticky theo ảnh duyệt (hiện dùng placeholder từng bước).
- T4.1 trang recipients riêng 2/3+1/3 + bulk resend/remind/export qua Job; CTA click tracking.
- BQL-NOTI-04 cấu hình chi tiết per-kênh; BQL-NOTI-06 preview đa kênh đầy đủ.
- Job-hoá publish cho audience lớn (hiện đồng bộ, đủ cho demo/tòa nhỏ).
- Provider thật Zalo/SMS (chờ owner). BellReader thêm content_type nếu app cần.

## Deploy
```bash
git checkout feat/bql-notification-communication-wizard   # hoặc merge vào main
php artisan migrate --force        # 7 migration additive (000001..000007); composite FK/trigger chỉ MySQL
php artisan db:seed --class=CommunicationDemoSeeder   # CHỈ non-prod, cần X2_DEMO_SEED=true
# Bật/tắt wizard mới: X2_BQL_WIZARD=true|false (.env) — tắt = ẩn wizard/detail mới, giữ compose cũ
```

## Rollback
- **Nhanh (không đổi schema):** `X2_BQL_WIZARD=false` → ẩn wizard + detail mới; `NotificationCenter` compose cũ +
  dispatcher cũ vẫn chạy; API cư dân giữ nguyên (field mới additive, client cũ bỏ qua).
- **Hoàn tác schema:** `php artisan migrate:rollback` (7 migration reversible). Cột thêm nullable/additive; không drop
  bảng dữ liệu người dùng. Bản ghi đã phát hành không mất (status=published giữ nguyên).
