# Kế hoạch tổng — 3 luồng BQL (Cư dân/Căn hộ · Duyệt/Tài khoản · Xe/Thẻ/Ra vào)

> Lập 2026-07-03. Nguồn: 3 gói handoff `handoff/BQL/X2_BMS_BQL0{1,2,3}_*_20260703`
> đối chiếu với hệ thống hiện tại (`/admin` bespoke + DS-01 component lib) và `docs/CANONICAL_ENTITY_MAP.md`.
>
> **Quyết định chủ dự án (chốt 2026-07-03):**
> 1. **AI = rule-based inline + FAB chung.** Cảnh báo suy ra bằng luật tất định (không LLM) hiển thị theo 4 mức
>    `info/warning/high_risk/policy_block`, `policy_block` chặn nút duyệt; hỏi-đáp sinh sinh vẫn ở X2AI FAB.
> 2. **Phạm vi = phân đợt, DS-01 trước.** BQL-01 làm đầy đủ theo spec trên component DS-01 (kiêm luôn DS-01 Phase 2),
>    rồi BQL-02, rồi BQL-03. Mỗi đợt = nâng cấp màn đã có + bù màn thiếu, KHÔNG dựng lại.
> 3. **Schema = TÁI DÙNG là chính, KHÔNG migrate preemptive.** _(Cập nhật 2026-07-03 sau khi introspect live DB 326 bảng.)_
>    DB thật đã phủ ~toàn bộ nhu cầu 30 màn — **bảng mới cần ≈ 0**. Chỉ thêm **cột nullable khi 1 màn cụ thể chứng minh là thiếu**;
>    ưu tiên ánh xạ tên handoff → bảng có sẵn (xem §1). Đây đúng khuyến nghị §11.5 của `ERD_CURRENT_20260703.md`.
> 4. **Kiến trúc = giữ bespoke `/admin` Page + DS-01 Blade component** (deviation có chủ đích so với khuyến nghị
>    Filament-Resource của handoff). Mapping `*Resource::table()/view()` trong handoff chỉ là **tham chiếu**.

---

## 0. Nguyên tắc nền (áp cho mọi màn)

- **Panel:** tất cả trên `/admin` (workspace BQL). `/fila` (stock, 123 Resource) chỉ dùng làm bàn CRUD thô/kiểm thử bảng mới, không phải deliverable.
- **Scope bắt buộc:** mọi query lọc `whereIn('building_id', app(CurrentContext::class)->buildingIds() ?: [0])` + `tenant_id`. BQL chỉ thấy dự án mình.
- **Component:** dựng bằng `resources/views/components/x2/*` (đã có 23 component: `x2.kpi-row/kpi-card`, `x2.status-badge`, `x2.data-table`, `x2.filter.bar/chip`, `x2.table.bulk-actions`, `x2.record.*`, `x2.approval.detail-panel`, `x2.ai.suggestion-card`, `x2.ai-panel`, `x2.page.tabs/action-group`…). Thiếu thì bổ sung vào lib chứ không code lẻ.
- **KPI bất biến theo filter** (tổng theo context), filter chỉ tác động bảng; chip filter + `Xóa tất cả` chỉ xoá filter bảng.
- **Audit:** mọi hành động nhạy cảm (PII, gắn/hủy căn, đổi vai trò/quyền, khóa/mở, duyệt/từ chối, cấp/thu thẻ, đổi link phí, resolve conflict/anomaly, export PII) gọi `$this->audit(action, description, subjectType, subjectId)` (trait `WritesAudit`) — ghi `before/after` khi có nghĩa.
- **No hard delete** cho bản ghi đã phát sinh nghiệp vụ: dùng `archive/lock/end_relation/revoke/expire/cancel` hoặc soft-delete draft.
- **AI rule-based:** mỗi màn có "risk rules" trả `[{level, code, message, checklist[]}]`; render bằng `x2.ai.suggestion-card`/`status-badge`. `policy_block` disable nút quyết định + chỉ HQ/SuperAdmin override (ghi audit lý do override). Không gọi model. Đẩy tóm tắt màn vào FAB qua `ProvidesAiContext::shareAiContext`.
- **Cross-module event:** thay đổi `vehicle_card_fee_links` phát `VehicleCardFeeLinkChanged` để BQL-04/05 tính phí/đối soát (giai đoạn này: emit event + ghi audit + bảng outbox, listener finance làm sau).
- **Route tiếng Anh**, không lộ screen-code; giữ redirect cũ.

