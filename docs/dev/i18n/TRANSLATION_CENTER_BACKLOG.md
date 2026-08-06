# Trung tâm dịch — Backlog (deferred từ I18N-010 MVP)

Ngày: 2026-08-06 · Nhánh: `feat/i18n-localization`

## Đã build trong MVP I18N-010
- Nav group **Trung tâm dịch** trong panel `/sa`.
- **LocaleResource** — danh mục ngôn ngữ (bật/tắt, mặc định, thứ tự), read-mostly.
- **TranslationKeyResource** — sửa giá trị product-scope vi-VN / en-US; hiển thị giá trị đang published; lọc theo namespace; bảo vệ cờ khóa hệ thống.
- **TranslationReleaseResource** — liệt kê bản phát hành; header action **Phát hành gói mới**; row action **Khôi phục** (rollback).
- Service `PublishTranslationRelease` (publish + rollback), `TranslationValueWriter`.
- Audit qua `AuditLog` (translation.value.updated / .release.published / .release.rolled_back).
- Test feature: `tests/Feature/Localization/PublishTranslationReleaseTest.php`.

## Deferred — CHƯA build (theo scope chốt của I18N-010)
| Hạng mục | Task ref | Ghi chú |
|---|---|---|
| Dashboard Tổng quan (KPI coverage/cost/queue) | I18N-018 | Cần query/service riêng, không hardcode; theo rule widget. |
| Từ điển thuật ngữ (Glossary UI, import/export CSV) | — | Bảng `translation_glossaries*` đã seed; chỉ thiếu UI. |
| Mẫu thông báo (Notification-template UI) | I18N-013 | Bảng `notification_templates*` đã seed; maker-checker high/critical. |
| Nội dung chờ dịch (content-translation queue) | I18N-014+ | Bảng `content_translation*` (migration 000002). |
| Ghi đè tenant/dự án (override UI) | I18N-012 | Bảng `translation_overrides`, `tenant/project_locale_settings`. |
| AI chờ duyệt (AI draft approve/reject, mark outdated) | — | `translation_method='ai'` + review flow. |
| Mức sử dụng & chi phí | I18N-018 | Cost theo tenant/provider. |
| Nhật ký (Translation Center audit viewer riêng) | — | Hiện dùng chung `audit_logs` + AuditLogViewer. |
| RBAC chi tiết (shield `localization.*`) | — | MVP dựa hoàn toàn vào cổng `EnsurePlatformAdmin` của panel `/sa`. |
| Thêm/xóa Locale & Key qua UI | — | `canCreate()=false`; locale/key là master data từ seed/mã nguồn. |
| Namespace `category` cho khóa | — | Cột `category` KHÔNG tồn tại trong `translation_keys`; hiện dùng `description`. |

## Rủi ro / lưu ý
- Checksum phát hành dùng `TranslationPackChecksum` (canonical JSON: ksort + UNESCAPED_UNICODE|SLASHES) — PHẢI giữ giống hệt verifier Dart của app.
- Phát hành CHỈ chụp giá trị `status='published'`, `scope_type='product'`, `scope_id=''`. Bản dịch nháp/scope khác không lọt vào gói.
- Rollback không xóa; chỉ đổi `status='rolled_back'` để bản published trước đó thành active (pack service order theo `published_at desc, id desc`).
- Version tự động `rel-{Ymd-His}` có thể trùng nếu phát hành 2 lần trong cùng 1 giây cho cùng namespace/locale (unique index sẽ chặn) — nhập version thủ công khi phát hành hàng loạt sát nhau.
