# Cộng đồng — Lớp GHI + Kiểm duyệt (thiết kế chốt 2026-07-27)

> Slice mở khóa tab Cộng đồng app cư dân. UI app đã xong (`x2mobile@852478a`),
> backend hiện **chỉ đọc** → app đang giữ tạm trong RAM (`localCommunityPostsProvider`).
>
> **Quyết định của chủ dự án (2026-07-27):**
> 1. **KHÔNG duyệt trước.** Cư dân đăng là hiện ngay (`status=published`). Hậu kiểm.
> 2. BQL **có thể khóa** bài.
> 3. Chức năng kiểm duyệt đầy đủ **trên Web BQL** (`/admin`).
> 4. **Ngay trên app** — nếu người xem là BQL thì thấy option **ẩn / xóa mềm** trực tiếp trên bài.

---

## 1. Ba hành động kiểm duyệt — KHÁC NHAU, đừng gộp

Đây là điểm dễ làm sai nhất. "Khóa" và "ẩn" không phải một.

| Hành động | Cột | Bài còn hiện? | Bình luận/reaction mới? | Dùng khi |
|---|---|---|---|---|
| **Khóa** | `locked_at` | ✅ Có | ❌ Không | Bài hợp lệ nhưng tranh cãi căng → dừng đổ thêm dầu, vẫn giữ nội dung cho mọi người đọc |
| **Ẩn** | `status='hidden'` | ❌ Không (trừ tác giả + BQL) | ❌ Không | Nội dung vi phạm → gỡ khỏi feed nhưng còn nguyên để đối chất |
| **Xóa mềm** | `deleted_at` | ❌ Không (chỉ BQL xem thùng rác) | ❌ Không | Spam/rác rõ ràng, hoặc tác giả tự xóa bài mình |

Tác giả **tự xóa được bài mình** (soft delete) — không cần BQL. Cư dân không tự khóa/ẩn.

Bài bị ẩn thì **tác giả vẫn thấy** bài của mình kèm nhãn "Đã bị ẩn bởi BQL" + lý do — tránh
cảnh người ta đăng xong thấy bài biến mất không hiểu vì sao rồi đăng lại lần nữa.

---

## 2. Schema (migration ADD-ONLY, guard `hasColumn`/`hasTable` như chuẩn hiện hành)

### 2a. `community_posts` — thêm cột

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `author_user_id` | `foreignId` nullable | **Cần thiết**: hiện chỉ có `author_resident_id`, không đủ để xác định "bài của tôi" (1 user có nhiều resident membership) và không cover bài do BQL đăng |
| `author_kind` | `string` default `resident` | `resident` \| `staff` — bài BQL đăng thẳng lên feed (map 3.7 có "bài viết cư dân/**BQL**") |
| `locked_at` | `timestamp` nullable | Khóa tương tác |
| `locked_by_user_id` | `foreignId` nullable | |
| `moderated_at` | `timestamp` nullable | Thời điểm ẩn/bỏ ẩn gần nhất |
| `moderated_by_user_id` | `foreignId` nullable | |
| `moderation_reason` | `string` nullable | Hiện cho tác giả xem |
| `report_count` | `unsignedInteger` default 0 | Sắp xếp hàng đợi kiểm duyệt |

`status` giữ nguyên enum sẵn có `published|hidden|pending`. **`pending` không dùng** (không duyệt
trước) — giữ cột để sau này bật per-project nếu có dự án khó tính muốn duyệt trước.

`deleted_at` đã có sẵn qua `2026_07_01_000025_add_soft_deletes_and_archive` (thêm cho mọi bảng
nghiệp vụ) — model `CommunityPost` đã `use SoftDeletes`. Không cần làm gì.

### 2b. `community_post_reactions` (mới)

```
id · community_post_id · user_id · emoji (string) · timestamps
UNIQUE (community_post_id, user_id)   ← 1 người 1 cảm xúc; đổi emoji = UPDATE, không tạo dòng mới
INDEX  (community_post_id, emoji)     ← đếm summary
```

Emoji giới hạn whitelist đúng bộ app đang dùng: `like|love|haha|wow|sad|angry` (lưu **mã**, không
lưu ký tự emoji — đổi bộ icon sau này không phải migrate data).

`like_count` sẵn có trên `community_posts` → giữ, cập nhật bằng tổng số reaction (không chỉ "like")
để không phá code cũ đang đọc cột này.

### 2c. `community_post_reports` (mới)

```
id · community_post_id · reported_by_user_id · reason (string) · note (text nullable)
   · status: open|resolved|dismissed · resolved_by_user_id · resolved_at · timestamps
UNIQUE (community_post_id, reported_by_user_id)   ← 1 người report 1 bài 1 lần
```