### Bẫy đã trả giá (bắt buộc nhớ)
- Column Filament closure **PHẢI tên `$state`** (đặt tên khác → 500 "unresolvable"). Đã dính 3 lần.
- **KHÔNG** đặt method Page trùng tên Livewire (`mount/render/dispatch/transition`…) → Fatal "must be public".
- Enum là **BackedEnum cast** → luôn `enumVal()` về scalar trước khi so sánh / làm key trong PHP/blade.
- Verify mỗi màn: `php -l` → `view:cache` → render HTTP 200 (headless kernel) → script logic hành động thật → append `docs/DEV_JOURNAL.md`.

---

## 1. Ánh xạ Entity handoff → BẢNG THẬT (introspect live DB 326 bảng, 2026-07-03)

Ký hiệu: **✅ dùng thẳng** · **♻️ tái dùng bảng khác tên** · **🧮 suy ra (không cần bảng)** · **➕col thêm cột nullable khi màn cần** · **⏳ hoãn/tùy chọn**.
**Tổng bảng mới cần ≈ 0.** `residents` đã có 40+ cột (id_no, id_issued_date/place, nationality, kyc_status, face_match_status, source, profile_status, documents json…); `apartments` có type/ownership_type/handover_date/management_fee…

### BQL-01 — Cư dân & Căn hộ
| Handoff entity | Bảng thật | TT | Ghi chú |
|---|---|---|---|
| resident_profiles | `residents` | ✅ | cột giấy tờ/kyc/source/status đã đủ; `data_quality_score` **suy ra**, không thêm cột |
| apartments | `apartments` | ✅ | `debt_snapshot`/`current_resident_count` **suy ra** từ debts/relations |
| resident_apartment_relations | `resident_apartment_relations` (+ `resident_unit_bindings` cho vòng đời) | ✅ | move-out/history suy ra từ `resident_unit_bindings.ends_at` / `apartment_status_histories`; **KHÔNG thêm cột** |
| households / household_members | (không có bảng) | 🧮 | `HouseholdRelationships` gộp theo `apartment_id` — giữ suy ra |
| residency_events | `apartment_status_histories` + relations + `resident_unit_bindings` | 🧮 | **CHỐT (2026-07-03): giữ suy ra, KHÔNG thêm cột** |
| data_quality_issues | `data_correction_requests`(+affected_records/diff_items/approvals/rollbacks) | ♻️ | workflow sửa dữ liệu có duyệt+rollback đã có |
| resident_documents | `residents.documents(json)` + `id_front/back/portrait_path` | ✅ | |
| import_batches / rows | `import_batches` / `import_batch_rows` | ✅ | đã có |

### BQL-02 — Duyệt / Gắn căn / Tài khoản
| Handoff entity | Bảng thật | TT | Ghi chú |
|---|---|---|---|
| resident_approval_requests | `resident_approval_requests` | ✅ | có match_score/document_count/status/submitted_at; `risk_level`/`assigned_to` **suy ra bằng rule** hoặc +col khi cần |
| apartment_binding_requests | `resident_binding_requests` + `resident_unit_bindings` | ♻️ | ĐÚNG luồng: request(code, requested_role, evidence_json, reviewed_by) → binding(starts_at/ends_at/status/approved_request_id) |
| account_change_requests | `data_correction_requests` (+diff_items = before/after) | ♻️ | |
| approval_conflict_cases | (view suy ra) | 🧮 | **CHỐT (2026-07-03): suy ra** hồ sơ trùng / căn bị chiếm / quyền vượt mức từ residents+relations+bindings; không bảng, không cột |
| account_role_assignments | `user_role_scopes` + `relations.role` | ✅ | |
| device_sessions | `login_sessions` (device/ip/location/last_active/is_current) | ✅ | `LoginSessions` page đã có |
| resident_accounts | `users` + `global_user_accounts` + `residents.link_status/kyc_status` | ✅ | |
| ai_recommendation_runs | `ai_suggestions` / `ai_requests` | ✅/⏳ | rule-based có thể không lưu; nếu lưu thì dùng bảng này |
| approval_requests / steps | `approval_requests` / `approval_steps` | ✅ | luồng duyệt nhiều bước (polymorphic subject) |

