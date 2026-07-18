# Ghi chú thiết kế: Nhật ký gửi truyền thông đa kênh (theo 3 tầng quyền)

> Trạng thái: **CHƯA dựng** — owner tự thiết kế màn. Ghi chú 2026-07-18 để giữ phát hiện + hạ tầng sẵn có. Liên quan: `PASSWORD_RESET_AND_MAIL.md`.

## 1. Yêu cầu (owner)
Mọi thông báo/thông tin gửi đi qua các kênh **Email · Push (app) · Web · Zalo · SMS** phải đưa về **1 màn nhật ký (log) để kiểm tra**, phân theo **3 nhóm quyền: SuperAdmin · Tenant · Ban quản lý (BQL)**. Trên màn log cho phép **retry** — thực hiện **hàng loạt** hoặc **từng item**.

## 2. Rà handoff (2026-07-18)
**KHÔNG có màn chuyên dụng** cho nhật ký-gửi + retry theo 3 tầng. Các màn liên quan chỉ là **phía gửi/soạn** hoặc audit khác:
- `BQL-07-02/03` Trung tâm thông báo & Chi tiết gửi — soạn/duyệt/gửi chiến dịch, preview 4 kênh (App Push · Email · SMS · Zalo OA), toggle kênh + ước tính tiếp cận, lịch sử phê duyệt. → định nghĩa **taxonomy kênh** nhưng không có log gửi theo người nhận + retry.
- `HQ-05-08` Nhắc nợ & chiến dịch thu hồi — kênh gửi + lịch sử gửi, chuyên tài chính.
- `BQL-00-09`, `HQ-04-06`, SA `AccountSecurityLog` — audit hoạt động, không phải delivery.

→ **Màn log + retry là MỚI.**

## 3. Hạ tầng backend ĐÃ CÓ (tái dùng, không tạo bảng mới)
- `notifications` — có **`owner_level` = `platform` | `tenant` | `project`** ↔ **đúng 3 tầng SuperAdmin / Tenant / BQL**; kèm `tenant_id/project_id/building_id`, `code/type/title/body/status/publish_at`…
- `notification_channels` — `notification_id, channel, enabled, config` (kênh đang thấy: `app`, `email`).
- **`notification_delivery_logs`** — `notification_id, user_id, resident_id, channel, status, error, sent_at` = **log gửi theo từng người/kênh** (nền cho màn log; hiện chỉ seed channel `app`, status `sent`).
- `notification_audiences` — `scope_type, scope_id` (đối tượng nhận).
- `notification_reads` (+ archive) — trạng thái đã đọc.
- Model: `App\Models\Notification`, `NotificationChannel`, `NotificationDeliveryLog`.

## 4. Gợi ý triển khai (khi dựng)
1. **Chuẩn hoá 1 "outbox" trung tâm**: mọi phát đi (kể cả OTP/link reset mật khẩu — hiện chỉ ghi `audit_logs`) ghi 1 dòng `notification_delivery_logs` với `channel` + `status` (mở rộng `sent`/`failed`/`queued`) + `error` + scope + `owner_level`.
2. **3 màn xem log theo tầng** (theo `LISTING_PAGE_STANDARD.md`): `/sa` (platform — tất cả) · `/hq` (tenant) · `/admin` (project/building). Filter kênh/trạng thái/thời gian/người nhận; KPI đã gửi/thất bại/chờ.
3. **Retry**: bulk (chọn nhiều dòng `failed` → gửi lại) + per-item (nút ↻); mỗi lần retry ghi lần gửi mới + cập nhật status + audit. Áp **chuẩn action UX** (§5b Listing): retry per-item màu `warning`/`gray`, retry hàng loạt qua bulk bar.

## 5. Cần mở rộng khi làm
- Enum `channel`: thêm `sms`, `zalo`, `web` (hiện `app`/`email`).
- Enum `status` delivery: `queued`/`sent`/`failed`/`retried`.
- Nối gateway thật: SMS, Zalo OA (hiện stub). Email đã chạy (SMTP elasticemail).
