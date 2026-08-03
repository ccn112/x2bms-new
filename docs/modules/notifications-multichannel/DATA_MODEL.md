# Data Model — notifications-multichannel

> Chờ duyệt. Quyết định gốc: `ADR-001`. Cột **[có]** = đã tồn tại; **[mới]** = thêm; **[MỚI-BẢNG]** = bảng mới.

## Toàn cảnh 4 bảng + 1 mốc

```
┌─ NỘI DUNG BROADCAST ─────────────┐        ┌─ NỘI DUNG TARGETED (bell) ───────────────┐
│ notifications            [có]    │        │ activity_notifications        [MỚI-BẢNG] │
│  = bản tin CHÍNH THỨC BQL        │        │  = 1 dòng / 1 người / 1 sự kiện          │
│  1 dòng, audience-scoped         │        │  nhắc việc + tương tác cộng đồng          │
└──────────────┬───────────────────┘        └───────────────────┬──────────────────────┘
               │ fan-out ON READ (match audience)                │ đã targeted sẵn
               └──────────────┬──────────────────────────────────┘
                              ▼   CHUÔNG = merge 2 nguồn, sort time, keyset
        ┌─ Đã đọc broadcast ─────────────┐   ┌─ Giao nhận / AUDIT đa kênh ──────────────┐
        │ bell_seen_at (mốc/user) [mới]  │   │ notification_delivery_logs   [có, làm giàu]│
        │ notification_reads (chỉ ack)[có]│   │  push/email/sms/zalo/postal + vòng đời    │
        └────────────────────────────────┘   └───────────────────────────────────────────┘

community_comments [có, GĐ7]  ── phát sự kiện ──▶ sinh activity_notifications (KHÔNG chứa noti)
```

## 1. `notifications` — bản tin chính thức BQL (broadcast)  [có, giữ]
Giữ nguyên. Đã có: `source` (bql|interaction), `category/subtype/action_key/entity_type/entity_id`,
`requires_ack`, `cover_path`, `status`, `owner_level`, `tenant_id/project_id/building_id`.
- **1 dòng/thông báo**, KHÔNG nhân theo người nhận.
- Audience ở `notification_audiences` (scope_type: all|project|building|apartment|resident|user).
- Kênh phát ở `notification_channels`.
- `source='interaction'` KHÔNG dùng ở bảng này nữa (chuyển sang activity_notifications) —
  giữ giá trị cho tương thích, sẽ deprecate trong N2.

## 2. `activity_notifications` — chuông targeted  [MỚI-BẢNG]
```
id                    bigint pk
tenant_id             fk            [index]
project_id            fk nullable
recipient_user_id     fk            ← NHẮM SẴN 1 người
actor_user_id         fk nullable   ← ai gây ra (người bình luận / BQL duyệt)
kind                  string        ← ticket_approved | debt_reply | payment_received |
                                      amenity_confirmed | post_comment | post_reaction |
                                      mention | follow | announcement (bản sao broadcast)
subtype               string nullable
title                 string        ← render sẵn theo sự kiện
body                  string nullable
image_url             string nullable
entity_type           string nullable  ← statement | ticket | community_post | ...
entity_id             bigint nullable
action_key            string nullable  ← qua allowlist registry (đã có)
announcement_id       fk nullable      ← nếu kind=announcement: trỏ notifications.id
group_key             string nullable  ← để COALESCE ("post:123:reaction" gộp nhiều actor)
coalesce_count        int default 1    ← "và 4 người khác"
read_at               timestamp nullable   ← đã đọc TỪNG dòng (targeted)
created_at            timestamp
INDEX (recipient_user_id, created_at desc)   ← feed keyset
INDEX (recipient_user_id, read_at)           ← đếm chưa đọc
INDEX (tenant_id, created_at)                ← retention/archive
UNIQUE (recipient_user_id, group_key) WHERE group_key IS NOT NULL  ← coalesce
```
- Retention: archive `created_at` cũ **> 180 ngày** (chốt) sang `activity_notifications_archive`.
- Coalesce: reaction/comment cùng entity trong cửa sổ → cập nhật `coalesce_count` + `actor` mới
  nhất thay vì đẻ dòng mới (giảm volume tương tác cộng đồng).

