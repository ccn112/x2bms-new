# Audit rò dữ liệu — API cư dân (BOLA/IDOR) · 2026-08-04

> **Phạm vi đợt này:** nhóm route `->prefix('resident')` (`auth:sanctum`+`ability:resident`) và 29
> controller `app/Http/Controllers/Api/V1/Resident/*` (109 endpoint có tham số). Tiêu chuẩn:
> **OWASP API Security Top 10 — API1:2023 BOLA** / **OWASP Top 10 A01 Broken Access Control**.
> **Phương pháp:** rà từng endpoint nhận định danh đối tượng (route param / field id) xem có kiểm
> đối tượng **thuộc về cư dân/căn/dự án của người đăng nhập** trước khi đọc/ghi không. *Tự đánh giá.*

## Nền tảng
Cư dân có `users.tenant_id = NULL` → global scope `tenant` **no-op** cho request cư dân → route-model
binding **trần** resolve xuyên tenant. Hàng rào scope thật là `ResidentContextService`
(apartmentIds/projectIds/buildingIds/tenantIds) + kiểm tường minh trong controller.

## Kết quả: 1 HIGH + 2 MEDIUM (đã xử lý), phần còn lại SAFE

| # | Mức | Endpoint | Vấn đề | Xử lý |
|---|---|---|---|---|
| 1 | **HIGH** | `POST community/polls/{poll}/vote` | Poll bind trần, **không kiểm project scope** → cư dân dự án A bình chọn/bóp méo khảo sát dự án/tenant B | ✅ **ĐÃ SỬA**: check `project_id ∈ projectIds` → 404. Test `CommunityPollScopeTest` |
| 2 | **MEDIUM** | `POST community/posts/{post}/comments` (@mention) | `mentioned_user_ids` chỉ kiểm "user tồn tại" → chèn activity/push tới user **xuyên tenant** (spam định hướng) | ✅ **ĐÃ SỬA**: chỉ giữ cư dân **cùng dự án**. Test `CommunityCommentMentionTest` (siết) |
| 3 | **MEDIUM→LOW** | `GET resident/articles/{article}`, `GET resident/articles` | Trả mọi `PlatformContent` published không lọc tenant/project | 🟡 **GHI NHẬN (chưa vá)**: `platform_contents` **không có cột tenant/project target** → hiện là nội dung editorial **platform-global** (không phải data riêng tenant). Vá đúng cần **cơ chế target** (feature) — xem "Còn lại" |

**Đã xác minh SAFE (scope đúng theo cư dân/căn/dự án):** tiền/công nợ/ví (`StatementController`,
`PaymentController`, `PaymentChannelController`, `DebtByAssetPaymentController`, `PaymentPreviewController`,
`WalletController`, `BillingSummaryController`, `DebtByServiceController`), phiếu/tiện ích
(`SlipCommentController`, `VisitorController`, `AmenityController`, `FeedbackController`), thông báo/chuông
(`NotificationController`, `BellController` qua `ResidentNotificationService`), cộng đồng ghi
(`CommunityPostController` show/update/destroy/react/report qua `findInScope`+`isAuthor`), chợ/BĐS
(`MarketController`, `ListingController` — cross-tenant CÓ CHỦ Ý + `findInteractable` chặn 403),
`ApartmentController`, `EmergencyAlertController`, `SosController`, `Loyalty/Offer`, `UploadController`,
`DeviceTokenController`, `NotificationPreferenceController`, `LinkPreviewController` (có chống SSRF).

## Còn lại (đưa vào backlog data-leak)
- **#3 ArticleController**: thêm cơ chế target `PlatformContent` (tenant/project/building) rồi lọc theo
  `ResidentContextService`; giữ scope `platform` là công khai. (Ưu tiên thấp — editorial, không PII/tiền.)
- **Chưa audit đợt này:** API panel BQL/HQ/SA (Filament — server-rendered, đã có `scopeVisibleTo`/
  `canManageBy` nhưng chưa rà BOLA toàn diện); **over-exposure** (Resource trả field thừa); **PII trong
  log**; RBAC chi tiết trong-tenant; MFA admin. → theo `SECURITY_CONTROLS_AND_STANDARDS.md §5`.

## Bằng chứng
Fix: `CommunityController::vote()`, `CommunityPostController::storeComment()`.
Test: `tests/Feature/CommunityPollScopeTest.php`, `CommunityCommentMentionTest.php`.
