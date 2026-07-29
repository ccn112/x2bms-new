# COMMUNITY_DB_MAPPING

> Bước 2 của handoff Community Domain. Ánh xạ **bảng hiện có → logical target**
> (`docs/08_DB_MAPPING.md`). Lập 2026-07-29.
>
> Nguyên tắc bám quy tắc 1 & 10 của master prompt: **không tạo bảng/model trùng nếu đã
> có**, và mọi migration **additive**, có rollback.

---

## 1. Tổng quan quyết định

| Target | Cách làm | Vì sao |
|---|---|---|
| `community_groups` | **Mở rộng bảng hiện có** | 16 nhóm đang chạy, đã có `kind`/`post_policy`; đổi sang bảng mới là ép migrate dữ liệu sống mà không được gì |
| `community_group_memberships` | **Giữ `community_group_members`** | Cùng ngữ nghĩa; đổi tên bảng chỉ để khớp chữ là chi phí không đổi lấy giá trị |
| `community_membership_grants` | **Bảng mới** | Không có gì tương đương; đây là thứ làm cho multi-apartment đúng |
| `user_project_follows` | **Bảng mới** | `user_public_projects` trỏ sai bảng dự án — xem §4 |
| `community_posts` | **Mở rộng** | 40 bài đang chạy |
| `community_post_project_links` | **Bảng mới** | |
| `community_comments` | **Bảng mới + dual-write** | Điểm rủi ro cao nhất — xem §5 |
| `community_reactions` | **Giữ `community_post_reactions`, mở rộng** | Target muốn reaction cả trên comment → cần `target_type` |
| `community_events/polls` | **Giữ `events`/`polls`** + bảng attendee/vote | |
| `community_reports` / `moderation_actions` | **Bảng mới** | Hiện chỉ có cột đếm trên post, không có nhật ký hành động |
| `link_previews` | **Bảng mới**, giai đoạn sau | `link_share` chưa có |
| `community_feed_items` | **Chưa làm** | Bật khi load test chứng minh cần (docs 09 Stage 6) |

---

## 2. `community_groups` — mở rộng

### Cột thêm

| Cột | Kiểu | Mặc định | Ghi chú |
|---|---|---|---|
| `slug` | string | sinh từ name | `unique(tenant_id, slug)`; tenant platform dùng `tenant_id IS NULL` |
| `group_type` | string | ánh xạ từ `kind` | 6 space |
| `verification_level` | string | `none` | `bql_official` \| `platform_verified` \| `none` |
| `lifecycle_state` | string | `active` | thay dần `status` |
| `scope_type` | string | suy từ dữ liệu | `platform`/`tenant`/`project`/`building`/`floor` |
| `scope_id` | bigint null | | |
| `parent_group_id` | FK null | | |
| `join_policy` | string | suy từ `kind` | **tách khỏi `post_policy`** |
| `created_by_user_id` | FK null | | |
| `post_count` | int | 0 | counter denormalized |

### Ánh xạ `kind` → `group_type`

| `kind` hiện tại | Số nhóm | `group_type` | `verification_level` | `join_policy` |
|---|---|---|---|---|
| `platform` | 1 | `platform_community` | `none` | `auto_enroll` |
| `project_interest` | 2 | `project_interest_channel` | `none` | `follow_based` |
| `project_resident` | 2 | `official_resident_group` | **`bql_official`** | `auto_join_resident` |
| `private` (do BQL/seed tạo) | 11 | `resident_interest_group` | `none` | `open` |

⚠️ **Chỗ phải quyết bằng tay, không tự đoán được:** 11 nhóm `private` hiện gộp cả *câu
lạc bộ sở thích* (Chợ nội khu, Yêu bếp, CLB Chèo thuyền) lẫn *nhóm cư dân tự lập*. Không
có cột nào phân biệt vì tất cả đều do seeder tạo. Backfill mặc định `resident_interest_group`
và **để BQL sửa lại từng nhóm trong Filament** — đoán sai ở đây là gán sai quyền.

`platform_verified_resident_group` hiện **0 nhóm**; chỉ sinh ra khi có dự án chưa có BQL.

### Giữ lại tạm

`kind`, `post_policy`, `status` **giữ nguyên trong ít nhất một release** (quy tắc 10:
không drop cột legacy cùng release). App hiện đọc `kind` — bỏ ngay là vỡ.

---

## 3. `community_membership_grants` — bảng mới

```
id
membership_id        FK community_group_members
source_type          resident_relation | manual_join | invitation | system_enrollment
source_id            bigint null   -- resident relationship id, KHÔNG phải apartment id
granted_by_user_id   FK null
status               active | revoked
granted_at / revoked_at / expires_at
unique(membership_id, source_type, source_id)
index(source_type, source_id, status)
index(expires_at, status)
```

**Quy tắc nghiệp vụ:** membership chỉ bị thu hồi khi **không còn grant active nào**. Đây
là điều làm cho tình huống của tài khoản demo (2 căn ở 2 dự án) chạy đúng.

