# X2-BMS — Hướng dẫn Deploy trên CloudPanel

> Cập nhật 2026-07-20. Stack: **Laravel · PHP 8.4 · MySQL 8 · Redis · Filament 5 · Vite (Tailwind v4)**.
> Môi trường: **CloudPanel** (Nginx + PHP-FPM + MySQL, quản lý qua UI). Không dùng Docker/Octane.
>
> **Nếu "Vite và API chạy không đúng" → nhảy tới [§6 Xử lý sự cố](#6-xử-lý-sự-cố-vite--api).** Đa số lỗi nằm ở **document root sai `/public`, chưa build asset, hoặc APP_URL/HTTPS**, không phải code.

---

## 0. Bản đồ URL của ứng dụng (để test đúng)

1 codebase Laravel phục vụ nhiều panel, phân biệt theo **PATH**:

| Đường dẫn | Ý nghĩa |
|---|---|
| `/sa` | Panel SuperAdmin |
| `/hq` | Panel Công ty vận hành / Tenant |
| `/admin` | Panel Ban Quản lý dự án — **BQL** |
| `/fila` | Panel stock CRUD |
| `/api/v1/...` | **API mobile** (Bearer token — Sanctum) |
| `/up` | Health check |

> ⚠️ API không có route ở `/api` trần. Đúng phải là **`/api/v1/auth/login`**, `/api/v1/public/bootstrap`, v.v.

---

## 1. Chuẩn bị trên CloudPanel

### 1.1 Cài phần mềm cần thiết (SSH vào server, quyền root)
CloudPanel đã có Nginx + PHP + MySQL. Cần bổ sung **Node.js** (build Vite), **Composer**, và bật các **PHP extension**:

```bash
# Node 20 LTS — để chạy npm run build
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash - && sudo apt install -y nodejs

# Composer (nếu chưa có)
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer

# PHP 8.4 extensions cho Laravel/Filament (CloudPanel thường có sẵn phần lớn)
sudo apt install -y php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath php8.4-redis
```
> Redis: cài server nếu muốn dùng cache/queue Redis: `sudo apt install -y redis-server`. Nếu chưa có, tạm để cache/queue/session = `database` (xem §4).

### 1.2 Tạo Site trong CloudPanel (UI)
1. **Sites → Add Site → Create a PHP Site**.
2. Chọn:
   - **Application**: `Generic` (hoặc `Laravel` nếu có sẵn template).
   - **PHP Version**: **8.4**.
   - **Domain Name**: domain thật của bạn (vd `app.xbuilding.vn`).
   - **Site User** / **Password**: đặt (đây là user Linux sở hữu site).
3. CloudPanel tạo thư mục site tại: `/home/<site-user>/htdocs/<domain>/`.

### 1.3 Tạo Database (UI)
**Databases → Add Database** → ghi lại **Database Name / User / Password**. Host = `127.0.0.1`, Port `3306`.

---

## 2. Đưa mã nguồn lên & cấu hình document root

### 2.1 Clone code vào thư mục site
CloudPanel mặc định tạo sẵn thư mục web. Ta thay bằng repo Laravel. **Đăng nhập bằng đúng site-user** (không phải root, để quyền file đúng):

```bash
# SSH bằng site-user (hoặc: su - <site-user>)
cd /home/<site-user>/htdocs
rm -rf <domain>                       # xóa thư mục mẫu CloudPanel tạo
git clone https://github.com/ccn112/x2bms-new.git <domain>
cd <domain>
git checkout main
```

### 2.2 Cài dependencies & build asset
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build               # BẮT BUỘC: sinh public/build/manifest.json cho Vite
```

### 2.3 ⚠️ Trỏ document root vào `/public` (lỗi Vite/giao diện hay gặp nhất)
Trong CloudPanel: **Sites → (site của bạn) → Vhost** (hoặc **Site Settings → Root Directory**), sửa document root/`root` thành thư mục con **`public`**:

```
root /home/<site-user>/htdocs/<domain>/public;
```
> Nếu để root ở thư mục gốc dự án thay vì `/public` → CSS/JS Vite 404, lộ file `.env`, panel vỡ giao diện. Đây là nguyên nhân #1.

---

## 3. Khởi tạo Laravel

```bash
cd /home/<site-user>/htdocs/<domain>
cp .env.example .env
php artisan key:generate               # sinh APP_KEY
# → sửa .env theo §4, rồi:
php artisan migrate --force            # KHÔNG --seed ở production thật
php artisan storage:link

# Cache production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# Quyền ghi (site-user chạy PHP-FPM, thường cùng user)
chmod -R 775 storage bootstrap/cache
```
> Tạo admin đầu tiên: `php artisan db:seed --force` (nếu có seeder) hoặc `php artisan tinker`.

---

## 4. Biến `.env` cho production

```dotenv
APP_NAME="X2-BMS"
APP_ENV=production
APP_DEBUG=false                       # BẮT BUỘC false khi public
APP_URL=https://<domain>              # PHẢI https://, KHÔNG dấu / cuối
APP_LOCALE=vi

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<db>
DB_USERNAME=<user>
DB_PASSWORD=<pass>

# Nếu ĐÃ cài Redis:
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
# Nếu CHƯA có Redis, dùng tạm:
# CACHE_STORE=database
# QUEUE_CONNECTION=database
# SESSION_DRIVER=database

SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp
MAIL_HOST=smtp.elasticemail.com
MAIL_PORT=2525
MAIL_USERNAME=<...>
MAIL_PASSWORD=<...>
MAIL_FROM_ADDRESS="no-reply@<domain>"
MAIL_FROM_NAME="X2-BMS"

FILESYSTEM_DISK=local                 # ảnh CCCD/chân dung để private
SANCTUM_STATEFUL_DOMAINS=<domain>     # nếu web cư dân dùng cookie

X2AI_API_KEY=
X2AI_MODEL=claude-haiku-4-5
```

---

## 5. SSL, Queue, Scheduler, Upload

### 5.1 SSL (UI)
**Sites → (site) → SSL/TLS → Let's Encrypt** → Issue. Sau khi có HTTPS, đảm bảo `APP_URL=https://...` và chạy lại `php artisan config:cache`.

### 5.2 Queue worker + Scheduler (UI CloudPanel)
CloudPanel không dùng Supervisor UI, nhưng có **Cron Jobs** cho từng site. **Sites → (site) → Cron Jobs → Add**:

- **Scheduler** (mỗi phút):
  ```
  * * * * * cd /home/<site-user>/htdocs/<domain> && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Queue worker** (giữ chạy liên tục — dùng cron canh mỗi phút, `--stop-when-empty` hoặc dùng systemd/Supervisor riêng):
  ```
  * * * * * cd /home/<site-user>/htdocs/<domain> && php artisan queue:work --stop-when-empty >> storage/logs/worker.log 2>&1
  ```
  > Ổn định hơn: tạo **systemd service** cho `queue:work` (chạy nền, tự restart) hoặc cài Supervisor. Nói tôi biết nếu muốn file service mẫu.

### 5.3 Giới hạn upload (php.ini)
Panel Filament upload ảnh → tăng trong PHP settings của site (CloudPanel: **Site Settings → PHP → Advanced**):
```
upload_max_filesize = 25M
post_max_size = 25M
```
Nginx: CloudPanel mặc định `client_max_body_size` khá lớn; nếu bị `413`, thêm `client_max_body_size 25M;` trong Vhost.

---

## 6. XỬ LÝ SỰ CỐ: VITE & API

### 6.1 Vite: giao diện vỡ / CSS-JS không load

1. **Document root chưa trỏ vào `/public`** (nguyên nhân #1 trên CloudPanel).
   → Sửa Vhost root thành `.../htdocs/<domain>/public` (xem §2.3), reload Nginx qua UI.
   Kiểm tra: mở `https://<domain>/build/manifest.json` — phải trả JSON, không phải 404.

2. **Chưa `npm run build` / thiếu `public/build`.**
   Lỗi Blade *"Unable to locate file in Vite manifest"*.
   ```bash
   ls -la public/build/manifest.json     # phải tồn tại
   npm ci && npm run build               # nếu thiếu
   php artisan view:clear && php artisan view:cache
   ```
   > Tailwind v4 `@import` theme Filament cần `vendor/` — nên chạy `composer install` **trước** `npm run build`.

3. **Mixed content (asset ra `http://` khi trang là `https://`).**
   Console báo *"Mixed Content / blocked"*.
   - Đặt `APP_URL=https://<domain>` rồi `php artisan config:cache`.
   - CloudPanel terminate SSL ở Nginx rồi proxy sang PHP-FPM. Nếu Laravel vẫn tưởng là HTTP, thêm tin cậy proxy trong `bootstrap/app.php` (cần sửa code, làm qua nhánh):
     ```php
     ->withMiddleware(function (Middleware $middleware): void {
         $middleware->trustProxies(at: '*');   // đọc X-Forwarded-Proto từ Nginx
         // ... giữ nguyên phần alias/redirectGuestsTo ...
     })
     ```

4. **Đừng chạy `npm run dev` trên production** — chỉ `npm run build`. Dev server Vite (5173/HMR) chỉ dùng ở máy local.

> Chẩn đoán nhanh: DevTools → Network → reload → xem `/build/assets/*.css`. **404** = root sai hoặc chưa build (mục 1–2). **Bị chặn / http://** = mixed content (mục 3).

### 6.2 API: gọi không được

1. **404** → sai prefix. Đúng là **`/api/v1`**:
   ```
   POST https://<domain>/api/v1/auth/login
   POST https://<domain>/api/v1/auth/otp/request
   GET  https://<domain>/api/v1/public/bootstrap
   GET  https://<domain>/api/v1/me/bootstrap        (cần Bearer token)
   ```
2. **404 mọi route Laravel** → thường do document root chưa trỏ `/public` (xem 6.1 mục 1) hoặc thiếu rule `try_files ... /index.php`. CloudPanel PHP-site có sẵn rule này; nếu tự sửa Vhost đừng xóa nó.
3. **401 `AUTH_UNAUTHENTICATED`** → thiếu/hết hạn header `Authorization: Bearer <token>`.
4. **429 `RATE_LIMITED`** → dính throttle (`auth-login`, `otp`, `api`). Đợi hoặc chỉnh limiter khi test tải.
5. **500 nhưng message ẩn** (do `APP_DEBUG=false`) → xem log thật:
   ```bash
   tail -f storage/logs/laravel.log
   ```
6. **CORS** (nếu gọi từ browser khác origin) → kiểm tra `config/cors.php`. App Flutter native gọi trực tiếp không dính CORS.

### 6.3 Lệnh chẩn đoán
```bash
php artisan about                    # xem env, driver, URL thực tế
php artisan route:list --path=api    # liệt kê route API
curl -fsS https://<domain>/up        # healthcheck → phải 200
php artisan optimize:clear           # xóa toàn bộ cache khi nghi cache cũ
```
> ⚠️ **Sau khi sửa `.env` hoặc code, phải `php artisan config:cache` (hoặc `optimize:clear`) lại** — cache cũ là nguyên nhân "sửa rồi mà không đổi".

---

## 7. Quy trình cập nhật (mỗi lần deploy code mới)
```bash
cd /home/<site-user>/htdocs/<domain>
php artisan down || true
git fetch --all && git reset --hard origin/main
composer install --no-dev --optimize-autoloader
npm ci && npm run build              # BẮT BUỘC build lại Vite
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan filament:optimize
php artisan queue:restart            # nạp lại worker
php artisan up
```

---

## 8. Checklist trước khi mở test
- [ ] Document root Vhost = `.../htdocs/<domain>/**public**`.
- [ ] `https://<domain>/up` → **200**; SSL xanh.
- [ ] `/build/manifest.json` trả JSON (đã `npm run build`).
- [ ] Mở `/admin`, `/hq`, `/sa` → CSS đầy đủ, đăng nhập được.
- [ ] `POST /api/v1/auth/login` (hoặc `otp/request`) trả JSON envelope chuẩn.
- [ ] `APP_KEY` đã set, `APP_DEBUG=false`, `APP_URL=https://...`.
- [ ] Scheduler cron + queue worker chạy; migrate sạch.
- [ ] Email reset mật khẩu gửi được; link mở đúng domain.
- [ ] Backup DB tự động (CloudPanel có Backups, hoặc mysqldump + cron).

---

### Tài liệu liên quan
- `docs/DEPLOYMENT_GUIDE.md` — bản Nginx thủ công (tương tự, không dùng UI CloudPanel).
- `docs/SESSION_HANDOFF_20260718.md` — kế hoạch tách panel theo subdomain.
- `Dockerfile`, `docker-compose.yml` — hạ tầng Docker/Coolify (mô hình cũ, không dùng nữa).
