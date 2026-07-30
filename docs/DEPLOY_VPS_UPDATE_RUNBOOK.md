# Deploy bản cập nhật lên VPS live (`x2.fino.vn`) — runbook

> Viết 2026-07-30. Đây là quy trình **cập nhật** server đang chạy, không phải dựng mới.
> Dựng mới từ đầu: `DEPLOYMENT_GUIDE.md` (bản đó viết cho nginx thủ công/`xbuilding.vn`).
> Server live đang dùng **CloudPanel** (xem `docs/guide/deploy-cloudpanel-docs-subdomain.md`).
>
> ⚠️ Tôi **không** truy cập được VPS từ máy dev, nên §0 là các lệnh để bạn tự xác nhận
> sự thật trên server. Đừng chạy §2 trước khi §0 cho kết quả đúng.

---

## 0. Xác nhận sự thật trên server (chạy trước, một lần)

```bash
ssh <user>@<ip-vps>            # CloudPanel: thường là site user, không phải root

# a) Thư mục app thật ở đâu? CloudPanel hay đặt ở /home/<siteuser>/htdocs/<domain>
ls -d /home/*/htdocs/*/ 2>/dev/null
# Xác nhận đúng thư mục app Laravel (phải thấy artisan + public/):
cd <APP_DIR> && ls -1 artisan public composer.json

# b) Có phải deploy bằng git không, đang ở nhánh/commit nào?
git remote -v && git branch --show-current && git log --oneline -3
git status --short          # PHẢI trống. Có file lạ = ai đó sửa tay trên server

# c) PHP nào đang chạy (CloudPanel cho mỗi site 1 phiên bản)
php -v && which php
php artisan --version

# d) DB đang trỏ đâu, còn bao nhiêu migration chưa chạy
php artisan migrate:status | tail -20
```

Ghi lại `<APP_DIR>` và `<user>` — phần dưới dùng lại.

---

## 1. Lần này deploy những gì

9 commit backend chưa push (`git log --oneline origin/main..HEAD`), gồm:

| Commit | Nội dung |
|---|---|
| `10fbe58` | **Bịt rò dữ liệu đa dự án** — thiếu `X-Context-Id` thì thu về căn primary thay vì trả mọi căn |
| `6c77bfa` | Chuẩn hoá `events.status`; sự kiện BQL tạo qua web nay lên được app |
| `492827d` | Sự kiện/bình chọn vào bảng tin dạng bài `*_ref` |
| `86d5254` | Endpoint đăng ký · check-in · huỷ đăng ký sự kiện |
| `0aec3a6` | Endpoint bóc metadata link (thẻ xem trước) |
| `aa6b61c` | Phân trang thật bằng cursor cho bình luận |
| `0a1f44d` | Dải thống kê tiện ích + mốc thời gian thật cho phiếu đặt |
| `4774b22` | Quy trình duyệt tin rao BĐS + quyền rao xác minh |
| `0d7235e` | Mốc thời gian demo + pint format |

**4 migration mới sẽ chạy** (tất cả add-only, không xoá cột, không đổi kiểu):

| Migration | Làm gì | Rủi ro |
|---|---|---|
| `2026_07_30_100000_add_listing_approval_workflow` | Thêm cột duyệt vào `real_estate_listings` (bảng đã có trên live từ `create_marketplace_ecosystem`) | Thấp |
| `2026_07_30_100000_normalize_event_status` | Đổi `events.status` `'published'` → `'upcoming'` | **Có ghi dữ liệu.** `down()` để trống có chủ ý — không lần lại được giá trị cũ, nên backup ở §2 là bắt buộc |
| `2026_07_30_100001_ensure_residents_soft_deletes` | Bù cột `deleted_at` còn thiếu | Rất thấp — có `hasColumn` chặn, **no-op hoàn toàn trên MySQL** (nó chỉ sinh ra để bù cho DB test SQLite) |
| `2026_07_30_150000_add_cancelled_at_to_amenity_bookings` | Thêm `cancelled_at` | Thấp |

> Nếu agent đang chạy commit thêm `2026_07_30_160000_add_listing_escalation_fields`
> (đẩy tin rao lên `/sa` duyệt) thì deploy luôn cùng lượt — đừng deploy nửa vời,
> vì màn duyệt sẽ tham chiếu cột chưa có.

---

## 2. Quy trình deploy