### BQL-03 — Xe / Thẻ / Ra vào
| Handoff entity | Bảng thật | TT | Ghi chú |
|---|---|---|---|
| vehicles | `vehicles` | ✅ | có `parking_card_no`+`monthly_fee` (đã denormalize link-phí); status varchar (linh hoạt, không cần migrate enum) |
| vehicle_registration_requests | `vehicles.status='pending'` (VehicleRequests chạy trên đây) | ✅ | không cần bảng request riêng cho MVP |
| vehicle_documents | `residents.documents`/media pattern | ✅/⏳ | denormalize; tách sau nếu cần |
| access_cards | `access_cards` (card_no/type/is_biometric/valid_from-to/status) | ✅ | type/status varchar linh hoạt |
| vehicle_card_fee_links | **denormalize trên `vehicles`** (parking_card_no+monthly_fee) | ✅ | event → BQL-04/05 phát từ thay đổi vehicle; bảng link chỉ khi cần N-N lịch sử |
| biometric_profiles | `access_cards.is_biometric` + `residents.face_match_status` | ✅ | |
| access_permission_groups / zones / schedules | `areas` + `areas.access_config`(json: zones/schedule/card_types/rules) | ➕col | **CHỐT (2026-07-03): dựng tạm trên `areas`+json** — thêm 1 cột json nullable `access_config`; KHÔNG bảng mới. RBAC `permission_groups` là khái niệm khác, không đụng |
| access_devices | `access_devices` | ✅ | |
| access_logs | `access_logs` | ✅ | dựng màn tra cứu/xuất |
| access_anomalies (+evidence) | `ioc_alerts` + `alert_actions` | ♻️ | source='access'; ack/dispatch/resolve/escalate qua alert_actions |

> **Nguyên tắc schema (thay cho "gate migrate"):** (1) KHÔNG tạo bảng preemptive; (2) mỗi lần định thêm cột/bảng, phải chỉ ra **màn cụ thể + lý do bảng hiện có không đủ**; (3) chốt danh sách delta thật (dự kiến chỉ `relations.end_date/status`) → viết vào 1 doc ánh xạ, cập nhật `CANONICAL_ENTITY_MAP` cho khớp thực tế (map đang lỗi thời — xem ERD §11.1).

---

## 2. Lộ trình thực thi

### Đợt A — Ánh xạ tên & rà cột (NHẸ, không migrate hàng loạt)
1. Viết `docs/BQL_ENTITY_NAME_MAP.md`: handoff entity → bảng thật (chốt từ §1), để mọi màn tham chiếu 1 nguồn.
2. **Không tạo bảng mới. Delta cột DUY NHẤT: `areas.access_config` (json, nullable)** cho màn BQL-03-06 — thêm khi làm màn đó. residency_events + approval_conflict + households = suy ra (0 cột).
3. Bổ sung Model/quan hệ/cast cho các bảng có sẵn nhưng chưa có Model (vd `resident_binding_requests`, `data_correction_requests`, `login_sessions`, `ioc_alerts`) nếu màn dùng tới.
4. **Seed cân bằng** cho domain sắp lên UI (ERD §10.3: nhiều domain <50 rows) — không đổi schema, chỉ thêm dữ liệu mẫu để nghiệm thu màn.
5. Vá write-path audit (ERD §10.1: `audit_logs` gần trống) — xác nhận `$this->audit()` thực sự ghi.

### Đợt B — BQL-01 Cư dân & Căn hộ (= DS-01 Phase 2, 10 màn)
Nâng cấp màn cũ về DS-01 + bù màn thiếu. Thứ tự trong đợt: 05→06→07 (căn hộ) · 01→04→03→02 (cư dân) · 08→09→10 (hộ/di chuyển/chất lượng).

