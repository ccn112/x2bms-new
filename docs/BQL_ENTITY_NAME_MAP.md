# BQL 3-luồng — Ánh xạ tên handoff → BẢNG/MODEL THẬT

> Lập 2026-07-03. Nguồn: introspect live MySQL `x2bms` (326 bảng) + `app/Models`.
> **Quy tắc:** màn nào cũng tham chiếu bảng thật ở đây. **KHÔNG tạo bảng mới.**
> Delta cột đã chốt: **duy nhất `areas.access_config` (json, nullable)** cho BQL-03-06.
> Ba khái niệm suy ra (0 bảng, 0 cột): households · residency_events · approval_conflict_cases.

## Ký hiệu
✅ dùng thẳng · ♻️ tái dùng (khác tên) · 🧮 suy ra · ➕col thêm cột khi làm màn.

## BQL-01 — Cư dân & Căn hộ
| Handoff | Bảng thật | Model | TT |
|---|---|---|---|
| resident_profiles | `residents` (40+ cột: id_no, id_issued_date/place, nationality, kyc_status, face_match_status, source, profile_status, documents json) | `Resident` | ✅ |
| apartments | `apartments` (type, ownership_type, handover_date, management_fee, bedroom/bathroom_count) | `Apartment` | ✅ |
| resident_apartment_relations | `resident_apartment_relations` + vòng đời qua `resident_unit_bindings` | `ResidentApartmentRelation` / `ResidentUnitBinding` | ✅ |
| households / household_members | gộp theo `apartment_id` | — | 🧮 |
| residency_events (move-in/out/transfer) | `apartment_status_histories` + `resident_unit_bindings.starts_at/ends_at` | `ApartmentStatusHistory` / `ResidentUnitBinding` | 🧮 |
| data_quality_issues | `data_correction_requests` (+affected_records/diff_items/approvals/rollbacks) hoặc suy ra | `DataCorrectionRequest` | ♻️ |
| resident_documents | `residents.documents` (json) + `id_front_path`/`id_back_path`/`portrait_path` | `Resident` | ✅ |
| import_batches / rows | `import_batches` / `import_batch_rows` | `ImportBatch` | ✅ |

## BQL-02 — Duyệt / Gắn căn / Tài khoản
| Handoff | Bảng thật | Model | TT |
|---|---|---|---|
| resident_approval_requests | `resident_approval_requests` (match_score, document_count, status, submitted_at) | `ResidentApprovalRequest` | ✅ |
| apartment_binding_requests | `resident_binding_requests` (code, requested_role, evidence_files_json, reviewed_by, review_note) | `ResidentBindingRequest` | ♻️ |
| → gắn căn sau duyệt | `resident_unit_bindings` (role, status, starts_at, ends_at, approved_request_id) | `ResidentUnitBinding` | ♻️ |
| account_change_requests | `data_correction_requests` + diff_items (before/after) | `DataCorrectionRequest` | ♻️ |
| approval_conflict_cases | suy ra (trùng hồ sơ / căn bị chiếm / quyền vượt) từ residents+relations+bindings | — | 🧮 |
| account_role_assignments | `user_role_scopes` (scope_type/tenant/project/building) + `relations.role` | `UserRoleScope` | ✅ |
| device_sessions | `login_sessions` (device, ip_address, location, last_active_at, is_current) | `LoginSession` | ✅ |
| resident_accounts | `users` + `global_user_accounts` + `residents.link_status/kyc_status/linked_at` | `GlobalUserAccount` | ✅ |
| ai_recommendation_runs | `ai_suggestions` (rule-based có thể không lưu) | `AiSuggestion` | ✅/⏳ |
| approval_requests / steps (đa bước) | `approval_requests` / `approval_steps` (polymorphic subject) | `ApprovalRequest` / `ApprovalStep` | ✅ |

## BQL-03 — Xe / Thẻ / Ra vào
| Handoff | Bảng thật | Model | TT |
|---|---|---|---|
| vehicles | `vehicles` (plate_no, type, brand, `parking_card_no`, `monthly_fee`, status, valid_to) | `Vehicle` | ✅ |
| vehicle_registration_requests | `vehicles.status='pending'` (VehicleRequests chạy trên `Vehicle::query()`) | `Vehicle` | ✅ |
| vehicle_documents | `residents.documents`/media pattern | — | ⏳ |
| access_cards | `access_cards` (card_no, type, is_biometric, valid_from/to, status) | `AccessCard` | ✅ |
| vehicle_card_fee_links | denormalize trên `vehicles` (parking_card_no + monthly_fee); event→BQL-04/05 phát từ đổi vehicle | `Vehicle` | ✅ |
| biometric_profiles | `access_cards.is_biometric` + `residents.face_match_status` | `AccessCard`/`Resident` | ✅ |
| access_permission_groups / zones / schedules | `areas` + **`areas.access_config` (json)** ← delta cột duy nhất | `Area` | ➕col |
| access_devices | `access_devices` | `AccessDevice` | ✅ |
| access_logs | `access_logs` | `AccessLog` | ✅ |
| access_anomalies (+evidence) | `ioc_alerts` (source='access') + `alert_actions` (ack/dispatch/resolve/escalate) | `IocAlert` / `AlertAction` | ♻️ |

## Audit
Mọi hành động → `audit_logs` qua trait `WritesAudit::audit()`. ⚠️ ERD §10.1: `audit_logs` gần trống → xác nhận write-path thực ghi khi build.