## 3. `bell_seen_at` — mốc đã-thấy chuông / user  [mới, nhẹ]
Có thể là **cột trên `users`/`residents`** hoặc bảng nhỏ `resident_bell_state`:
```
user_id                pk/fk
bell_seen_at           timestamp   ← bump khi mở chuông
INDEX (user_id)
```
- 2 triệu dòng CỐ ĐỊNH (1/user), cập nhật tại chỗ.
- Badge chưa đọc = (broadcast audience-match có `published_at > bell_seen_at`) + (activity `read_at IS NULL`).

## 4. `notification_reads` — CHỈ cho requires_ack  [có, thu hẹp vai trò]
Giữ `read_at` + `acknowledged_at` (A3). Sau ADR này: **chỉ ghi cho thông báo `requires_ack`**
(ai đã xác nhận — thưa, quan trọng). KHÔNG dùng làm cờ đã-đọc đại trà cho broadcast (dùng `bell_seen_at`).

## 5. `notification_delivery_logs` — giao nhận / audit đa kênh  [có, làm giàu]
```
id
source_type, source_id   [mới]  ← polymorphic: notifications | activity_notifications | 'topic'
recipient_user_id        [có]   ← null nếu là dòng topic-level
resident_id              [có]
channel                  [có]   ← push | app | email | sms | zalo | postal
status                   [có→mở rộng] queued|sent|delivered|read|failed|suppressed|bounced
queued_at                [mới]
sent_at                  [có]
delivered_at             [mới]  ← từ callback provider (FCM receipt/ZNS/…)
read_at                  [mới]
provider_message_id      [mới]  ← đối soát FCM/SES/ZNS/nhà mạng
error                    [có]
cost                     [mới]  ← SMS/Zalo tốn tiền → đối soát
topic                    [mới]  ← khi gửi broadcast qua FCM topic (dòng topic-level)
INDEX (source_type, source_id)          ← audit theo 1 thông báo → mọi người/kênh
INDEX (recipient_user_id, created_at)   ← audit theo 1 căn/người
UNIQUE (source_type, source_id, recipient_user_id, channel)  [có, A2] ← idempotent
```
- Broadcast push → **1 dòng topic-level** (`recipient_user_id` null, `topic='project:45'`).
- Kênh trả phí/pháp lý (SMS/email/Zalo) targeted → per-recipient (mới đầy đủ vòng đời).
- In-app (chuông) → KHÔNG ghi ở đây (đã có `activity_notifications.read_at` / `bell_seen_at`).

## Chuông đọc thế nào (pseudo)
```
GET /resident/notifications  (bell)
  A = notifications
        WHERE published AND audience match (all | my project | my building | my apartment)
        AND published_at > (bell_seen_at - cửa_sổ)   -- giới hạn cửa sổ, không quét toàn bộ
  B = activity_notifications WHERE recipient_user_id = me
  return merge(A,B) sort by time desc, keyset paginate
  unread = count(A: published_at > bell_seen_at) + count(B: read_at is null)
```
- Màn **"Thông báo BQL"** vẫn chỉ đọc `notifications` (source=bql, audience match) — announcement-centric.
- Mở chuông → `bell_seen_at = now()`. Bấm 1 activity → set `read_at`. Bấm 1 broadcast requires_ack → ghi `notification_reads.acknowledged_at`.

## Isolation (bắt buộc negative test)
- `activity_notifications`: cư dân A KHÔNG bao giờ đọc được dòng của B (scope `recipient_user_id`).
- Broadcast: audience match phải chặt — cư dân toà 1 KHÔNG thấy broadcast chỉ gửi toà 2.
- `MUST_NOT_LEAK` marker ở tenant/dự án/toà/căn khác + delivery_logs không lộ chéo.

## Ước lượng volume sau ADR
| Bảng | Ước lượng | Ghi chú |
|---|---|---|
| `notifications` | ~7.300/năm | 20/ngày broadcast |
| `bell_seen_at` | ~2.000.000 cố định | 1/user, update in-place |
| `activity_notifications` | theo hoạt động thật + coalesce + archive | KHÔNG theo dân số × broadcast |
| `notification_delivery_logs` | broadcast=topic-level; per-recipient chỉ kênh phí/pháp lý | |