| # | Màn | Route | Page hiện có | Việc |
|---|---|---|---|---|
| 01 | DS cư dân | `/admin/residents` | ResidentDirectory | nâng DS-01: filter-bar+chips+saved-view+column-config+density, KPI bất biến, export scope |
| 02 | Timeline cư dân | `/admin/residents/{id}/timeline` | ResidentTimeline | gộp vào detail hoặc trang riêng; timeline từ audit + sự kiện |
| 03 | Wizard thêm cư dân | `/admin/residents/create` | ResidentCreate | 5 bước: cá nhân→căn hộ→hộ→giấy tờ→xác nhận; panel tóm tắt/rule bên phải |
| 04 | Chi tiết 360 | `/admin/residents/{id}` | ResidentDetail | highlights + tabs (căn/hộ/xe-thẻ/công nợ/tài liệu/timeline+audit) |
| 05 | DS căn hộ | `/admin/apartments` | ApartmentDirectory | nâng DS-01 |
| 06 | Chi tiết căn hộ 360 | `/admin/apartments/{id}` | ApartmentProfile | cư dân/quan hệ/công nợ/xe-thẻ/lịch sử |
| 07 | Cây căn hộ | `/admin/apartments/tree` | ApartmentTree | nâng DS-01 + panel chi tiết + gắn/hủy nhanh |
| 08 | Quan hệ hộ gia đình | `/admin/households` | HouseholdRelationships | + bảng `households` mới; sơ đồ + thêm/kết thúc thành viên |
| 09 | Lịch sử chuyển đến/đi | `/admin/residency-events` | MoveInOutHistory | refactor sang `residency_events` + state machine (confirm/cancel/correct) |
| 10 | Chất lượng dữ liệu | `/admin/resident-data-quality` | ResidentDataQuality | + bảng `data_quality_issues`; fix/merge/gửi yêu cầu cập nhật/assign/ignore |
+ Import wizard 6 bước (dùng chung) + export scoping (dùng chung) — dựng 1 lần ở đợt này.

### Đợt C — BQL-02 Duyệt / Gắn căn / Tài khoản (10 màn)
| # | Màn | Route | Hiện có | Việc |
|---|---|---|---|---|
| 01 | Inbox duyệt | `/admin/approval-inbox` | ~my-work | inbox hợp nhất: KPI + tabs + bulk approve/reject/request-info + rule warnings |
| 02 | Chi tiết duyệt cư dân | `/admin/resident-approval-requests/{id}` | AccountApprovalDetail | căn chỉnh route + bảng đối chiếu khai↔hệ thống + panel rule risk + sticky duyệt |
| 03 | DS gắn căn hộ | `/admin/apartment-binding-requests` | ResidentBindingQueue | + bảng `apartment_binding_requests`; confidence/conflict + bulk decision |
| 04 | Chi tiết gắn căn | `/admin/apartment-binding-requests/{id}` | 🆕 | so khớp căn/occupant/vai trò + rule + quyết định/reassign |
| 05 | Hàng đợi kích hoạt TK | `/admin/resident-accounts/activations` | 🆕 | mời/resend/khóa; rule phát hiện trùng phone-email/thiết bị lạ |
| 06 | Chi tiết tài khoản & quyền | `/admin/resident-accounts/{id}` | 🆕 | role + `account_role_assignments` + `device_sessions` + quyền thanh toán |
| 07 | Yêu cầu đổi thông tin | `/admin/account-change-requests` | 🆕 | + bảng; before/after + approve/partial/reject/apply |
| 08 | Lịch sử duyệt & audit | `/admin/approval-history` | ~audit-logs | timeline chuyên biệt duyệt + export + mở chi tiết |
| 09 | Workbench xung đột | `/admin/approval-conflicts` | 🆕 | + bảng `approval_conflict_cases`; resolve/escalate |
| 10 | Trung tâm rule/AI duyệt | `/admin/approval-ai-copilot` | 🆕 (nhẹ) | tổng hợp rule warning + checklist + human-gate (không LLM) |

