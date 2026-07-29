# COMMUNITY_EXISTING_AUDIT

> Stage 0 của `X2_BMS_COMMUNITY_DOMAIN_HANDOFF_20260729` — **audit, chưa sửa code
> nghiệp vụ, chưa tạo migration**.
> Lập 2026-07-29 tại x2bms `6ac4291` / x2mobile `ba1a946`.

---

## 0. Kết luận trước, chi tiết sau

Năm điều quan trọng nhất tìm được:

1. **Bình luận cộng đồng ĐANG dùng chung bảng polymorphic `comments`** với thông báo và
   phiếu giao dịch. Đây đúng là tình huống Stage 4 dự phòng → phải tách, và phải tách
   theo dual-read/dual-write chứ không big-bang.
2. **Bảng `comments` KHÔNG có `tenant_id`.** Cô lập tenant hiện dựa hoàn toàn vào việc
   scope `commentable`. Đây là lỗ hổng cần đóng khi tách bảng, không phải sau.
3. **Chưa có `user_project_follows`.** "Dự án quan tâm" hiện lưu ở
   `user_public_projects` — trỏ vào **bảng danh mục** `public_projects` (6.005 dòng),
   trong khi nhóm cư dân trỏ vào **bảng vận hành** `projects` (27 dòng). Khoá nối
   `projects.public_project_id` vừa thêm 29/07 nhưng **mới backfill 5 dự án theo tên**.
4. **Bậc thang nhóm đã có một phần** (`kind` 4 giá trị, `post_policy`, `is_default`) —
   trùng ý nhưng **không trùng tên** với 6 space của handoff. Cần bảng ánh xạ.
5. **Chưa có gì về grants, verification badge, feed projection.** Membership hiện là
   quan hệ phẳng `(group, resident)` — không biểu diễn được "một người vào nhóm nhờ hai
   căn hộ khác nhau".

---

## 1. Routes hiện hữu

Tất cả dưới `/api/v1/resident/*`, nhóm `auth:sanctum` + `ability:resident`.

| Method | Path | Controller |
|---|---|---|
| GET | `community/posts` | `CommunityController@posts` |
| POST | `community/posts` | `CommunityPostController@store` |
| GET | `community/posts/{post}` | `@show` |
| DELETE | `community/posts/{post}` | `@destroy` |
| POST/DELETE | `community/posts/{post}/reactions` | `@react` / `@unreact` |
| GET/POST | `community/posts/{post}/comments` | `@comments` / `@storeComment` |
| POST | `community/posts/{post}/report` | `@report` |
| POST | `community/posts/{post}/moderate` | `@moderate` (ability `resident,staff`) |
| GET | `community/events` | `CommunityController@events` |
| GET | `community/polls` | `@polls` |
| POST | `community/polls/{poll}/vote` | `@vote` |
| GET | `community/groups` | `@groups` |
| POST/DELETE | `community/groups/{group}/join` | `@joinGroup` / `@leaveGroup` |

**Ngoài community nhưng dùng chung hạ tầng bình luận:**

| Method | Path | Ghi chú |
|---|---|---|
| GET/POST | `notifications/{id}/comments` | `NotificationController` |
| GET/POST | `{resource}/{id}/comments` | `SlipCommentController`, `resource` ∈ visitor-registrations, payments, amenity-bookings |
| POST | `uploads` | `UploadController` — pipeline ảnh dùng chung |

**Chưa có:** follow dự án · tham gia/rời nhóm theo grant · nâng cấp tích vàng→xanh ·
feed theo `sort` · tab theo `content_type` · bình luận cấp 2 có contract riêng.

---

## 2. Bảng và model

