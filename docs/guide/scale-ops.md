# Hướng dẫn vận hành & scale backend X2

Áp dụng khi lên tải lớn (hàng trăm nghìn → triệu cư dân). Tham chiếu kiến trúc: `x2/docs/ARCHITECTURE_X2_PLATFORM_V1.md` (§10 capacity, §13b checklist).

> **Nguyên tắc local vs production:** Local/dev dùng driver `database` cho cache/queue/session (chưa cần Redis). Production bật Redis + Horizon + Octane + read replica. Việc chuyển đổi **chỉ bằng env** — xem `x2/x2web/.env.production.example`.

## 1. Redis (cache / queue / session)
Local hiện **chưa cài Redis** → giữ `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`.
Production: đặt các biến trong `.env.production.example` (redis host/password) → `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`. Cấu hình Redis đã có sẵn trong `config/database.php`.

## 2. Horizon (quản lý queue)
- Chạy worker: `php artisan horizon` (chỉ Linux — cần `ext-pcntl`, `ext-posix`; **không chạy trên Windows dev**).
- Dashboard: `/horizon` (được bảo vệ bởi Gate `viewHorizon` — xem `app/Providers/HorizonServiceProvider.php`).
- Hàng đợi ưu tiên: `emergency` > `default` > `bulk` (push/hóa đơn khối lượng lớn vào `bulk`).
- Cấu hình pool/worker: `config/horizon.php`.
- Dev trên Windows: cài package với `--ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`; queue vẫn test được bằng `QUEUE_CONNECTION=database` + `php artisan queue:work`.

## 3. Octane (throughput API)
- Server: **FrankenPHP** (`OCTANE_SERVER=frankenphp`). Cấu hình: `config/octane.php`.
- Production chạy: `php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000` (thường sau reverse proxy).
- **Local Windows:** Octane/FrankenPHP/Swoole không chạy tốt trên Windows → dev vẫn dùng `php artisan serve`. Octane chỉ bật ở môi trường Linux/Docker.
- Lưu ý code với Octane: tránh state rò rỉ giữa request (singleton giữ dữ liệu request-scoped). Kiểm tra kỹ service dùng `request()`/static.

## 4. Read/Write DB split
- `config/database.php` (mysql) đã có `read`/`write` + `sticky=true`.
- Chưa có replica: bỏ trống `DB_READ_HOST` → đọc & ghi cùng primary (an toàn).
- Có replica: `DB_READ_HOST=replica1,replica2` (nhiều host phân tách bằng dấu phẩy) để dồn tải đọc.

## 5. Việc còn lại (chưa xử lý trong đợt này)
- **Index + eager-load** các truy vấn nóng; cache `me/bootstrap`.
- **Sửa `BelongsToTenant` no-op trong queue** (rủi ro rò dữ liệu tenant ở job) — cần review riêng.
- **CDN** cho asset/public content.
- **Load test** (k6/Locust) — tiêu chí nghiệm thu năng lực thật.

## 6. Checklist bật production (tóm tắt)
1. Cài Redis, đặt env redis, chuyển cache/queue/session → redis.
2. Chạy Horizon (systemd/supervisor) + Octane (FrankenPHP) sau reverse proxy.
3. Cấu hình replica + `DB_READ_HOST`.
4. `php artisan config:cache route:cache event:cache`.
5. Bật CDN, chạy load test, theo dõi metrics (queue backlog, p95, replica lag, cache hit-rate).
