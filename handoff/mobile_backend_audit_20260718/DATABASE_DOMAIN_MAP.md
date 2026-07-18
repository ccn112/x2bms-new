# X2-BMS — Bản đồ Domain cơ sở dữ liệu (Database Domain Map)

> Audit READ-ONLY — 2026-07-18. Không chỉnh sửa source/migration/route/config.
> Nguồn dữ liệu: introspect trực tiếp MySQL `x2bms` (MySQL 8.0.44, qua Herd php 8.4) + đối chiếu 67 file migration + ~284 model trong `app/Models`.

## 0. Tổng quan

| Chỉ số | Giá trị |
|---|---|
| DB engine | MySQL 8.0.44, schema `x2bms` (host 127.0.0.1:3306) |
| Tổng số bảng trong schema `x2bms` | **326** (bao gồm ~15 bảng `*_archive` + bảng hạ tầng Laravel/Spatie) |
| Số file migration | **67** (`database/migrations/`), **tất cả đã chạy** (`migrate:status` = Ran) |
| Migration mới nhất | `2026_07_17_000002_add_apartment_rich_detail_fields` |
| Model Eloquent | ~284 file trong `app/Models` |
| Enum PHP thật sự | **4** (`app/Enums`: FeedbackStatus, ResidentApprovalStatus, VehicleType, WorkOrderStatus) — xem `DOMAIN_STATE_MACHINES.md` |
| Kiểu multi-tenant | Cột vô hướng `tenant_id` / `project_id` / `building_id` rải trên hầu hết bảng nghiệp vụ (không dùng schema-per-tenant) |

**Đặc điểm kiến trúc quan trọng cho mobile backend:**
- Hầu hết bảng nghiệp vụ mang bộ cột scope **`tenant_id` + `project_id` + `building_id`** (không đồng nhất — một số bảng chỉ có `tenant_id`+`building_id`, không có `project_id`). Mobile API phải luôn lọc theo scope này.
- **SoftDeletes** (`deleted_at`) có mặt trên phần lớn bảng master; các bảng lịch sử/log thường KHÔNG có `deleted_at`.
- Nhiều migration là **"batch"** tạo hàng chục bảng cùng lúc (vd `..._000005_create_operations_tables`, `..._000016_create_marketplace_ecosystem`), nên không có bảng "mồ côi không migration" đáng kể; các bảng của package Spatie (permission, activitylog, media library, schedule-monitor) do chính package tạo.
- Bảng `*_archive` (access_logs_archive, ai_requests_archive, audit_logs_archive, sla_events_archive…) được tạo bởi `2026_07_01_000025_add_soft_deletes_and_archive` để chuyển dữ liệu nguội; hiện đa số rỗng.

### Ký hiệu dùng trong bảng
- **SD** = có `deleted_at` (SoftDeletes)
- **Scope** = các cột trong {tenant_id, project_id, building_id} thực sự tồn tại
- **Audit** = có timestamps + cột người tạo/sửa (created_by / created_by_id / updated_by …)

---

## 1. Tổ chức & Danh mục lõi (Org / Structure)

Migration: `2026_06_28_000001_create_org_structure_tables`, `..._000007_create_residents_and_structure`, `2026_06_30_000003_extend_tier1_org_structure`, `2026_07_02_000001_create_hq01_project_org`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `tenants` | id | — | ✓ | (root) | Công ty vận hành (SaaS tenant). Có `status`. |
| `companies` | id | tenant_id→tenants | ✓ | tenant | `status`. |
| `projects` | id | company_id→companies, tenant_id→tenants | ✓ | tenant | **Đơn vị scope chính của /admin (BQL)**; `status`. 27 dòng. |
| `blocks` | id | project_id, tenant_id | ✓ | tenant,project | Cụm/tòa nhóm. |
| `buildings` | id | block_id→blocks, project_id, tenant_id | ✓ | tenant,project | `status`. 6 dòng. |
| `floors` | id | building_id, tenant_id | ✓ | tenant,building | 35 dòng. |
| `areas` | id | building_id, tenant_id | ✓ | tenant,building | Khu vực trong tòa. |
| `departments` | id | (tenant) | — | tenant | Phòng ban vận hành. |
| `teams` / `bql_teams` | id | — | — | tenant | Nhóm xử lý (feedback/work order). |
| `staff_profiles` | id | user | ✓ | tenant | Hồ sơ nhân sự (133 dòng). |
| `employee_project_assignments` | id | project, user | — | tenant | Gán nhân sự ↔ dự án (148 dòng). |
| `employee_assignment_histories` | id | — | — | tenant | Lịch sử điều chuyển. |

