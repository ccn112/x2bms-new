# Resident API — Tài liệu vận hành (Operations Runbook)

_Backend x2bms · App x2mobile · Cập nhật: 2026-07-24_

Runbook dựng/seed/kiểm thử/cấu hình/deploy toàn bộ bề mặt **API cư dân** (24 endpoint) +
trợ lý **X2AI** + metric **AQI**. Shape chi tiết: `RESIDENT_API_REFERENCE.md`. Domain: `docs/contracts/RESIDENT_API_DOMAIN.md`.

---

## 1. Môi trường

### Dev (Windows + Laravel Herd)
- Site: `https://x2bms.test` (Herd trỏ repo, phục vụ nhánh đang checkout).
- PHP: `C:/Users/ADMIN/.config/herd/bin/php84/php.exe` (8.4). Composer: `~/.config/herd/bin/composer.bat`.
- DB: MySQL `x2bms` @ 127.0.0.1:3306. **DB dev sync tay** → sau khi thêm cột phải `migrate` + verify HTTP thật (KHÔNG dựa sqlite).
- App Flutter: `D:/Chinh/app/flutter/bin/flutter`. Build app trỏ backend: `--dart-define=X2_API_BASE_URL=https://x2bms.test/api/v1 --dart-define=X2_USE_MOCK=false`.

### Prod
- Deploy theo `deploy.md` (CloudPanel). Đảm bảo `.env` prod set đủ khoá ở §4 (đặc biệt `X2AI_API_KEY`, và gói AQI thương mại nếu cần).

---

## 2. Kho endpoint cư dân (24 route — `/api/v1/resident/*` + `/api/v1/me/*` + `/api/v1/ai/*`)

| Nhóm | Endpoint |
|---|---|
| Bootstrap/Me | `GET me/bootstrap` · `PATCH me/profile` · `POST/DELETE me/devices` |
| Hồ sơ | `GET resident/apartment` |
| Hoá đơn | `GET resident/statements(+/{id})` · `GET resident/billing/summary(+/trend)` · `GET resident/payments(+/{id})` |
| Thông báo | `GET resident/notifications` · `POST resident/notifications/{id}/read` |
| Ưu đãi | `GET resident/loyalty(+/activities,+/gifts)` · `GET resident/offers` |
| Cộng đồng | `GET resident/community/{posts,events,polls,groups}` · `POST resident/community/polls/{id}/vote` |
| Chợ + BĐS | `GET resident/market/{listings,services,categories}` · `GET resident/real-estate` |
| Home | `GET resident/home` |
| An ninh | `POST resident/sos` |
| Trợ lý X2AI | `POST ai/chat` (SSE) · `GET ai/chat/sessions(+/{session})` |

Kiểm tra nhanh toàn bộ: `php artisan route:list --path=api/v1/resident`.

---

## 3. Seed dữ liệu demo

Dữ liệu demo các tab cư dân trong **`ResidentDemoContentSeeder`** (idempotent — `updateOrCreate`, chạy lại an toàn).

```bash
php artisan db:seed --class=ResidentDemoContentSeeder
```

