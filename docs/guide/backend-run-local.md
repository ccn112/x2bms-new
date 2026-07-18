# Hướng dẫn chạy & kiểm thử backend X2 cục bộ

Yêu cầu: PHP 8.4, Composer, (tuỳ chọn) MySQL/MariaDB. Có thể dùng SQLite để test nhanh.

## 1. Cài đặt lần đầu
```powershell
cd E:\App\code\x2\x2web
composer install
Copy-Item .env.example .env   # nếu chưa có
php artisan key:generate
```

## 2. Cấu hình DB
### Nhanh (SQLite — để test API)
Trong `.env`:
```
DB_CONNECTION=sqlite
```
Tạo file DB rỗng rồi migrate:
```powershell
New-Item -ItemType File database\database.sqlite -Force | Out-Null
php artisan migrate
```
### Thật (MySQL)
Đặt `DB_CONNECTION=mysql` + `DB_DATABASE/DB_USERNAME/DB_PASSWORD` rồi `php artisan migrate`.

## 3. Chạy server
```powershell
php artisan serve      # http://127.0.0.1:8000
php artisan route:list --path=api/v1   # xem các route mobile
```

## 4. Smoke test Mobile API (Phase 0)
```powershell
# 1) Yêu cầu OTP (dev trả dev_code)
curl -X POST http://127.0.0.1:8000/api/v1/auth/otp/request `
  -H "Accept: application/json" -H "Content-Type: application/json" `
  -d '{"channel":"phone","destination":"0900000000","purpose":"login"}'

# 2) Đăng nhập mật khẩu (cần user có sẵn + X-Device-Id)
curl -X POST http://127.0.0.1:8000/api/v1/auth/login `
  -H "Accept: application/json" -H "Content-Type: application/json" -H "X-Device-Id: 11111111-1111-1111-1111-111111111111" `
  -d '{"identifier":"admin@example.com","password":"password"}'

# 3) Bootstrap (dùng access_token nhận được)
curl http://127.0.0.1:8000/api/v1/me/bootstrap `
  -H "Accept: application/json" -H "Authorization: Bearer <access_token>"
```

## 5. Lưu ý quan trọng
- **KHÔNG commit `.env`** (chứa APP_KEY/secret). Đã có trong `.gitignore`.
- Có user để test: chạy seeder (`php artisan db:seed`) hoặc tạo qua panel Filament `/admin`.
- Nếu đổi `config/*` mà không thấy hiệu lực: `php artisan config:clear`.
- Tài liệu API đầy đủ: [mobile-api-usage.md](mobile-api-usage.md).