---

## 2. Cư dân & Căn hộ (Residents / Apartments) — TRỌNG TÂM MOBILE

Migration: `2026_06_28_000007`, `2026_06_30_000001_extend_resident_profile_fields`, `2026_06_30_000004_extend_residents_kyc_avatar`, `2026_06_30_000005_global_identity_and_resident_linking`, `2026_07_17_000001/000002_add_apartment_detail_fields`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `residents` | id | building_id, tenant_id, user_id→users | ✓ | tenant,building | **1306 dòng**. Nhiều cột status song song: `status`, `link_status`, `profile_status`, `kyc_status`, `residence_status`, `face_match_status`, `marital_status`. |
| `apartments` | id | building_id, floor_id, tenant_id | ✓ | tenant,building | **1305 dòng**. `status`, `furniture_status`. Batch 2026_07_17 thêm: handover_price, contract_no, contract_signed_at, ownership_term, purpose… |
| `resident_apartment_relations` | id | apartment_id, resident_id, tenant_id | — | tenant | **1306 dòng** — quan hệ N-N cư dân↔căn (vai trò owner/tenant/member). |
| `resident_emergency_contacts` | id | resident_id, tenant_id | ✓ | tenant | Liên hệ khẩn (80 dòng). |
| `apartment_status_histories` | id | apartment_id, changed_by_id→users, tenant_id | — | tenant | `from_status`/`to_status` — nhật ký đổi trạng thái căn (8 dòng). |
| `resident_approval_requests` | id | apartment_id, building_id, tenant_id | ✓ | tenant,building | Duyệt hồ sơ cư dân (`status`). 11 dòng. |
| `resident_binding_requests` | id | apartment_id, project_id, tenant_id, reviewed_by→users, **user_account_id→global_user_accounts** | ✓ | tenant,project,building | Yêu cầu gắn tài khoản SaaS ↔ căn (`status`). 10 dòng. |
| `resident_unit_bindings` | id | apartment_id, approved_request_id→resident_binding_requests, user_account_id→global_user_accounts, tenant/project/building | ✓ | full | Liên kết đã duyệt (`status`=active). |

**Mô hình danh tính SaaS (quan trọng cho mobile login):**
| Bảng | PK | Ghi chú |
|---|---|---|
| `global_user_accounts` | id | Tài khoản toàn cục (12 dòng). Cột `identity_status`, `account_status`. Liên kết cư dân theo CCCD/định danh, KHÔNG theo tên. |
| `users` | id | tenant_id, project_id, building_id | Người dùng nội bộ (admin/nhân viên) — 135 dòng; có `kyc_status`, SoftDeletes. |
| `two_factor_settings` / `login_sessions` | id | 2FA + phiên đăng nhập (revoke được). |
| `personal_access_tokens` | id | Sanctum token (0 dòng) — kênh auth API/mobile khả dụng. |
| `password_reset_tokens` | (email) | Bảng Laravel chuẩn (0 dòng); dùng cho luồng quên mật khẩu. |

---

## 3. Thông báo (Notifications)

Migration: `2026_07_01_000003_create_notifications`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `notifications` | id | tenant/project/building, created_by_id, published_by_id→users | ✓ | full | `status` (draft/scheduled/published/archived). |
| `notification_audiences` | id | notification_id | — | — | Đối tượng nhận (toàn dự án/tòa/căn/cư dân). |
| `notification_channels` | id | notification_id | — | — | Kênh (app/email/sms/zalo…). |
| `notification_reads` | id | notification_id, resident_id, user_id | — | — | Trạng thái đã đọc — **quan trọng cho badge mobile**. |
| `notification_delivery_logs` | id | notification_id, resident_id, user_id | — | — | `status` (sent…) — log gửi (+ bảng `_archive`). |

---

## 4. Phí / Bảng kê / Công nợ (Fee / Billing / Statement / Debt)

