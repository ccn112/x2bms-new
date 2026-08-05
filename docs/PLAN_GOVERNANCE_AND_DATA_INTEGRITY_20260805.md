# Kế hoạch: Tuân thủ Governance (SAGOS/XHub) + Toàn vẹn dữ liệu & An toàn xóa — x2bms

> Ngày 2026-08-05. Gộp 2 việc: (A) x2bms cần làm gì để **audit-ready** dưới khung governance XHub
> (2 handoff mới), (B) kết quả **audit thao tác xóa / số liệu** (căn hộ · cư dân · phiếu thu) đang rà.
> Nguồn: `XHUB_SOFTWARE_AI_GOVERNANCE_AUDIT_READY_HANDOFF_20260805`, `XHUB_UNIFIED_AUDIT_SURVEY_AND_PRODUCT_INTAKE_20260805`.

---

## A. Governance (SAGOS) — x2bms cần làm gì với hiện tại

### A.0. Nguyên tắc (đọc trước)
- **XHub = SoR quản trị** (product intent, requirement, gate decision, control mapping, evidence metadata, audit trail). **KHÔNG xây lại governance trong x2bms.**
- Git = SoR source/PR; CI/CD = SoR build/deploy; test runner = SoR raw result. x2bms chỉ cần **sản xuất bằng chứng đúng định dạng** để XHub Audit Room tiêu thụ.
- Đây là **baseline audit-ready**, KHÔNG phải tuyên bố đạt chứng nhận. Mỗi nghĩa vụ pháp lý chỉ bật sau **applicability assessment** (lãnh thổ/ngành/loại dữ liệu/vai trò controller-processor).
- ⚠️ Đối chiếu phiên trước: 2 gói có **xung đột schema** với phần engineering-governance XHub đã build → phần đó là việc của XHub, **không đụng x2bms**. Phần **additive** đáng làm cho x2bms: **Privacy, AI governance, Control-evidence mapping**. Trích dẫn **luật VN cần cập nhật** (91/2025, 356/2025, 134/2025, 116/2025).

### A.1. Chuỗi truy vết audit phải chứng minh được (mục tiêu)
`business need → approved requirement → code change → reviewer → build → SBOM → test run → security/privacy/AI checks → go-live approval → deployment → monitoring → feedback/incident → corrective action`

### A.2. x2bms ĐÃ CÓ (map vào SAGOS) — không phải làm lại
| Lớp SAGOS | Đã có ở x2bms |
|---|---|
| Delivery | Business map (BQL/HQ/App), `PROGRESS_TRACKER.md`, commit + `DEV_JOURNAL`, test suite (feature/architecture), plan go-live |
| Trust & Compliance | `docs/security/SECURITY_CONTROLS_AND_STANDARDS.md`, `OWASP_ASVS_V4_*`, `ADR-001` tenant-scope, hard-lock tenant (composite FK + trigger + ratchet test), `DATA_LEAK_AUDIT_20260804.md`, G9/G10 gates |
| Assurance | `activity_log` (AuditLog) + `WritesAudit`/`WritesBillingAudit`, AuditLogViewer, billing reconcile tool |

### A.3. GAP cần đóng để audit-ready (ưu tiên cho go-live)
| # | Hạng mục | Việc | Ưu tiên |
|---|---|---|---|
| G-1 | **Privacy / Data governance** | Lập **PII inventory + processing activities** (dữ liệu cư dân: CCCD, SĐT, email, biển số, khuôn mặt nếu có) + cơ sở pháp lý; PIA rút gọn; rà **PII trong log** (đã ghi nợ ở data-leak audit) | **Cao** (có PII thật) |
| G-2 | **AI governance** | Đăng ký **AI use-case** đang dùng: X2AI assistant, **AI duyệt cư dân** (ApprovalRuleCenter), gợi ý; impact assessment mức rủi ro; nêu rõ **có người duyệt** (không auto-quyết) | **Cao** (đang có AI quyết trợ giúp) |
| G-3 | **Control-evidence mapping** | Bảng ánh xạ control → evidence (test/log/ADR) theo `07_XHUB_UNIFIED_CONTROL_FRAMEWORK`; gắn evidence ID, **giữ nguyên ID audit lịch sử** | Trung bình |
| G-4 | **Requirement → release traceability** | Một register nối màn/endpoint ↔ commit ↔ test ↔ release (dùng `DEVELOPMENT_TRACEABILITY_REGISTER.csv` của handoff intake) | Trung bình |
| G-5 | **Cybersecurity/DevSecOps** | SBOM + dependency/secret scan trong CI (theo `08_...`); hiện chưa có pipeline chuẩn | Trung bình (sau go-live pilot) |
| G-6 | **Go-live approval + sign-off** | Gắn gate G1–G5 (plan go-live) vào **evidence + chữ ký** (Owner/kế toán/BQL pilot) — đã có gate, thiếu artifact ký | Cao (điều kiện Go/No-Go) |
| G-7 | **Cập nhật trích dẫn luật VN** | Sửa các trích dẫn luật cũ → 91/2025, 356/2025, 134/2025, 116/2025 trong tài liệu bảo mật/privacy | Thấp |