```bash
# ── TRÊN MÁY DEV ──
cd D:/Code/x2/x2bms
php artisan test --testsuite=Unit,Feature      # phải xanh trước khi push
git push origin main

# ── TRÊN VPS ──
ssh <user>@<ip-vps>
cd <APP_DIR>

# 1) BACKUP DB TRƯỚC KHI MIGRATE — bắt buộc, vì normalize_event_status ghi dữ liệu
mysqldump -u <db_user> -p <db_name> | gzip > ~/backup-x2bms-$(date +%F-%H%M).sql.gz
ls -lh ~/backup-x2bms-*.sql.gz        # xác nhận file KHÔNG rỗng trước khi đi tiếp

# 2) Bật bảo trì (cư dân đang dùng app sẽ nhận 503 thay vì lỗi nửa vời)
php artisan down --retry=60

# 3) Lấy code
git fetch --all
git reset --hard origin/main           # xoá mọi sửa tay trên server — §0.b đã kiểm tra
composer install --no-dev --optimize-autoloader
npm ci && npm run build                # chỉ cần nếu có đổi asset Filament/Vite

# 4) Migrate
php artisan migrate --force
php artisan migrate:status | tail -8   # 4 dòng mới phải là [Ran]

# 5) Nạp lại cache (BẮT BUỘC — route mới sẽ 404 nếu bỏ bước này)
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan filament:optimize
php artisan storage:link || true       # bỏ qua nếu symlink đã có

# 6) Quyền ghi + queue
sudo chown -R <siteuser>:<siteuser> storage bootstrap/cache   # CloudPanel dùng site user, KHÔNG phải www-data
php artisan queue:restart

# 7) Mở lại
php artisan up
```

---

## 3. Dữ liệu demo (server live đang dùng để test app)

**KHÔNG chạy `php artisan db:seed`.** `DatabaseSeeder` gọi `DemoDataSeeder`, trong đó có
**374 chỗ `::create(`** và gần như không có `firstOrCreate` — chạy lần hai là **nhân đôi
toàn bộ dữ liệu demo**, không tự dọn được.

4 seeder của đợt này thì an toàn, chỉ dùng `firstOrCreate`/`updateOrCreate`, không
`delete`/`truncate`, chạy lại nhiều lần vẫn ra một kết quả — gọi **từng cái theo tên**:

```bash
php artisan db:seed --force --class=Database\\Seeders\\ResidentDemoContentSeeder
php artisan db:seed --force --class=Database\\Seeders\\SecondProjectDemoSeeder
php artisan db:seed --force --class=Database\\Seeders\\CommunityRefPostsSeeder
php artisan db:seed --force --class=Database\\Seeders\\ListingDemoSeeder
```

Thứ tự trên có ý nghĩa: `CommunityRefPostsSeeder` sinh bài `event_ref`/`poll_ref` từ sự
kiện và bình chọn đã tồn tại, nên phải chạy **sau** hai seeder tạo ra chúng.

---

## 4. Kiểm tra sau deploy (đừng tin "chạy xong là được")

Cách phân biệt chắc chắn: route resident nằm sau `auth:sanctum`, nên **route có thật mà
chưa xác thực trả 401**, còn **route không tồn tại trả 404**. Trước deploy tôi đã đo trên
live, kết quả:

```
GET  /resident/community/posts          -> 200   (đã có)
POST /resident/link-preview             -> 404   (chưa có)
GET  /resident/community/listings       -> 404   (chưa có)
POST /resident/community/events/1/register -> 404 (chưa có)
```

Sau deploy, 3 dòng 404 đó **phải đổi khác 404**. Chạy lại từ máy dev:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\probe_live_endpoints.ps1
```

Kèm kiểm tay:
- Đăng nhập `/admin` (BQL) và `/sa` — vào được, không lỗi 500.
- Mở app trên điện thoại: tab Cộng đồng thấy bài, bấm **Đăng ký** một sự kiện → thành công.
- `tail -f storage/logs/laravel.log` trong lúc bấm — không có exception mới.

---

## 5. Nếu vỡ thì lùi thế nào

```bash
cd <APP_DIR>
php artisan down
git reset --hard <commit-cũ>            # lấy từ §0.b đã ghi lại
composer install --no-dev --optimize-autoloader
php artisan optimize:clear && php artisan config:cache && php artisan route:cache
php artisan up
```

Lùi code là đủ cho hầu hết sự cố. **Nếu phải lùi cả DB** thì nạp lại từ file dump ở §2 —
đừng `migrate:rollback`, vì `normalize_event_status` có `down()` trống nên rollback sẽ
để DB ở trạng thái lệch giữa code cũ và dữ liệu đã đổi:

```bash
gunzip < ~/backup-x2bms-<mốc>.sql.gz | mysql -u <db_user> -p <db_name>
```

---

## 6. Nên làm sau (chưa có)

- **Chưa có CI/CD**: hiện phải SSH tay mỗi lần. Mẫu GitHub Actions có sẵn ở
  `DEPLOYMENT_GUIDE.md` §9 — thêm secret `SSH_HOST`/`SSH_USER`/`SSH_KEY` là chạy được,
  nên cho `needs: tests` để test đỏ thì không deploy.
- **Chưa có backup DB tự động** — hiện chỉ backup thủ công lúc deploy. Đặt cron
  `mysqldump` hằng ngày + giữ 7–14 bản.
- **Chưa zero-downtime**: `git reset --hard` tại chỗ nên có vài giây app ở trạng thái nửa
  vời (vì vậy mới cần `artisan down`). Muốn bỏ cửa sổ này thì chuyển sang deploy theo
  release + symlink (Deployer/Envoyer).