| Bảng | Có | Model | Ghi chú so với target |
|---|---|---|---|
| `community_posts` | ✅ 40 dòng | `CommunityPost` | Đã có `community_group_id`, `author_kind`, trạng thái kiểm duyệt |
| `community_post_reactions` | ✅ 46 | — | |
| `community_groups` | ✅ 16 | `CommunityGroup` | Đã có `kind`/`post_policy`/`is_default` (thêm 29/07) |
| `community_group_members` | ✅ | `CommunityGroupMember` | Có `resident_id`, `user_id`, `left_at` (thêm 29/07). **Chưa có grants** |
| `comments` | ✅ | `Comment` | **Polymorphic dùng chung** — xem §4 |
| `events` | ✅ | `Event` | Chưa có `source reference` từ feed |
| `polls` / `poll_options` / `poll_votes` | ✅ | | |
| `attachments` | ✅ | `Attachment` | Polymorphic, trait `HasAttachments` |
| `user_public_projects` | ✅ | — | Trỏ `public_projects`, **không phải** `projects` |
| `user_project_follows` | ❌ | — | Target yêu cầu — chưa có |
| `community_feed_items` | ❌ | — | Projection, bật khi load test chứng minh cần |
| verification history | ❌ | — | Cho gold→blue |
| membership grants | ❌ | — | |

### `community_posts` — cột hiện có

`id, tenant_id, project_id, community_group_id, author_resident_id, author_user_id,
author_kind, title, body, like_count, comment_count, status, locked_at,
locked_by_user_id, moderated_at, moderated_by_user_id, moderation_reason, report_count,
is_pinned, is_important, image_paths, created_at, updated_at, deleted_at`

Đủ cho `status` content type. **Thiếu** cho target: `content_type`, `source_type`,
`source_id`, `visibility`, `hashtags`.

### `community_groups` — cột hiện có

`id, tenant_id, project_id, name, kind, post_policy, is_default, description,
member_count, status, created_at, updated_at, deleted_at`

**Thiếu:** `building_id`, `floor_id`, `verification_level`, `created_by`, `join_policy`
(hiện `post_policy` đang gánh cả hai nghĩa), `slug`.

---

## 3. Ánh xạ bậc thang nhóm hiện tại → 6 space của handoff

| `kind` hiện tại | Số nhóm | Space target | Khớp? |
|---|---|---|---|
| `platform` | 1 | `platform_community` | ✅ trùng ý |
| `project_interest` | 2 | `project_interest_channel` | ✅ trùng ý |
| `project_resident` | 2 | `official_resident_group` | ⚠️ **thiếu** `verification_level` — hiện không phân biệt được nhóm BQL (tích xanh) với nhóm SaaS xác minh (tích vàng) |
| `private` | 11 | `resident_custom_group` **hoặc** `resident_interest_group` | ⚠️ **gộp làm một** — target tách hai |
| — | 0 | `platform_verified_resident_group` | ❌ chưa có |

`post_policy` hiện chỉ hai giá trị `members` / `staff`. Target cần tách **join policy**
khỏi **post policy** (handoff §6: "scope không đồng nghĩa join policy").

---

## 4. Bình luận — điểm nóng nhất

### Hiện trạng

**Một bảng `comments` polymorphic dùng cho MỌI thứ:**

```
id, commentable_type, commentable_id, parent_id, user_id,
author_name, author_subtitle, is_staff, body,
created_at, updated_at, deleted_at
```

Phân bố thực tế:

| `commentable_type` | Số bình luận |
|---|---|
| `App\Models\CommunityPost` | 5 |
| `App\Models\Notification` | 2 |

Trait `HasComments` gắn trên: `CommunityPost`, `Notification`, `VisitorRegistration`,
`Payment`, `AmenityBooking`.

Phía Flutter: **một module `features/comments/`** (431 dòng `CommentThread`) dùng chung,
gọi theo `resourcePath` — `resident/community/posts/{id}` và
`resident/{resource}/{id}`.

### Vì sao phải tách (khớp với chỉ đạo của chủ dự án)

