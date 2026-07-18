# Hướng dẫn: Module Cư dân & Căn hộ

> Không gian **Ban quản lý (BQL)**. Cập nhật 2026-07-18.

## 1. Danh sách cư dân
Vào **Cư dân & Căn hộ → Cư dân**.

- **Thẻ KPI** trên đầu (Tổng · Hoạt động · Chờ duyệt · Tạm khoá · Thiếu dữ liệu) — **tự tính lại theo bộ lọc** đang áp.
- **Bộ lọc**: chọn nhanh Tòa / Loại cư dân / Trạng thái; ô tìm kiếm (mã, họ tên, SĐT, email); **"Bộ lọc nâng cao"** (ngày tạo, đã/chưa gắn căn). Điều kiện đang áp hiện thành **chip**, bấm ✕ để bỏ.
- **Cột**: nút **"Cột"** để ẩn/hiện cột (có cột **Ảnh** = avatar cư dân). Cột **Mã CD** và cột **thao tác** được **ghim** khi cuộn ngang.
- **Thao tác từng dòng** (bên phải): 👁 Xem nhanh · ✏️ Sửa · 🔑 **Đặt lại mật khẩu** · ⋮ (Gắn căn hộ, Gửi lại mã kích hoạt, Gửi thông báo, Tạo công việc, Khoá tài khoản, Lịch sử).
- **Chọn nhiều dòng** → thanh thao tác hàng loạt: Duyệt/kích hoạt, Khoá, Xuất Excel.
- Trên điện thoại: hiển thị dạng **thẻ** (card), có avatar.

## 2. Chi tiết cư dân 360
Bấm **tên cư dân** để mở hồ sơ đầy đủ.

- **Đầu trang**: tên cư dân + đường dẫn; **3 nút chính** (Chỉnh sửa · Gửi thông báo · Đặt lại mật khẩu) + **"Thao tác khác" (⋯)**: Thêm quan hệ, Yêu cầu cập nhật, Xuất hồ sơ, **Khoá/Mở khoá tài khoản**.
- **Dải KPI**: Mã CD · Trạng thái · Căn hộ · Vai trò · Thành viên hộ · Công nợ hiện tại · Phản ánh đang mở.
- **6 tab**: Hồ sơ tổng quan · Căn hộ · Phương tiện & thẻ · Công nợ · Phản ánh · Nhật ký.
  - *Hồ sơ tổng quan*: ảnh + thông tin cá nhân, căn hộ liên kết, snapshot phí & công nợ, thành viên hộ gia đình, và **Gợi ý từ AI** (tự phát hiện thiếu giấy tờ, công nợ, thẻ sắp hết hạn).

## 3. Đặt lại mật khẩu cho cư dân
Có ở **cả** màn Danh sách (nút 🔑 mỗi dòng) và màn Chi tiết (nút **Đặt lại mật khẩu**).

> Điều kiện: cư dân phải **đã có tài khoản đăng nhập**. Nếu chưa, hệ thống báo cần kích hoạt/liên kết tài khoản trước.

Bấm nút → cửa sổ chọn **1 trong 4 cách**:

| Cách | Khi nào dùng | Kết quả |
|---|---|---|
| **Cấp mật khẩu tạm (hiện 1 lần)** | Cư dân đang ở quầy, cần dùng ngay | Hệ thống hiện **mật khẩu tạm** — đọc lại cho cư dân. Chỉ hiện **một lần**. |
| **Gửi mã OTP đăng nhập** | Xác thực nhanh qua mã 6 số | Sinh **OTP (hiệu lực 10 phút)** và gửi qua kênh đã chọn. |
| **Gửi link đặt lại mật khẩu** | Để cư dân tự đặt mật khẩu mới | Gửi **link đặt lại** tới cư dân qua kênh đã chọn. |
| **Tạo link để copy (gửi qua Zalo)** | Muốn tự gửi cho cư dân | Hiện **link** kèm nút **Copy** — dán vào Zalo/tin nhắn. |

- Với OTP / Gửi link: chọn **kênh gửi** (SMS · Zalo · Email). *(Hiện Email hoạt động; SMS/Zalo đang chờ nối dịch vụ.)*
- Sau khi tạo, cửa sổ **Kết quả** hiện giá trị (mật khẩu/OTP/link) kèm nút **Copy**.
- Cư dân mở link → nhập mật khẩu mới (≥ 8 ký tự) → xác nhận → đăng nhập bằng mật khẩu mới. Link hết hạn sau 60 phút, dùng 1 lần.
- Mọi lần đặt lại đều được **ghi nhật ký** (ai làm, phương thức, thời điểm).

## 4. Mẹo
- Cần gửi link cho cư dân qua Zalo: chọn **"Tạo link để copy"** → bấm **Copy** → dán vào Zalo.
- Không thấy email cư dân nhận được? Ở môi trường test, email có thể được chuyển về **địa chỉ test** của quản trị — hỏi bộ phận vận hành (xem `docs/operations`).
