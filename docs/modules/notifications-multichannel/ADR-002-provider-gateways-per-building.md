# ADR-002 — Cổng chờ provider đa kênh + cấu hình tham số theo tòa

- Trạng thái: **ACCEPTED** (chủ dự án chốt 2026-08-04) · 2026-08-04
- Nối tiếp: [ADR-001](ADR-001-broadcast-vs-targeted.md) (mục 5 — giao nhận per-recipient cho kênh tốn phí),
  handoff 03/08 mục "CÒN LẠI / cần quyết" (N4 provider thật + auto-wire kênh).

## Bối cảnh
N4 (`MultiChannelNotifier`) mới dựng khung + stub chung: mọi kênh chưa-có-provider đều ghi
`provider_not_configured`. Hai thiếu sót:
1. **Không phân biệt** "chưa khai gì" với "đã khai tham số, chờ đi live".
2. **Tham số provider khác nhau theo từng tòa** (mỗi tòa có OA Zalo riêng, bot Telegram riêng,
   workspace X.Space riêng…) nhưng chưa có nơi khai báo cấp tòa.

## Quyết định
1. **Email = kênh gửi THẬT** duy nhất hiện tại, qua Elastic Email (SMTP đã cấu hình ở `.env`:
   `MAIL_HOST=smtp.elasticemail.com`). `EmailChannelDispatcher` gửi ngay, `cost = 0`.
2. **Zalo, WhatsApp, Telegram, X.Space (xhub) = CỔNG CHỜ** (`PendingProviderChannelDispatcher`).
   Chưa đấu nối provider thật; notifier **ghi ý định** vào `notification_delivery_logs` nhưng
   **không gửi**. Cắm provider thật về sau = viết 1 `ChannelDispatcher` mới + đổi trong
   `MultiChannelNotifier`, **không đụng** chỗ gọi.
3. **Tham số theo TÒA**: bảng mới `building_notification_channels` (một dòng / tòa / kênh) giữ
   `enabled`, `status` (pending|active) và `config` (json tham số riêng từng kênh). Đây là NƠI
   khai báo provider ở cấp tòa — khác `notification_channels` (kênh của một thông báo cụ thể).
4. **Ba trạng thái cổng chờ** ghi vào sổ gửi để BQL đối soát:
   | Tình huống cấu hình tòa | status | error |
   |---|---|---|
   | Chưa có dòng cấu hình | `queued` | `provider_not_configured` |
   | Có cấu hình, `status=pending` (đã khai, chưa live) | `queued` | `provider_pending` |
   | Có cấu hình, `enabled=false` (tòa tắt kênh) | `suppressed` | `channel_disabled` |
   | Có cấu hình, `status=active` + đã có adapter (email) | `sent` | — |
5. **Không auto-bật kênh trả phí cho broadcast-to-all** (giữ ADR-001 mục 5): email/Zalo/SMS gửi
   rộng = per-người, tốn phí — để BQL chủ động chọn kênh với phạm vi nhỏ/targeted.
6. **X.Space thuộc hệ sinh thái xhub**: coi như một kênh outbound (webhook tới workspace) — cùng
   khuôn cổng chờ; tham số `{workspace_id, webhook_url, api_key}` khai theo tòa.

## Hệ quả
- `MultiChannelNotifier::notify(..., ?int $buildingId)` trở nên **building-aware**: resolve cấu
  hình tòa qua `ChannelConfigResolver` để chọn adapter + trạng thái cổng chờ đúng.
- BQL có màn Filament **"Cấu hình kênh gửi (theo tòa)"** để khai/sửa tham số từng kênh.
- Sổ gửi (`/admin/notifications/delivery-audit`) phân biệt được `provider_pending` (đã khai, chờ
  đi live) với `provider_not_configured` (chưa khai) — biết tòa nào còn thiếu cấu hình.

## Chưa làm (ngoài phạm vi ADR này)
- Adapter thật cho Zalo ZNS / WhatsApp Cloud API / Telegram Bot / X.Space webhook (chờ chốt hợp
  đồng + template + ai trả phí từng provider).
- Auto-wire chọn kênh trả phí ngay trong luồng phát hành ở NotificationCenter (hiện BQL chọn
  kênh; gửi đa kênh chạy khi có adapter live).