1. **Quy mô lệch nhau ba bậc.** Bình luận phiếu: vài cái trên một giao dịch, đời sống
   vài ngày. Bình luận cộng đồng: dự kiến hàng triệu, sống mãi, cần phân trang/xếp
   hạng/đếm nóng.
2. **Nhu cầu tính năng khác hẳn.** Cộng đồng cần reply đa cấp, nhắc tên, cảm xúc trên
   bình luận, kiểm duyệt hàng loạt, chặn người dùng. Phiếu không cần gì trong số đó —
   và không nên gánh chi phí của chúng.
3. **`comments` không có `tenant_id`.** Cô lập tenant hiện dựa vào scope của
   `commentable`. Với 5 loại chủ thể và sắp thêm nữa, đó là mặt phẳng tấn công rộng dần.
4. **Chỉ số ghi.** Một bảng nhận cả bình luận cộng đồng lẫn giao dịch sẽ có index phục
   vụ hai mẫu truy vấn đối nghịch.

### Rủi ro khi tách

- **Chỉ 7 bình luận thật** trong DB dev → *không* chứng minh được migration đúng. Phải
  seed khối lượng lớn trước khi chạy thử (docs 15).
- Flutter dùng **chung một `CommentThread`** — tách bảng phải giữ widget đó chạy được ở
  cả hai nhánh trong thời gian dual-read, nếu không là sửa UI đã verify (vi phạm quy
  tắc 2).
- `parent_id` đang tự tham chiếu trong cùng bảng → migrate phải giữ đúng cây, không chỉ
  copy phẳng.

---

## 5. Pipeline ảnh

- `POST resident/uploads` (multipart, field `file`) → trả `{id, url}`.
- Bảng `attachments` polymorphic: `attachable_type/id, disk, path, url, file_name,
  mime_type, size, width, height, variants, order_column, uploaded_by`.
- Trait `HasAttachments::linkAttachments(ids, userId)`.
- Đang dùng ở: bài cộng đồng, bình luận, và (từ 29/07) phiếu đăng ký khách.
- Flutter: `SlipPhotoPicker` (form tạo phiếu) và bộ chọn ảnh riêng trong
  `ComposePostScreen`. **Nén 1600px/82% lúc chọn**, tải lên trước khi tạo bản ghi.
- `community_posts.image_paths` (json) **song song tồn tại** với `attachments` — hai
  đường lưu ảnh cho cùng một thực thể. Số liệu thực tế: **37/40 bài dùng
  `image_paths`, 0 bài dùng `attachments`** — nhưng `CommunityPostController@store`
  lại gọi `linkAttachments()`. Nghĩa là bài **seed** đi đường json còn bài **tạo qua
  API** đi đường attachments; cả hai nhánh đều sống, chỉ là dữ liệu hiện tại toàn từ
  seeder. Đây là bẫy: test bằng dữ liệu seed sẽ không bao giờ chạm nhánh attachments.

---

## 6. Payload và capability flags hiện có

`CommunityPostResource` trả `'can' => (object) $meta['can']` — do
`CommunityModerationService` tính. Các cờ hiện có xoay quanh kiểm duyệt (khoá/ẩn/xoá/
báo cáo).

`CommunityGroupResource` (sửa 29/07) trả `kind`, `can_post`, `is_default`,
`project_name`, `joined`, `members`.

**Thiếu so với target:** `can_comment`, `can_invite`, `can_moderate`,
`verification_level`, `content_type`, `source_type/source_id`, `sort`, `visibility`.

Quy tắc "quyền do server quyết, app chỉ render" **đã được tuân thủ** ở phần đã có —
`can_post` tính ở server, Flutter chỉ đọc.

---

## 7. Flutter