Không có report thì hậu kiểm không chạy được — BQL sẽ không biết bài nào cần xem.

### 2d. Bình luận — KHÔNG tạo bảng mới

`use HasComments` trên `CommunityPost` là xong. Bảng `comments` polymorphic + `CommentResource` +
nhãn tác giả 3 tầng đã chạy thật cho thông báo và 3 loại phiếu. Reply 1 cấp qua `parent_id`.

---

## 3. API resident

Tất cả nằm trong nhóm `prefix('resident')` sẵn có. Scope theo `project_id ∈ ResidentContextService::projectIds()`.

### 3a. Cư dân

| Method | Route | Ghi chú |
|---|---|---|
| `POST` | `community/posts` | `{body, attachment_ids[], community_group_id?}` → `status=published` ngay. Ảnh qua `POST resident/uploads` đã có |
| `GET` | `community/posts/{post}` | Chi tiết 1 bài (màn `PostDetailScreen`) |
| `DELETE` | `community/posts/{post}` | **Chỉ tác giả** (`author_user_id === auth id`) → soft delete |
| `GET/POST` | `community/posts/{post}/comments` | `SlipCommentController` mở rộng whitelist, hoặc route riêng — xem §5 |
| `PUT` | `community/posts/{post}/reactions` | `{emoji}` → upsert. `DELETE` để bỏ |
| `POST` | `community/posts/{post}/report` | `{reason, note?}` |

**Chặn ghi khi bài bị khóa/ẩn:** `POST comments` và `PUT reactions` trả **423 Locked** nếu
`locked_at != null`, **404** nếu `status=hidden` (với người không phải tác giả/BQL).

### 3b. BQL kiểm duyệt ngay trên app

```
POST resident/community/posts/{post}/moderate
     {action: hide|unhide|lock|unlock|delete|restore, reason?}
```

**Middleware:** nhóm riêng `ability:resident,staff` — alias `ability` trong `bootstrap/app.php` là
`CheckForAnyAbility` (**OR**, không phải AND), nên BQL **không** phải là cư dân vẫn gọi được.
Nhóm resident hiện tại dùng `ability:resident` sẽ chặn nhân sự thuần → phải tách nhóm.

**Kiểm quyền trong controller** (không dựa vào middleware là đủ):
```php
abort_unless($user->isStaffOperator(), 403);
$scope = $user->accessibleProjectIds();          // null = platform admin, không giới hạn
abort_unless($scope === null || in_array($post->project_id, $scope, true), 403);
```
`accessibleProjectIds()` (`app/Models/User.php:123`) đã trả đúng project-scope grants ∪ home project.

Ghi `moderated_by_user_id` + `moderated_at` + `moderation_reason` mỗi lần, và ghi `AuditLog` —
kiểm duyệt là hành động có thể bị khiếu nại, phải truy vết được ai làm.

### 3c. Payload bổ sung cho `CommunityPostResource`

```jsonc
{
  "id": "12",
  "author": { "name": "...", "role": "...", "avatar_url": "...", "verified": true,
              "kind": "resident|staff", "subtitle": "A-0205 | Ban quản lý" },
  "body": "...", "image_urls": [...],
  "comments": 4,
  "reactions": { "summary": {"like": 12, "love": 3}, "total": 15, "mine": "love" },
  "locked": false,
  "hidden": false,                 // chỉ true khi người xem là tác giả/BQL
  "moderation_reason": null,
  "can": { "comment": true, "react": true, "delete": false, "moderate": true }  // ← app dựng menu theo cờ này
}
```

**`can` do server tính, app không tự suy luận.** App chỉ hỏi "tôi được làm gì" chứ không đi
đoán vai trò từ `abilities` — tránh chuyện client và server hiểu quyền khác nhau.

⚠️ **Sửa luôn `CommunityPostResource`:** hiện bài **không có ảnh** thì fallback ra ảnh demo
(`DemoImage::url(...)`, dòng 33). Bài cư dân đăng chay bằng chữ sẽ tự mọc ảnh lạ. Chỉ fallback
cho bài seeder (`author_user_id === null`), bài thật để `image_urls: []`.

---

## 4. Web BQL — màn kiểm duyệt (`/admin`)

Resource hiện có `app/Filament/Resources/CommunityPosts/` là **scaffold sinh tự động**: cột raw
`project_id`/`author_resident_id` dạng số, chỉ có Edit/Delete mặc định, chưa theo
`docs/LISTING_PAGE_STANDARD.md`. Cần dựng lại thành màn nghiệp vụ **BQL-07-08 Kiểm duyệt cộng đồng**.