Migration: `2026_06_28_000003_create_finance_tables`, `2026_06_30_000006_create_fee_catalog`, `..._000007_create_billing_and_approvals`, `..._000009_add_approval_status_to_statements`, `..._000010_add_approval_lifecycle_to_billing_runs`, `2026_07_02_000006/000007/000008` (fee catalog / receivables / fee cycles).

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `fee_types` | id | tenant_id | ✓ | tenant | Danh mục loại phí (`status`). 38 dòng. |
| `fee_rates` | id | fee_type_id, tenant_id | ✓ | tenant | Đơn giá theo kỳ (`status`). |
| `fee_formulas` / `fee_formula_versions` | id | fee_type_id, tenant_id | ✓ | tenant | Công thức tính phí (có version). |
| `fee_scope_assignments` | id | — | — | — | Gán phí theo phạm vi. |
| `billing_periods` | id | — | — | — | Kỳ tính phí (20 dòng). |
| `billing_runs` | id | billing_period_id, building_id, created_by_id, approver_id, tenant | ✓ | tenant,building | **Đợt phát hành**; `status` (completed) + `approval_status` (pending→approved…). 7 dòng. |
| `billing_run_items` | id | billing_run_id, apartment_id, statement_id | ✓ | — | Dòng chi tiết đợt; `status`. |
| `statements` | id | apartment_id, billing_period_id, building_id, tenant | ✓ | tenant,building | **Bảng kê phí/căn — 1360 dòng**; `status` (issued/partial/paid) + `approval_status` (pending/published). |
| `statement_lines` | id | statement_id, fee_type_id | ✓ | — | **7298 dòng** — chi tiết khoản phí. |
| `statement_approvals` | id | statement_id, billing_period_id, approver_id, tenant | ✓ | tenant | `status`. |
| `statement_publish_logs` | id | — | — | — | Log phát hành bảng kê (0 dòng). |
| `debts` | id | apartment_id, building_id, tenant | ✓ | tenant,building | Công nợ căn (`recovery_status`: new/in_progress/overdue_handling). 28 dòng. |
| `debt_reminder_campaigns` | id | owner_id→users, tenant | ✓ | tenant | Chiến dịch nhắc nợ (`status`: running/paused/completed). |
| `debt_reminder_logs` | id | — | — | — | Log nhắc nợ (18 dòng). |
| `billing_adjustments` / `credit_notes` | id | — | ✓ | tenant | Điều chỉnh / giảm trừ. |

---

## 5. Thanh toán / Quỹ / Phiếu thu chi (Payment / CashFund / Receipt / Fund)

Migration: `2026_06_30_000008_create_payments_and_reconciliation`, `2026_07_01_000009_create_approvals_and_ops_finance`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `payments` | id | apartment_id, resident_id, payment_method_id, building_id, tenant | ✓ | tenant,building | Giao dịch thu (`status`: confirmed). 9 dòng. |
| `payment_allocations` | id | payment_id, debt_id, statement_id, statement_line_id | — | — | Phân bổ tiền vào bảng kê/nợ (9 dòng). |
| `payment_methods` | id | — | — | — | PTTT (tiền mặt/CK/QR…). |
| `payment_requests` | id | fund_id, approval_request_id, requested_by_id, project, tenant | ✓ | tenant,project | Đề nghị chi (`status`: draft/pending/approved/paid). |
| `receipts` | id | payment_id, issued_by_id→users, tenant | ✓ | tenant | Biên lai (9 dòng). |
| `payment_gateway_configs` | id | — | — | tenant | Cấu hình cổng thanh toán. |
| `qr_payment_tokens` | id | — | — | — | Token QR thanh toán (5 dòng) — **liên quan mobile pay**. |
| `funds` | id | project_id, tenant | ✓ | tenant,project | Quỹ (`status`). |
| `fund_transactions` | id | fund_id, cash_voucher_id, created_by_id | — | — | Giao dịch quỹ. |
| `cash_funds` | id | project_id, tenant | ✓ | tenant,project | Quỹ tiền mặt (`status`). |
| `cash_transactions` | id | cash_fund_id, created_by→users, project, tenant | — | tenant,project | Có cột **`created_by`** (không phải created_by_id). |
| `cash_vouchers` | id | fund_id, payment_request_id, created_by_id, project, tenant | ✓ | tenant,project | Phiếu thu/chi (`status`: posted). |
| `bank_accounts` / `bank_transactions` / `bank_statement_imports` | id | — | — | tenant | Đối soát ngân hàng. |
| `reconciliation_matches` | id | — | — | — | Khớp đối soát. |
| `pass_through_wallets` / `pass_through_transactions` | id | — | — | tenant | Ví thu hộ/chi hộ (4 dòng). |

