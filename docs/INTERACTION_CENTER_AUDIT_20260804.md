# Audit — Resident Interaction Center (handoff v1.1) · 2026-08-04

Nguồn: `handoff/x2mobile/X2_BMS_RESIDENT_INTERACTION_CENTER_HANDOFF_V1.1_20260803_FLAT`.
Mục tiêu: read-model HỢP NHẤT `/api/v1/resident/interactions/*` (summary + list + detail + common actions),
gom phiếu đa module về 1 view; giữ nguyên endpoint nghiệp vụ nguồn (create/edit).

## Current → Target mapping (nguồn resident có thật)
| Type (target) | Subtype | Bảng nguồn | Chủ sở hữu (scope) | Status nguồn |
|---|---|---|---|---|
| `feedback` Phản ánh | — | `feedback_requests` (152) | apartment_id/resident_id/user_id | new,assigned,in_progress,resolved,closed |
| `service_request` YCDV | `guest_registration` | `visitor_registrations` (10) | apartment/resident/host_user_id | pending,approved,checked_in,checked_out,cancelled |
| `service_request` | `amenity_booking` | `amenity_bookings` (14) | apartment/resident/user_id | pending,confirmed,completed,cancelled,rejected |
| `service_request` | `vehicle_card_request` | `resident_binding_requests` (10) | apartment/user_account_id | pending,approved,need_more_info,rejected,cancelled |
| `payment_confirmation` Xác nhận TT | — | `payments` (18) | apartment/resident | pending,confirmed |

## Không map được (ghi rõ)
- **`support_suggestion`**: `support_tickets` (318) là hệ hỗ trợ **SA/nền tảng** (module/environment/sla_policy/channel), **KHÔNG có cột resident/apartment/user** → không phải phiếu của cư dân. → tab "Góp ý hỗ trợ" tạm **rỗng** (hoặc gom vào feedback category "suggestion" sau). Cần nguồn resident-owned mới.
- **`access_request` (Ra vào)**: **không có bảng** nguồn → subtype tạm ẩn.

## Status family hợp nhất (map về 5 họ)
`new · in_progress · waiting_resident · done · cancelled`
- feedback: new→new; assigned,in_progress→in_progress; resolved,closed→done.
- payment: pending→in_progress; confirmed→done.
- visitor: pending→new; approved,checked_in→in_progress; checked_out→done; cancelled→cancelled.
- amenity: pending→new; confirmed→in_progress; completed→done; cancelled,rejected→cancelled.
- binding: pending→new; approved→done; need_more_info→waiting_resident; rejected,cancelled→cancelled.

## Quyết định kiến trúc
- **Adapter/union theo query** (không tạo bảng projection lúc này): mỗi cư dân ít phiếu → gom + sort + phân trang ở tầng service. ⚠️ Khi quy mô lớn → chuyển sang **projection table** `resident_interactions` (materialized). Ghi TECH_DEBT.
- KPI summary chỉ theo **context căn hộ/dự án**, KHÔNG theo filter list (đúng layout override).
- Scope theo cư dân: `apartment_id ∈ apartmentIds` HOẶC `resident_id ∈ residentIds` HOẶC `user_id = user.id` — mỗi nguồn (đúng chuẩn BOLA đã audit).
- Common actions (comment/cancel/reopen/rating): unified endpoint **định tuyến về service nguồn** theo `source_type`; create/edit giữ endpoint chuyên biệt.

## Kế hoạch giao (staged)
1. **Nền backend (slice này):** `InteractionAggregator` + `GET interactions/summary` + `GET interactions` (list, filter q/type/subtype/status_family/sort/cursor) + capabilities + test.
2. Detail resolver + common actions (định tuyến nguồn).
3. **UI Flutter** (Trung tâm tương tác) theo layout override + acceptance + screenshot đối chiếu.
