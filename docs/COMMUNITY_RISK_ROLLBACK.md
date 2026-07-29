# COMMUNITY_RISK_ROLLBACK

> Bước 2 của handoff Community Domain. Rủi ro, cờ tính năng, đường lùi.
> Lập 2026-07-29.

---

## 1. Xếp hạng rủi ro

| # | Rủi ro | Khả năng | Hậu quả | Giai đoạn |
|---|---|---|---|---|
| R1 | **Mất/hỏng cây bình luận** khi tách bảng | Trung bình | **Mất dữ liệu người dùng** | GĐ7 |
| R2 | Grants sai → người hết quyền vẫn đọc nội dung nội bộ | Trung bình | Rò rỉ nội bộ | GĐ3 |
| R3 | Backfill follow nối nhầm dự án | Cao nếu dùng khớp mờ | Feed hiện sai dự án (**không còn là lỗi quyền** sau khi chốt follow ≠ quyền) | GĐ4 |
| R10 | Hashtag tự do chen vào feed người theo dõi | Cao nếu không validate | Spam/giả mạo | GĐ5 |
| R4 | Backfill `group_type` gán sai 11 nhóm `private` | **Cao** | Sai quyền đăng/tham gia | GĐ2 |
| R5 | Đổi payload làm vỡ app đang chạy | Trung bình | App trắng màn | GĐ2, GĐ5 |
| R6 | Rò rỉ chéo tenant ở bảng comment mới | Thấp nếu làm đúng | Nghiêm trọng | GĐ7 |
| R7 | Counter lệch sau backfill | **Cao** | Số hiển thị sai | mọi GĐ |
| R8 | Feed chậm khi dữ liệu lớn | Trung bình | Trải nghiệm tệ | GĐ5/9 |
| R9 | Ảnh mất khi gộp hai đường lưu | Thấp | Bài mất ảnh | GĐ6 |

---

## 2. Từng rủi ro — cách chặn và cách lùi

### R1 — Cây bình luận

**Vì sao đáng sợ nhất:** đây là thứ duy nhất trong cả kế hoạch có thể **mất dữ liệu người
dùng thật**. Và hiện DB chỉ có **7 bình luận** — không đủ để migration script chạm phải
bất kỳ trường hợp biên nào.

**Chặn:**
- Không viết migration trước khi seed vài chục nghìn bình luận, cây sâu 3–4 cấp.
- Dual-write trước, đọc bảng cũ. Bảng cũ **không bị đụng vào** suốt giai đoạn này.
- Đối chiếu ba chiều: số lượng theo post · độ sâu cây · thứ tự thời gian trong từng cây.
- Soak tối thiểu một tuần trước khi tắt dual-write.

**Lùi:** đổi cờ đọc về bảng cũ. Bảng cũ vẫn đầy đủ vì dual-write chưa tắt. **Lùi được
tức thì, không mất gì** — chừng nào chưa qua bước tắt dual-write.

**Điểm không quay lại:** tắt dual-write. Sau mốc đó phải migrate ngược.

### R2 — Grants

**Chặn:** test bắt buộc trên tài khoản demo #6 (2 căn, 2 dự án): gỡ một quan hệ → mất
quyền đúng một nhóm, nhóm kia còn nguyên. Thêm test: hai grant cùng nhóm, revoke một →
membership **vẫn còn**.

**Lùi:** cờ `community.grants_enabled`. Tắt → quay về kiểm tra membership phẳng như hiện
tại. Bảng grants giữ lại, không drop.

### R3 — Follow nối nhầm dự án

**Chặn:** **không dùng khớp mờ**. Chỉ backfill 5 dự án đã nối chính xác theo tên; 22 dự
án còn lại BQL nối tay trong Filament.

Lý do dứt khoát: "Sunshine Garden" có ở nhiều tỉnh. Nối nhầm không phải lỗi hiển thị —
nó cho người lạ vào kênh nội dung của dự án khác, và **không ai phát hiện ra** vì mọi thứ
trông vẫn bình thường.

**Lùi:** xoá dòng trong `user_project_follows`, bảng nguồn `user_public_projects` không
bị đụng.

### R4 — Gán sai loại nhóm

**Khả năng cao nhất trong bảng** vì hiện **không có cột nào** phân biệt "câu lạc bộ sở
thích" với "nhóm cư dân tự lập" — cả 11 nhóm đều do seeder tạo.

**Chặn:** backfill mặc định `resident_interest_group` (loại **ít quyền hơn**), rồi BQL
nâng lên nếu cần. Sai theo hướng chặt hơn thì chỉ phiền; sai theo hướng lỏng hơn thì rò
nội dung.