---

## 6. Phản ánh & Công việc (Feedback / WorkOrder)

Migration: `2026_06_28_000004_create_feedback_tables`, `2026_07_01_000005_extend_feedback_and_children`, `2026_07_01_000008_work_orders_full_and_shifts`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `feedback_requests` | id | resident_id, apartment_id, assigned_to_id→users, feedback_category_id, team_id, project/building/tenant | ✓ | full | **147 dòng**; `status` (enum FeedbackStatus). |
| `feedback_categories` | id | — | — | tenant | Danh mục phản ánh. |
| `feedback_assignments` | id | feedback_request_id, assigned_to_id, assigned_by_id, team_id | — | — | Phân công (`status`: assigned). |
| `feedback_status_histories` | id | feedback_request_id, changed_by_id | — | — | `from_status`/`to_status`. |
| `feedback_comments` | id | feedback_request_id, resident_id, user_id | ✓ | — | Bình luận (12 dòng). |
| `feedback_attachments` | id | — | — | — | Đính kèm. |
| `work_orders` | id | feedback_request_id, apartment_id, assigned_to_id, department_id, team_id, created_by_id, project/building/tenant | ✓ | full | **195 dòng**; `status` (enum WorkOrderStatus). |
| `work_order_assignments` | id | work_order_id, assigned_to_id, assigned_by_id, team_id | — | — | `status`: assigned/done. |
| `work_order_checklists` / `_items` | id | work_order_id | ✓/— | — | Checklist nghiệm thu. |
| `work_order_attachments` / `_signatures` | id | work_order_id | — | — | Ảnh + chữ ký nghiệm thu. |
| `shifts` / `duty_rosters` | id | — | — | tenant | Ca trực / lịch trực. |
| `sla_policies` / `sla_events` | id | — | — | tenant | SLA vận hành (+ `_archive`). |

---

## 7. Xe / Thẻ / Kiểm soát ra vào (Vehicle / Card / Access)

Migration: `2026_06_29_000001_create_vehicles_cards_approvals`, `2026_07_01_000006_create_visitors_and_packages`, `2026_07_01_000010_create_security_and_access`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `vehicles` | id | apartment_id, resident_id, building_id, tenant | ✓ | tenant,building | **108 dòng**; `status` (pending→active/revoked…). |
| `access_cards` | id | apartment_id, resident_id, building_id, tenant | ✓ | tenant,building | **120 dòng**; `status`: active/revoked. |
| `access_devices` | id | building_id, project, tenant | ✓ | full | Đầu đọc/thiết bị (`status`). |
| `access_logs` | id | access_card_id, visitor_pass_id, apartment_id, resident_id, building/project/tenant | — | full | Log ra vào (`status`: granted) — **KHÔNG SoftDeletes** (+ `_archive`). |
| `visitor_registrations` | id | apartment_id, resident_id, host_user_id, approved_by_id, project/building/tenant | ✓ | full | Đăng ký khách (`status`: pending/approved/checked_in/checked_out). |
| `visitor_passes` | id | visitor_registration_id | ✓ | — | Vé khách (`status`: active/used). |
| `package_deliveries` | id | apartment_id, resident_id, received_by_id, project/building/tenant | ✓ | full | Bưu kiện (`status`: notified/received/picked_up). |
| `cameras` / `iot_devices` / `smart_devices` / `patrol_routes` / `patrol_checkpoints` / `patrol_sessions` | id | — | — | tenant | An ninh / tuần tra / IoT. |
| `sos_alerts` / `emergency_alerts` / `security_incidents` / `ioc_alerts` | id | — | — | tenant | Cảnh báo khẩn/sự cố an ninh. |

---

## 8. Tài liệu & Tri thức (Documents / Knowledge / AI)

