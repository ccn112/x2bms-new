# X2-BMS — Hướng dẫn chuẩn bị Server, Domain & CI/CD

> Cho môi trường test/staging trên `xbuilding.vn`. Cập nhật 2026-07-18.
> Stack: Laravel v13 · PHP 8.4 · MySQL 8 · Filament 5 · Vite (Tailwind). Không chứa secret — điền giá trị thật trên server.

---

## 0. Kiến trúc triển khai (chọn mô hình)
**Khuyến nghị: 1 app Laravel phục vụ TẤT CẢ subdomain** (đơn giản, 1 codebase, 1 DB). Các panel Filament phân biệt theo **domain** thay vì path:

| Subdomain | Panel | Ghi chú |
|---|---|---|
| `sa.xbuilding.vn` | SuperAdmin | `SaPanelProvider` |
| `hq.xbuilding.vn` | Tenant/HQ | `HqPanelProvider` |
| `bql.xbuilding.vn` | BQL (panel `admin`) | `AdminPanelProvider` |
| `web.xbuilding.vn` | Web cư dân | *(chưa có — làm sau)* |
| `api.xbuilding.vn` | API mobile | *(chưa có — Phase 0)* |

Tất cả trỏ về **cùng document root** `/var/www/x2bms/public`. (Phương án khác: mỗi subdomain 1 app riêng — chỉ dùng khi cần tách scale, phức tạp hơn.)

---

## 1. Chuẩn bị máy chủ (Ubuntu 22.04/24.04)
```bash
sudo apt update && sudo apt upgrade -y
# PHP 8.4 + extensions cho Laravel/Filament
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath php8.4-redis
# Nginx, MySQL, Redis (tùy chọn), Supervisor, unzip, git
sudo apt install -y nginx mysql-server redis-server supervisor unzip git
# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
# Node 20 LTS (build asset)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash - && sudo apt install -y nodejs
```
Bảo mật cơ bản:
```bash
sudo mysql_secure_installation
sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable
```
Tạo DB + user:
```sql
CREATE DATABASE x2bms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'x2bms'@'localhost' IDENTIFIED BY '<mật-khẩu-mạnh>';
GRANT ALL PRIVILEGES ON x2bms.* TO 'x2bms'@'localhost';
FLUSH PRIVILEGES;
```

---

## 2. DNS & domain
Tại nhà cung cấp domain `xbuilding.vn`, tạo bản ghi A (hoặc CNAME) trỏ về IP server:
```
sa.xbuilding.vn    A   <SERVER_IP>
hq.xbuilding.vn    A   <SERVER_IP>
bql.xbuilding.vn   A   <SERVER_IP>
web.xbuilding.vn   A   <SERVER_IP>
api.xbuilding.vn   A   <SERVER_IP>
```
> Tiện hơn: 1 bản ghi wildcard `*.xbuilding.vn A <SERVER_IP>` (khi đó SSL nên dùng wildcard qua DNS-01).

---

## 3. Deploy mã nguồn
```bash
sudo mkdir -p /var/www && cd /var/www
sudo git clone https://github.com/ccn112/x2bms-new.git x2bms
sudo chown -R $USER:www-data x2bms && cd x2bms
git checkout main

composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
# → sửa .env (xem §4), rồi:
php artisan key:generate
php artisan migrate --force            # KHÔNG --seed ở production thật; test có thể --seed
php artisan storage:link

# Cache production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize          # cache Filament components

# Quyền ghi
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

---

## 4. Biến `.env` cho production/test
```
APP_NAME=X2-BMS
APP_ENV=production          # test/staging: 'staging'
APP_DEBUG=false            # BẮT BUỘC false ở public
APP_URL=https://xbuilding.vn
APP_LOCALE=vi

SESSION_DRIVER=database     # hoặc redis
SESSION_DOMAIN=.xbuilding.vn   # chia session giữa subdomain (cân nhắc tách nếu cần cô lập)
SESSION_SECURE_COOKIE=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=x2bms
DB_USERNAME=x2bms
DB_PASSWORD=<...>

QUEUE_CONNECTION=database   # hoặc redis
CACHE_STORE=database        # hoặc redis
FILESYSTEM_DISK=local       # Ảnh CCCD/chân dung để disk PRIVATE (xem §8), KHÔNG public

MAIL_MAILER=smtp
MAIL_HOST=smtp.elasticemail.com
MAIL_PORT=2525
MAIL_USERNAME=<...>
MAIL_PASSWORD=<...>
MAIL_FROM_ADDRESS="no-reply@xbuilding.vn"
MAIL_FROM_NAME="X2-BMS"
MAIL_TEST_TO_ADDRESS=       # ĐỂ TRỐNG ở production (bật chỉ khi test)

