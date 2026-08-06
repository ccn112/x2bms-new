# I18N / Localization — Test Log

Nhật ký kiểm thử gói đa ngôn ngữ X2 (backend `x2bms` + mobile `x2_mobile`), nhánh `feat/i18n-localization`.

## Backend (Laravel/Filament) — `php artisan test`

| Bộ test | Phạm vi | Kết quả |
|---|---|---|
| `tests/Feature/Localization/LocalizationSeederTest` | Seeder idempotent + đúng count | ✅ 1433 key / 2866 value / 6 locale / 9 ns / 21 template / 18 release |
| `tests/Feature/Localization/LocaleResolverTest` | Thứ tự resolve + fallback vi-VN + tenant gating | ✅ |
| `tests/Feature/Localization/LocalePreferenceApiTest` | `GET /localization/bootstrap`, `PATCH /me/localization-preference`, Content-Language, 422/401 | ✅ |
| `tests/Feature/Localization/TranslationPackApiTest` | Pack + checksum + ETag/304 + lọc namespace + rollback | ✅ |
| `tests/Feature/Localization/PublishTranslationReleaseTest` | Publish→pack→rollback, checksum khớp verifier Dart | ✅ |
| `tests/Feature/Localization/TranslationCenterPagesTest` | Render Filament `/sa` (kind/inline/publish action) | ✅ |
| `tests/Feature/Localization/NotificationLocalizationTest`, `Unit/Localization/*` | Notification l10n, checksum, phân loại kind | ✅ |
| **Tổng localization** | | **✅ 31 passed / 99 assertions** |

- Chạy trên sqlite `:memory:` (phpunit.xml, memory_limit 1024M cho render Filament).
- **Verify môi trường thật (MySQL local + Herd/serve 8123):** `migrate` + `db:seed --class=LocalizationMasterSeeder` sạch; `/sa/{locales,translation-keys,translation-releases}` → HTTP 302 (không 500); `GET /localization/packs/*` + `/localization/bootstrap` trả đúng version/checksum.
- **Bug chỉ lộ trên môi trường thật, đã fix:** index MySQL >64 ký tự; cache `Collection` → `__PHP_Incomplete_Class` (bootstrap 500 request thứ 2); publish không bust `pack_versions` cache.
- Không chạy full suite tự động: `Batch08IntegrationApiTest` OOM (lỗi môi trường có sẵn, không liên quan i18n).

## Mobile (Flutter) — `flutter analyze` + `flutter test`

| Kiểm tra | Kết quả |
|---|---|
| `flutter analyze lib` | ✅ **No issues found** (đã guard `use_build_context_synchronously`) |
| `flutter analyze lib test` | 3 lỗi PRE-EXISTING không liên quan (`amenity_booking_stats_test`, `comment_replies_paging_test`) |
| `flutter test test/localization` | ✅ (translator resolve order, remote override, placeholder, remote pack sync/304/checksum/offline, locale switch) |
| Smoke test màn đã migrate (auth/home/notifications/billing/payment/interaction/community/profile/amenity/visitor/settings) | ✅ khi chạy **cô lập từng file** |

- Lưu ý: nhồi nhiều file test vào **một lệnh** `flutter test` gây flaky (ô nhiễm chéo giữa file); `flutter test` chuẩn của CI chạy mỗi file 1 isolate → không dính. Từng file pass khi chạy riêng.

## Verify end-to-end trên máy thật (Samsung SM-A057F, Android 15)

- Build debug trỏ server local (`--dart-define=X2_API_BASE_URL=http://127.0.0.1:8123/api/v1`, mock off) + `adb reverse tcp:8123 tcp:8123`.
- **VÒNG LẶP proven:** sửa key ở `/sa` Trung tâm dịch → Phát hành gói → app "Kiểm tra bản cập nhật ngôn ngữ" → Home đổi chữ ("Thao tác nhanh" → "Thao tác nhanh (đã sửa từ web)") **không build lại**.
- Màn Ngôn ngữ redesign + đổi Việt⇄English hiển thị đúng trên máy thật.
