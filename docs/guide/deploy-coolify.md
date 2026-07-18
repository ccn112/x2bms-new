# Deploy X2-BMS lên server bằng Coolify

Áp dụng cho backend Laravel `x2/x2web` (Laravel 13 + PHP 8.4 + Filament 5 + Horizon + Octane).

## 0. File đã thêm sẵn trong repo (commit rồi deploy)
- `Dockerfile` — FrankenPHP + Octane, build Vite, cài extension PHP.
- `docker/entrypoint.sh` — tạo thư mục storage, `storage:link`, `migrate --force`, cache config/route/view/event (chỉ container web).
- `docker-compose.yml` — 3 service dùng chung image: **web** (Octane), **worker** (Horizon), **scheduler** (`schedule:work`).
- `.dockerignore`.

> MySQL & Redis KHÔNG nằm trong compose — dùng resource Database của Coolify (có backup + UI).

## 1. Chuẩn bị
- Server đã cài **Coolify** (có domain trỏ về, Traefik lo SSL).
- Repo `x2web` đẩy lên Git (GitHub/GitLab) mà Coolify truy cập được.
- Sinh `APP_KEY` (chạy local): `php artisan key:generate --show` → copy giá trị `base64:...`.

## 2. Tạo Project + Database trong Coolify
1. **Projects → + New** → tạo project `x2bms`, environment `production`.
2. **+ New Resource → Database → MySQL** (hoặc MariaDB) 8.x. Đặt tên `x2-mysql`. Ghi lại: **Internal host/URL**, database, username, password.
3. **+ New Resource → Database → Redis**. Đặt tên `x2-redis`. Ghi lại internal host + password.
   - Bật **Backups** cho MySQL (lịch hằng ngày).

## 3. Tạo Application (Docker Compose)
1. **+ New Resource → Application → Public/Private Repository** → chọn repo `x2web`, branch `main`.
2. **Build Pack = Docker Compose**. Compose file: `docker-compose.yml`.
3. Coolify nhận 3 service. Với service **web**: gán **Domain** (vd `https://bms.tenmien.vn`), **Port 8000**, **Health check path `/up`**.
4. Persistent storage: compose đã khai báo volume `app-storage` (giữ KYC private + logs qua mỗi lần deploy) — không cần thêm.

## 4. Biến môi trường (Coolify → Environment Variables)
Dán khối sau, thay giá trị theo resource của bạn (dùng **Internal host** của MySQL/Redis Coolify):
```dotenv
APP_NAME="X2-BMS"
APP_ENV=production
APP_KEY=base64:....(dán từ bước 1)
APP_DEBUG=false
APP_URL=https://bms.tenmien.vn

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=<internal-host-cua-x2-mysql>
DB_PORT=3306
DB_DATABASE=<db>
DB_USERNAME=<user>
DB_PASSWORD=<pass>

# Redis (production: cache/queue/session dùng Redis — xem scale-ops.md)
REDIS_CLIENT=phpredis
REDIS_HOST=<internal-host-cua-x2-redis>
REDIS_PASSWORD=<pass>
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

FILESYSTEM_DISK=local

# X2AI chat (code đọc CHAT_PROVIDER + ANTHROPIC_*). Bỏ trống key → không dùng được chat thật.
CHAT_PROVIDER=anthropic
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-haiku-4-5

# Mail (điền SMTP thật để gửi mail/OTP email)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=no-reply@tenmien.vn
MAIL_FROM_NAME="X2-BMS"

# (Tuỳ chọn) đọc/ghi replica khi có: DB_READ_HOST=replica1,replica2
```
> Lưu ý: `.env.example` cũ có `X2AI_API_KEY/X2AI_MODEL` (legacy) — code chat hiện dùng `ANTHROPIC_API_KEY` + `CHAT_PROVIDER`. Dùng đúng biến ở trên.

## 5. Deploy lần đầu
1. Bấm **Deploy**. Coolify build image + chạy 3 service.
2. Container **web** khi khởi động tự: `storage:link` → `migrate --force` → cache config/route/view/event (nhờ `WEB_ROLE=true`).
3. Xem **Logs** service web để chắc migrate chạy xong, `/up` trả 200.

### Tạo dữ liệu ban đầu
Mở **Terminal** của service web trong Coolify:
- Tạo tài khoản/dữ liệu nền của bạn. **KHÔNG** chạy `DemoDataSeeder` trên production (đó là dữ liệu demo). Nếu chỉ để thử: `php artisan db:seed` (sẽ nạp demo). Production nên có seeder riêng cho role/superadmin.
- Tạo super admin thật: dùng tinker `php artisan tinker` → tạo `User` với `is_platform_admin=true` + gán role `super_admin`.

## 6. Kiểm tra sau deploy
- Web: mở domain → trang đăng nhập `/admin`.
- Horizon: `/horizon` (chỉ platform admin xem được — gate đã cấu hình). Kiểm tra worker `web/default/bulk` đang chạy (service **worker**).
- Scheduler: service **scheduler** log chạy `schedule:work`.
- Mobile API: `GET https://bms.tenmien.vn/api/v1/public/bootstrap` → 200 envelope.
- App Flutter production: build với `--dart-define=X2_API_BASE_URL=https://bms.tenmien.vn/api/v1 --dart-define=X2_USE_MOCK=false`.

## 7. Lưu ý quan trọng
- **KYC private**: file lưu ở `storage/app/private` trên volume `app-storage` → giữ qua deploy. Chạy `php artisan kyc:migrate-private` nếu di trú dữ liệu cũ.
- **Octane + Filament/Livewire**: chạy tốt trong đa số trường hợp; nếu gặp lỗi state rò rỉ giữa request, tạm đổi command service `web` sang chế độ cổ điển (php-fpm/FrankenPHP non-worker) rồi bật lại Octane sau. Octane giúp throughput cao (xem ARCHITECTURE §13b).
- **Migrate & nhiều instance**: entrypoint migrate chỉ ở service `web`. Nếu scale web >1 replica, tách migrate ra **post-deploy command** của Coolify thay vì entrypoint (tránh race).
- **Assets**: `npm run build` chạy trong Docker; `public/build` cũng đã commit làm dự phòng.
- **Bảo mật**: `APP_DEBUG=false`, không commit `.env`, secret chỉ đặt trong Coolify. Font đã self-host (không phụ thuộc CDN ngoài).
- **Scale tiếp** (khi tải lớn): tăng replica service `web`, thêm read replica (`DB_READ_HOST`), tăng worker Horizon — xem `scale-ops.md`.

## 8. Cập nhật về sau
Push code lên `main` → Coolify auto-deploy (nếu bật) hoặc bấm Deploy. Entrypoint tự migrate + re-cache. Rollback bằng lịch sử deploy của Coolify.
