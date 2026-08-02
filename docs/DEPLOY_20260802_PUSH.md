# DEPLOY 2026-08-02 — Push notification + Cộng đồng GĐ7

Deploy lên **x2.fino.vn** (CloudPanel). Từ commit đang chạy (`4818742`) → `origin/main` (`506ef6c`).
Gồm: hạ tầng FCM push, kênh thông báo, push cộng đồng (bình luận/trả lời/mention/cảm xúc),
BQL phát hành thông báo → đẩy push cư dân, share count, D10 billing, feedback đính kèm.

> Chạy tuần tự. `<APP_ROOT>` = thư mục site trên CloudPanel (vd `/home/<user>/htdocs/x2.fino.vn`).

## 0. Backup nhanh (an toàn)
```bash
cd <APP_ROOT>
php artisan down --render="errors::503"        # (tuỳ chọn) bật bảo trì
mysqldump -u <db_user> -p <db_name> > ~/backup_x2_$(date +%F_%H%M).sql
```

## 1. Kéo code
```bash
cd <APP_ROOT>
git pull origin main
```

## 2. ⚠️ BẮT BUỘC — cài dependency mới (kreait/firebase-php)
Push dùng `kreait/firebase-php ^8.3` — MỚI. Không chạy bước này thì gọi push sẽ fatal "class not found".
```bash
composer install --no-dev --optimize-autoloader
```

## 3. Migrate (7 migration mới)
```bash
php artisan migrate --force
```
Gồm: `community_comments`, `community_comment_reactions`, `add_mentions_to_community_comments`,
`device_tokens`, `notification_preferences`, `add_share_count_to_community_posts`,
`add_claimed_line_items_to_payments`. (Idempotent — cái nào đã chạy sẽ bỏ qua.)

## 4. ⚠️ BẮT BUỘC — cấu hình Firebase (để push gửi được)
1. **Upload service account JSON** (KHÔNG có trong git) lên đúng đường dẫn:
   ```
   <APP_ROOT>/storage/app/firebase/service-account.json
   ```
   (File tải từ Firebase Console → Project settings → Service accounts → Generate new private key. Project `x2bms-a37d2`.)
2. **Sửa `.env`** (thêm/đảm bảo):
   ```env
   FIREBASE_PROJECT_ID=x2bms-a37d2
   FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
   FCM_ENABLED=true
   ```
3. Quyền đọc file: `chmod 640 storage/app/firebase/service-account.json` (user web đọc được).

## 5. Nạp lại cache cấu hình
```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
```

## 6. Đồng bộ số đếm cộng đồng (bài cũ đang hiện sai)
```bash
php artisan community:resync-counts
```

## 7. (Nếu chưa có) tài khoản test cộng đồng
```bash
php artisan db:seed --class=CommunityTestResidentsSeeder
# test.cudan1@x2bms.vn / test.cudan2@x2bms.vn — mật khẩu Test@2026!
```

## 8. Khởi động lại worker (nếu dùng Horizon/queue/Octane)
```bash
php artisan queue:restart
# nếu chạy Horizon:  supervisorctl restart horizon   (hoặc tên chương trình của bạn)
# nếu chạy Octane:   php artisan octane:reload
php artisan up            # tắt bảo trì nếu đã bật ở bước 0
```

## 9. Kiểm tra sau deploy
```bash
# a) Gửi push thử tới 1 tài khoản có thiết bị đã đăng nhập app:
php artisan push:test --email=test.cudan1@x2bms.vn --title="X2BMS" --body="Kiểm tra push sau deploy"
# Kỳ vọng: "Thành công: N" (N = số thiết bị đã đăng nhập tài khoản đó).
# Nếu "0 token": tài khoản đó chưa đăng nhập app trên máy nào (chưa có device_token).

# b) BQL phát hành 1 thông báo trong panel /admin → Trung tâm thông báo:
#    chọn phạm vi (căn hộ/toà/dự án) + kênh 'push' → cư dân trong phạm vi nhận push.
```

## Điều kiện để CƯ DÂN nhận được push
- App cư dân trên máy đã **đăng nhập** (đăng ký `device_token`) + **cho phép quyền thông báo**.
- Cư dân **không tắt** kênh tương ứng trong "Cài đặt thông báo" (kênh khẩn cấp luôn nhận).
- BQL khi soạn thông báo **có chọn kênh 'push'** (không chọn thì chỉ hiện trong app).

## Web admin (push trên web) — KHÁC repo này
Repo backend này lo push **app cư dân** (FCM di động). Push **web admin** cần khoá VAPID public
đã tạo sẵn — điền vào env của **repo web admin**:
```
FIREBASE_VAPID_KEY=BAHRFMbB0mGeE5RFPS_uDPDBQJ5tkuf4OrNDZ9a_a5qts-KJxZ_xh4ByP-JFN4RH5KnMDTbeEAgwvuZ9sFE4eE0
```

## Rollback (nếu cần)
```bash
cd <APP_ROOT>
git reset --hard 4818742
composer install --no-dev --optimize-autoloader
php artisan migrate:rollback --step=7   # gỡ 7 migration mới
php artisan config:cache && php artisan up
```
