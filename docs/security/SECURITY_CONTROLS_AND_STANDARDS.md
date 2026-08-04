# X2-BMS — Kiểm soát bảo mật & Ánh xạ tiêu chuẩn

> **Phiên bản:** 1.0 · **Cập nhật:** 2026-08-04 · **Trạng thái:** *Tự đánh giá nội bộ (self-assessment)*
>
> **Tuyên bố trung thực:** Tài liệu này ánh xạ các kiểm soát bảo mật **đã hiện thực trong mã nguồn**
> tới các **tiêu chuẩn/nguyên tắc được công nhận**, kèm bằng chứng (file/test/migration). Đây là
> **tự đánh giá của đội phát triển**, **CHƯA phải chứng nhận của bên thứ ba** (SOC 2 / ISO 27001 /
> PCI-DSS) và **CHƯA phải kết luận tuân thủ pháp lý** (Nghị định 13/2023/NĐ-CP — PDPD Việt Nam / GDPR).
> Mỗi kiểm soát ghi rõ trạng thái: ✅ Đã làm · 🟡 Một phần · ⬜ Kế hoạch. Dùng làm căn cứ audit và
> trao đổi cam kết với khách hàng — **đọc kèm phần "Phạm vi CHƯA phủ" (§5)**.

## 1. Tiêu chuẩn & nguyên tắc tham chiếu
| Mã | Tiêu chuẩn / Nguyên tắc | Dùng cho |
|---|---|---|
| OWASP-A01 | **OWASP Top 10 (2021) — A01 Broken Access Control** | Rò dữ liệu chéo-tenant, IDOR, phân quyền |
| OWASP-ASVS-V4 | **OWASP ASVS 4.0.3 — V4 Access Control** ("enforce server-side") — checklist: `OWASP_ASVS_V4_ACCESS_CONTROL_CHECKLIST.md` | Chốt phân quyền phía server |
| S&S | **Saltzer & Schroeder** — *complete mediation*, *fail-safe defaults*, *defense in depth* | Chặn ở nhiều tầng, tầng thấp nhất |
| AWS-SaaS | **AWS SaaS Lens (Well-Architected)** — tenant isolation | Mẫu cô lập multi-tenant |
| PG-RLS | **PostgreSQL Row-Level Security** (mẫu tham chiếu) | Hard-lock đọc (đã đánh giá, hoãn — §5) |
| G9/G10 | **Chuẩn nội bộ repo** — G9 Anti-bypass, G10 Money & Authority (`docs/delivery/03_VERTICAL_SLICE_GATES.md`) | Cổng nghiệm thu bắt buộc |

## 2. Mô hình đe doạ chính
SaaS multi-tenant single-DB (row-level theo `tenant_id`). Đe doạ trọng yếu: **rò/ghi dữ liệu chéo
tenant** (một công ty đọc/sửa/nhắm dữ liệu của công ty khác) — ánh xạ **OWASP-A01**. Nguyên tắc chốt:
**không dựa vào code đúng** — chặn ở nhiều tầng, ưu tiên tầng DB (S&S *defense in depth* + *fail-safe*).

## 3. Kiểm soát cô lập tenant — phòng thủ nhiều lớp
| Lớp | Kiểm soát | Chuẩn | Bằng chứng (file/test) | Trạng thái |
|---|---|---|---|---|
| L1 | Global scope `tenant_id` tự động mọi query; auto-fill khi tạo | OWASP-A01, AWS-SaaS | `app/Models/Concerns/BelongsToTenant.php` | ✅ |
| L1b | Platform admin bypass có kiểm soát (gated `is_platform_admin`) | OWASP-A01 | `BelongsToTenant::currentTenantId()` | ✅ |
| L2 | Phân quyền **phía server** khi nhắm đối tượng (không tin form): phạm vi + target phải trong quyền người soạn | **OWASP-ASVS-V4**, G9 | `NotificationCenter::audienceTargetOptions()` + `createNotification()` + guard Phát hành/Lưu trữ | ✅ |
| L2b | Lọc danh sách + quyền quản trị theo cấp (platform/tenant/project) | OWASP-A01 | `Notification::scopeVisibleTo()`, `canManageBy()` | ✅ |
| L3 | **Hard-lock tầng DB — write-integrity**: composite FK chống ghi lai-tenant (dù code lỗi/raw SQL) | **S&S** *complete mediation/fail-safe*, G10 | mig `..._000002` (notifications↔buildings), `..._000003` (14 quan hệ nhóm tiền) | ✅ |
| L4 | Hard-lock cho bảng junction **không có `tenant_id`** bằng TRIGGER: `payment_allocations` ép payment.tenant = statement.tenant = statement_line→statement.tenant | S&S, G10 | mig `..._000004` + `TenantCompositeFkTest` | ✅ (payment_allocations) 🟡 (junction khác) |
| L5 | **Cổng CI ratchet**: chặn sinh "cửa sau" bỏ-tenant-scope mới trên đường web | G9 | `tests/Feature/TenantScopeRatchetTest.php` + `tests/Architecture/tenant_scope_baseline.json` | ✅ |
| L6 | Test **MUST_NOT_LEAK** (bằng chứng, không niềm tin): tenant A không đọc/sửa/nhắm được tenant B | G9 | `NotificationAudienceScopeTest`, `TenantCompositeFkTest` | 🟡 (đã có cho notifications+tiền; cần lan mọi bề mặt) |

