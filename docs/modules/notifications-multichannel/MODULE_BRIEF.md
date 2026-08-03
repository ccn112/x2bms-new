# Module Brief — notifications-multichannel

> Trạng thái: **DRAFT chờ chủ dự án duyệt** (2026-08-03). Chưa code. Quyết định gốc ở
> `ADR-001-broadcast-vs-targeted.md`; schema ở `DATA_MODEL.md`; lộ trình ở `IMPLEMENTATION_PLAN.md`.

## User job
- **Cư dân**: một cái "chuông" hiển thị mọi thứ liên quan tới mình (thông báo chính thức BQL +
  nhắc việc + tương tác cộng đồng), còn lại sau khi tắt khỏi khay máy, biết cái nào chưa đọc,
  bấm vào đi đúng chỗ.
- **BQL**: soạn & phát hành thông báo chính thức; và **tra cứu về sau** đã gửi gì, cho ai, qua
  kênh nào, trạng thái gửi/nhận/đọc (audit).
- **Hệ thống**: đẩy nhắc việc (phiếu duyệt, trả lời công nợ…) và tương tác cộng đồng tới đúng
  người, qua đúng kênh họ bật, không trùng, gửi lại được, đối soát được.

## Actors
Cư dân (T3) · BQL/HQ/SuperAdmin (T2b/T2a/T1, người soạn broadcast) · Hệ thống (sinh activity).

## In scope
- Ba loại nội dung: **thông báo chính thức BQL** (broadcast), **activity/bell** (targeted nhắc
  việc + tương tác), và sổ **giao nhận đa kênh** (audit).
- Mô hình đọc chuông chịu được **500 toà × 4.000 dân = 2.000.000 người**, ~20 broadcast/ngày.
- Kênh: in-app (chuông), push (FCM), và khung mở rộng email/SMS/Zalo/thư tay.

## Out of scope
- **Bình luận cộng đồng** — ĐÃ tách bảng riêng `community_comments` (GĐ7). Module này chỉ
  *nhận sự kiện* từ đó để sinh activity, không quản lý nội dung bình luận.
- Nội dung/luồng nghiệp vụ của phiếu, công nợ, đặt tiện ích — chỉ *phát sự kiện* sang đây.
- Chốt nhà cung cấp email/SMS/Zalo + ai trả phí (để slice N4 + quyết định riêng).

## Primary journey
1. BQL phát hành 1 thông báo tới "toàn dự án" → lưu **1 dòng** + audience → gửi push qua **FCM topic** (1 message) → cư dân thấy ở chuông (tính lúc đọc).
2. BQL duyệt phiếu của cư dân X → hệ thống sinh **1 dòng activity** cho X → push cho X.
3. Cư dân mở chuông → thấy merge (broadcast áp cho mình + activity của mình), cái mới = chưa đọc.
4. Sau 3 tháng BQL mở màn audit → lọc theo thông báo/căn/kênh → thấy đã gửi ai, trạng thái gì.

## Source of truth
- Nội dung broadcast: `notifications`. Bell targeted: `activity_notifications`. Giao nhận/audit:
  `notification_delivery_logs`. Đã đọc broadcast: mốc `bell_seen_at`/user (+ `notification_reads`
  chỉ cho `requires_ack`).
- Bản đồ nghiệp vụ tổng: `_BUSINESS_MAP_20260725/01_APP_CU_DAN_business_map.md` (chuông/thông báo).

## Existing implementation
- `notifications` + `notification_audiences` + `notification_channels` + `notification_reads`
  (đã có). `notification_delivery_logs` (đã có, A2 mới bắt đầu ghi cho push BQL).
- `NotificationPushDispatcher` (A2: persist-trước-gửi, idempotent). Push tương tác cộng đồng
  hiện bắn thẳng FCM ở `CommunityPostController`, **chưa lưu** → khoảng trống module này lấp.
- Cột `source` (bql|interaction, A4), `requires_ack` + taxonomy (A3/03/08) đã có.

## Smallest reference slice
**N0** — dựng `activity_notifications` + đọc chuông từ *merge* (broadcast read-time + activity),
với 1 nguồn activity thật (ví dụ "phiếu đã duyệt"). Chứng minh chuông không nhân dòng theo dân.

## Demo outcome
Một cư dân test thấy ở chuông: 1 thông báo BQL toàn dự án (không đẻ 2 triệu dòng) + 1 nhắc
"phiếu đã duyệt" (targeted) + badge đếm đúng; BQL tra được audit của thông báo broadcast đó.
