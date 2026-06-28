# X2-BMS Backend

Nền tảng SaaS multi-tenant quản lý vận hành chung cư/tòa nhà/khu đô thị — **Web Admin + Web Action + API** cho App Cư dân & App BQL.
Backend: **Laravel 13 · Filament 5 · Livewire 4 · PHP 8.4 · MySQL/MariaDB · Tailwind v4**.

> UI đã duyệt = **data contract**. Không hardcode dữ liệu trong view: seeder tạo dữ liệu, query nạp vào view.
> Sau `migrate:fresh --seed`, mỗi màn tái hiện đúng dữ liệu mẫu trong ảnh handoff.
> Tài liệu: [`docs/SESSION_CONTEXT.md`](docs/SESSION_CONTEXT.md) · [`docs/PACKAGE_SELECTION_PLAN.md`](docs/PACKAGE_SELECTION_PLAN.md) · [`docs/CANONICAL_ENTITY_MAP.md`](docs/CANONICAL_ENTITY_MAP.md) · [`docs/ERD_DRAFT.md`](docs/ERD_DRAFT.md) · [`docs/contracts/`](docs/contracts).

---

## 1. Yêu cầu

| Thành phần | Phiên bản | Ghi chú |
|---|---|---|
| PHP | **8.4** | ext: pdo_mysql, mbstring, openssl, fileinfo, gd (media) |
| Composer | **2.9+** | |
| MySQL / MariaDB | 8.0 / 10.6+ (đã test MariaDB 12.3) | |
| Node.js | **22.12+** | chỉ để build assets + Playwright (Node 20 quá cũ cho Vite 8) |
| Redis | tùy chọn (production) | cho queue/Horizon |

> **Windows + Laravel Herd:** dùng `php` 8.4 của Herd; **composer của Herd** `~/.config/herd/bin/composer.bat` (v2.9).
> Khi `composer require`, tránh ký tự `^` trong ràng buộc phiên bản (cmd nuốt caret) — dùng tên trần hoặc `5.*`.

---

## 2. Cài đặt (local / dev)

```bash
# 1. Cài dependencies
composer install
npm install            # dùng Node 22 (nvm use 22)

# 2. Cấu hình môi trường
cp .env.example .env
php artisan key:generate

# 3. Sửa .env phần database (xem mục 3), tạo database
#    CREATE DATABASE x2bms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Tạo schema + dữ liệu mẫu (tenant Sunshine Garden, 120 căn/cư dân, ...)
php artisan migrate:fresh --seed

# 5. Build front-end (Node 22)
npm run build          # hoặc: npm run dev (watch)

# 6. Chạy server
php artisan serve
```

Mở **http://localhost:8000** → chuyển tới `/dashboard`. Đăng nhập tại `/admin/login`.

**Tài khoản admin demo:** `x2bms@x2bms.vn` / `Bms@2026!` (role `super_admin`).

> **Hiệu năng dev:** `php artisan serve` đơn luồng và không cache opcode → trang Filament chậm/nghẽn.
> `.env` đã đặt `PHP_CLI_SERVER_WORKERS=6`. Để nhanh hơn nữa khi chạy test:
> `php -d opcache.enable_cli=1 artisan serve --host=127.0.0.1 --port=8000`.

---

## 3. Cấu hình `.env`

```dotenv
APP_NAME="X2-BMS"
APP_ENV=local                 # production khi deploy
APP_DEBUG=true                # false ở production
APP_URL=http://localhost:8000
APP_LOCALE=vi                 # vi mặc định, hỗ trợ en (nội dung đa ngôn ngữ)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=x2bms
DB_USERNAME=root
DB_PASSWORD=secret

# Dev: database queue/cache/session (không cần Redis)
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

PHP_CLI_SERVER_WORKERS=6      # serve đa luồng cho trang Filament

# Production (xem mục 6): chuyển sang redis
# QUEUE_CONNECTION=redis
# CACHE_STORE=redis
# SESSION_DRIVER=redis
# REDIS_HOST=127.0.0.1
```

Stack đa-giao-diện: **single DB, row-level tenancy** (`tenant_id`/`building_id`). Mọi model nghiệp vụ dùng trait `App\Models\Concerns\BelongsToTenant` (tự scope theo tenant của user đăng nhập; no-op khi chạy console/seeder).

