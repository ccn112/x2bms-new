# Community Mass Seed — sinh dữ liệu cộng đồng quy mô

Bộ công cụ sinh dữ liệu cộng đồng deterministic từ `demo` đến `full` để test UX,
feed, phân trang keyset, kiểm duyệt và tải. Tích hợp từ gói handoff
`X2_BMS_COMMUNITY_MASS_SEED_HANDOFF_20260726`, đã **map sang schema thật** của x2bms.

## Thành phần

| File | Vai trò |
|---|---|
| `app/Console/Commands/CommunitySeedScale.php` | Command `community:seed-scale` |
| `app/Support/CommunitySeed/CommunityMassSeeder.php` | Bộ sinh (batch insert, resume, deterministic) |
| `config/community_seed.php` | 4 profile + seed + đường dẫn ngân hàng nội dung |
| `database/seed-data/community/*.vi.json` | Ngân hàng nội dung tiếng Việt (posts/comments/entities) |
| `database/migrations/2026_08_02_100000_add_community_mass_seed_support.php` | Cột `seed_tag` + index feed/kiểm duyệt/reply còn thiếu |
| `tests/Feature/CommunityMassSeedTest.php` | Cô lập tenant · cursor keyset · counter consistency |

## Lệnh chạy

```bash
# Demo (2.000 bài) — DÙNG trên DB dev để verify
php artisan community:seed-scale --profile=demo --tenant=1 --project=1 --reset

# UX (50.000 bài) — infinite scroll / filter / moderation
php artisan community:seed-scale --profile=ux --tenant=1 --project=1 --reset

# Load / Full — CHỈ staging (xem cảnh báo bên dưới)
php artisan community:seed-scale --profile=full --tenant=1 --project=1 --resume

# Chỉ xem kế hoạch, không ghi
php artisan community:seed-scale --profile=ux --dry-run

# Ghi đè nhanh
php artisan community:seed-scale --profile=demo --posts=500 --comments-min=1 --comments-max=5
```

Cờ: `--reset` (xoá bài seed cũ theo `seed_tag`, GIỮ dữ liệu demo/thật), `--resume`
(tiếp tục từ checkpoint trong cache), `--dry-run`.

## Ánh xạ schema thật (KHÁC file mẫu handoff)

File mẫu giả định `community_comments`, `community_reactions`, cột
`post_type/scope_type/reaction_count`… — **không tồn tại** trong x2bms. Ánh xạ đúng:

| Khái niệm | Bảng / cột THẬT |
|---|---|
| Bài | `community_posts` (id **bigint**), `body`, `like_count`, `comment_count`, `status`(published\|hidden\|pending), `published_at`, `is_pinned`, `is_important`, `content_type`, `author_resident_id`, `author_user_id`, `author_kind`, `community_group_id`, `report_count`, `moderation_reason`, `moderated_at`, `deleted_at`(soft delete) |
| Cảm xúc | `community_post_reactions` — MỘT hàng/1 user/1 bài (`unique(post,user)`), `emoji`∈{like,love,haha,wow,sad,angry}. `like_count` = số hàng |
| Report/kiểm duyệt | `community_post_reports` (reason spam\|offensive\|false_info\|other) + cột moderation trên bài |
| **Bình luận** | Bảng **polymorphic DÙNG CHUNG** `comments` (`commentable_type`=`App\Models\CommunityPost`, `commentable_id`), đa cấp 1 lớp qua `parent_id`. **Không** có cột status/tenant/project |

Loại bài (BQL/sự kiện/hỏi đáp/đồ thất lạc/thú cưng/mua bán…) phản ánh qua **nội dung
`body`** + `is_pinned`/`is_important` + `author_kind`, vì schema không có cột `post_type`.
Trạng thái `rejected` (không thuộc enum thật) biểu diễn = `hidden` + `moderation_reason`;
`deleted` = xoá mềm (`deleted_at`). Media chỉ là `image_paths` (JSON) — seed để `null`.

## ⚠ Comment quy mô `full` (25 triệu) cần bảng chuyên dụng GĐ7

`comments` là bảng **dùng chung** (thông báo, phản ánh, ticket, cộng đồng…). Seed
comment vào đó ở `demo`/`ux` (vài trăm nghìn dòng) là ổn. **Nhưng mục tiêu 25 triệu
comment của profile `full` PHẢI có bảng chuyên dụng `community_comments`** (đánh chỉ
mục + cân nhắc partition theo tenant/tháng) — thuộc **Giai đoạn 7 đang thiết kế, CHƯA
tồn tại**. Không tự tạo bảng đó ở task này để tránh xung đột. Khi GĐ7 sẵn sàng: trỏ
`CommunityMassSeeder::insertComments()` sang bảng mới, giữ nguyên phần bài/cảm xúc.