Phủ (scope tenant 1 / project 1 / resident demo #1305 = user #6):
- **Vouchers:** 3 offers (points_cost=0) + 1 voucher **platform** rollout tenant 1 + 2 gift cũ.
- **Loyalty:** tài khoản 3200đ (Bạc) + 4 hoạt động.
- **Cộng đồng:** 3 posts + 2 events + 1 poll(4 options) + 3 groups.
- **Payments:** 2 thanh toán (apt 11) + allocation vào statement đã trả.

Market/BĐS/notifications/statements dùng dữ liệu seed nền có sẵn (đủ scope user #6).
Khi thêm module → bổ sung hàm `seedXxx()` vào seeder này.

---

## 4. Cấu hình ENV

| Nhóm | ENV | Mặc định | Ghi chú |
|---|---|---|---|
| **X2AI** (trợ lý chat) | `X2AI_API_KEY` | — | **BẮT BUỘC để chat hoạt động** (Anthropic Messages API; fallback `ANTHROPIC_API_KEY`). |
| | `X2AI_MODEL` | `claude-haiku-4-5` | Rẻ; nâng `claude-sonnet-5`/`claude-opus-4-8` khi cần thông minh hơn. |
| | `X2AI_MAX_TOKENS` | `1024` | Trần token đầu ra/lượt (kiểm soát chi phí). |
| | `X2AI_DATA_API_URL/TOKEN` | — | Mode tra cứu DB (bật khi sẵn); chưa set → tool báo "not configured", không lỗi. |
| **AQI** (metric Home) | `AQI_PROVIDER` | `open-meteo` | Free, phi thương mại. |
| | `AQI_BASE_URL` | Open-Meteo air-quality | Đổi sang WAQI/IQAir khi prod (không sửa code). |
| | `AQI_API_KEY` | — | Set khi dùng nguồn có key. |
| | `AQI_CACHE_TTL` | `3600` | Giây; cache AQI theo project. |
| **VNPay** | `VNPAY_TMN_CODE` / `VNPAY_HASH_SECRET` | — | Bật cổng vnpay (khoá bí mật KHÔNG lưu DB). `VNPAY_BASE_URL`/`VNPAY_RETURN_URL`. |
| **MoMo** | `MOMO_PARTNER_CODE` / `MOMO_ACCESS_KEY` / `MOMO_SECRET_KEY` | — | Bật cổng momo. `MOMO_BASE_URL`/`MOMO_RETURN_URL`. |
| **VietQR** | — | (không cần key) | Cấu hình tài khoản nhận trong bảng `payment_channels.config`. |

Cấu hình đọc qua `config('services.x2ai')` / `config('services.aqi')`.

---

## 5. Trợ lý X2AI (chat AI) — vận hành

- **Kiến trúc:** app hiển thị **orb nổi X2AI** (icon `x2ai.svg`, "floating assistant, không phải tab") → gọi `POST /api/v1/ai/chat` (SSE stream). Key + logic LLM **chỉ ở backend** (frontend không chạm key).
- **Auth:** cư dân đã đăng nhập (Bearer) hoặc ẩn danh theo `X-Device-Id`.
- **Bật chat prod:** set `X2AI_API_KEY`. Chưa set → endpoint trả lỗi cấu hình (app nên degrade mềm).
- **Kiểm soát chi phí:** `X2AI_MAX_TOKENS`, model rẻ mặc định; rate-limit ở tầng route (`throttle`). Chi tiết kiến trúc/tái dùng: `.claude/skill/CHAT_MODULE_HANDOFF.md`.

---

## 5b. Cổng thanh toán — bật/cấu hình per tenant/dự án

Bảng **`payment_channels`** (tenant_id, project_id nullable, channel `vietqr|vnpay|momo`, is_enabled, config json).
`project_id = NULL` → áp dụng mọi dự án của tenant; có giá trị → riêng dự án đó (ưu tiên bản ghi riêng dự án).

**Bật VietQR** (không cần credential — chỉ tài khoản nhận):
```php
App\Models\PaymentChannel::updateOrCreate(
  ['tenant_id'=>1,'project_id'=>null,'channel'=>'vietqr'],
  ['is_enabled'=>true,'display_name'=>'Chuyển khoản VietQR','config'=>[
     'bank_bin'=>'970436','bank_code'=>'VCB',
     'account_no'=>'1234567890','account_name'=>'BAN QUAN LY ...']]);
```
QR sinh server-side (EMVCo napas, CRC16) với số tiền = công nợ hoá đơn, nội dung = `TT <mã HĐ>`.
App render QR (`qr_string`) + hiện danh sách app ngân hàng (`bank_apps` từ `config/vietnam_banks.php`) để deeplink.

**Bật VNPay/MoMo:** tạo bản ghi channel `vnpay`/`momo` + set ENV `VNPAY_*` / `MOMO_*` (§4). Chưa set khoá →
`intent` trả `status=not_configured` (app xử lý mềm). Signer redirect_url hoàn thiện khi có sandbox creds.

## 6. Lấy token & kiểm thử HTTP

```bash
php artisan tinker --execute='echo App\Models\User::find(6)->createToken("cli",["resident"])->plainTextToken;'

curl -sk -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" \
  https://x2bms.test/api/v1/resident/home
```

---

## 7. Quy tắc scope cư dân (BẮT BUỘC)

Cư dân `tenant_id = NULL` → **global scope `BelongsToTenant` no-op** → mọi query dữ liệu cư dân phải scope **tường minh** qua `ResidentContextService`:
- Voucher/offers → `tenantIds()` (∪ platform rollout qua pivot `voucher_tenant`).
- Community/market listings/BĐS → `projectIds()`.
- Market services → `tenantIds()` (bảng `service_providers` không có project_id).
- Loyalty/payments/của-chính-mình → `resident_id ∈` residents của user; hoá đơn/SOS → `apartment_id ∈` căn của user.

---

## 8. Checklist thêm/đổi 1 endpoint resident

1. Resource riêng (`App\Http\Resources\Api\V1\*Resource`) — money string, snake_case.
2. Controller kế thừa `ApiController`, trả qua `ApiResponse::success|paginated|error`.
3. Scope tường minh (§7). Không dựa tenant global scope.
4. Route trong nhóm `resident` (`routes/api.php`, middleware `auth:sanctum,ability:resident,throttle:api`).
5. Bổ sung seed vào `ResidentDemoContentSeeder`.
6. **Verify HTTP thật** trên `x2bms.test` bằng token resident.
7. Cập nhật `RESIDENT_API_REFERENCE.md` + `RESIDENT_API_DOMAIN.md` + ghi `DEV_JOURNAL`.

---

## 9. Điểm chờ owner chốt (chặn shape cuối)

- **Cổng thanh toán** (VietQR/VNPay/MoMo + credentials) → build `POST /resident/payments` (nút Thanh toán CD-PAY-05).
- **AQI prod:** chốt gói/nguồn thương mại (Open-Meteo free = phi thương mại).
- **Community groups:** bảng membership để `joined` đúng.
- **eKYC/household invite:** contract chưa chốt (app còn stub).
