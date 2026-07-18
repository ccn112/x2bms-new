# X2-BMS — Backend Mobile Readiness Audit (2026-07-18)

Gói rà soát **READ-ONLY** source code backend X2-BMS nhằm chuẩn bị triển khai:
1. **Flutter App cư dân**
2. **Flutter App Ban quản lý (BQL)**
3. **Web cư dân**

> Đây là tài liệu phân tích. **Không** sửa source/migration/route/config/dependency.
> **Không** chứa secret, mật khẩu, token, private key hay nội dung `.env` (chỉ tham chiếu TÊN biến khi cần).

## Phát hiện then chốt (đọc trước)
Backend hiện tại là một **monolith Filament (admin, server-rendered Livewire)**. Toàn bộ nghiệp vụ cư dân/BQL nằm trong `app/Filament/Pages/*`. **`routes/api.php` chỉ có API cấp nền tảng (SaaS Billing / Integration / Support, quyền `platform.admin`)** — **KHÔNG có API cho cư dân hoặc BQL**. Dữ liệu (model + migration) cho hầu hết domain đã có, nhưng **lớp API + token cho mobile gần như chưa tồn tại**. Vì vậy trọng tâm audit là: *tận dụng model/logic sẵn có, xây lớp API + xác thực token cho mobile*.

## Phạm vi
- `composer.json` + cấu trúc source (không liệt kê `vendor/`, `node_modules/`).
- `routes/api.php`, `routes/web.php`, `routes/console.php`.
- Models, migrations, enums, casts; tenancy scopes.
- Controllers (Platform/*), middleware, policies, gates.
- app/Support services, jobs/events/listeners (nếu có).
- Auth/Sanctum/OTP/session/device.
- Notification/queue/email/SMS/Zalo/FCM; upload/storage.
- Payment/webhook/invoice/reconciliation (SaaS billing).
- Seeders, factories, tests; tài liệu API hiện có.

## Cách đọc gói này
| # | File | Nội dung |
|---|---|---|
| 1 | `README.md` | Mục tiêu, phạm vi, cách đọc (file này) |
| 2 | `REPOSITORY_SNAPSHOT.md` | Cây thư mục, stack, version, package chính |
| 3 | `BACKEND_ARCHITECTURE.md` | Kiến trúc module, luồng request, service/queue/event, điểm dở |
| 4 | `TENANCY_AND_CONTEXT.md` | Phân cấp tenant/project/building/apartment, context, scope, rủi ro rò dữ liệu |
| 5 | `AUTH_AND_RESIDENT_IDENTITY.md` | User/resident/household, OTP/token/session/device, quan hệ căn hộ, quyền |
| 6 | `DATABASE_DOMAIN_MAP.md` | Bảng theo module, PK/FK, soft delete, audit field, trạng thái migration |
| 7 | `DOMAIN_STATE_MACHINES.md` | Enum trạng thái, transition, actor, side effect |
| 8 | `API_ENDPOINT_INVENTORY.md` | Mọi endpoint: method/route/auth/scope/request/response/status/mobile-ready |
| 9 | `API_RESPONSE_AND_ERROR_STANDARD.md` | Envelope, lỗi, phân trang, thời gian, tiền tệ, i18n |
| 10 | `MODULE_READINESS_MATRIX.md` | Ma trận sẵn sàng theo module (migration/model/service/API/policy/seed/test/mobile) |
| 11 | `MOBILE_API_GAPS.md` | API còn thiếu cho cư dân/BQL, method sai, N+1, offline/sync, idempotency |
| 12 | `PAYMENT_NOTIFICATION_UPLOAD_AUDIT.md` | Payment/webhook/idempotency, FCM/device, upload/signed URL |
| 13 | `SECURITY_AND_PRIVACY_AUDIT.md` | Tenant isolation, PII, consent, revoke, audit, rate limit, OTP abuse, file, log |
| 14 | `RECOMMENDED_IMPLEMENTATION_ORDER.md` | Thứ tự dựng backend theo vertical slice + dependency/blocker |
| 15 | `manifest.json` | Danh sách file, thời điểm export, git branch/commit, version (không secret) |

## Bối cảnh kỹ thuật (nhanh)
- PHP `^8.3`, Laravel `^13.8`, Filament `5.*`, Sanctum `^4.3`, spatie/laravel-permission `^7.4`.
- 3 tầng ↔ 3 panel: `/sa` SuperAdmin · `/hq` Tenant(công ty) · `/admin` BQL(dự án) (+ `/fila` CRUD).
- Quy mô: ~284 model, 67 migration, 20 controller (đều thuộc `Platform/*` + Auth), 4 PHP enum, 5 test.

*Xuất bởi rà soát tự động, 2026-07-18. Xem `manifest.json` để biết commit/branch chính xác.*
