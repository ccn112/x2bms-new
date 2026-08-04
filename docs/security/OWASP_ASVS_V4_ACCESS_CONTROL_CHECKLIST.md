# OWASP ASVS — V4 Access Control · Checklist ánh xạ (cô lập tenant)

> **ASVS:** 4.0.3 · **Phạm vi:** V4 Access Control, trọng tâm **cô lập multi-tenant** · **Cập nhật:** 2026-08-04
> **Trạng thái:** *Tự đánh giá nội bộ* — không phải kiểm định bên thứ ba. Đọc kèm
> `docs/security/SECURITY_CONTROLS_AND_STANDARDS.md`.
>
> Trạng thái: ✅ Đạt · 🟡 Một phần · ⬜ Chưa/ngoài phạm vi tài liệu này.

| ASVS | Yêu cầu (rút gọn) | Hiện thực trong X2-BMS | Bằng chứng | TT |
|---|---|---|---|---|
| **4.1.1** | Access control enforce ở **tầng service tin cậy (server)**, không ở client | Phân quyền nhắm đối tượng validate server (`createNotification`); lọc danh sách `scopeVisibleTo`; quyền quản trị `canManageBy`; chặn tầng DB (FK/trigger) | `NotificationCenter.php`, `Notification.php`, mig `..._000002/03/04` | ✅ |
| **4.1.2** | Thuộc tính/chính sách phân quyền **không bị người dùng sửa** trừ khi được phép | `tenant_id` lấy từ phiên đăng nhập (global scope), KHÔNG từ request; audience target validate lại theo quyền người soạn; platform-admin gated cờ DB `is_platform_admin` | `BelongsToTenant.php`, `NotificationCenter::audienceTargetOptions()` | ✅ |
| **4.1.3** | **Least privilege**: chỉ truy cập tài nguyên được cấp quyền | Scope theo tenant + `accessibleProjectIds`; HQ giới hạn công ty mình; BQL giới hạn dự án mình | `NotificationCenter::scopeOptions()/audienceTargetOptions()`, `User::accessibleProjectIds()` | 🟡 (RBAC chi tiết trong-tenant chưa audit hết) |
| **4.1.5** | Access control **fail securely** (deny by default) | Global scope lọc mặc định; validate không khớp → return/reject; DB FK/trigger từ chối ghi lai-tenant | `BelongsToTenant.php`, mig `..._000003/04` | ✅ |
| **4.2.1** | Chống **IDOR** — tham chiếu đối tượng trực tiếp được kiểm quyền | Với bề mặt đã phủ: global scope + composite FK/trigger chặn truy cập/ghi chéo tenant. **CHƯA** audit IDOR toàn bộ endpoint | `TenantScopeRatchetTest`, `TenantCompositeFkTest`, `NotificationAudienceScopeTest` | 🟡 |
| **4.3.1** | Giao diện quản trị cần xác thực mạnh hơn (MFA) | Panel admin sau đăng nhập + phân quyền; **MFA chưa bắt buộc** | — | ⬜ |
| **4.3.3** | Bảo vệ chống thao tác hàng loạt vượt quyền | Money-write qua service chuẩn + idempotent + lockForUpdate (G10) | `ResidentPaymentClaimReviewer`, gate G10 | 🟡 |

## Nguyên tắc nền (ngoài ASVS, củng cố V4)
- **Saltzer & Schroeder**: *complete mediation* (chặn ở mọi đường, kể cả raw SQL — bằng FK/trigger tầng DB),
  *fail-safe defaults* (global scope deny mặc định), *defense in depth* (L1–L6, xem SECURITY_CONTROLS §3).
- **OWASP Top 10 A01:2021 Broken Access Control** — mối đe doạ gốc mà toàn bộ mục V4 ở trên nhắm tới.

## Ngoài phạm vi checklist này (ghi để audit sau)
- IDOR **toàn diện** từng endpoint API; **over-exposure** (API trả field thừa); **PII trong log**;
  RBAC chi tiết trong cùng tenant; MFA admin; kiểm định bên thứ ba (SOC 2/ISO 27001/PCI-DSS) và
  tuân thủ Nghị định 13 (PDPD). → theo dõi ở SECURITY_CONTROLS §5 + TECH_DEBT.