Vì lý do đó **`load`/`full` chỉ chạy trên staging**, không chạy trên DB dev/laptop.

## Profile & ước lượng RAM / disk

| Profile | Posts | Comment/bài | ~Tổng comment | ~Reaction | Nơi chạy |
|---|---:|---:|---:|---:|---|
| demo | 2.000 | 3–12 | ~15.000 | ≤ pool×bài | **dev** |
| ux | 50.000 | 10–30 | ~1.000.000 | ~vài trăm nghìn | dev (khá nặng) |
| load | 1.000.000 | 0–30 (zipf) | ~6–10 triệu | ~vài triệu | **staging** |
| full | 1.000.000 | 20–30 | ~25 triệu | ~vài triệu | **staging** |

- **RAM tiến trình PHP:** ổn định ~O(batch_size) nhờ batch insert theo lô + `unset()`
  mỗi vòng. Đặt `php -d memory_limit=1024M` là dư cho mọi profile (không giữ toàn bộ
  tập trong RAM).
- **Disk (InnoDB, ước lượng thô, gồm index):** demo ~10–20 MB · ux ~0.4–0.6 GB ·
  load ~4–7 GB · full ~12–20 GB (chủ yếu bảng `comments` + index). Dự trù thêm cho
  `community_post_reactions`/`_reports`.
- **Reaction bị chặn trần bởi số cư dân có tài khoản** (`residents.user_id`) do ràng
  buộc `unique(post,user)`. Pool dev nhỏ ⇒ reaction/bài thấp là ĐÚNG; `like_count`
  luôn khớp số hàng thật.

## Nguyên tắc kỹ thuật

- **Deterministic**: `mt_srand(config('community_seed.seed'))` — cùng seed, cùng số liệu.
- **Không event/observer/notification**: toàn bộ bọc `Model::withoutEvents()` + dùng
  `DB::table()` batch insert (không `save()` từng dòng).
- **Idempotent + resume**: `seed_tag='mass'` đánh dấu bài seed; checkpoint offset lưu
  cache. `--reset` xoá theo `seed_tag` nên KHÔNG đụng dữ liệu demo/thật.
- **Lấy id sau batch insert**: dùng `max(id) trước → SELECT id > max sau` (KHÔNG dùng
  `lastInsertId` vì MySQL trả id đầu còn SQLite trả id cuối) → đúng trên cả 2 driver.
- **Bình luận 2 cấp**: pass 1 chèn root, pass 2 chèn reply trỏ `parent_id` về root cùng bài.

## Index bổ sung (chỉ thêm cái THIẾU)

Đối chiếu index đã có (`cp_type_published_idx`, `cp_group_state_idx`,
`comments(commentable_type,commentable_id,id)`), migration thêm:

| Index | Cột | Phục vụ |
|---|---|---|
| `cp_feed_cursor_idx` | `(project_id, status, is_pinned, created_at, id)` | Feed dự án keyset (đúng ORDER BY của `CommunityController@posts`) |
| `cp_group_cursor_idx` | `(community_group_id, status, is_pinned, created_at, id)` | Feed theo nhóm |
| `cp_report_scan_idx` | `(project_id, status, report_count)` | Hàng đợi kiểm duyệt |
| `cp_seed_tag_idx` | `(tenant_id, project_id, seed_tag)` | Reset nhanh |
| `comments_reply_page_idx` | `(commentable_type, commentable_id, parent_id, id)` | Phân trang reply |

Tất cả **guarded** (`hasColumn`/`hasIndex`) + có `down()`.

## Rollback

```bash
# Gỡ toàn bộ dữ liệu seed cho tenant/project (giữ demo/thật)
php artisan community:seed-scale --profile=demo --tenant=1 --project=1 --reset
# (bước reset chạy trước khi seed lại; muốn chỉ xoá thì --dry-run sau reset không có,
#  dùng tinker: app(CommunityMassSeeder::class)->reset(1,1); )

# Gỡ cột + index (rollback migration)
php artisan migrate:rollback --step=1
```

## Kết quả verify (dev, MySQL, profile demo)

```
posts=2.000 · comments=14.872 · reactions=3.259 · reports=75 · ~1.3s
counter drift: like_count=0, comment_count=0
status: published 1.918 · hidden 57 · pending 25 · soft-deleted 15 · group-scope 415
reset lại: xoá đúng 2.000 bài seed, giữ nguyên 88 bài demo/thật
tests: CommunityMassSeedTest 4 passed / 191 assertions
```
