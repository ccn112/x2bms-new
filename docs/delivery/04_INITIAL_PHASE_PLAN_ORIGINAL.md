# Initial Phase Plan for X2-BMS

## Phase A0 — Repository truth audit

- Xác nhận framework versions từ lockfiles.
- Lập catalog migration/model/policy/Filament/API/Flutter.
- Xác định tenant và project scope hiện tại.
- Phát hiện bảng/model trùng khái niệm.
- Chuẩn hóa command migrate/seed/test.

## Phase A1 — Reference slice: Resident Identity

Mục tiêu demo:

```text
BQL tạo/nhập cư dân
→ liên kết căn hộ và vai trò hộ gia đình
→ gửi/yêu cầu xác minh
→ cư dân được cấp quyền đúng dự án/căn hộ
→ app đọc hồ sơ
→ tài khoản ngoài scope không thấy dữ liệu
```

Deliverables:

- Domain contract.
- Seed một tenant demo, hai dự án hoặc hai tòa để test isolation.
- Filament back-office cho hồ sơ/quan hệ/xác minh.
- API mobile đọc hồ sơ và danh sách căn hộ được phép.
- Tests tenant/project/apartment isolation.

## Phase A2 — Home read model

- Xây query/read model cho Home cư dân.
- Dữ liệu công nợ, thông báo, tiện ích, shortcut từ seed thật.
- Một endpoint tổng hợp có version.
- Flutter render đủ loading/empty/error/forbidden.

## Phase A3 — Feedback vertical slice

```text
Cư dân tạo phản ánh
→ BQL tiếp nhận
→ phân công
→ cập nhật SLA/trạng thái
→ cư dân nhận thông báo
→ đóng và đánh giá
```

## Phase A4 — Amenity booking vertical slice

- Catalog tiện ích.
- Slot/capacity/rule.
- Booking/idempotency/conflict.
- Filament vận hành.
- Flutter journey.

## Phase A5 — Fee and debt read/acknowledge slice

Bắt đầu từ read-only/statement trước khi triển khai payment orchestration đầy đủ.

## Phase A6 — Community foundation

Chỉ bắt đầu sau khi identity, scope, moderation roles và media contract ổn định. Không dùng Filament làm feed cư dân.
