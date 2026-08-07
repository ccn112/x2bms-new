# UAT EVIDENCE — BQL Communication · 2026-08-07

Kịch bản UAT (spec 14 §5). Mỗi kịch bản nêu: phủ tự động (test) + cách kiểm thủ công (owner/QA).
Ảnh màn hình thật cần chạy app + `/admin` (owner thực hiện — xem "Cách chạy" cuối trang).

| UAT | Kịch bản | Phủ tự động | Kiểm thủ công |
|---|---|---|---|
| UAT-01 | Thông báo: tạo → chủ hộ S1/S2 → App+Email → hẹn giờ → duyệt → phát hành → cư dân đọc → KPI | `CommunicationWizardTest` + `CommunicationPublishTest` (tạo→resolve→duyệt→phát hành→delivery inbox) | Wizard `/admin/notifications/create`, chọn tòa + kênh App/Email, gửi duyệt; tài khoản khác duyệt ở trang chi tiết; Phát hành; mở app cư dân đọc → KPI "Đã đọc" tăng |
| UAT-02 | Tin tức nổi bật + ảnh bìa → hiển thị resident/public → App/Web → CTA | `ContentSubtypeTest` (news meta) + `CommunicationApiContractTest` (content_type=news) + seeder 8 tin | Tạo tin tức có ảnh bìa + featured; kiểm app hiển thị mục Tin tức + CTA |
| UAT-03 | Sự kiện PCCC sức chứa 120 → đăng ký → waitlist khi đầy → check-in → thống kê | `ContentSubtypeTest` (event link, capacity, ends_at) + seeder 6 sự kiện (open/full_waitlist/completed/cancelled) | Tạo sự kiện; app đăng ký; khi đủ chỗ → waitlist; QR check-in; chi tiết xem thống kê |
| UAT-04 | Poll phạm vi căn hộ → 1 phiếu/căn → đóng → quyền xem kết quả + aggregate khớp | `ContentSubtypeTest` (poll options) + `CommunicationSeederTest` (**vote_count option == poll.vote_count**) + migration unique (poll_id,apartment_id) | Tạo poll vote_scope=apartment; app bình chọn; kiểm 1 phiếu/căn; đóng → kết quả theo result_visibility |
| UAT-05 | Fake App success + Email bounce + SMS invalid → partial → lọc lỗi → resend/fallback → audit | Seeder `delivery_samples` (read/delivered/bounced/failed/invalid_phone) + `NotificationPublisher` (partial-aware) | Trang chi tiết → bảng người nhận → lọc trạng thái "Lỗi"; (bulk resend = follow-up T4.1) |
| UAT-06 | BQL A truy cập campaign/recipient của B → 403/404, không rò PII | `CommunicationDomainTest::test_resolver_dedupe...` (**MUST_NOT_LEAK**) + `CommunicationDetail::mount` (canManageBy→403) + PII mask trong bảng người nhận | Đăng nhập BQL tòa A, mở `/admin/notifications/detail?record=<của B>` → 403 |

## Trạng thái
- Lõi nghiệp vụ (tạo/duyệt/phát hành/resolve/snapshot/cách ly tenant/aggregate) — **đã chứng minh bằng test tự động**.
- Ảnh màn hình 7 màn + đăng ký/vote/check-in end-to-end trên app thật — **cần owner/QA chạy** (chưa kèm ảnh ở đây).
- Bulk resend/remind/export + preview đa kênh đầy đủ — follow-up (T4.1), chưa nằm trong UAT-05 tự động.

## Cách chạy để lấy ảnh + UAT thủ công
```bash
# 1) DB demo (local)
X2_DEMO_SEED=true php artisan migrate:fresh --seed        # gồm CommunicationDemoSeeder
# hoặc chỉ seed truyền thông trên DB có sẵn:
X2_DEMO_SEED=true php artisan db:seed --class=CommunicationDemoSeeder

# 2) Web BQL
php artisan serve --host=127.0.0.1 --port=8123
#   /admin/notifications/center  → danh sách (link "Chi tiết (mới)")
#   /admin/notifications/create  → wizard 5 bước

# 3) App cư dân (máy thật) — xem memory x2mobile-apk-build-flavor
adb reverse tcp:8123 tcp:8123
flutter run -t lib/main_dev.dart --dart-define=X2_API_BASE_URL=http://127.0.0.1:8123/api/v1 --dart-define=X2_USE_MOCK=false
```
Tài khoản BQL demo: theo DemoDataSeeder (tenant T-X2-DEMO). Không curl-login tài khoản app đang đăng nhập (xóa token → 401 hàng loạt).