**Kỷ luật scope** (khi nào `withoutGlobalScopes()`): quy định ở **ADR-001** `docs/adr/ADR-001-tenant-scope-discipline.md`
+ rule `.claude/rules/x2bms-laravel-domain.md`.

## 4. Bất biến tiền (G10) — chống sửa/ghi sai tài chính
| Kiểm soát | Bằng chứng | Trạng thái |
|---|---|---|
| Một đường ghi tiền; UI chỉ orchestrate | `docs/delivery/03_VERTICAL_SLICE_GATES.md` §G10 | ✅ (chuẩn) |
| Bất biến ở tầng DB + command đối soát chạy độc lập | `billing:reconcile-statement-balances`, `reconcile-line-ledger` | ✅ |
| Tiền VND số nguyên, làm tròn từng dòng half-up | `docs/BILLING_OWNER_DECISIONS_20260731.md` | ✅ |
| Ghi tiền idempotent + transaction + lockForUpdate | `ResidentPaymentClaimReviewer` | ✅ |
| **Cô lập tenant cho bảng tiền** (composite FK 14 quan hệ) | mig `..._000003` | ✅ |

## 5. Phạm vi CHƯA phủ (đọc kỹ trước khi cam kết)
- ✅ **payment_allocations** (L4): đã có trigger chặn allocation nối payment↔statement/statement_line
  **lai-tenant** (chứng minh reject SQLSTATE 45000 trên MySQL). 🟡 Các bảng junction không-`tenant_id`
  khác (nếu phát sinh) cần rà tương tự.
- ⬜ **Audit data-leak TOÀN DIỆN**: mới phủ *một chiều cô lập tenant*. Chưa rà hệ thống: **IDOR từng
  endpoint**, **API trả field thừa** (over-exposure), **PII trong log**, **luồng xuất/nhập dữ liệu**,
  phân quyền theo vai trò **trong cùng tenant** (RBAC chi tiết).
- ⬜ **Hard-lock ĐỌC tầng DB (RLS)**: MySQL không có RLS; đã đánh giá migrate Postgres (~4–8 tuần) → **hoãn**.
  Hiện cô lập đọc dựa L1+L2+L5+L6 (tầng app + test), không phải DB-enforced đọc.
- ⬜ **Chứng nhận bên thứ ba / tuân thủ pháp lý**: chưa map hình thức SOC 2 / ISO 27001 / PCI-DSS /
  Nghị định 13. Tài liệu này là tự đánh giá.
- 🟡 **Lan test MUST_NOT_LEAK** ra mọi bề mặt đọc tenant (hiện mới có notifications + nhóm tiền).

## 6. Chỉ mục bằng chứng
- ADR: `docs/adr/ADR-001-tenant-scope-discipline.md`
- Gate: `docs/delivery/03_VERTICAL_SLICE_GATES.md` (G9, G10)
- Nợ kỹ thuật: `docs/delivery/TECH_DEBT_REGISTER.md` (T9)
- Code: `app/Models/Concerns/BelongsToTenant.php`, `app/Filament/Pages/NotificationCenter.php`, `app/Models/Notification.php`
- Migration: `database/migrations/2026_08_04_000002_*`, `..._000003_*`
- Test: `tests/Feature/NotificationAudienceScopeTest.php`, `TenantScopeRatchetTest.php`, `TenantCompositeFkTest.php`

## 7. Bảo trì tài liệu
Cập nhật khi: thêm/bớt kiểm soát, đóng một mục ⬜/🟡, hoặc trước mỗi kỳ audit. Mỗi thay đổi ghi ở
`docs/DEV_JOURNAL.md`. Giữ **trạng thái khớp code** (đánh ✅ cho thứ chưa có test là lỗi tài liệu — theo G9).
