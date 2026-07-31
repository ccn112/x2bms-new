# Filament Decision Matrix

| Nhu cầu | Surface ưu tiên | Lý do |
|---|---|---|
| Danh mục dự án/tòa/tầng/căn hộ | Filament Resource | CRUD, filter, relation chuẩn |
| Hồ sơ cư dân và quan hệ căn hộ | Resource + Relation Managers | Back-office tổng hợp có aggregate rõ |
| Hàng đợi xác minh cư dân | Custom Filament Page | Workflow vận hành, nhiều action/state |
| Cấu hình loại phí/kỳ phí | Filament Resource | Dữ liệu quản trị chuẩn |
| Thu phí/công nợ vận hành | **Custom Page BẮT BUỘC — không dùng Resource thô** | Xem §Ngoại lệ tiền bên dưới |
| Dashboard BQL | Custom Page/Widget | Query tổng hợp, role scope |
| Phản ánh cư dân phía BQL | Custom Page hoặc Resource | Queue/SLA/state transition |
| Trang chủ cư dân | Flutter | Consumer UX |
| Đặt tiện ích cư dân | Flutter + API | Mobile journey |
| Community feed | Flutter/separate frontend | Feed, realtime, pagination, media |
| Marketplace | Flutter/separate frontend | Discovery, transaction, moderation |
| Smart Home/IoT | Flutter + realtime/API | High-interaction device UX |
| Chat | Dedicated realtime surface | Không phù hợp Resource CRUD |

## Ngoại lệ tiền — chỉnh khi cài vào repo x2bms, 2026-07-31

Bảng gốc ghi "Thu phí/công nợ → Resource + custom actions/page". **Đã sửa**, vì chính
Resource thô là chỗ nguy hiểm nhất đang tồn tại trong repo:

`app/Filament/Resources/Payments/Schemas/PaymentForm.php:33` cho sửa `status` bằng
`TextInput` tự do và sửa `amount`. Đặt `status='confirmed'` ở đó **không sinh
`payment_allocations`, không sinh `receipts`**; sửa `amount` sau khi đã phân bổ **không**
đồng bộ lại `statements.paid_amount`. Toàn bộ công sức của
`ResidentPaymentClaimReviewer` (transaction + 2 lớp lock + idempotent + 11 test) bị vô
hiệu bởi một form CRUD sinh tự động.

**Quy tắc cho bảng có bất biến tiền** — `payments`, `payment_allocations`, `receipts`,
`statements`, `statement_lines`, `apartment_wallets*`, `debts`:

1. **Không có Filament Resource cho phép sửa** cột tiền hoặc cột trạng thái. Chỉ Custom
   Page gọi Application Service.
2. Cần xem/tra cứu thì Resource **read-only** (không form, không edit, không bulk
   action), hoặc dùng Page riêng.
3. Panel `/fila` (CRUD thô) **không được** expose các bảng này ở dạng sửa được.
4. Mọi màn tiền phải qua **G9 (anti-bypass) + G10 (money)** ở
   `03_VERTICAL_SLICE_GATES.md`.

## Rule of thumb

- Nếu người dùng là nhân sự back-office và công việc chủ yếu là tìm, lọc, xem, chỉnh, duyệt: ưu tiên Filament.
- Nếu người dùng là cư dân hoặc hành trình cần cảm xúc, realtime, gesture, offline, push: ưu tiên Flutter.
- Nếu nghiệp vụ gồm nhiều aggregate và trạng thái: domain/application service trước, surface sau.
