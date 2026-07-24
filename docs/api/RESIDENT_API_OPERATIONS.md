# Resident API — Tài liệu vận hành (Operations)

_Backend x2bms · Cập nhật: 2026-07-24_

Hướng dẫn dựng/seed/kiểm thử/deploy phần API cư dân trên môi trường dev (Herd) và prod.

## 1. Môi trường dev (Windows + Laravel Herd)

- Site: `https://x2bms.test` (Herd trỏ vào thư mục repo, phục vụ nhánh đang checkout).
- PHP: `C:/Users/ADMIN/.config/herd/bin/php84/php.exe` (php 8.4). Composer: `~/.config/herd/bin/composer.bat`.
- DB: MySQL `x2bms` @ 127.0.0.1:3306 (root/`.env`). **DB dev sync tay** → sau khi thêm cột phải `migrate` rồi verify HTTP thật (không dựa sqlite).

## 2. Seed dữ liệu demo cư dân

Dữ liệu demo cho các tab cư dân nằm trong **`ResidentDemoContentSeeder`** (idempotent — chạy lại an toàn, dùng `updateOrCreate`).

```bash
php artisan db:seed --class=ResidentDemoContentSeeder
```

Nội dung seed (scope tenant 1 / project 1 / resident demo #1305 = user #6):
- **Vouchers:** 3 offers (`points_cost=0`) + 1 voucher **platform** rollout xuống tenant 1 + giữ 2 gift cũ (`points_cost>0`).
- **Loyalty:** tài khoản 3200đ (hạng Bạc) + 4 hoạt động điểm cho resident #1305.

> Khi thêm module mới (community/market/…), bổ sung hàm `seedXxx()` vào seeder này để `--class=ResidentDemoContentSeeder` phủ toàn bộ tab.

## 3. Lấy token để kiểm thử HTTP

```bash
php artisan tinker --execute='
  $u = App\Models\User::find(6);                       // cư dân demo
  echo $u->createToken("cli", ["resident"])->plainTextToken;'
```
Gọi thử:
```bash
curl -sk -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" \
  https://x2bms.test/api/v1/resident/offers
```

## 4. Cấu hình liên quan (ENV)

| Nhóm | ENV | Ghi chú |
|---|---|---|
| AQI (Home metric) | `AQI_PROVIDER`, `AQI_BASE_URL`, `AQI_API_KEY`, `AQI_CACHE_TTL` | Open-Meteo free (phi thương mại) — gắn key/gói khi lên prod. Xem `config/services.php`. |

## 5. Quy tắc scope cư dân (bắt buộc)

Cư dân có `tenant_id = NULL` → **global scope `BelongsToTenant` là no-op**. Mọi query dữ liệu
cư dân phải scope **tường minh** qua `ResidentContextService`:
- Voucher/offers: `tenantIds()` (∪ platform rollout qua pivot `voucher_tenant`).
- Community/market/BĐS: `projectIds()`.
- Loyalty/payments/của-chính-mình: `resident_id ∈` residents của user.

## 6. Checklist khi thêm/đổi 1 endpoint resident

1. Resource riêng (`App\Http\Resources\Api\V1\*Resource`) — money string, snake_case.
2. Controller kế thừa `ApiController`, trả qua `ApiResponse::success|paginated|error`.
3. Scope tường minh (mục 5). Không dựa tenant global scope.
4. Đăng ký route trong nhóm `resident` (`routes/api.php`, middleware `auth:sanctum,ability:resident,throttle:api`).
5. Bổ sung seed demo vào `ResidentDemoContentSeeder`.
6. **Verify HTTP thật** trên `x2bms.test` bằng token resident.
7. Cập nhật `docs/api/RESIDENT_API_REFERENCE.md` + `docs/contracts/RESIDENT_API_DOMAIN.md` + ghi `DEV_JOURNAL`.
