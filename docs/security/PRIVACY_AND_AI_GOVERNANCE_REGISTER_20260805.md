# Privacy (PII) & AI Governance Register — x2bms

> 2026-08-05. Đáp ứng khung SAGOS/XHub (plan A.3 G-1/G-2). **Baseline audit-ready**, KHÔNG phải
> tuyên bố chứng nhận. Nghĩa vụ pháp lý cụ thể chỉ bật sau **applicability assessment** (lãnh thổ/loại
> dữ liệu/vai trò controller-processor). Nguồn dữ liệu: rà migrations + models thật.

## 1. Lưu ý pháp lý (VN, cập nhật 2025)
- Cập nhật trích dẫn về **Luật Bảo vệ dữ liệu cá nhân 91/2025/QH15** và các nghị định/thông tư 2025
  (356/2025, 134/2025, 116/2025) khi hoàn thiện bản pháp lý — thay các trích dẫn cũ trong `docs/security/*`.
- x2bms là **SaaS vận hành chung cư**: nhà cung cấp (SuperAdmin) là **processor**; công ty quản lý/BQL
  (HQ/BQL tenant) là **controller** với dữ liệu cư dân của mình. Ranh giới này quyết định nghĩa vụ.

## 2. PII Inventory — dữ liệu cá nhân đang xử lý
| Nhóm dữ liệu | Trường (thật) | Mục đích xử lý | Cơ sở | Lưu trữ / xóa |
|---|---|---|---|---|
| Định danh cư dân | `full_name`, `id_no` (CCCD), `date_of_birth?` | Xác thực cư trú, duyệt gắn căn hộ, xuất hóa đơn | Hợp đồng cư trú + nghĩa vụ quản lý | Xóa mềm → archive 90 ngày (`records:archive`); CCCD nhạy — hạn chế hiển thị |
| Liên hệ | `phone`, `email`, `address` | Thông báo, hỗ trợ, gửi hóa đơn/OTP | Hợp đồng + đồng ý | Như trên; email/phone dùng cho noti đa kênh (ADR-002) |
| Phương tiện | `vehicle_plate` | Thẻ ra vào, phí gửi xe | Hợp đồng dịch vụ | Theo vòng đời thẻ/xe |
| Ảnh | `avatar` (ui-avatars/upload) | Nhận diện trong app | Đồng ý | Theo tài khoản |
| Sinh trắc học | **KHÔNG lưu server** — vân tay/Face ID **chỉ ở máy** (`local_auth`) | Mở khóa app | Đồng ý tại máy | Không rời thiết bị |
| Tài chính | bảng kê/thanh toán gắn cư dân/căn hộ | Công nợ, thu phí | Hợp đồng | Bảng TIỀN **không xóa** — đảo/hoàn; giữ theo luật kế toán |

**Việc cần làm (còn nợ):** rà **PII trong log** (đã ghi ở `DATA_LEAK_AUDIT_20260804.md`) — không log CCCD/phone
trần; áp masking. Cơ chế **đồng ý + yêu cầu truy cập/xóa của chủ thể** (DSAR) — chưa có, đánh giá sau pilot.

## 3. AI Use-Case Register
Hạ tầng AI thật: `AiChatSession/Message` (Trợ lý X2AI), `AiApproval` + `ApprovalRuleCenter` (AI duyệt cư dân),
`AiSuggestion`/`AiInsight`, `AiGuardrailPolicy`/`AiPolicy` (guardrail), `AiUsageLog`/`AiRetrievalLog`/`AiTestRun`
(vết + kiểm thử), `AiProviderFactory`/`ChatService`.

| Use-case | Hệ | Dữ liệu dùng | Mức rủi ro | Người-trong-vòng | Guardrail / vết |
|---|---|---|---|---|---|
| **Trợ lý X2AI** (chat cư dân/BQL) | ChatService + provider | Câu hỏi + tri thức (KnowledgeSource) | Trung bình | N/A (chỉ trả lời, không quyết định nghiệp vụ) | `AiGuardrailPolicy` + `AiUsageLog` + `AiRetrievalLog` |
| **AI hỗ trợ DUYỆT cư dân** | `AiApproval` + `ApprovalRuleCenter` | Hồ sơ cư dân + rule | **Cao** (ảnh hưởng quyền cư trú) | **BẮT BUỘC có người duyệt** — AI chỉ đề xuất, BQL quyết | Rule center + vết duyệt + `AiApproval` log |
| Gợi ý/insight vận hành | `AiSuggestion`/`AiInsight` | Dữ liệu vận hành tổng hợp | Thấp–TB | Người dùng chọn áp dụng | UsageLog |

**Nguyên tắc (khớp ranh giới go-live):** *"AI agent tự động xử lý KHÔNG có người duyệt"* nằm **ngoài phạm vi**
go-live. Mọi quyết định nghiệp vụ (duyệt cư dân, tiền) **phải có người xác nhận**.

**Việc cần làm:** với use-case **rủi ro Cao** (AI duyệt), lập **AI impact assessment** ngắn (mục đích, dữ liệu,
sai số, cơ chế phản đối/khiếu nại, người chịu trách nhiệm) theo contract `ai-impact-assessment.schema.json`
của handoff governance → feed XHub Audit Room.

## 4. Ánh xạ SAGOS (feed XHub, không xây lại)
- Register này = **evidence** cho lớp *Trust & Compliance* (privacy, AI) của SAGOS.
- Giữ nguyên **ID audit lịch sử**; khi số hóa vào XHub thì gắn evidence ID, không sửa ID cũ.

---
*Liên quan: `docs/PLAN_GOVERNANCE_AND_DATA_INTEGRITY_20260805.md` (A.3), `docs/security/DATA_LEAK_AUDIT_20260804.md`, memory `xhub-governance-handoffs-collision-20260805`.*