**Màn `Pages/CommunityModeration.php`** theo chuẩn listing:
- **KPI strip (tính lại theo filter):** Bài mới hôm nay · **Chờ xử lý report** · Đang khóa · Đang ẩn · Đã xóa mềm.
- **X2FilterBar:** trạng thái (Hiện/Khóa/Ẩn/Đã xóa) · có report · dự án · tòa · khoảng ngày · search nội dung/tác giả.
- **Mặc định sắp xếp:** `report_count desc, created_at desc` — bài bị tố nhiều nổi lên đầu. Hậu
  kiểm mà xếp theo thời gian thì BQL phải đọc hết cả feed.
- **Cột:** tác giả (tên + căn hộ, click sang chi tiết cư dân) · trích nội dung · ảnh (thumb) ·
  tương tác (👍 số / 💬 số) · report · trạng thái (badge) · ngày.
- **Row action:** Khóa/Mở khóa · Ẩn/Bỏ ẩn · Xóa mềm/Khôi phục · Xem chi tiết. Mỗi action mở modal
  **bắt nhập lý do** (trừ mở khóa/bỏ ẩn/khôi phục).
- **Bulk inline** (không `BulkActionGroup`, theo chuẩn): ẩn / xóa mềm nhiều bài spam một lượt.
- **Màn chi tiết bài** (07-09): nội dung đầy đủ + ảnh + **cây bình luận** (ẩn được từng bình luận) +
  danh sách report kèm người tố + lịch sử kiểm duyệt.

**Bẫy Filament v5 đã trả giá (DEV_JOURNAL 2026-07-17), áp lại:** bảng phải
`->query(fn () => $this->filteredQuery())` dạng **closure**; đổi filter phải
`resetPage(...)` **+ `flushCachedTableRecords()`**; layout `Section/Grid` ở
`Filament\Schemas\Components`; action modal dùng `->schema()` không `->form()`.

---

## 5. App cư dân — chạm vào đâu

**Menu "⋯" trên mỗi thẻ bài** (`resident_community_screen.dart`), dựng theo cờ `can` từ server:
- Cư dân thường: *Báo cáo bài viết* · *Xóa bài* (chỉ bài mình).
- BQL: thêm khối phân cách + *Khóa bình luận* · *Ẩn bài* · *Xóa bài* — mỗi cái mở sheet nhập lý do.
- Bài đang khóa: composer bình luận thay bằng dải xám "BQL đã khóa bình luận bài này".
- Bài bị ẩn (tác giả xem): banner vàng "Bài đã bị ẩn bởi BQL — {lý do}".

**Thay `localCommunityPostsProvider` bằng remote repository** — đây là việc chính. Feed, đăng bài,
reaction, bình luận đều đi qua API. Giữ optimistic update (bấm tim là đổi màu ngay, rollback nếu
request hỏng) để không mất cảm giác mượt hiện tại.

**`CommentThread` đã có sẵn** (`features/comments/`) — nối vào `PostDetailScreen`, không viết lại.

Bootstrap `/me/bootstrap` **đã trả sẵn** `user.abilities` và `available_contexts` gồm
`type:'staff'` + `project_id` (`BootstrapController.php:86-92`) — app chỉ chưa parse. Dùng để
quyết định có gọi API kiểm duyệt hay không; còn hiện/ẩn từng nút thì theo `can` của từng bài.

---

## 6. Thứ tự làm

1. Migration (cột + 2 bảng) · `HasComments` + `HasAttachments` trên `CommunityPost` · model reaction/report.
2. `POST/GET/DELETE posts`, comments, reactions, report — `CommunityPostResource` + `can`, sửa fallback ảnh demo.
3. `POST moderate` + nhóm middleware `ability:resident,staff` + AuditLog.
4. App: remote repository thay local provider + menu ⋯ + trạng thái khóa/ẩn.
5. Web: `CommunityModeration` + màn chi tiết bài theo chuẩn listing.

Bước 1–3 chặn bước 4. Bước 5 làm song song được.

---

## 7. Điểm còn hở

- **Không có kênh báo cho tác giả** khi bài bị ẩn ngoài lúc họ tự mở app xem. Nếu muốn push
  notification thì nối vào `NotificationDeliveryLog` — chưa nằm trong slice này.
- **Ngưỡng tự động ẩn** khi `report_count` vượt N: chưa làm, cố tình. Hậu kiểm thủ công trước, có
  số liệu thật rồi mới chốt ngưỡng.
- **Bài BQL đăng** (`author_kind=staff`) đã chừa cột nhưng chưa có màn soạn bài phía web — hiện BQL
  vẫn dùng Thông báo. Làm khi có nhu cầu thật.
