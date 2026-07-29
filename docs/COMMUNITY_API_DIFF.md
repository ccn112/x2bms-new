# COMMUNITY_API_DIFF

> Bước 2 của handoff Community Domain. Diff **route/payload hiện có → logical target**
> (`docs/07_API_CONTRACT.md`). Lập 2026-07-29.
>
> Quy tắc bám: **không phá UI đã verify** (Stage 1) — route đang chạy giữ nguyên, cái mới
> đi kèm hoặc thêm cạnh.

---

## 1. Nguyên tắc đường đi

Target đặt community ở `/api/v1/community/*`, hiện tại ở `/api/v1/resident/community/*`.

**Không đổi prefix trong đợt này.** Lý do: prefix `resident/*` gắn với `ability:resident`
của Sanctum. Đổi prefix là đổi luôn ranh giới quyền, mà guest feed (yêu cầu mới) lại cần
một ranh giới khác hẳn. Làm hai việc trong một lần đổi là cách chắc chắn nhất để hỏng cả
hai.

Đường đi:

1. Giữ `resident/community/*` cho cư dân — **thêm** field vào payload, không đổi đường.
2. Mở `public/community/*` **mới** cho guest — không đụng gì đang chạy.
3. Cân nhắc alias `community/*` cho member (chưa là cư dân) ở đợt sau, khi tier `member`
   thực sự có màn dùng tới.

---

## 2. Route — giữ, thêm, hoãn

### Giữ nguyên đường, mở rộng payload

| Route hiện có | Thay đổi |
|---|---|
| `GET resident/community/posts` | Thêm query `sort` (`ranked`\|`latest`), `content_type`, `group_id` (đã có 29/07). Payload thêm `content_type`, `source`, `capabilities` |
| `GET resident/community/groups` | Payload thêm `group_type`, `verification_level`, `join_policy`, `capabilities`, `scope` |
| `POST resident/community/posts` | Thêm `content_type`, `project_links[]`, `attachment_ids[]` (đã có) |
| `POST/DELETE .../reactions` | Thêm `target_type` để reaction được cả comment |
| `GET/POST .../comments` | **Đổi nguồn đọc** sang `community_comments` sau khi dual-read xong — đường không đổi |
| `POST .../moderate` | Thêm các action mới của docs 12 |

### Thêm mới

| Route | Ưu tiên | Ghi chú |
|---|---|---|
| `GET resident/community/bootstrap` | **Cao** | Quyết định tab/scope/composer ở một lượt. Thiếu nó app phải đoán và tự suy quyền |
| `GET resident/community/groups/{group}` | Cao | Hiện chỉ có list |
| `GET resident/community/groups/{group}/feed` | Cao | Hiện lọc bằng `?group_id=` — chấp nhận được, nhưng target muốn đường riêng |
| `GET/POST/DELETE me/project-follows` | Cao | Nguồn chuẩn cho theo dõi dự án |
| `POST resident/community/comments/{c}/replies` | Trung bình | Hiện reply đi qua `parent_id` trong body |
| `POST resident/community/posts/{post}/save` | Trung bình | Chưa có lưu bài |
| `GET public/community/feed` | Trung bình | Guest — chỉ `public_guest` |
| `PATCH .../groups/{g}/verification` | Trung bình | Tích vàng → xanh |
| `POST .../groups/{g}/upgrade-to-official` | Trung bình | Giữ nguyên id/thành viên/nội dung |
| `GET community/moderation/queue` | Thấp | Hiện kiểm duyệt từng bài trong app |
| `PATCH .../groups/{g}/notification-settings` | Thấp | |
| `POST .../events/{e}/rsvp` | Thấp | Sự kiện hiện chỉ đọc |

### Hoãn có chủ đích

- `PATCH posts/{post}` — sửa bài. Chưa có, và sửa bài kéo theo lịch sử phiên bản +
  kiểm duyệt lại. Để sau khi domain ổn.
- `POST community/groups` — cư dân **tự lập nhóm**. Đây là thứ mở van cho nội dung tăng
  đột biến; nên bật sau khi hàng đợi kiểm duyệt chạy được, không phải trước.

---

## 3. Diff payload cụ thể

### `CommunityGroupResource`