| Tệp | Dòng | Vai trò |
|---|---|---|
| `resident_community_screen.dart` | 1.771 | Màn chính — trục **nhóm** (đổi 29/07) |
| `community_providers.dart` | ~330 | Repo switch, ladder, feed patch, actions |
| `community_dto.dart` | ~380 | Parse posts/events/polls/groups |
| `remote_community_repository.dart` | ~150 | 11 endpoint |
| `mock_community_repository.dart` | ~120 | Mock đầy đủ |
| `comment_thread.dart` | 431 | **Dùng chung** cộng đồng + phiếu + thông báo |

Cache: `CacheKeys.residentCommunityPosts` (+ hậu tố `.g{groupId}` từ 29/07),
`...Events`, `...Polls`, `...Groups`. SWR cache-first, TTL 10 phút.

Ngữ cảnh: `X-Context-Id` gắn ở `ApiHeadersInterceptor` (nối 29/07). Đổi căn hộ →
`switchResidentContext()` dọn 16 khoá cache + invalidate 16 provider.

---

## 8. Tests và seeders

**Seeders:** `CommunityFeedDemoSeeder`, `CommunityGroupLadderSeeder` (29/07),
`ResidentDemoContentSeeder`, `SecondProjectDemoSeeder` (dự án thứ hai).

**Tests:** không có test Feature nào cho community ở backend. Phía app có
`resident_community_smoke_test` và `community_write_test`.

⚠️ **Không có test cô lập tenant, không có test access matrix.** Quy tắc 12 của master
prompt yêu cầu cả hai — đây là khoảng trống lớn nhất về chất lượng.

---

## 9. Bảng khoảng cách hiện tại → target

| Hạng mục | Hiện tại | Target | Mức việc |
|---|---|---|---|
| Space nhóm | 4 `kind` | 6 space | Nhỏ — thêm giá trị + tách `private` |
| Verification badge | không có | blue/gold + history | Vừa |
| Membership grants | quan hệ phẳng | nhiều grant/membership | **Lớn** |
| Follow dự án | `user_public_projects` (bảng danh mục) | `user_project_follows` (bảng vận hành) | Vừa + **backfill khó** |
| Content type | chỉ `status` | 7 loại + source ref | Vừa |
| Feed sort | `is_pinned` rồi mới nhất | `ranked` + `latest` | Vừa |
| Bình luận | polymorphic chung | module riêng | **Lớn, rủi ro cao** |
| Projection | không | `community_feed_items` (khi cần) | Hoãn |
| Cô lập tenant | dựa vào scope chủ thể | test bắt buộc | Vừa |
| Guest access | app chưa có feed cho guest | guest xem `public_guest` | Vừa |

---

## 10. Thứ tự tôi đề xuất (chờ chốt trước khi code)

1. **Foundation không rủi ro** — enums, access service, resource/capability, error code,
   audit event. Không đụng bảng.
2. **Group hierarchy + verification** — additive, ánh xạ 4 `kind` sang 6 space.
3. **Grants** — bảng mới, backfill từ membership hiện có, chạy song song.
4. **Follow dự án** — bảng mới + backfill từ `user_public_projects` **qua**
   `projects.public_project_id`. Chỗ này sẽ hụt: mới nối được 5/27 dự án theo tên.
5. **Content reference** — thêm cột, backfill `content_type='status'` cho 40 bài hiện có.
6. **Tách bình luận** — làm sau cùng và làm chậm. Cần seed khối lượng lớn trước khi tin
   là migration đúng: 7 bình luận thật không chứng minh được gì.

**Hai chỗ tôi cần chốt trước khi sang Stage 1:**

- `community_posts.image_paths` (json) và `attachments` đang là **hai đường lưu ảnh cho
  cùng một bài**. Chốt giữ đường nào — không chốt thì mọi việc sau đều phải xử lý cả hai.
- Backfill follow dự án: **22/27** dự án vận hành chưa nối được với danh mục công khai
  (đã kiểm: `projects.public_project_id` khác null chỉ 5/27).
  Nối tay, nối bằng thuật toán khớp mờ, hay chấp nhận chỉ follow được dự án đã nối?
