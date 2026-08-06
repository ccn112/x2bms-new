# SESSION HANDOFF — 2026-08-06

**Phạm vi phiên:** Triển khai **đa ngôn ngữ / localization** cho X2 (backend `x2bms` + app cư dân `x2_mobile`) theo gói `handoff/X2_BMS_I18N_LOCALIZATION_HANDOFF_20260806` + `handoff/x2mobile/X2_BMS_LANGUAGE_SCREEN_HANDOFF_20260806`. Kèm 2 lượt fix `tenant_id` (audit lớp lỗi).

**Trạng thái cuối:** Đã **merge + push `main`** cả 2 repo. Working tree sạch.
- `x2bms` → `origin/main = fb30f9b`
- `x2_mobile` → `origin/main = 37c0647`

Nhật ký chi tiết: `docs/DEV_JOURNAL.md` (cả 2 repo). Bằng chứng test: `docs/dev/i18n/I18N_TEST_LOG.md`.

---

## 1. Đã làm (I18N-000 → I18N-010 + P2)

**Backend (Laravel 13 / Filament 5):**
- Schema localization (20 bảng, additive, reversible), seed master idempotent (production-safe; demo chặn production).
- `LocaleResolver` + middleware `SetLocaleFromRequest`; API `GET /localization/bootstrap`, `PATCH /me/localization-preference`, `GET /localization/packs/{ns}/{locale}` (ETag/304); khối `localization` trong `/me/bootstrap` + `/public/bootstrap`.
- **Trung tâm dịch `/sa`** (nhóm "Trung tâm dịch"): Locales · Khóa dịch (cột **Loại**=nav/tiêu đề/nút/nhãn/trạng thái/thông báo/lỗi/ghi chú + **Nhóm**, lọc/gom nhóm, **sửa inline** vi/en, nút **Phát hành gói**) · Bản phát hành (publish + rollback). Audit đầy đủ.
- Notification localization domain (template versioned/immutable + channel + risk).
- Lệnh `php artisan i18n:export-app-baseline` → sinh baseline app từ DB.
- **1433 key** (vi-VN + en-US) trong DB — nguồn sự thật quản lý ở Trung tâm dịch.

**Mobile (Flutter):**
- gen_l10n/ARB + màn **Ngôn ngữ** redesign (cờ SVG + checkmark + "Sắp hỗ trợ" + auto-dịch + kiểm tra cập nhật), `LocaleController` (Riverpod, local-first).
- **Resolver `context.tr('x2.<ns>.<key>')`**: gói remote → baseline bundled (sinh từ DB) → fallback; reactive; migrate **toàn bộ màn go-live + P2** sang tr.
- **Remote pack**: tự cập nhật ngầm khi mở app + nút "Kiểm tra cập nhật"; verify checksum; cache offline; critical namespace `x2.api`/`x2.shared` không bị đè.

**VÒNG LẶP proven trên Samsung SM-A057F:** sửa key ở `/sa` → Phát hành → app "Kiểm tra cập nhật" → màn đổi chữ **không build lại**.

---

## 2. Quyết định / kiến trúc chốt
- **Một key-space DB** cho mobile: `context.tr('<ns>.<key>')` — baseline sinh từ DB (`i18n:export-app-baseline`), remote pack đè. Cập nhật key: sửa DB/Trung tâm dịch → export lại baseline → build.
- Fallback cuối `vi-VN`; go-live bật `vi-VN` + `en-US`; ko/ja/zh có seed nhưng "Sắp hỗ trợ".
- `config/localization.php` dùng `X2_DEFAULT_LOCALE`/`X2_FALLBACK_LOCALE` (tách khỏi APP_LOCALE).
- Cập nhật gói: **tự động ngầm khi mở app** (+ nút tay) — owner chốt.
- Tiền/ngày/mã giữ là data ({amount}/{date}/{count}), không dịch.

## 3. Bug đã bắt & fix (phần lớn chỉ lộ trên MySQL/serializing-cache/máy thật)
1. Index MySQL `notification_delivery_snapshots` > 64 ký tự (sqlite giấu).
2. `LocaleResolver::supportedLocales` cache `Collection` → `__PHP_Incomplete_Class` → bootstrap 500 request thứ 2 → cache array.
3. `PublishTranslationRelease` không bust cache `pack_versions` → app không thấy version mới.
4. Mobile `refreshNow`/`_rebuildMerged` phụ thuộc `pack_versions` store rỗng → dùng union baseline namespaces.
5. **Duyệt cư dân** (`ResidentApprovalQueue::approve`) thiếu `tenant_id` → MySQL 1364 (bug có sẵn, không do i18n).
6. **Audit lớp `tenant_id`**: 3 chỗ AI-config thiếu tenant_id (Sa/AiKnowledgeConfig crash 100%; Hq/AiWorkflowAutomation + AiGovernance crash khi platform-admin) — đã fix + migration `ai_prompt_templates.tenant_id` nullable. Phần còn lại của repo đã set tenant_id tường minh (an toàn).

## 4. Test
- Backend localization: **31/31 pass**. Mobile: `flutter analyze lib` sạch; mỗi file test màn migrate pass khi cô lập (chạy gộp nhiều file 1 lệnh flaky — CI isolate không dính). Chi tiết: `docs/dev/i18n/I18N_TEST_LOG.md`.

## 5. APK
`x2_mobile/apps/resident_mobile/build/app/outputs/flutter-apk/app-release.apk` (prod, mock off, ~89 MB, gồm toàn bộ i18n).

## 6. Cách test local
- `php artisan serve --host=127.0.0.1 --port=8123` (chung MySQL với Herd x2bms.test).
- Máy thật: `adb reverse tcp:8123 tcp:8123` + build debug `-t lib/main_dev.dart --dart-define=X2_API_BASE_URL=http://127.0.0.1:8123/api/v1 --dart-define=X2_USE_MOCK=false`.
- Quản lý bản dịch: `/sa` → Trung tâm dịch → sửa Khóa dịch → Phát hành gói → app Kiểm tra cập nhật.

## 7. CÒN LẠI (phiên sau)
- **I18N-011/012/013**: notification đa ngôn ngữ theo locale người nhận (domain đã có, chưa wire delivery/preview).
- **I18N-014→018**: dịch nội dung động (tin/cộng đồng), giữ source + source_hash, provider adapter (BLOCKED_PROVIDER_SELECTION), dashboard coverage/cost.
- **I18N-019**: XHub federation (sau khi ổn định).
- **P2 nhỏ (mobile)**: enum-label domain (ContentType/ProjectStatus), list search/filter so-chuỗi, FAQ/legal dài, chuỗi nội bộ package `x2_resident_ui` (shell/states/theme — cần lift-to-caller ở cấp thiết kế).
- **App BQL** (Flutter tương lai) dùng lại domain `x2.bql_app`/`x2.bql_web` đã seed.
- Deploy production: chạy `php artisan migrate` + `db:seed --class=LocalizationMasterSeeder` + `i18n:export-app-baseline` trước khi build app.