# Mobile API (khi có): Sanctum stateful cho web cư dân
SANCTUM_STATEFUL_DOMAINS=web.xbuilding.vn
```

---

## 5. Chuyển Filament panel sang subdomain (thay đổi code cần làm)
Trong `app/Providers/Filament/*PanelProvider.php`, thêm `->domain()` (giữ hoặc bỏ `->path()`):
```php
// SaPanelProvider
->domain('sa.xbuilding.vn')
// HqPanelProvider
->domain('hq.xbuilding.vn')
// AdminPanelProvider  (đây là BQL)
->domain('bql.xbuilding.vn')
```
> Nên đọc env để không hard-code (dev vẫn dùng path). Ví dụ: `->domain(config('x2.panel_domains.sa'))` hoặc bọc trong `if (app()->environment('production'))`. Làm việc này trong 1 nhánh riêng + PR, verify kỹ (đăng nhập từng panel).
> Link đặt lại mật khẩu dùng `config('app.url')` → set `APP_URL` đúng.

---

## 6. Nginx (1 server block cho các subdomain)
`/etc/nginx/sites-available/x2bms`:
```nginx
server {
    listen 80;
    server_name sa.xbuilding.vn hq.xbuilding.vn bql.xbuilding.vn web.xbuilding.vn api.xbuilding.vn;
    root /var/www/x2bms/public;
    index index.php;

    charset utf-8;
    client_max_body_size 25M;   # khớp upload (php.ini cũng cần)

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```
```bash
sudo ln -s /etc/nginx/sites-available/x2bms /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 7. HTTPS (Let's Encrypt)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d sa.xbuilding.vn -d hq.xbuilding.vn -d bql.xbuilding.vn -d web.xbuilding.vn -d api.xbuilding.vn
```
> Dùng wildcard `*.xbuilding.vn`: `sudo certbot certonly --manual --preferred-challenges dns -d '*.xbuilding.vn' -d xbuilding.vn` (thêm TXT record theo hướng dẫn). Certbot tự gia hạn (`systemctl status certbot.timer`).

---

## 8. Queue worker + Scheduler + Bảo mật file
**Queue (Supervisor)** — `/etc/supervisor/conf.d/x2bms-worker.conf`:
```ini
[program:x2bms-worker]
command=php /var/www/x2bms/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/x2bms/storage/logs/worker.log
stopwaitsecs=3600
```
```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start x2bms-worker:*
```
**Scheduler (cron)** — `sudo crontab -e -u www-data`:
```
* * * * * cd /var/www/x2bms && php artisan schedule:run >> /dev/null 2>&1
```
**File nhạy cảm (ảnh CCCD/chân dung):** theo `SECURITY_AND_PRIVACY_AUDIT.md`, KHÔNG để disk public. Dùng disk `local` (private) + route trả file có kiểm tra quyền / signed URL. Cần sửa code trước khi đưa dữ liệu thật lên.

---

## 9. CI/CD với GitHub Actions (deploy khi push `main`)
Thêm secrets trong repo GitHub (**Settings → Secrets and variables → Actions**): `SSH_HOST`, `SSH_USER`, `SSH_KEY` (private key), (tùy chọn) `SSH_PORT`.

`.github/workflows/deploy.yml`:
```yaml
name: Deploy to server
on:
  push:
    branches: [ main ]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy over SSH
        uses: appleboy/ssh-action@v1.2.0
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_KEY }}
          port: ${{ secrets.SSH_PORT || 22 }}
          script: |
            set -e
            cd /var/www/x2bms
            php artisan down || true
            git fetch --all && git reset --hard origin/main
            composer install --no-dev --optimize-autoloader
            npm ci && npm run build
            php artisan migrate --force
            php artisan config:cache && php artisan route:cache && php artisan view:cache
            php artisan filament:optimize
            sudo chown -R www-data:www-data storage bootstrap/cache
            php artisan queue:restart
            php artisan up
```
> Muốn chạy test trước khi deploy: thêm job `tests` (setup PHP + `composer install` + `php artisan test`) và cho `deploy` `needs: tests`.
> Nâng cấp zero-downtime: dùng **Deployer** (`deployer.org`) hoặc **Laravel Envoyer** (release symlink, rollback nhanh).

---

## 10. Checklist trước khi mở cho test
- [ ] DNS 5 subdomain đã trỏ đúng IP, HTTPS xanh.
- [ ] `APP_DEBUG=false`, `APP_ENV` đúng, `.env` quyền 600, `MAIL_TEST_TO_ADDRESS` trống (hoặc chủ ý bật khi test).
- [ ] `migrate --force` chạy sạch; đăng nhập được 3 panel theo subdomain.
- [ ] Gửi thử email reset mật khẩu → nhận được; link `/reset-password` mở đúng domain.
- [ ] Queue worker + scheduler chạy (`supervisorctl status`, cron).
- [ ] Ảnh CCCD/tài liệu KHÔNG truy cập công khai (đã chuyển disk private).
- [ ] Backup DB tự động (mysqldump + cron / dịch vụ managed).
- [ ] CI/CD: push `main` → server tự cập nhật.

---

## 11. Lệnh vận hành nhanh
```bash
php artisan optimize:clear      # xóa toàn bộ cache khi debug
php artisan queue:restart       # nạp lại worker sau deploy
php artisan migrate:status      # xem trạng thái migration
tail -f storage/logs/laravel.log
sudo supervisorctl restart x2bms-worker:*
```

> Liên quan: `docs/operations/README.md` (vận hành app), `docs/SESSION_HANDOFF_20260718.md` (kế hoạch domain), `handoff/mobile_backend_audit_20260718/` (API mobile). Việc chuyển panel sang subdomain (§5) và bảo mật file (§8) cần sửa code — nên làm qua nhánh + PR.
