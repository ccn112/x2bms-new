# notifications-multichannel — thiết kế chờ duyệt

Gói thiết kế hệ thống thông báo đa kênh + chuông kiểu Facebook, chịu quy mô
**500 toà × 4.000 dân = 2.000.000 người**, ~20 broadcast BQL/ngày. **Chưa code** — chờ chủ dự án duyệt.

## Đọc theo thứ tự
1. **[ADR-001](ADR-001-broadcast-vs-targeted.md)** — quyết định cốt lõi: broadcast fan-out-on-**read**
   (1 dòng, không nhân 2 triệu) vs targeted fan-out-on-write. **← duyệt cái này trước.**
2. [MODULE_BRIEF](MODULE_BRIEF.md) — phạm vi, 3 loại thông báo, out-of-scope.
3. [DOMAIN_CONTRACT](DOMAIN_CONTRACT.md) — ngôn ngữ, aggregate, invariants, sự kiện, audit, SoR.
4. [DATA_MODEL](DATA_MODEL.md) — schema 4 bảng + mốc `bell_seen_at`, index, cách chuông đọc, volume.
5. [IMPLEMENTATION_PLAN](IMPLEMENTATION_PLAN.md) — lát N0→N4.

## Tóm tắt quyết định để duyệt nhanh
- **Thông báo chính thức BQL** = `notifications` (broadcast, 1 dòng, audience). Đọc chuông tính lúc đọc.
- **Bell (nhắc việc + tương tác)** = `activity_notifications` (MỚI, targeted, per-người, coalesce + archive).
- **Bình luận cộng đồng** = `community_comments` (ĐÃ tách, GĐ7) — chỉ phát sự kiện sang đây.
- **Đã đọc broadcast** = mốc `bell_seen_at`/user (không ghi 2 triệu dòng chưa-đọc); `requires_ack` mới ghi per-người.
- **Push broadcast** = FCM **topic** (1 message); audit topic-level. Per-recipient chỉ cho kênh phí/pháp lý.
- **Audit đa kênh** = `notification_delivery_logs` làm giàu vòng đời (queued→sent→delivered→read + provider id + cost).

## 4 câu hỏi cần chủ dự án chốt (ở cuối ADR-001)
1) Badge chưa-đọc coarse theo `bell_seen_at` cho broadcast — OK chứ?
2) Retention `activity_notifications` (đề xuất 90–180 ngày).
3) Coalesce tương tác ("5 người thả cảm xúc" = 1 dòng) — OK chứ?
4) Kênh nào cần audit per-người bắt buộc (pháp lý)?