**Backfill:** mỗi `community_group_members` hiện có sinh một grant:
- nhóm `is_default = true` → `source_type = system_enrollment`
- còn lại → `source_type = manual_join`

Idempotent theo unique key.

---

## 4. `user_project_follows` — bảng mới, backfill hụt

```
id
user_id      FK users
project_id   FK projects        -- BẢNG VẬN HÀNH, không phải public_projects
followed_at
unique(user_id, project_id)
index(project_id, followed_at)
```

### Vai trò của follow — chốt 2026-07-29

**Follow KHÔNG cấp quyền và KHÔNG cho vào nhóm.** Nó chỉ là **tín hiệu ưu tiên hiển
thị**: bài có gắn dự án mình theo dõi được đẩy lên trong feed.

Điều này khớp với quy tắc 4 của master prompt ("không dùng hashtag để phân quyền") và
docs 01 §5. Nhưng có một chỗ phải cẩn thận:

> Nếu **hashtag tự do** quyết định bài nào lọt vào feed người theo dõi dự án, thì bất kỳ
> ai cũng gõ được `#SunshineGarden` để xuất hiện trước mặt vài nghìn người quan tâm dự
> án đó. Đấy là kênh phát tán cho spam và giả mạo, mở sẵn.

**Cách làm an toàn — hashtag là ĐẦU VÀO, project link là SỰ THẬT:**

1. Người viết gõ `#TenDuAn` cho tiện (hoặc chọn từ gợi ý).
2. Lúc **ghi bài**, server phân giải hashtag → `community_post_project_links`, có
   validate: dự án có thật, cùng tenant, và người viết có liên hệ gì với nó
   (cư dân / đang theo dõi / là publisher).
3. Feed xếp hạng theo `community_post_project_links` — **không** quét chuỗi hashtag lúc
   đọc. Quét chuỗi lúc đọc thì vừa không index được, vừa không chặn được ai.
4. Hashtag không phân giải được thì vẫn giữ trong nội dung như chữ thường, **không** tạo
   liên kết dự án.

Hệ quả cho kế hoạch: kênh `project_interest_channel` **không cần membership**. Ai theo
dõi dự án thì thấy kênh đó, hết. Không join, không rời, không grant.

### Vấn đề backfill — cần chốt

"Dự án quan tâm" hiện ở `user_public_projects.public_project_id` → trỏ **bảng danh mục**
`public_projects` (6.005 dòng). `user_project_follows` phải trỏ **bảng vận hành**
`projects` (27 dòng).

Cầu nối là `projects.public_project_id`, nhưng **mới nối được 5/27** (khớp theo tên
chính xác, 29/07).

Ba lựa chọn:

| Cách | Kết quả | Rủi ro |
|---|---|---|
| **A. Chỉ backfill dự án đã nối** | 5/27 dự án follow được | Người quan tâm 22 dự án còn lại mất dữ liệu quan tâm — im lặng |
| **B. Khớp mờ tên + địa chỉ** | có thể lên ~20/27 | **Nối nhầm dự án** = cho người lạ vào kênh dự án khác. Đây là lỗi quyền, không phải lỗi hiển thị |
| **C. BQL nối tay trong Filament** | đúng 100% | Chậm, cần màn quản trị mới |

**Tôi đề xuất A + C:** backfill 5 dự án đã nối ngay, dựng màn nối tay trong Filament cho
22 dự án còn lại. Không dùng B — nối nhầm dự án là rò rỉ phạm vi, mà khớp mờ tên dự án
bất động sản Việt Nam thì sai rất dễ ("Sunshine Garden" có ở nhiều tỉnh).

`user_public_projects` **giữ nguyên** — nó vẫn đúng vai trò "quan tâm ở danh mục công
khai" cho người chưa là cư dân.

---

## 5. `community_comments` — bảng mới, đường đi chậm

```
id
tenant_id            FK              -- ĐÓNG LỖ HỔNG: bảng `comments` cũ không có
post_id              FK community_posts
parent_comment_id    FK null
root_comment_id      FK null         -- gom cây, tránh đệ quy khi đọc
author_user_id       FK
body / lifecycle_state / moderation_state
reply_count / reaction_count
created_at / updated_at / deleted_at
index(post_id, parent_comment_id, created_at, id)
index(root_comment_id, created_at)
index(author_user_id, created_at)
```

### Đường di trú (docs 09 Stage 4)

1. **Đóng băng ngữ nghĩa bằng test** trên bảng `comments` hiện tại — trước khi đụng gì.
2. Tạo `community_comments`.
3. **Dual-write**: ghi cả hai bảng, đọc bảng cũ.
4. Migrate theo chunk, giữ nguyên `parent_id` → dựng lại `root_comment_id`.
5. **Đối chiếu**: số lượng theo post, độ sâu cây, thứ tự thời gian.
6. Chuyển đọc sang bảng mới; vẫn dual-write.
7. Sau soak, tắt dual-write và bỏ nhánh cũ.

### Điều kiện tiên quyết