**Lùi:** cột `kind` cũ giữ nguyên cả release → chạy lại backfill với bảng ánh xạ mới.

### R5 — Vỡ app

**Chặn:**
- Snapshot test JSON **trước** mọi thay đổi payload (Stage 1).
- Trường mới **thêm cạnh**, trường cũ giữ nguyên ít nhất một release: `can_post` ↔
  `capabilities.can_post`, `kind` ↔ `group_type`, `can{}` ↔ `capabilities`, `likes` ↔
  `reactions.total`.
- App hiện có 139 test pass — chạy đủ trước mỗi lần đổi payload.

**Lùi:** cờ `community.payload_v2` per-tenant. Tắt → resource trả đúng hình dạng cũ.

### R6 — Rò rỉ chéo tenant

**Chặn:** `community_comments.tenant_id` **NOT NULL ngay từ migration đầu** — đây là dịp
duy nhất đóng lỗ hổng của bảng `comments` cũ mà không phải migrate lần nữa. Kèm test cô
lập tenant trước khi mở endpoint.

**Không có đường lùi tử tế** cho rò rỉ dữ liệu. Phải chặn từ đầu.

### R7 — Counter lệch

Counter lệch là chuyện **khi nào**, không phải **nếu**.

**Chặn:** cập nhật trong transaction qua domain event + **job đối soát chạy định kỳ**,
không phải chạy một lần sau backfill.

**Lùi:** chạy lại job đối soát. Counter là dữ liệu dẫn xuất, luôn dựng lại được.

### R8 — Feed chậm

**Chặn:** cursor pagination từ đầu, không deep offset. Index theo DB_MAPPING. Load test
trước khi bật cho tenant lớn.

**Lùi:** bật `community_feed_items` (GĐ9). Interface đã sẵn từ GĐ5 nên không phải sửa
lại tầng gọi.

### R9 — Mất ảnh

**Chặn:** backfill `image_paths` → `attachments` là **thêm**, không xoá. Đọc song song
một release, đối chiếu số ảnh mỗi bài, rồi mới drop cột.

**Lùi:** cột `image_paths` còn nguyên đến khi drop. Trước mốc đó lùi tự do.

---

## 3. Cờ tính năng

| Cờ | Mặc định | Điều khiển |
|---|---|---|
| `community.payload_v2` | off | Trường mới trong resource |
| `community.grants_enabled` | off | Cấp quyền theo grant |
| `community.project_follows` | off | Kênh Quan tâm theo follow |
| `community.comments_dual_write` | off | Ghi cả hai bảng |
| `community.comments_read_new` | off | Đọc bảng mới |
| `community.feed_projection` | off | Bảng projection |
| ~~`community.member_group_creation`~~ | — | **Bỏ** — thay bằng thiết lập của BQL/SuperAdmin trong sản phẩm |

Tất cả **per-tenant** — bật cho một tenant nhỏ trước, không bật toàn hệ thống.

---

## 4. Lệnh backout

Mỗi tác vụ dual-write/backfill phải có lệnh nghịch đảo, viết **cùng lúc** với lệnh xuôi
chứ không để sau:

```
community:backfill-group-types      --rollback
community:backfill-grants           --rollback
community:backfill-project-follows  --rollback
community:migrate-comments  --chunk=1000 --dry-run | --rollback
community:backfill-post-media       --rollback
community:reconcile-counters                     # luôn an toàn, chạy lại được
```

`--dry-run` bắt buộc cho migrate-comments: in ra số bản ghi sẽ đụng và cây sâu nhất, chưa
ghi gì.

---

## 5. Nguyên tắc migration

1. **Additive trước.** Không drop cột/bảng legacy cùng release với việc thêm cái mới.
2. **Mỗi migration một việc.** Gộp nhiều thay đổi vào một file là ép lùi cả cụm.
3. **Backfill tách khỏi migration schema.** Backfill là lệnh artisan chạy lại được, không
   phải code trong `up()` — chạy lỗi giữa chừng trên bảng lớn là mắc kẹt.
4. **Không backfill trong giờ cao điểm** với bảng đang có ghi.

---

## 6. Ba điều tôi sẽ không tự quyết

1. ~~Bật cho cư dân tạo nhóm khi nào~~ — **đã chốt 29/07**: BQL và SuperAdmin tự thiết
   lập trong sản phẩm (`community_group_creation_policies`), mặc định `staff_only`.
2. **Nối tay 22 dự án** với danh mục công khai — cần người biết dự án nào là dự án nào.
3. **Phân loại 11 nhóm `private`** thành câu lạc bộ hay nhóm cư dân tự lập.

Cả ba đều là chỗ đoán sai thì hỏng quyền, mà không có dữ liệu nào trong hệ thống trả lời
thay được.