---

## 4. RBAC / phân quyền

- `spatie/laravel-permission` + **Filament Shield** (panel `/admin`).
- `super_admin` được bypass mọi quyền qua `Gate::before` trong `AppServiceProvider` (hoạt động ngay sau fresh seed).
- Sinh permission/policy cho resource:
  ```bash
  php artisan shield:generate --all --panel=admin --option=policies_and_permissions
  ```
- Cư dân (đầu cuối, quy mô lớn) **không** đưa vào bảng RBAC — quyền suy ra từ quan hệ căn hộ + Sanctum abilities.

---

## 5. Kiểm thử (Playwright screenshot)

```bash
npx playwright install chromium     # lần đầu (Node 22)
php artisan migrate:fresh --seed    # reseed trước mỗi lần chạy
npx playwright test                 # 1 spec/batch trong tests/Browser
npx playwright test --update-snapshots   # tạo/cập nhật baseline ảnh
```

- Đăng nhập 1 lần qua `tests/Browser/auth.setup.ts` → lưu `.auth/admin.json` (storageState) tái dùng cho mọi test.
- Baseline ảnh: `tests/Browser/*-snapshots/`.
- Yêu cầu app đang chạy ở `:8000` (config tự khởi động `php artisan serve` nếu chưa có).

---

## 6. Triển khai Production (Linux)

```bash
# 1. Mã nguồn + dependencies (không dev)
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Môi trường
cp .env.example .env        # đặt APP_ENV=production, APP_DEBUG=false, APP_KEY, DB, REDIS...
php artisan key:generate    # nếu chưa có APP_KEY

# 3. Database
php artisan migrate --force
php artisan db:seed --force # chỉ môi trường demo; KHÔNG seed dữ liệu demo ở prod thật

# 4. RBAC
php artisan shield:generate --all --panel=admin --option=policies_and_permissions

# 5. Tối ưu
php artisan config:cache route:cache view:cache event:cache
php artisan storage:link
```

**Web server:** Nginx + PHP-FPM 8.4 (hoặc Laravel Octane). Document root: `public/`.

**Queue & lịch (Linux):**
```bash
composer require laravel/horizon   # CHỈ trên Linux — Horizon cần ext-pcntl (không có trên Windows)
php artisan horizon                 # chạy bằng supervisor/systemd
# Scheduler: thêm cron -> * * * * * php /path/artisan schedule:run
```

**Khuyến nghị production:**
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`.
- Bật OPcache (PHP-FPM); fan-out thông báo 5M cư dân phải qua queue (không gửi đồng bộ).
- Media trên S3-compatible (`spatie/laravel-medialibrary`), conversions qua queue.
- Sanctum: lên lịch `sanctum:prune-expired`; cân nhắc read replica cho auth ở quy mô lớn.
- Multi-tenant: hiện single DB + row-level scope; nếu tách DB theo tenant sau này, đánh giá `spatie/laravel-multitenancy`.

---

## 7. Cấu trúc thư mục

```
app/
  Enums/                      # FeedbackStatus, WorkOrderStatus, VehicleType, ResidentApprovalStatus...
  Filament/Resources/         # CRUD resources (Building, Apartment, Resident, ...)
  Livewire/                   # Custom pages (OperationalDashboard, ResidentDirectory, ApartmentProfile, ...)
  Models/                     # + Concerns/BelongsToTenant
  Providers/                  # AppServiceProvider (super_admin gate), Filament/AdminPanelProvider
resources/
  css/app.css                 # Tailwind v4 + brand tokens (x2-*)
  views/components/x2/        # bộ component dùng chung X2*
  views/components/layouts/   # x2-app layout
  views/livewire/             # blade của custom pages
database/migrations | seeders/DemoDataSeeder.php
tests/Browser/                # Playwright specs + auth.setup.ts
docs/                         # SESSION_CONTEXT, PACKAGE_SELECTION_PLAN, CANONICAL_ENTITY_MAP, ERD_DRAFT, contracts/
```

---

## 8. Lệnh thường dùng

```bash
php artisan migrate:fresh --seed     # reset + seed dữ liệu demo
npm run build                        # build assets (Node 22)
php artisan serve                    # dev server (:8000)
npx playwright test                  # screenshot tests
php artisan optimize:clear           # xóa cache khi đổi config/route/view
```