### Đợt D — BQL-03 Xe / Thẻ / Ra vào (10 màn)
| # | Màn | Route | Hiện có | Việc |
|---|---|---|---|---|
| 01 | DS phương tiện | `/admin/vehicles` | VehiclesAndCards (tách) | list xe + enum mới + rule (trùng biển, thiếu thẻ/phí) |
| 02 | Chi tiết xe 360 | `/admin/vehicles/{id}` | 🆕 | giấy tờ/ảnh/cư dân/căn/thẻ/phí/audit + in thẻ |
| 03 | Duyệt đăng ký xe | `/admin/vehicle-registration-requests` | VehicleRequests | + bảng `vehicle_registration_requests` + SLA + state machine |
| 04 | DS thẻ & quyền | `/admin/access-cards` | AccessCards | enum card_type/status đầy đủ + activate/lock/extend/revoke |
| 05 | Wizard cấp thẻ | `/admin/access-cards/create` | 🆕 | chủ thẻ→loại→mã/NFC→vùng→lịch→thiết bị→preview + rule |
| 06 | Nhóm quyền & khu vực | `/admin/access-permission-groups` | 🆕 | + bảng nhóm/lịch; **zones map từ `areas`**; ngoại lệ |
| 07 | Hồ sơ truy cập cư dân | `/admin/resident-access-profiles/{id}` | ResidentAccessProfile | căn chỉnh route + Face ID/QR + cảnh báo |
| 08 | Nhật ký ra vào | `/admin/access-logs` | 🆕 (data có) | tra cứu/lọc/xuất + tạo incident + rule bất thường |
| 09 | Liên kết xe–thẻ–phí | `/admin/vehicle-card-fee-links` | 🆕 | + bảng; **phát event `VehicleCardFeeLinkChanged`** + outbox |
| 10 | Workbench bất thường | `/admin/access-anomalies` | 🆕 | + bảng `access_anomalies`(+evidence); resolve/escalate/tạo rule/lock tạm |

### Đợt E — Xuyên suốt & hardening
- **Rule engine** dùng lại: `App\Support\Rules\{ApprovalRiskRules, AccessRiskRules, DataQualityRules}` trả mảng cảnh báo 4 mức + checklist; test unit từng luật (trùng biển số, thiếu giấy tờ, thẻ hết hạn còn log, đổi link phí giữa kỳ = policy_block…).
- **Event → finance:** `VehicleCardFeeLinkChanged` + bảng outbox `fee_link_events`; BQL-04/05 consume sau (giai đoạn này chỉ emit + audit + ghi outbox).
- **Import/export** hoàn thiện scope (context/filtered/selected/template) + mask PII cho role thấp + audit kèm scope + row count.
- **Policy** đầy đủ cho action nhạy cảm; `EnsureProjectContext` guard.
- **13-state** ưu tiên empty/loading/permission-denied; responsive table→card + drawer mobile.
- QA acceptance theo từng `ACCEPTANCE_CRITERIA.md` của 3 gói; verify headless đối chiếu ảnh.

---

## 3. Checklist mỗi màn
1. Đọc ảnh UI + `UI_SCREEN_CONTRACT`/`CRUD_MATRIX`/`WORKFLOW_STATE_MACHINE`/`PERMISSION_MATRIX` của màn.
2. Tạo/nâng `Page` + `getViewData()` scope building; KPI bất biến bằng `<x-x2.kpi-row>`.
3. Bảng: search/filter/sort/pagination/saved-view/column-config/density + row/bulk action.
4. Workflow action → policy tường minh + `requiresConfirmation` + lý do + `audit()` (before/after).
5. Rule warnings (4 mức) render inline; `policy_block` chặn nút; override chỉ HQ/SA + ghi audit.
6. Empty-state + seed có dữ liệu; đẩy context vào FAB.
7. `php -l` → `view:cache` → render 200 → script logic → append `DEV_JOURNAL.md`.

## 4. Rủi ro
- **Migrate bảng lõi** đang có seed → dùng `default`/backfill, không phá dữ liệu đợt trước.
- **Trùng màn cũ:** một số route đã tồn tại với slug khác (`/admin/access/*`, `/admin/resident-timeline`) → thống nhất route tiếng Anh của handoff, thêm redirect từ slug cũ.
- **`areas` vs `access_zones`:** ưu tiên tái dùng `areas`; chỉ thêm bảng nhóm/lịch. Chốt ở gate canonical.
- **Rule-based ≠ LLM:** không hứa "AI thông minh"; cảnh báo là luật tất định — ghi rõ trong UI ("Kiểm tra tự động").
- **Khối lượng 30 màn:** bám phân đợt; đóng gọn từng đợt (verify + journal) trước khi sang đợt sau.