Migration: `2026_07_01_000022_create_document_templates`, `2026_07_01_000002_knowledge_3tier_ownership`, `2026_06_30_000011_create_ai_engine_tables`, `..._000012/000013` (ai chat), `2026_07_01_000023_create_kb_ai_governance`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `documents` | id | library_id, owner_id→users, tenant | ✓ | tenant | `status`, `ai_sync_status`. |
| `document_libraries` / `document_versions` | id | — | ✓ | tenant | Thư viện + version. |
| `document_templates` | id | category_id, created_by, approved_by→users | ✓ | — | `status`; cột **`created_by`**. |
| `document_template_clones` / `_shares` / `template_assignments` | id | — | — | — | Nhân bản / chia sẻ / gán template. |
| `knowledge_articles` | id | author_id, knowledge_category_id, project, tenant | ✓ | tenant,project | **34 dòng**; `status`: draft/published. |
| `knowledge_categories` / `knowledge_documents` / `knowledge_chunks` / `knowledge_scopes` | id | — | — | tenant | KB 3-tier + chunk cho RAG. |
| `ai_requests` | id | ai_chat_session_id, user_id, tenant | — | tenant | `status`: success/pending_approval/failed (+`_archive`). |
| `ai_chat_sessions` / `ai_chat_messages` | id | — | — | tenant | Hội thoại AI. |
| `ai_approvals` | id | ai_usage_log_id, approver_id, requested_by_id, tenant | ✓ | tenant | `status`: pending… (duyệt hành động AI). |
| `ai_policies` / `ai_guardrail_policies` / `ai_prompt_templates` / `ai_workflows` / `ai_workflow_runs` / `ai_insights` / `ai_suggestions` / `ai_usage_logs` / `ai_knowledge_sources` … | id | — | — | tenant | Hạ tầng AI governance/automation (rule-based, không LLM theo master plan). |

---

## 9. Cộng đồng & Tiện ích (Community / Amenity / Booking)

Migration: `2026_07_01_000004_create_amenities_bookings`, `2026_07_01_000015_create_handover_community`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `community_posts` | id | author_resident_id→residents, community_group_id, project, tenant | ✓ | tenant,project | `status`: published. |
| `community_groups` | id | — | — | tenant,project | Nhóm cộng đồng. |
| `events` / `event_registrations` | id | project, tenant | ✓/— | tenant,project | Sự kiện (`status`: upcoming). |
| `polls` / `poll_options` / `poll_votes` | id | project, tenant | ✓ | tenant,project | Khảo sát (`status`: open). |
| `amenities` | id | building_id, project, tenant | ✓ | full | Tiện ích (`status`). |
| `amenity_slots` | id | amenity_id | ✓ | — | Khung giờ (`status`). |
| `amenity_bookings` | id | amenity_id, amenity_slot_id, apartment_id, resident_id, approved_by_id, building/tenant | ✓ | tenant,building | **Đặt chỗ mobile** — `status`: pending/confirmed/cancelled/completed/rejected. |
| `booking_qr_passes` | id | amenity_booking_id | — | — | QR check-in (`status`: active/used). |
| `handover_batches` / `handover_units` / `handover_checklists` / `handover_punch_items` | id | — | — | tenant | Bàn giao căn hộ. |
| `loyalty_accounts` / `loyalty_transactions` / `vouchers` | id | — | — | tenant | Điểm thưởng / voucher. |
| `marketplace_products` / `marketplace_orders` / `order_items` / `service_providers` / `service_orders` / `service_evaluations` | id | — | — | tenant | Chợ dịch vụ nội khu. |
| `real_estate_listings` / `listing_inquiries` / `public_projects` | id | — | — | tenant | Sàn BĐS / dự án public. |

---

## 10. SaaS Billing / Gói cước (Subscription / Plan / Usage)

