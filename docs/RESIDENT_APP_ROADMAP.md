# Resident App — Roadmap các màn tiếp theo

_Cập nhật: 2026-07-24 · App x2mobile ↔ Backend x2bms_

Kế hoạch xây tiếp app cư dân sau khi đã xong: auth, hồ sơ/căn hộ, hóa đơn(+trend+lịch sử+VietQR),
thông báo, Ưu đãi (loyalty/offers/gifts), Cộng đồng (posts/events/polls/groups+join), Home (AQI/tasks),
SOS, X2AI chat. Ưu tiên theo **giá trị vận hành** × **độ sẵn sàng backend** (bảng đã có = làm nhanh).

Chú thích effort: **S** ≤0.5 ngày · **M** ~1 ngày · **L** ≥2 ngày. Mỗi màn theo quy trình chuẩn:
backend (Resource/Controller/route + verify HTTP + seed) → app (slice mock/remote, giữ UI) → doc + commit.

---

## 🟢 PHASE 1 — Nghiệp vụ vận hành hằng ngày (bảng ĐÃ CÓ → làm ngay, giá trị cao)

| # | Màn / tính năng | Backend (bảng sẵn) | Việc app | Effort |
|---|---|---|---|---|
| 1 | **Đăng ký khách** (Visitor) | `visitor_registrations` ✅ | Form đăng ký khách (tên/SĐT/thời gian/mục đích/biển số) + danh sách + mã/QR cho bảo vệ | M |
| 2 | **Đặt tiện ích** (Amenity booking) | `amenities`+`amenity_slots`+`amenity_bookings` ✅ | List tiện ích (hồ bơi/gym/BBQ…) → chọn ngày/slot → đặt → "Lịch đặt của tôi" (huỷ) | M |
| 3 | **Phản ánh / Yêu cầu dịch vụ** | `feedback_requests`(147)+`feedback_categories` ✅ | Tạo phản ánh (danh mục/mô tả/ảnh) + danh sách + timeline trạng thái/SLA | M |
| 4 | **Chi tiết thông báo** | `notifications` ✅ (thêm GET `/{id}`) | Màn chi tiết thật (đang mock) + đánh dấu đã đọc | S |
| 5 | **Biên lai thanh toán** | `receipts`(9)+`payment_allocations` ✅ | Sau payment → xem/tải biên lai; đính vào chi tiết giao dịch | S |

> Backend cần thêm cho Phase 1: các endpoint `GET/POST /resident/visitors`, `/amenities`+`/amenity-bookings`,
> `/feedback`(+categories,+detail), `notifications/{id}`, `payments/{id}` trả receipt. Đều dựng trên bảng có sẵn.

## 🟡 PHASE 2 — Cộng đồng sâu + Loyalty đổi quà

| # | Màn / tính năng | Backend | Việc app | Effort |
|---|---|---|---|---|
| 6 | **Đăng ký sự kiện** | `event_registrations` ✅ (thêm POST register/cancel) | Nút Đăng ký/Huỷ trên event (đã có cờ `registered`) | S-M |
| 7 | **Đổi quà bằng điểm** | ⚠️ cần bảng `loyalty_redemptions` (chưa có) + logic trừ điểm | Nút "Đổi" ở gift → xác nhận → mã đổi quà; lịch sử đổi | M |
| 8 | **Chi tiết bài + bình luận + đăng bài** | ⚠️ cần bảng `community_post_comments` (chưa có) | Màn chi tiết post, comment, tạo bài (ảnh) | M-L |

## 🟠 PHASE 3 — Hồ sơ, giấy tờ, phương tiện

| # | Màn / tính năng | Backend | Việc app | Effort |
|---|---|---|---|---|
| 9 | **Yêu cầu đổi thông tin** | `data_fix_requests` ✅ (BQL đã có màn duyệt) | Cư dân gửi yêu cầu sửa hồ sơ/hộ khẩu + theo dõi duyệt | M |
| 10 | **Đăng ký phương tiện** | `vehicles`(108) ✅ | Danh sách xe của hộ + đăng ký thẻ xe/biển số | M |
| 11 | **Tài liệu / giấy tờ** | `documents`(12) ✅ | Xem tài liệu tòa nhà/hợp đồng/thông báo pháp lý | S-M |

## 🔵 PHASE 4 — Onboarding / eKYC / Household (⛳ cần owner chốt)

| # | Màn / tính năng | Ghi chú | Effort |
|---|---|---|---|
| 12 | **eKYC onboarding** | Chụp CCCD → trích xuất → đối chiếu khuôn mặt → nộp → theo dõi duyệt. **Cần chốt nhà cung cấp eKYC** (FPT.AI / VNPT eKYC / Trusting Social…) + hợp đồng. App đã có slice stub `resident_application`. | L |
| 13 | **Mời thành viên hộ (Household)** | Chủ hộ mời thành viên → liên kết tài khoản/căn. Backend household API chưa có → cần dựng. | M-L |

## 🟣 PHASE 5 — Thanh toán nâng cao (⛳ cần credential/nhà cung cấp)

| # | Màn / tính năng | Ghi chú | Effort |
|---|---|---|---|
| 14 | **VNPay / MoMo golive** | Hoàn thiện signer + return/IPN webhook + cập nhật `payments.status`. Cần credential + bật channel. | M |
| 15 | **Đối soát tự động chuyển khoản** | Webhook ngân hàng qua Casso/PayOS/SePay (hoặc API bank) → tự gạch nợ hóa đơn khi có tiền về. **Chọn nhà cung cấp.** | M-L |

## ⚪ PHASE 6 — Tương lai / IoT (cần bảng mới + phần cứng)
Tủ nhận đồ (parcel), Liên lạc nội bộ (intercom), Smart home / EV charging / Smart parking — icon đã có trong UI kit,
nhưng **chưa có bảng + cần tích hợp thiết bị**. Để sau khi các phase trên xong.

## Việc nền cắt ngang (nên xen kẽ)
- **Push notification thật** (FCM/APNs): `mobile_devices` đã đăng ký thiết bị → cần dịch vụ gửi push khi có thông báo/khách/hóa đơn. (Chưa có `push_tokens`/service gửi.)
- **Ảnh thật** cho offers/events/posts/market: thêm cột image + upload (hiện placeholder).
- **Public experience** (xem dự án trước đăng nhập): slice `public_experience` còn stub.

---

## 👉 Đề xuất sprint kế tiếp (khuyến nghị)
**Làm trọn PHASE 1** (5 màn) — toàn bộ bảng đã sẵn, không chờ owner chốt, giá trị vận hành cao nhất
(khách/tiện ích/phản ánh là 3 nghiệp vụ dùng hằng ngày). Ước tính ~1 tuần: backend build + verify HTTP theo
`RESIDENT_API_OPERATIONS.md`, app wire theo pattern slice, cập nhật `RESIDENT_API_REFERENCE.md` + `DEV_JOURNAL`.

Sau Phase 1 → Phase 2 (đăng ký sự kiện + đổi quà) song song trong lúc owner chốt eKYC/cổng thanh toán cho Phase 4/5.
