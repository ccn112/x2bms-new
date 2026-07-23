# X2-BMS — Session Handoff (2026-07-23)

Tiếp nối `SESSION_HANDOFF_20260719.md`. Phiên này: khôi phục sau mất điện, cắm Rule Engine vào luồng duyệt, trả P1 mobile API, và **thiết lập cơ chế đồng bộ 2 phía** (x2mobile build API resident, phía này điều phối domain).

## 1. Phân công MỚI (quan trọng)
- **Agent x2mobile build luôn API resident bên x2bms.** Phiên D:\Code (Claude) = **điều phối đồng bộ đối tượng/domain**, KHÔNG tự viết endpoint resident.
- Nguồn chân lý: **`docs/contracts/RESIDENT_API_DOMAIN.md`** (ánh xạ endpoint→model/cột/enum + scope + bẫy) + brief ngắn **`docs/contracts/RESIDENT_API_BRIEF.md`** cho agent kia.
- Phía này giữ đồng bộ: review commit API góc domain (envelope/scope tenant-null/naming/money-string), cập nhật contract khi phát sinh.

## 2. Đã làm phiên này (commit `main`)
- **Rule Engine (Module 0)** — `app/Support/Rules/*` (RiskLevel/Finding/Report + ApprovalRiskRules + DataQualityRules), test 7/7. Cắm vào **BQL-02 màn 02** (Chi tiết duyệt: panel rủi ro + gate `policy_block`, override chỉ HQ/SA) và **màn 01** (Hàng đợi: chip rủi ro + chặn duyệt nhanh).
- **Mobile API P1** — `GET resident/billing/summary`, `GET resident/notifications` + `POST .../{id}/read`, `unread_notification_count` thật trong `me/bootstrap`. `ResidentNotificationService` (audience all/building/apartment). Verify HTTP thật (user_id=6). Agent x2mobile đã thêm `billing/summary/trend`.
- **Nền dùng chung Resident API** — `ResidentContextService::projectIds()/tenantIds()`; voucher platform (`owner_level`+`tenant_id` nullable + pivot `voucher_tenant` rollout có kỳ hạn); `loyalty_tiers`+`loyalty_tier_benefits` (+seed); `community_posts` +`is_pinned/is_important/image_paths`; AQI config ENV-ready.
- **Fix parity:** guard 2 migration `import_batches` (`ALTER…MODIFY ENUM` MySQL-only) — trước đó vỡ mọi Feature test sqlite.

## 3. Quyết định domain đã chốt (owner)
- Offers/Gifts = `vouchers` **toàn tenant** + **voucher platform** (SA hợp tác đối tác) rollout xuống tenant **có kỳ hạn**.
- Market = products + services + categories; **BĐS tách riêng** `/resident/real-estate`.
- **AQI** ← Open-Meteo free (theo `projects.latitude/longitude`), ENV-ready, owner gắn key thương mại khi prod. **An ninh = nút SOS** → bảng có sẵn `sos_alerts` (`source=app,status=triggered`).
- Loyalty tier/benefits = bảng mới (đã dựng). community_posts thêm cột (đã dựng).
- **BQL-02 màn 05** activation: dựa `GlobalUserAccount` + track thiết bị (`MobileDevice`).

## 4. Việc tiếp theo
- **Agent x2mobile:** build endpoint P2/P3 theo contract (thứ tự: Home→Loyalty/Offers→Community→Market/BĐS→P3) + AqiService. Verify HTTP thật, ghi journal, báo khi phát sinh model/field mới.
- **Phía này (Claude, không đụng API resident):** **đóng nốt BQL-01** (màn 02 timeline, 08 households, 09 residency, 10 data-quality) → rồi **BQL-02** (màn 🆕: 03/04 gắn căn, 05 kích hoạt TK, 06/07/09/10). Review đồng bộ API khi agent kia push.

## 5. Bẫy còn hiệu lực
- Cư dân `tenant_id=NULL` → luôn scope tường minh (apartment/building/project/tenant Ids), KHÔNG dựa tenant global scope.
- `statements` không có cột `currency`. DB dev sync tay → **verify HTTP thật, không sqlite**.
- Notification cư dân theo `notification_audiences` (không `scopeVisibleTo`).
- **Trước khi tạo bảng/màn: kiểm tra đã tồn tại chưa** (vụ `sos_alerts` suýt trùng) — nguyên tắc "nâng cấp, KHÔNG dựng lại".

## 6. Còn treo (nhỏ)
- Rollout voucher platform: giới hạn số lượng/tenant (chưa yêu cầu).
- Nguồn AQI thương mại khi prod (WAQI/IQAir) — chỉ đổi ENV.
- SOS: endpoint `POST /resident/sos` + notify BQL (P3, đặc tả sau).