Migration: `2026_07_01_000011_create_saas_billing`, `2026_07_01_000024_reconcile_saas_billing_batch07`, `2026_07_02_000002_create_hq02_billing`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `plans` | id | — | ✓ | (global) | Gói cước SaaS (**không có cột `status`**). 3 dòng. |
| `plan_prices` / `plan_features` / `features` / `modules` | id | plan_id | — | — | Giá / tính năng / module theo gói. |
| `tenant_subscriptions` | id | plan_id, contract_id, owner_user_id, tenant | ✓ | tenant | `status`: active/trial/pending_renewal/suspended. |
| `subscription_contracts` | id | responsible_user_id, tenant | ✓ | tenant | Hợp đồng (`status`: draft/active/near_expiry/expired). |
| `subscription_items` / `subscription_addons` / `subscription_renewals` | id | — | — | tenant | Dòng gói / add-on / gia hạn. |
| `plan_change_requests` / `_items` | id | from_plan_id, to_plan_id, requested_by, approved_by, project, tenant | ✓ | tenant,project | **128 dòng**; `status`: pending_approval/processing/completed/rejected. |
| `billing_invoices` / `billing_invoice_lines` | id | subscription_id, tenant | ✓ | tenant | Hóa đơn SaaS (`status`: issued/partially_paid/paid). |
| `billing_payments` / `billing_reconciliations` / `billing_rate_cards` / `billing_audit_logs` | id | — | — | tenant | Thu / đối soát / bảng giá / audit (+`_archive`). |
| `usage_meters` / `usage_periods` / `usage_records` / `quota_alerts` | id | usage_period_id, tenant | — | tenant | Đo lường usage (`status`: open/calculated/locked). |
| `tenant_entitlements` / `tenant_module_overrides` / `project_module_overrides` / `project_subscription_periods` / `config_inheritance_rules` | id | — | — | tenant/project | Quyền tính năng + kế thừa cấu hình 3 tầng. |

---

## 11. Tích hợp (Integration Center)

Migration: `2026_07_01_000026_create_integration_center_batch08`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `integration_connections` | id | category_id, owner_user_id→users | ✓ | — | `status`: active/disabled/rotated…; `sla_status`. |
| `integration_credentials` | id | — | — | — | **Bí mật kết nối** (không đọc/không xuất giá trị). `status`: valid/expiring/rotated. |
| `integration_api_keys` / `_scopes` / `_rotations` | id | owner_user_id | — | — | API key (`status`: active/revoked/suspended). |
| `webhook_endpoints` | id | event_group_id | ✓ | — | `status`: pending_verification/active/disabled. |
| `webhook_event_groups` / `webhook_delivery_attempts` | id | — | — | — | Nhóm sự kiện + log gửi (+`_archive`). |
| `integration_events` / `_retry_jobs` / `_mappings` / `_incidents` / `_connection_checks` / `_ip_allowlists` / `_rate_limits` / `_security_policies` / `_audit_logs` / `_categories` | id | — | — | — | Vận hành tích hợp (nhiều bảng có `_archive`). |
| `intercom_events` | id | — | — | — | Sự kiện chuông cửa/intercom (+`_archive`). |

---

## 12. Support Center (nội bộ SaaS)

Migration: `2026_07_01_000027_create_support_center_batch10`, `2026_07_02_000005_create_hq04_iam_support`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `support_tickets` | id | team_id, owner_id, sla_policy_id, tenant | ✓ | tenant | **318 dòng**; `status` (open/in_progress/resolved/escalated/closed/reopened/waiting_customer/new) + `sla_state`. |
| `support_ticket_messages` / `_attachments` / `_status_logs` | id | support_ticket_id, changed_by | — | — | Hội thoại + `from_status`/`to_status` (+`_archive`). |
| `support_escalations` / `support_assignments` / `support_teams` / `support_team_members` | id | — | — | tenant | Leo thang / phân công / đội hỗ trợ. |
| `support_sla_policies` / `support_sla_events` / `support_entitlements` / `support_reports` / `support_audit_logs` | id | — | — | tenant | SLA + báo cáo (+`_archive`). |
| `support_kb_articles` / `_versions` / `_categories` / `_draft_workflows` | id | — | — | tenant | KB hỗ trợ. |
| `data_correction_requests` | id | requested_by, approver_id, support_ticket_id, tenant | ✓ | tenant | Sửa dữ liệu có kiểm soát (`status`: pending_approval/approved/executed/rejected/rolled_back). |
| `data_fix_approvals` / `_executions` / `_rollbacks` / `_snapshots` / `_diff_items` / `_wizard_sessions` / `data_correction_affected_records` | id | data_correction_request_id | — | — | Wizard sửa dữ liệu + snapshot/rollback. |
| `import_batches` / `_rows` / `import_jobs` / `export_jobs` / `report_export_jobs` / `report_schedules` | id | — | — | tenant | Nhập/xuất dữ liệu, lịch báo cáo. |

