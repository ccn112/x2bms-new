# X2-BMS — Handoff phiên & dự án (2026-07-18)

> Mục đích: tiếp tục phát triển trên **máy mới**, khởi động **Flutter app cư dân + BQL**, và chuẩn bị **deploy backend lên `xbuilding.vn`**.
> Không chứa secret — mọi giá trị nhạy cảm (mật khẩu DB/mail, APP_KEY) phải mang sang máy mới theo kênh an toàn, KHÔNG qua git.

---

## 1. Tổng quan dự án
**X2-BMS** — hệ quản lý toà nhà (BMS) đa công ty (SaaS), backend Laravel + Filament (admin server-rendered), 3 tầng quyền ↔ hiện là 4 panel theo path:

| Panel | Path hiện tại | Domain dự kiến | Vai trò |
|---|---|---|---|
| SuperAdmin | `/sa` | `sa.xbuilding.vn` | Quản trị nền tảng, đa công ty |
| Tenant/HQ | `/hq` | `hq.xbuilding.vn` | Công ty vận hành, đa dự án |
| BQL | `/admin` | `bql.xbuilding.vn` | Ban quản lý 1 dự án |
| CRUD kỹ thuật | `/fila` | (nội bộ) | CRUD nhanh |
| Web cư dân | *(chưa có)* | `web.xbuilding.vn` | Khách hàng/cư dân |
| Mobile API | *(chưa có)* | `api.xbuilding.vn` (đề xuất) | Flutter app cư dân + BQL |

**Stack:** PHP 8.4 (Herd), Laravel v13.17, Filament v5.6, Sanctum v4.3, spatie/permission v7.4, MySQL 8. ~284 model, 67 migration (DB thật 326 bảng). Ngữ cảnh theo **project** (không phải building).

---

## 2. Trạng thái git (QUAN TRỌNG)
- Repo: **`ccn112/x2bms-new`**, base `main`.
- Nhánh làm việc phiên này: **`feat/bql01-04-resident-detail-password-reset`** (đã push origin).
- Commit tính năng: `ced3094` (chi tiết cư dân 360 + reset mật khẩu + mail SMTP) + commit handoff/audit (xem `git log`).
- **PR:** chưa merge. Mở PR tại: `https://github.com/ccn112/x2bms-new/pull/new/feat/bql01-04-resident-detail-password-reset` hoặc `gh pr create` (gh v2.96 đã cài — mở terminal mới).
- `.env` **không** nằm trong git (gitignore) — phải tạo lại trên máy mới.

---

## 3. Việc đã làm phiên 2026-07-18
Chi tiết: `docs/DEV_JOURNAL.md` (mục 2026-07-18). Tóm tắt:
1. **BQL-01-04 Chi tiết cư dân 360** (bản giàu, format ApartmentProfile: title+breadcrumb/action, KPI strip, 6 tab).
2. **Đặt lại mật khẩu cư dân** — trait `ResetsResidentPassword`, nút ở list+detail, 4 cách (mật khẩu tạm/OTP/gửi link/link copy Zalo), modal Copy; trang guest `/reset-password/{token}` (Password broker).
3. **Gửi email SMTP thật** (elasticemail) + `config('mail.test_to')` (chế độ test route mọi email về 1 địa chỉ).
4. **UI/UX**: fix z-index popup bị cột freeze đè; avatar trong bảng cư dân; header gọn 3 nút + dropdown; **màu nút theo ý nghĩa**; breadcrumb tô màu link.
5. **Chuẩn action UX** ghi `docs/LISTING_PAGE_STANDARD.md §5b`.
6. **Gói audit mobile** đầy đủ: `handoff/mobile_backend_audit_20260718/` (+ file zip) — 15 tài liệu đánh giá backend cho mobile.

---

## 4. Setup trên MÁY MỚI
```bash
git clone https://github.com/ccn112/x2bms-new.git
cd x2bms-new
git checkout feat/bql01-04-resident-detail-password-reset   # hoặc main sau khi merge
composer install
npm install
cp .env.example .env
# → điền .env (xem §5), rồi:
php artisan key:generate
php artisan migrate --seed        # tạo DB demo (seed đầy đủ ~284 model)
npm run build
php artisan serve --host=127.0.0.1 --port=8010   # hoặc dùng Herd domain
```
- Yêu cầu: PHP 8.3+ (khuyến nghị 8.4 như hiện tại), MySQL 8, Node ~ (xem `.nvmrc`), Composer.
- Tài khoản demo: BQL/HQ/SA `Bms@2026!`; cư dân `Resident@2026!`; login BQL test `nv1@x2bms.vn`.

---

## 5. Biến `.env` cần khai (mang secret sang an toàn)
Nhóm bắt buộc: `APP_NAME/APP_ENV/APP_KEY/APP_DEBUG/APP_URL/APP_LOCALE` · `DB_CONNECTION/DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` · `SESSION_DRIVER/SESSION_DOMAIN` · `FILESYSTEM_DISK` · `QUEUE_CONNECTION` · `CACHE_STORE`.
Mail: `MAIL_MAILER` (`smtp` để gửi thật / `log` để test) · `MAIL_HOST/MAIL_PORT/MAIL_USERNAME/MAIL_PASSWORD/MAIL_FROM_ADDRESS/MAIL_FROM_NAME` · `MAIL_TEST_TO_ADDRESS` (để trống ở production).
Khác: `AWS_*` (nếu dùng S3), `X2AI_API_KEY/X2AI_MODEL`, `VITE_APP_NAME`.
> **Secret phải copy thủ công** (mật khẩu DB, `MAIL_PASSWORD`, `X2AI_API_KEY`, `APP_KEY`). KHÔNG commit `.env`.

