# Implementation Plan — notifications-multichannel

> Chờ duyệt ADR-001. Mỗi slice = 1 PR, test-first, có isolation + idempotency, không gộp.
> Thứ tự tôn trọng phụ thuộc; không đổi khi chưa có lý do.

## N0 — `activity_notifications` + đọc chuông merge  ⭐ reference slice  ✅ XONG (backend, 2026-08-03)
> Đã dựng: bảng `activity_notifications` + `resident_bell_state`; `ActivityEmitter` (coalesce);
> `BellReader` (merge + unread + seen); endpoint `GET /resident/bell` + `POST bell/seen` +
> `POST bell/activities/{id}/read`; `BellDemoSeeder`. Test `BellReaderTest` 4/4 (no-fanout,
> isolation, coalesce, unread/seen). CÒN: nối app sang `/resident/bell` (mobile, đợt dọn UI);
> nguồn activity mới đang là seeder — event thật (phiếu/công nợ) ở N1.

- Migration bảng `activity_notifications` (DATA_MODEL §2) + `bell_seen_at` (§3).
- Service `BellReader::render(user, cursor)` = merge (broadcast audience-match read-time) ∪
  (activity của user), keyset. `unreadCount()` theo `bell_seen_at` + activity chưa đọc.
- Endpoint chuông đọc từ BellReader (thay vì chỉ `notifications`). Màn "Thông báo BQL" giữ nguyên.
- 1 nguồn activity thật để chứng minh: `TicketApproved` → EmitActivity.
- **Test**: broadcast-to-all KHÔNG đẻ dòng per-recipient; 2 cư dân khác căn thấy khác nhau;
  activity của A không lộ sang B (isolation); unread đếm đúng; keyset phân trang.
- **Chứng minh**: seed 1 broadcast toàn dự án + 1 phiếu-duyệt cho 1 người → đếm dòng DB.

## N1 — Fan-out sự kiện nội bộ + FCM topics  ✅ XONG (backend, 2026-08-03)
- **N1a FCM topics**: `PushService::toTopic/subscribeToTopics/unsubscribeFromTopics`; `ResidentTopics`
  (tenant/project/building); device subscribe khi đăng ký token (`DeviceTokenController`);
  `NotificationPushDispatcher` phân loại broadcast(all/project/building)→topic (1 message, KHÔNG ghi
  per-người) vs targeted(apartment/resident/user)→per-user A2. Test `NotificationPushDispatchTest` 8/8.
- **N1b event nội bộ**: BQL trả lời phiếu (`SlipCommentController`, payment/amenity/visitor) → activity
  cho chủ phiếu (coalesce/phiếu); BQL duyệt thanh toán (`ResidentPaymentClaimReviewer::approve`) →
  activity 'payment_confirmed'. Test `InternalEventActivityTest` (+idempotent).
- CÒN (backlog cùng pattern): amenity booking confirmed, feedback reply → EmitActivity khi cần.
- Lưu ý topic KHÔNG lọc mute per-người (ADR-001 hệ quả); audit topic-level ghi ở N3.

## N2 — Nối tương tác cộng đồng vào bell  ✅ XONG (backend, 2026-08-03)
- `CommunityPostController` (comment/reply/mention/reaction) → **persist `EmitActivity`** (kind=
  post_comment|comment_reply|mention|reaction, entity=community_post, action_key=view_post) **SONG SONG**
  với push hiện có. Comment-bài & reaction COALESCE theo `post:{id}:{kind}:{recipient}`; mention/reply
  giữ riêng (đích danh). → tự vào `GET /resident/bell` (BellReader đọc activity_notifications).
- **Test `CommunityActivityPersistTest` 2/2**: 2 người thả cảm xúc → 1 dòng `coalesce_count=2`;
  bình luận → activity cho chủ bài, deeplink `view_post`; người thả KHÔNG tự nhận (isolation).
- CÒN: push cộng đồng vẫn gọi thẳng FCM (ghi delivery_log = N3); `notifications.source=interaction`
  chưa gỡ (seeder demo cũ còn dùng) — deprecate ở N3.

## N3 — Làm giàu delivery_logs + màn AUDIT BQL  ✅ XONG (backend, 2026-08-03)
- Migration `..._000013` thêm `source_type/source_id/queued_at/delivered_at/read_at/
  provider_message_id/cost/topic` + `notification_id` nullable. `NotificationPushDispatcher`
  ghi audit **topic-level** cho broadcast (1 dòng/topic) + enrich per-user (source polymorphic + queued_at).
- Màn Filament `/admin/notifications/delivery-audit` (READ-ONLY, scoped `Notification::visibleTo`):
  cột thông báo/kênh/người nhận/trạng thái/gửi-nhận-đọc/chi phí/mã NCC/lỗi + lọc kênh & trạng thái.
- Test: broadcast → 1 dòng topic-level (`NotificationPushDispatchTest`); per-user enrich source/queued.
- CÒN: callback provider cập nhật `delivered_at/read_at` (theo từng kênh ở N4); push tương tác
  cộng đồng (activity) chưa ghi delivery_log — nằm ngoài audit BQL (là tương tác cư dân–cư dân,
  bản thân dòng activity đã là vết); nối khi có kênh ngoài cho community.

## N4 — Kênh email / SMS / Zalo / thư tay  ✅ KHUNG XONG (backend, 2026-08-03) — chờ provider
- Interface `Channels\ChannelDispatcher` + `MultiChannelNotifier` (gửi 1 người × nhiều kênh, ghi sổ
  gửi per-(người×kênh) đầy đủ vòng đời + `cost`, idempotent theo source×người×kênh).
- **Email**: `EmailChannelDispatcher` qua Laravel Mail — CHẠY THẬT (driver theo `.env`), cost 0.
- **SMS/Zalo/thư tay**: `PendingProviderChannelDispatcher` (stub) — ghi 'queued' + 'provider_not_configured'
  để audit thấy ý định; **chưa gửi thật**. Cắm provider = viết adapter mới + thay trong `$registry`.
- Test `MultiChannelNotifierTest` 2/2 (email sent + stub queued; idempotent).
- **CẦN CHỦ DỰ ÁN CHỐT** để làm thật SMS/Zalo/thư tay: nhà cung cấp (SMS brandname? Zalo ZNS OA?),
  ai trả phí, template được duyệt, cơ chế đối soát cost. → ADR-002 riêng.
- CÒN: nối `MultiChannelNotifier` vào luồng phát hành BQL (chọn kênh email/SMS ở NotificationCenter)
  — chưa auto-wire vì cần UX quyết định "khi nào dùng kênh trả phí" + broadcast email không dùng topic
  (per-người, tốn phí) nên chỉ cho phạm vi nhỏ/targeted.

## Không làm trong cụm này (tránh scope creep)
- Sửa nội dung/luồng nghiệp vụ phiếu/công nợ/tiện ích — chỉ nhận event.
- Quản lý nội dung bình luận cộng đồng — đã ở `community_comments`.
- Chuyển bell sang Redis/stream — chỉ khi DB chạm trần (ghi backlog, không làm sớm).

## Ghi chú liên kết audit AR-05
Cụm này hiện thực AR-05 (outbox/events) theo lát: EmitActivity nên đi qua transactional outbox
để "sự kiện ghi cùng transaction nghiệp vụ, worker retry idempotent, replay được". N0 có thể tạm
emit trực tiếp; N1 nối outbox chuẩn.
