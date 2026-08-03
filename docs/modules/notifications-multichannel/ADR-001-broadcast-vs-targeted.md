# ADR-001 — Broadcast fan-out-on-read vs Targeted fan-out-on-write

- Trạng thái: **ACCEPTED** (chủ dự án duyệt 2026-08-03) · 2026-08-03
- Bối cảnh liên quan: audit AR-05 (outbox/events), module `notifications-multichannel`.

## Bối cảnh
Quy mô mục tiêu: **500 toà × 4.000 dân = 2.000.000 người**; BQL gửi **~20 thông báo/ngày**.
Nếu mỗi thông báo broadcast đẻ 1 dòng cho mỗi người nhận (fan-out-on-write):

> 2.000.000 × 20 = **40.000.000 dòng/ngày ≈ 14 tỷ dòng/năm** — chỉ riêng để hiển thị chuông.

Không chấp nhận được (chi phí ghi, lưu trữ, đánh index, dọn dẹp). Đây là lý do phải phân biệt
hai loại thông báo theo **cách nhân bản**.

## Quyết định
1. **Broadcast (BQL → all / dự án / toà / căn theo scope tĩnh)**: lưu **MỘT** dòng nội dung ở
   `notifications` + định nghĩa audience. **KHÔNG** nhân theo người nhận. Chuông tính "thông báo
   nào áp cho tôi" **lúc ĐỌC** (fan-out-on-read) bằng match audience với căn/toà/dự án của cư dân.
2. **Targeted (sự kiện nhắm ~1 người: phiếu duyệt, trả lời công nợ, @mention, tương tác)**: ghi
   **một** dòng `activity_notifications` cho người nhận (fan-out-on-write) — số lượng theo hoạt
   động thật, không theo dân số.
3. **Trạng thái đã đọc broadcast**: **KHÔNG** ghi dòng "chưa đọc" cho từng người. Mỗi cư dân giữ
   **một mốc `bell_seen_at`** (high-water). Chưa đọc = broadcast trong audience có
   `published_at > bell_seen_at`. Ghi per-user per-notification **chỉ** cho `requires_ack` (thưa).
4. **Gửi push broadcast**: qua **FCM topic** (thiết bị subscribe topic theo toà/dự án) — 1 message,
   không 2 triệu request, không 2 triệu delivery row. Audit broadcast ghi **1 dòng topic-level**.
5. **Giao nhận per-recipient** (`notification_delivery_logs`): chỉ dùng cho kênh **tốn tiền**
   (SMS/Zalo/email) hoặc thông báo **cần bằng chứng pháp lý**, và với phạm vi nhỏ/targeted —
   KHÔNG bật mặc định cho broadcast-to-all.
6. **Segment động** (vd "các căn nợ quá hạn") không map được topic tĩnh → chấp nhận dựng danh
   sách người nhận (targeted). Các segment này nhỏ/có chủ đích nên chi phí chấp nhận được.

## Quy tắc rút gọn
> Phạm vi **tĩnh & rộng** → 1 dòng nội dung + fan-out-on-read + FCM topic.
> **Động / cá nhân** → per-recipient (`activity_notifications` + gửi lẻ + delivery log).

## Hệ quả
### Tích cực
- 1 thông báo gửi-all tốn **1 dòng nội dung + 1 dòng gửi topic** thay vì 2 triệu.
- `bell_seen_at` = 2 triệu dòng CỐ ĐỊNH (1/user, cập nhật tại chỗ), không nhân theo số thông báo.
- Chuông đọc = merge 2 nguồn, keyset — rẻ và ổn định theo thời gian.
- Đường targeted vẫn đầy đủ (đã đọc/deeplink/lịch sử) cho cái thực sự cần.

### Chi phí / rủi ro
- Đọc chuông phải **merge + sort 2 nguồn** → cần index đúng + giới hạn cửa sổ thời gian.
- Badge chưa-đọc broadcast dựa mốc `bell_seen_at` là **coarse** (đọc 1 cái = coi như thấy hết cái cũ hơn). Chấp nhận cho broadcast; targeted vẫn đã-đọc từng cái.
- FCM topic cần **quản lý subscribe/unsubscribe** khi cư dân đổi căn/rời dự án (đăng ký lại topic).
- Audit broadcast là **topic-level**, không có bằng chứng đã-nhận per-người cho push (đúng bản chất FCM). Cần per-người thì phải chọn kênh có callback (email/SMS) hoặc opt-in.

## Phương án đã cân nhắc & loại
- **Fan-out-on-write cho tất cả** (kể cả broadcast): loại — 14 tỷ dòng/năm.
- **Chỉ một bảng `notifications` + audience, không có activity**: loại — không mô hình hoá được
  nhắc việc/tương tác nhắm cá nhân số lượng lớn, và trộn chung làm query chuông phức tạp/chậm.
- **Redis/stream thay DB cho bell**: để dành khi thực sự chạm trần; giai đoạn này DB + index +
  retention là đủ và dễ audit.

## Quyết định của chủ dự án (2026-08-03) — ĐÃ CHỐT
1. **Badge chưa-đọc coarse** theo `bell_seen_at` cho broadcast — **CHẤP NHẬN**. Không ghi per-item
   read cho broadcast; targeted vẫn đã-đọc từng dòng.
2. **Retention `activity_notifications` = 180 ngày** rồi archive sang `activity_notifications_archive`.
3. **Coalesce tương tác = CÓ** ("5 người thả cảm xúc bài của bạn" = 1 dòng, `coalesce_count`).
4. **Audit per-người BẮT BUỘC cho kênh email + SMS + Zalo** — mỗi lần gửi 3 kênh này ghi một dòng
   `notification_delivery_logs` per-recipient đầy đủ vòng đời + `cost`. Push broadcast vẫn topic-level;
   push targeted ghi per-recipient. In-app không ghi delivery_logs.