---

## 6. Kế hoạch domain `xbuilding.vn` (CHƯA làm — hướng dẫn khi deploy)
Hiện panel theo **path** (`->path('sa'|'hq'|'admin'|'fila')` trong `app/Providers/Filament/*PanelProvider.php`). Để chạy theo **subdomain**:
1. Đổi `->path('sa')` → thêm `->domain('sa.xbuilding.vn')` (tương tự cho hq/admin→bql/fila) trong từng `*PanelProvider`. Lưu ý panel **admin = BQL** nên map `bql.xbuilding.vn`.
2. `APP_URL=https://xbuilding.vn` (hoặc theo panel); ảnh hưởng **link đặt lại mật khẩu** (`makeResetLink` dùng `config('app.url')`).
3. `SESSION_DOMAIN=.xbuilding.vn` để chia sẻ session giữa subdomain (nếu cần); cân nhắc tách session theo panel.
4. **Web cư dân** (`web.xbuilding.vn`) + **API mobile** (`api.xbuilding.vn`): xây mới (xem §7). Cấu hình Sanctum `stateful` domains + CORS cho web cư dân.
5. Mail `MAIL_FROM_ADDRESS` theo domain xbuilding.vn; cấu hình SPF/DKIM.
6. DNS: A/CNAME cho `sa|hq|bql|web|api.xbuilding.vn`; HTTPS (Let's Encrypt).

---

## 7. Khởi động Flutter (cư dân + BQL) — ĐỌC TRƯỚC
**Chặn cứng:** backend hiện **CHƯA có API cho cư dân/BQL** — toàn bộ nghiệp vụ nằm trong Filament Pages (Livewire), API chỉ phục vụ platform-admin qua **phiên Filament**, chưa có token Sanctum.
→ Phải xây **lớp API + xác thực token trước** rồi mới code app.

**Nguồn tham chiếu bắt buộc:** gói `handoff/mobile_backend_audit_20260718/` (15 file), đặc biệt:
- `RECOMMENDED_IMPLEMENTATION_ORDER.md` — **Phase 0** (Sanctum token cư dân, response standard, API Resource/Policy, rào bảo mật) → slice Kích hoạt → Feedback → Hồ sơ → Tiện ích → Khách/ra vào → Hoá đơn/thanh toán (cuối).
- `MOBILE_API_GAPS.md` — danh sách endpoint còn thiếu cho từng app.
- `API_RESPONSE_AND_ERROR_STANDARD.md` — chuẩn envelope/lỗi/tiền tệ/thời gian đề xuất cho API mới.
- `AUTH_AND_RESIDENT_IDENTITY.md` + `TENANCY_AND_CONTEXT.md` — định danh cư dân + rủi ro scope khi API stateless (resident `tenant_id=NULL` → global scope no-op, phải scope thủ công theo `resident_apartment_relations`).
- `SECURITY_AND_PRIVACY_AUDIT.md` — vá trước khi mở API (ảnh CCCD ở disk public, rate-limit, OTP abuse).

**Đề xuất:** API tại `api.xbuilding.vn`, auth Bearer token (Sanctum), versioned `/api/v1/...`.

---

## 8. Việc còn dở / next
- **Mobile API Phase 0** (tiên quyết cho Flutter).
- Màn **nhật ký gửi truyền thông 3 tầng + retry** (owner tự thiết kế — `docs/COMMUNICATION_LOG_DESIGN_NOTE.md`).
- Cụm cư dân còn lại: **03 wizard thêm → 02 timeline → 08 hộ gia đình / 09 chuyển đến-đi / 10 chất lượng dữ liệu**.
- Nối gateway **SMS/Zalo** + hạ tầng **push/FCM** (hiện stub/trống).
- Chuyển panel sang **subdomain** khi deploy.
- **Merge PR** nhánh feat hiện tại.

---

## 9. Bản đồ tài liệu
| File | Nội dung |
|---|---|
| `docs/DEV_JOURNAL.md` | Nhật ký phát triển (mới nhất trên cùng) |
| `docs/LISTING_PAGE_STANDARD.md` | Chuẩn màn list/detail + §5b chuẩn action UX |
| `docs/PASSWORD_RESET_AND_MAIL.md` | Luồng reset mật khẩu + cấu hình mail |
| `docs/COMMUNICATION_LOG_DESIGN_NOTE.md` | Thiết kế màn nhật ký gửi (chưa dựng) |
| `docs/BQL_MASTER_BUILD_PLAN_20260703.md` | Kế hoạch 30 màn BQL |
| `docs/operations/README.md` | Tài liệu vận hành |
| `docs/user-guide/` | Hướng dẫn sử dụng (BQL) |
| `handoff/mobile_backend_audit_20260718/` | **Gói audit mobile-readiness (15 file)** |

*Handoff tạo 2026-07-18. Commit/branch chính xác xem `git log` và `handoff/mobile_backend_audit_20260718/manifest.json`.*