**Chỉ có 7 bình luận thật trong DB** (5 community + 2 notification). Con số đó không
chứng minh được gì. **Phải seed khối lượng lớn trước bước 3** — tối thiểu vài chục nghìn
bình luận có cây sâu 3-4 cấp, rải trên nhiều post/tenant (docs 15).

Bình luận **phiếu và thông báo ở lại bảng `comments`** — chúng không có vấn đề quy mô và
di chuyển chúng là rủi ro không đổi lấy gì.

---

## 6. Ảnh bài viết — chốt `attachments`

Hiện có **hai đường song song**: `community_posts.image_paths` (json, 37/40 bài) và
`attachments` (polymorphic, 0 bài — nhưng `CommunityPostController@store` đang gọi
`linkAttachments`).

**Chốt: `attachments` là nguồn chân lý.** Lý do:

1. Nó mang `width`/`height` — feed **cần** để chừa chỗ ảnh trước khi tải xong. Không có
   thì mỗi lần ảnh về là layout nhảy, và ở feed cuộn dài đó là lỗi thấy rõ nhất.
2. Nó mang `uploaded_by`. Khi chặn một tài khoản spam ảnh, phải trả lời được "xoá mọi ảnh
   người này tải lên" — với json thì phải quét toàn bộ bảng post.
3. Nó có `variants` cho ảnh nhiều kích thước. Ở quy mô triệu bài, gửi ảnh gốc cho
   thumbnail là hỏng băng thông.
4. Xoá mềm và kiểm duyệt **từng ảnh** — json không làm được.
5. Bình luận và phiếu **đã dùng** `attachments`. Một pipeline thay vì hai.

Đổi lại: feed phải join thêm một bảng. Chấp nhận được — eager-load theo trang, và nếu
load test chứng minh join này đau thì thêm cột json **dẫn xuất** (ghi lại lúc tạo/sửa
bài), chứ không quay lại lấy json làm nguồn chân lý.

**Đường đi:** backfill 37 bài từ `image_paths` sang `attachments` → đọc song song một
release → drop `image_paths`.

---

## 7. `community_posts` — mở rộng

| Cột thêm | Mặc định backfill |
|---|---|
| `content_type` | `status` cho cả 40 bài |
| `source_type` / `source_id` | null |
| `visibility` | suy từ `group_type` của nhóm chứa bài |
| `lifecycle_state` | ánh xạ từ `status` |
| `moderation_state` | ánh xạ từ `moderated_at`/`locked_at` |
| `published_at` | `created_at` |
| `reaction_count` | đếm lại từ `community_post_reactions` |

`community_post_project_links`: backfill từ `community_posts.project_id` với
`relation_type = 'primary'`.

---

## 8. Cô lập tenant

Ba việc phải làm, không hoãn:

1. `community_comments.tenant_id` **bắt buộc** ngay từ migration đầu.
2. Nhóm `platform_community` dùng `tenant_id IS NULL`; mọi nhóm khác bắt buộc có.
3. Validate `project_id`/`building_id` **cùng tenant** ở tầng service — không tin
   `project_id` client gửi (docs 08 §3).

Kèm **test cô lập tenant** trước khi mở bất kỳ endpoint nào — hiện backend chưa có test
nào loại này.

---

## 9. Counter và reconciliation

Đã có: `group.member_count`, `post.like_count`, `post.comment_count`, `post.report_count`.
Thêm: `group.post_count`, `post.reaction_count`, `comment.reply_count/reaction_count`.

Cập nhật qua domain event trong transaction. **Kèm job đối soát** — counter lệch là
chuyện khi nào cũng xảy ra, không phải nếu.

---

## 10. Quyền tạo nhóm — thiết lập, không phải cờ tính năng

Chốt 2026-07-29: **BQL và SuperAdmin thiết lập** ai được tạo nhóm, chứ không phải một
cờ kỹ thuật tôi bật/tắt.

Nghĩa là nó là **tính năng của sản phẩm**, phải có màn cấu hình, phải lưu, phải audit —
không phải dòng trong `config/`.

```
community_group_creation_policies
  id
  scope_type      platform | tenant | project
  scope_id        null với platform
  allowed_for     nobody | staff_only | verified_resident | any_member
  requires_approval  bool
  max_per_user    int null
  updated_by_user_id
  timestamps
  unique(scope_type, scope_id)
```

**Thứ tự áp dụng — hẹp thắng rộng:** `project` → `tenant` → `platform` → mặc định
`staff_only`.

Mặc định là `staff_only` chứ không phải `verified_resident`: mở van nội dung trước khi
có người trực kiểm duyệt là tự tạo việc cho mình. BQL nào sẵn sàng thì tự mở.

- **SuperAdmin** đặt mức `platform` và `tenant`.
- **BQL** đặt mức `project` của mình, nhưng **không nới rộng hơn** mức tenant cho phép —
  nếu không thì thiết lập cấp trên thành vô nghĩa.

Server tính `capabilities.can_create_group` từ bảng này; app chỉ đọc và ẩn/hiện nút.