### A.4. KHÔNG làm (ranh giới)
- Không dựng Product/Testing/Release/Audit Center **trong x2bms** — đó là XHub (SAGOS). x2bms chỉ **feed dữ liệu**.
- Không import/re-key register audit lịch sử vào x2bms; giữ ở XHub, **không sửa ID**.

---

## B. Audit thao tác xóa & số liệu (căn hộ · cư dân · phiếu thu) — kết quả rà 05/08

### B.1. Cơ chế audit hiện tại
`AuditLog` (bảng `activity_log`) + concern `WritesAudit`/`WritesBillingAudit` (`$this->audit(action, mô tả, model, id)`), gọi **thủ công** ở Action/Filament. **Rủi ro:** thao tác nào quên gọi `audit()` thì không có vết.

### B.2. Phát hiện — DELETE thiếu guard ràng buộc + cảnh báo
| Bảng | Panel | Hiện trạng | Rủi ro |
|---|---|---|---|
| `Payments` | /fila | ✅ **Đã bỏ** Edit/ForceDelete/bulk | Đạt (G10) |
| **`CashVouchers` (phiếu thu/chi)** | /fila | ⚠️ còn `ForceDeleteAction` + `DeleteBulkAction` + `ForceDeleteBulkAction` | **Bảng tiền bị force-delete được** — vi phạm bất biến tiền, không audit rõ |
| **`BillingPayments`** | /fila | ⚠️ tương tự | Như trên |
| **`Apartments` (căn hộ)** | /fila | ⚠️ ForceDelete/bulk, **không kiểm ràng buộc trước** | Xóa căn có cư dân/bảng kê/tiền → chỉ **raw DB error 1451** (composite FK), không cảnh báo thân thiện; ForceDelete có thể tạo mồ côi ở quan hệ chưa có FK |
| **`Residents` (cư dân)** | /fila | ⚠️ ForceDelete/bulk, **không kiểm ràng buộc** | Xóa cư dân còn binding/bảng kê/thanh toán → lỗi thô hoặc mồ côi |

### B.3. Việc cần làm (delete-safety)
1. **Bảng tiền không được xóa:** gỡ `ForceDeleteAction`/`DeleteBulkAction`/`ForceDeleteBulkAction` khỏi `CashVouchersTable` + `BillingPaymentsTable` (bám mẫu `PaymentsTable` đã làm). Điều chỉnh phải qua **nghiệp vụ đảo/hoàn**, không xóa.
2. **Xóa căn hộ / cư dân có ràng buộc → CẢNH BÁO trước, chặn nếu còn quan hệ:**
   - Thêm **guard đếm quan hệ** trước khi xóa (căn hộ: residents/relations/statements/payments/wallets; cư dân: bindings/relations/statements/payments). Còn quan hệ → **chặn + thông báo rõ** ("Còn N cư dân, M bảng kê — không thể xóa; hãy chuyển/đóng trước"), thay vì để DB 1451.
   - Cân nhắc **cấm ForceDelete** với các entity này (chỉ soft-delete có kiểm soát) để tránh mồ côi ở quan hệ chưa có composite FK.
3. **Audit mọi thao tác xóa:** mỗi delete/force-delete ghi `AuditLog` (ai, gì, khi, ràng buộc tại thời điểm). Tốt nhất bọc bằng **Observer/Concern dùng chung** để không phụ thuộc nhớ gọi thủ công.
4. **Số liệu:** rà các trang KPI/bảng đảm bảo **từ query/service** (không hardcode) + **tenant-scope** — theo rule "Dashboard totals must come from query/service classes".

### B.4. Kiểm thử định kỳ luồng audit trên giao diện (theo yêu cầu owner)
- Định kỳ mở **AuditLogViewer** (/admin Hệ thống) + **HqActivityLog** (/hq) đối chiếu: mỗi thao tác thật (tạo/sửa/duyệt/xóa/thu tiền) có đúng 1 dòng audit, đúng actor + tenant.
- Bộ smoke: tạo phản ánh → bình luận → duyệt → thu chứng từ → gạch nợ → xóa thử (bị chặn) — kiểm mỗi bước có vết audit + số liệu công nợ khớp trước/sau.

---

## C. Thứ tự ưu tiên (gộp A + B)
1. **B.3.1 + B.3.2** — chặn xóa bảng tiền + guard cảnh báo xóa căn hộ/cư dân (rủi ro toàn vẹn dữ liệu, làm ngay, có test).
2. **B.3.3** — audit dùng chung cho delete (đóng gate Assurance).
3. **A.3 G-6** — gắn evidence + chữ ký cho gate go-live (điều kiện Go/No-Go).
4. **A.3 G-1, G-2** — Privacy register + AI use-case register (PII + AI thật đang chạy).
5. **A.3 G-3, G-4** — control-evidence + traceability register (feed XHub Audit Room).
6. **A.3 G-5, G-7** — DevSecOps/SBOM + cập nhật trích dẫn luật VN (sau pilot).

---

*Liên quan: `docs/security/*`, `docs/adr/ADR-001-*`, `handoff/x2bms/X2_GOLIVE_PRIORITY_HANDOFF_20260805/PLAN_GOLIVE_FOCUSED_20260805.md`, memory `xhub-governance-handoffs-collision-20260805`.*