```diff
  id, name, description, category, members, joined, icon_key, image_url
  kind, project_name, can_post, is_default          // thêm 29/07
+ group_type            // 6 space, thay dần `kind`
+ verification_level    // bql_official | platform_verified | none
+ verification_label    // "Chính thức từ Ban quản lý" | "Đã xác minh bởi X2Living"
+ join_policy           // TÁCH khỏi post_policy
+ scope: {type, id, name}
+ capabilities: {can_post, can_comment, can_invite, can_moderate, can_leave}
+ post_count
```

`verification_label` trả **từ server**, không để app tự map — docs 01 §3 yêu cầu badge
luôn có nhãn ngữ nghĩa cho accessibility, và nhãn đó là chuyện nghiệp vụ chứ không phải
chuyện hiển thị.

`can_post` hiện là field phẳng → **chuyển vào `capabilities`**. Giữ field cũ song song
một release để app không vỡ.

### `CommunityPostResource`

```diff
  id, author{...}, body, likes, comments, pinned, important, image_urls, can{}
+ content_type          // status | official_announcement_ref | news_ref | ...
+ source: {type, id, deep_link}   // null với content_type=status
+ project_links: [{id, name, relation_type}]
+ visibility
+ reactions: {summary, mine, total}   // gộp, thay `likes` phẳng
+ capabilities: {...}                 // thay `can{}` cho nhất quán
```

`can{}` → `capabilities` là đổi tên thuần. Giữ `can` song song một release.

### Bootstrap (mới)

```json
{
  "identity_tier": "verified_resident",
  "current_context": {"resident_context_id": "apartment:1305", "project_id": 1},
  "default_feed_scope": "for_you",
  "available_feed_scopes": ["for_you", "latest", "x2living", "following_projects"],
  "tabs": ["all", "official_announcement", "event", "poll"],
  "groups": [ /* bậc thang đã sắp */ ],
  "project_follows": [],
  "composer": {"enabled": true, "allowed_types": ["status"]},
  "capabilities": {}
}
```

Đây là endpoint **đáng làm trước nhất**. Hiện app tự sắp bậc thang nhóm ở
`communityGroupLadderProvider` và tự đoán tab — tức logic nghiệp vụ đang nằm ở client,
đúng thứ quy tắc 3 cấm.

---

## 4. Chỗ app sẽ vỡ nếu làm ẩu

| Đổi | App đang đọc | Cách an toàn |
|---|---|---|
| `can_post` → `capabilities.can_post` | `CommunityGroupResource` field phẳng | Trả **cả hai** một release |
| `kind` → `group_type` | `communityGroupLadderProvider` sắp theo `kind` | Trả cả hai; app đổi sau |
| `can{}` → `capabilities` | `CommunityDto` | Trả cả hai |
| `likes` → `reactions.total` | `_PostActionBar` | Trả cả hai |
| Comment đổi bảng | `CommentThread` dùng chung với phiếu | **Đường API không đổi** → app không biết gì. Đây là lý do chọn giữ route |

App hiện có **139 test pass**; snapshot test JSON (Stage 1) phải chạy **trước** mọi thay
đổi payload.

---

## 5. Mã lỗi

Hiện dùng: `not_found`, `forbidden`, `validation_failed`, `no_context`, `no_apartment`.

Thêm cho domain này:

| Mã | Khi nào |
|---|---|
| `group_not_visible` | Nhóm ngoài phạm vi tier/context — **phân biệt với `not_found`** để log phân tích được, nhưng trả cùng 404 ra ngoài |
| `join_not_allowed` | `join_policy` không cho |
| `post_not_allowed` | Nhóm chỉ BQL đăng |
| `content_moderated` | Nội dung đang bị hạn chế |
| `rate_limited` | Chống spam |
| `idempotency_conflict` | Cùng key, khác payload |

**Ra ngoài client, `group_not_visible` phải trả 404 chứ không phải 403** — 403 xác nhận
nhóm đó tồn tại, tức để lộ thông tin cho người dò id.

---

## 6. Idempotency

Bắt buộc cho: tạo bài, tạo bình luận, RSVP, vote, join/leave, moderation action.

Header `Idempotency-Key`, lưu 24h. Hiện **chưa có gì** — mạng chập chờn là đăng trùng
bài, mà app lại có optimistic UI nên người dùng bấm lại là chuyện thường.