---

## 13. Duyệt / Quy trình (Approval / Form Builder)

Migration: `2026_07_01_000009_create_approvals_and_ops_finance`, `2026_07_01_000014_create_form_builder`.

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `approval_requests` | id | — | — | tenant | Yêu cầu duyệt chung (`status`: pending/reviewing/approved). |
| `approval_steps` | id | — | — | — | Bước duyệt (`status`: pending/approved). |
| `dynamic_forms` / `form_sections` / `form_fields` / `form_versions` / `form_workflows` | id | — | — | tenant | Form builder động. |
| `form_submissions` / `form_submission_values` | id | — | — | tenant | Bản gửi form. |
| `automation_steps` / `checklist_templates` / `checklist_items` / `sop_templates` | id | — | — | tenant | Automation / quy trình SOP. |

---

## 14. Audit / Log / Hạ tầng

| Bảng | PK | FK chính | SD | Scope | Ghi chú |
|---|---|---|---|---|---|
| `audit_logs` | id | user_id, building_id, tenant_id | — | tenant,building | **Log nghiệp vụ chính** do các Filament Page ghi (action, subject_type, subject_id, actor_name, description). 22 dòng — **thưa** (chỉ ghi ở các trang có `WritesAudit`). +`_archive`. |
| `activity_log` | id | — | — | — | Spatie activitylog — **0 dòng** (chưa dùng). +`_archive`. |
| `activity_logs` | id | — | — | — | Bảng riêng (5 dòng) — trùng tên khái niệm, cần phân biệt với `activity_log`. |
| `billing_audit_logs` / `integration_audit_logs` / `support_audit_logs` | id | — | — | tenant | Audit theo domain (đa số 0 dòng, +`_archive`). |
| `metric_snapshots` | id | — | — | tenant | Ảnh chụp KPI dashboard (150 dòng). |
| `settings` | — | — | — | — | spatie/laravel-settings (0 dòng). |
| `media` | id | — | — | — | spatie/medialibrary (0 dòng). |
| `permissions` / `roles` / `model_has_roles` / `role_has_permissions` / `permission_groups` / `permission_group_items` | id | — | — | — | Spatie permission + nhóm quyền. |
| `user_role_scopes` | id | — | — | tenant | RBAC theo scope (tự xây — `2026_06_30_000002/000002_create_rbac_role_scopes`). |
| `cache` / `cache_locks` / `jobs` / `job_batches` / `failed_jobs` / `sessions` / `migrations` / `monitored_scheduled_tasks` / `monitored_scheduled_task_log_items` | — | — | — | — | Hạ tầng Laravel + schedule-monitor. |

---

## 15. Ghi chú cho đội Mobile Backend

1. **Scope 3 tầng luôn phải áp dụng**: mọi truy vấn dữ liệu cư dân/căn/phí phải lọc theo `tenant_id` + (`project_id` và/hoặc `building_id`). Không phải bảng nào cũng có đủ 3 cột — kiểm tra theo bảng (vd `residents`/`apartments` KHÔNG có `project_id`, chỉ có `building_id`).
2. **Định danh cư dân mobile** đi qua `global_user_accounts` (tài khoản toàn cục) ↔ `resident_unit_bindings` ↔ `apartments`/`residents`. Đăng nhập/gắn căn dùng luồng `resident_binding_requests` → duyệt → `resident_unit_bindings`.
3. **Trạng thái là string tự do** ở hầu hết bảng (chỉ 4 enum PHP) — mobile không nên hardcode; tham chiếu `DOMAIN_STATE_MACHINES.md`.
4. **audit_logs thưa** — không thể coi là nguồn "lịch sử đầy đủ"; lịch sử tin cậy nằm ở các bảng `*_status_histories` / `*_status_logs` chuyên biệt.
5. **`plans` không có cột `status`** (khác các bảng khác) — tránh query nhầm.
6. **Cột người tạo không đồng nhất**: có nơi `created_by_id`, có nơi `created_by` (vd `cash_transactions`, `document_templates`), có nơi `*_by_id` (approver_id, changed_by_id…).
7. Token mobile khả dụng qua Sanctum `personal_access_tokens` (hiện rỗng — chưa phát hành).
