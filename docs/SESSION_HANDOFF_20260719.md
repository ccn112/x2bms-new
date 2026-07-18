# X2-BMS — Session Handoff (2026-07-19)

Tổng kết phiên làm việc: kiến trúc nền tảng, Backend Phase 0, scale layer, bảo mật, nghiệp vụ, Chat AI, app Flutter, và tối ưu web. Kèm cách kiểm tra + việc còn lại.

## 1. Quyết định lớn đã chốt
- **Một backend Laravel duy nhất, API-first** (không dựng backend Node như x1). Scale bằng Octane + Redis + read-replica + queue + offline-first + CDN.
- **Hợp nhất identity về `User`** (chủ thể đăng nhập duy nhất; vai trò suy từ quan hệ). Stack `GlobalUserAccount` để deprecate sau (chưa xoá).
- **App**: monorepo Flutter (`x2_mobile`), 2 app (Cư dân đã scaffold, BQL sẽ sau), offline-first.
- **Chat AI (X2AI)**: port kiến trúc chat của xweb sang Laravel; provider **Anthropic Haiku 4.5**; app cho chat ẩn danh (nâng cao → Action Gate), web tự định danh.

## 2. Đã làm & verify

### Backend Phase 0 (x2/x2web)
- Identity: `User` ẩn PII, `hasResidentMembership/isStaffOperator/tokenAbilities`.
- Sanctum access + **refresh rotate** device-bound (`TokenService`), OTP skeleton (`OtpService`).
- `/api/v1`: envelope chuẩn, auth (login/otp/refresh/logout), `me/bootstrap`, device registry (`mobile_devices`), rate limiter, exception→envelope.
- **Nghiệp vụ**: `GET /api/v1/resident/statements(+/{id})` — cursor paginate, ability `resident`, scope theo căn hộ. Verify: resident→200, staff→403.
- **Bảo mật**: private KYC (disk `local` + route signed+auth+policy `PrivateMediaController`); command `kyc:migrate-private` (public→private).
- Verify HTTP thật: login→me/bootstrap→refresh→logout + 401 cho token sai. ✅

### Scale layer (sẵn sàng production, bật bằng env)
- Read/write DB split (`config/database.php`), **Horizon** (hàng đợi emergency/default/bulk), **Octane** (config frankenphp), predis.
- Local giữ driver `database`; production dùng `.env.production.example` (redis/octane/replica).

### Chat AI Slice A (x2/x2web)
- `config/ai.php` (+ provider `fake` để test local không cần key), `App\Services\Ai\*` (LlmProvider/Anthropic/Fake/ChatService/ChatStore/GuardrailResolver), SSE `POST /api/v1/ai/chat` + sessions. Lưu vào bảng `Ai*` sẵn có.
- Verify (fake provider): stream deltas→done, lưu hội thoại + usage/cost. ✅

### App Flutter Cư dân (x2/x2_mobile)
- Monorepo + 6 package `x_*` + app `resident_mobile`: **12 màn** (6 public + 6 onboarding), 5 feature (data/domain/presentation), go_router 4 experience mode + shell 5 tab + pending intent 15', 2 theme runtime, **offline-first SWR** (CacheStore prefs-backed, Drift-swappable).
- `flutter analyze` sạch, `flutter test` pass, `flutter build web` OK. Chạy bằng **mock** (chưa nối API thật).

### Tối ưu Web (x2/x2web)
- **N+1** trang Căn hộ: 110→**8 query/lần tải** (DB 55→21ms); KPI gộp `groupBy`; +index `rar(apartment_id,role)`, `debts(apartment_id,is_overdue)`. Trang Cư dân: gộp KPI.
- **Self-host font** (bỏ request `fonts.bunny.net` render-blocking → `/fonts/x2-fonts.css`). Verify: 0 request ngoài.
- **Flash sidebar khi reload**: critical shell CSS/JS inline (`critical-shell.blade.php`) — layout đúng ngay first paint, gỡ preopen sau Alpine.

## 3. Cách kiểm tra

### App Flutter (mock, không cần backend)
```powershell
cd E:\App\code\x2\x2_mobile\apps\resident_mobile
flutter run -t lib/main_dev.dart -d chrome     # hoặc -d windows / -d edge
# analyze/test:
flutter analyze
flutter test
```
Nối API thật sau này: `--dart-define=X2_USE_MOCK=false --dart-define=X2_API_BASE_URL=http://127.0.0.1:8000/api/v1`.

### Backend API
```powershell
cd E:\App\code\x2\x2web
php artisan serve
# tài khoản admin seed: x2bms@x2bms.vn / Bms@2026!  (xem .env DEV_ADMIN_*)
```
Xem `x2/docs/guide/mobile-api-usage.md` (endpoint/headers/envelope) và `backend-run-local.md`.

### Web BQL/admin
- Đăng nhập `/admin` bằng tài khoản admin seed. **Đo hiệu năng trên Herd/production**, KHÔNG dùng `php artisan serve` (đơn luồng → số liệu sai).

## 4. Tài liệu (nguồn tra cứu)
- Kiến trúc: `x2/docs/ARCHITECTURE_X2_PLATFORM_V1.md` (+ §13b scale checklist)
- Backend Phase 0: `x2/x2web/docs/PHASE0_MOBILE_API_IMPLEMENTATION.md`
- Chat AI: `x2/docs/AI_CHAT_ADOPTION_PLAN.md`
- Tối ưu web: `x2/x2web/docs/PERF_LIST_PAGES_OPTIMIZATION.md`
- Nguyên tắc dev: `x2/docs/DEVELOPMENT_PRINCIPLES.md`
- Hướng dẫn sử dụng: `x2/docs/guide/` (mobile-api-usage, backend-run-local, scale-ops)
- App report: `x2/x2_mobile/IMPLEMENTATION_REPORT.md`

## 5. Việc còn lại (đề xuất ưu tiên)
1. **App**: nối `Remote*Repository` theo `mobile-api-usage.md` + **bật Drift** cho CacheStore (cache đầy đủ) → bản chạy thật đầu tiên.
2. **Backend**: `POST /api/v1/auth/register`; context header stateless `X-Context-Id`; nối SMS/Zalo cho OTP.
3. **Chat AI**: Slice B (web Livewire widget) · C (màn Flutter X2AI) · D (RAG theo project) · E (guardrail/prompt từ DB).
4. **Bảo mật/kiến trúc**: deprecate `GlobalUserAccount` (data-migration, cần xác nhận); sửa `BelongsToTenant` no-op trong queue.
5. **App BQL**: soạn foundation + build (Foundation V1 mới chỉ có app Cư dân).
6. **Scale**: bật Redis/Horizon/Octane/replica trên production + load test.

## 6. Lưu ý môi trường
- PHP 8.4.15 + Composer (qua PowerShell), Flutter 3.41.6, Node có sẵn. `bash` không có PHP trên PATH → dùng PowerShell cho artisan/composer.
- Local `.env`: `CHAT_PROVIDER=fake` (chưa có ANTHROPIC_API_KEY), DB thật đã nối, đã có tài khoản seed.
