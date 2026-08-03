# DEPLOY 2026-08-03 — Nhóm A (A1–A4) + seed demo

Nối tiếp nhánh `feat/billing-canonical-p1-p3` (backend) / `feat/finance-ui-gate-e` (mobile).
Gồm 4 lát cắt: A1 cờ non-selectable · A2 persist per-recipient push · A3 acknowledge ·
A4 tách nguồn BQL-feed.

## 1. Migration mới (chạy trên server)
```bash
cd <APP_ROOT> && git pull && composer install --no-dev --optimize-autoloader
php artisan migrate --force
```
5 migration nhóm A (đều additive, reversible):
- `..._000009_add_unique_recipient_to_notification_delivery_logs` — khoá idempotency push (A2)
- `..._000010_add_acknowledged_at_to_notification_reads` — cột ack (A3)
- `..._000011_add_source_to_notifications` — tách nguồn `source` (bql|interaction) (A4)
- (đã có trong batch 03/08: `..._000008_add_taxonomy` cho `requires_ack`; `..._000003 liability_periods` cho A1 chủ cũ)

## 2. Seed demo để test (đúng THỨ TỰ)
```bash
php artisan db:seed --class=CommunityTestResidentsSeeder --force   # 2 TK test + căn hộ
php artisan db:seed --class=DebtByServiceDemoSeeder --force        # công nợ D6 (xe 05/06/07)
php artisan db:seed --class=GroupAFeaturesDemoSeeder --force       # NHÓM A: A1/A3/A4
```
`GroupAFeaturesDemoSeeder` idempotent, gắn cho `test.cudan1@x2bms.vn` + `test.cudan2@x2bms.vn`
(mật khẩu `Test@2026!`). Nếu TK chưa có căn hộ → nó tự bỏ qua + báo (chạy 2 seeder trên trước).

## 3. Nghiệm thu trên app
| Lát | Ở đâu | Kỳ vọng |
|---|---|---|
| **A1** | Công nợ theo dịch vụ | "Phí quản lý · 03/2026" **khoá tick**, nhãn "Nợ của chủ cũ"; xe 05/06/07 vẫn tick được. Chọn-tất-cả KHÔNG gồm khoản khoá. Cố gửi khoản khoá → server trả 422 `charge_not_selectable`. |
| **A1** | Chi tiết bảng kê 07/2026 | Dòng "Phí quản lý" đã trả → cờ `selectable=false`, reason `paid`. |
| **A2** | (server) | Sau `push:demo-round`, bảng `notification_delivery_logs` có 1 dòng/người/kênh; chạy lại KHÔNG nhân đôi. |
| **A3** | Thông báo "Diễn tập PCCC" | Màn chi tiết hiện nút **"Tôi đã tiếp nhận"**; bấm → `POST notifications/{id}/ack`, đổi sang "Đã xác nhận". |
| **A4** | Chuông vs màn Thông báo BQL | "Có người bình luận bài viết của bạn" (source=interaction) HIỆN ở chuông, KHÔNG hiện ở màn Thông báo BQL. |

## 4. Bắn thử push một vòng (tùy chọn, cần FCM_ENABLED=true + service account)
```bash
php artisan db:seed --class=PushRoundDemoSeeder --force
php artisan push:demo-round
```
Giờ mỗi lượt bắn ghi vết vào `notification_delivery_logs` (A2) — kiểm gating kênh + đối soát.

## 4b. N0 — Chuông hợp nhất (module notifications-multichannel)
Migration `..._000012_create_activity_notifications` (bảng `activity_notifications` +
`resident_bell_state`) — đã nằm trong `migrate --force` ở mục 1.

Endpoint MỚI (backend, chưa nối app — sẽ nối trong đợt dọn UI / slice mobile riêng):
- `GET /resident/bell` — chuông hợp nhất: broadcast áp cho tôi (read-time) + activity của tôi. Mỗi item có `type` (announcement|activity).
- `POST /resident/bell/seen` — bump mốc đã-thấy (đưa chưa-đọc broadcast về 0).
- `POST /resident/bell/activities/{id}/read` — đánh dấu đọc 1 activity.

Seed demo để test bằng API/khi app nối:
```bash
php artisan db:seed --class=BellDemoSeeder --force   # 3 activity/TK: phiếu duyệt · công nợ · cảm xúc
```
Kiểm nhanh (chưa cần app): gọi `GET /resident/bell` với token cư dân → thấy merge broadcast + activity, `unread` đếm đúng; `POST bell/seen` → unread broadcast về 0.

> Lưu ý: app hiện VẪN dùng `GET /resident/notifications` cho chuông (chưa nhân activity).
> Nối app sang `/resident/bell` là việc mobile (đợt dọn UI). Backend đã sẵn + có test.

## 5. Rollback
Migration additive: `php artisan migrate:rollback --step=3` (gỡ 000009/000010/000011).
Seed demo xóa tay theo `code LIKE 'DEMO-A-%'` + `LiabilityPeriod where source='demo'` nếu cần.
