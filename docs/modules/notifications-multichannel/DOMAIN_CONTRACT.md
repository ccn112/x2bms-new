# Domain Contract — notifications-multichannel

> Chờ duyệt. Đọc kèm `ADR-001` + `DATA_MODEL.md`.

## Ubiquitous language
- **Announcement (Thông báo BQL)**: bản tin chính thức, chủ động soạn, phát tới một **audience**
  (phạm vi). Một nội dung dùng chung cho nhiều người.
- **Activity notification (Thông báo hoạt động / bell)**: một dấu hiệu nhắm **một** người về một
  sự kiện của riêng họ (phiếu duyệt, trả lời công nợ) hoặc tương tác (bình luận/cảm xúc/@mention).
- **Bell (Chuông)**: khung nhìn HỢP NHẤT của cư dân = announcement áp cho tôi ∪ activity của tôi.
- **Delivery (Lần gửi)**: một lần đẩy một item qua một **kênh** tới một người (hoặc một topic).
- **Channel (Kênh)**: in-app | push | email | sms | zalo | postal.
- **Audience (Phạm vi)**: all | project | building | apartment | resident | user.
- **Seen high-water (`bell_seen_at`)**: mốc thời gian cư dân mở chuông gần nhất.

## Aggregate and entities
- **Announcement** (aggregate root `notifications`) ⟶ audiences, channels, (ack) reads.
- **ActivityNotification** (aggregate root, recipient-centric) — độc lập, không thuộc announcement
  (trừ khi `kind=announcement` trỏ `announcement_id`).
- **DeliveryLog** (ledger, xuyên suốt) — trỏ polymorphic tới announcement hoặc activity hoặc topic.
- **CommunityComment** (`community_comments`, GĐ7) — **ngoài** aggregate này; chỉ là NGUỒN sự kiện.

## Relationships and cardinality
- Announcement 1—N Audience; Announcement 1—N DeliveryLog (thường topic-level cho push broadcast).
- ActivityNotification N—1 recipient (user); N—1 actor (nullable); N—1 announcement (nullable).
- DeliveryLog N—1 (announcement|activity); N—1 recipient (null nếu topic-level).
- User 1—1 bell_seen_at.

## Invariants
1. Broadcast **KHÔNG** đẻ dòng per-recipient để hiển thị (chỉ 1 dòng nội dung) — ADR-001.
2. ActivityNotification luôn có `recipient_user_id` (đã targeted); cư dân chỉ đọc dòng của mình.
3. DeliveryLog idempotent theo `(source_type, source_id, recipient_user_id, channel)`.
4. Đã đọc broadcast suy từ `bell_seen_at`; `notification_reads` chỉ ghi cho `requires_ack`.
5. Tiền/án phí kênh (cost) chỉ ghi ở DeliveryLog, không suy đoán.
6. Không kênh nào được gửi khi cư dân đã tắt kênh (trừ emergency) — kiểm ở dispatcher.

## Commands/use cases
- `PublishAnnouncement(content, audience, channels, requires_ack?)`
- `EmitActivity(recipient, kind, entity, actor?, action_key?)` — từ domain event.
- `RenderBell(user, cursor)` — merge read-time.
- `MarkBellSeen(user)` · `MarkActivityRead(user, id)` · `AcknowledgeAnnouncement(user, id)`
- `Dispatch(item, channel)` — persist delivery → gửi → cập nhật vòng đời.
- `AuditDeliveries(filter: announcement|apartment|channel|time)`

## Domain events (nguồn sinh activity)
`TicketApproved`, `DebtCommentReplied`, `PaymentReceived`, `AmenityBookingConfirmed`,
`CommunityPostCommented`, `CommunityPostReacted`, `CommunityMentioned`, `ProjectFollowed`…
→ mỗi handler `EmitActivity(...)` cho đúng người nhận. (Theo AR-05: qua transactional outbox.)

## Audit requirements
- Mọi lần gửi kênh ngoài ghi DeliveryLog với vòng đời `queued→sent→delivered→read` + provider id.
- BQL tra được: theo 1 thông báo (mọi người/kênh/trạng thái), theo 1 căn (lịch sử nhận), theo kênh.
- Broadcast push audit ở mức **topic** (bản chất FCM) — nêu rõ giới hạn (không có delivered per-người).

## System of record
- Nội dung broadcast: X2 (`notifications`). Bell targeted: X2 (`activity_notifications`).
- Trạng thái gửi kênh ngoài: **provider là SoR về delivered/bounced**; X2 lưu bản sao + provider id.
- Bình luận cộng đồng: `community_comments` (module khác) — không dual-write ở đây.

## Open questions/blockers
- (ADR-001 câu hỏi mở) badge coarse vs per-item cho broadcast; retention; coalesce; kênh audit bắt buộc.
- Quản lý subscribe/unsubscribe FCM topic khi cư dân đổi căn/rời dự án (thiết kế ở N1).
- Chốt provider email/SMS/Zalo + ai chịu phí (N4, quyết định riêng của chủ dự án).
