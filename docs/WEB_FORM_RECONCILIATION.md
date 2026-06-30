# WEB_FORM_RECONCILIATION — Gói web-form (Web Action) ↔ Schema hiện có

> Mục tiêu: đối chiếu **bảng đích** mà gói `X2_BMS_SESSION_WEB_ADMIN_WEB_ACTION` yêu cầu
> (`ENTITY_MAP.yaml` + `SCREEN_DB_MAPPING.yaml`, 24 nhóm WEB-FORM × 4 màn = 96 màn)
> với **bảng đã migrate thật** trong repo, sau khi áp quy ước đặt tên chuẩn của
> [`CANONICAL_ENTITY_MAP.md`](CANONICAL_ENTITY_MAP.md) (C1–C12).
>
> Kết luận chung: **chỉ THÊM, không BỚT.** Mọi tên bảng "lạ" của gói đều quy về 1 bảng canonical;
> không tạo bảng trùng. Đây là bản kế hoạch (chưa code).

## Chú thích trạng thái

| Ký hiệu | Nghĩa |
|---|---|
| ✅ DONE | Bảng đã được migrate vật lý trong repo |
| 🟡 PARTIAL | Bảng đã có nhưng **thiếu cột / thiếu bảng con** form đòi |
| 🔴 TODO | Chưa tạo — cần thêm migration |
| 🔗 alias | Tên của gói, **quy về canonical**, KHÔNG tạo bảng riêng |

## Tổng quan số liệu

- **Bảng nghiệp vụ đã migrate:** ~28 (Tier 1 gần đủ + một phần Tier 2/3).
- **Round 1 (Core Data, WEB-FORM-01→08):** phần lớn bảng "đầu domain" đã có; chủ yếu **thiếu cột** + thiếu bảng con (versions/logs/approvals).
- **Round 2 (Operations, 09→16):** ~50% bảng chưa có (form builder, work-order con, nhà thầu, tài sản, IoT, quỹ/duyệt chi).
- **Round 3 (Ecosystem/SaaS/AI, 17→24):** gần như **toàn bộ TODO**.
- **Không có bảng nào cần xoá.**

---

## Bảng đã tồn tại trong repo (nguồn đối chiếu)

**Tier 1:** `tenants` 🟡, `projects` 🟡, `buildings` ✅, `apartments` ✅, `floors` ✅, `areas` ✅,
`departments` ✅, `residents` ✅, `resident_apartment_relations` ✅, `resident_emergency_contacts` ✅,
`users` ✅(+`tenant_id`,`building_id`,`title`,`is_platform_admin`), `audit_logs` ✅,
Spatie: `roles`/`permissions`/`role_has_permissions`/`model_has_roles` ✅, `activity_log` ✅, `media` ✅.

**Tier 2/3 (một phần):** `vehicles` 🟡, `access_cards` 🟡, `resident_approval_requests` ✅,
`billing_periods` 🟡, `statements` 🟡, `statement_lines` 🟡, `debts` 🟡,
`feedback_categories` ✅, `feedback_requests` 🟡, `work_orders` 🟡, `sla_events` ✅, `ioc_alerts` ✅, `ai_suggestions` ✅.

---

## ROUND 1 — Core Data

### WEB-FORM-01 · Tenant / Công ty / Dự án / Tòa / Căn hộ
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| tenants | tenants | 🟡 | Có `code,name`. **Thiếu:** liên hệ/địa chỉ, người quản lý, gói dịch vụ, branding, cấu hình app (form 01-01 có 6 section). |
| companies | companies | 🔴 | Công ty vận hành dưới tenant — chưa tạo. |
| projects | projects | 🟡 | Có `code,name`. **Thiếu:** pháp lý, liên hệ, module dự án. |
| buildings | buildings | ✅ | Có thể cần thêm `block_id`, địa chỉ. |
| blocks | blocks | 🔴 | Chưa tạo. |
| floors | floors | ✅ | |
| apartments | apartments | ✅ | Đã mở rộng (type, ownership_type, handover_date, management_fee). |
| apartment_status_histories | apartment_status_histories | 🔴 | Chưa tạo. |
| tenant_modules / tenant_settings | tenant_modules | 🔴 | Tier 4; bật/tắt module. |
| building_structures | (gộp floors+blocks) | 🔗 | Không tạo riêng. |
| media / audit_logs | media / audit_logs | ✅ | |

### WEB-FORM-02 · Cư dân / Quan hệ căn hộ / Duyệt
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| residents | residents | ✅ | Đã có profile mở rộng. |
| resident_profiles | residents | 🔗 | Gộp vào `residents` (quyết định mục 8 canonical map). |
| resident_kyc | resident_kyc | 🔴 | Tách KYC (số giấy tờ, ảnh, đối chiếu) — nên thêm. |
| resident_documents | (dùng `media`) | 🔗 | Đính kèm qua media. |
| apartment_resident_relations | resident_apartment_relations | 🔗✅ | **C11** — đã có. |
| resident_access_permissions | resident_access_permissions | 🔴 | Quyền truy cập của cư dân. |
| resident_approval_requests / approval_items | resident_approval_requests | ✅ | Có; có thể thêm bảng item nếu cần nhiều dòng. |
| ownership_transfers | ownership_transfers | 🔴 | Chuyển chủ/thuê. |
| access_revocations | access_revocations | 🔴 | Thu hồi quyền. |
| debt_transfer_logs | debt_transfer_logs | 🔴 | Bàn giao công nợ khi đổi chủ. |
| duplicate_checks | duplicate_checks | 🔴 | Chống trùng khi duyệt. |

### WEB-FORM-03 · User / IAM / RBAC / Scope
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| users | users | ✅ | |
| staff_profiles | staff_profiles | 🔴 | Hồ sơ nhân sự (1:1 user). |
| roles / permissions / role_permissions / user_roles | (Spatie) | 🔗✅ | Dùng Spatie + Filament Shield. |
| permission_overrides / permission_change_logs | permission_change_logs | 🔴 | Ghi vết đổi quyền (RBAC audit). |
| role_templates / role_workflow_rules / role_scopes | user_role_scopes | 🔴 | Scope role theo tenant/project/building. |
| user_scopes | user_role_scopes | 🔗 | Alias. |
| departments | departments | ✅ | |
| teams | teams | 🔴 | Chưa tạo. |
| login_logs / login_policies / user_devices | login_logs | 🔴 | Nhật ký & thiết bị đăng nhập. |
| scope_assignment_logs | (gộp audit_logs) | 🔗 | |

### WEB-FORM-04 · Import dữ liệu nền
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| import_jobs / import_files / import_mappings / import_rows / import_errors | import_* | 🔴 | Toàn bộ chưa tạo (Tier 4). |
| import_templates / import_mapping_fields / import_preview_rows / import_fix_suggestions | import_* con | 🔴 | |
| data_validation_rules | data_validation_rules | 🔴 | |
| import_logs / import_job_results / background_jobs | import_* + `jobs` | 🟡 | `jobs` đã có (queue); log import chưa. |

### WEB-FORM-05 · Phương tiện / Thẻ / Sinh trắc
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| vehicles | vehicles | 🟡 | Có; thiếu `vehicle_owners` tách, tài liệu. |
| vehicle_registrations | vehicle_registrations | 🔴 | Hàng đợi duyệt đăng ký xe. |
| access_cards | access_cards | 🟡 | Có; thiếu `card_assignments`, `card_access_scopes`. |
| card_assignments / card_requests | card_assignments | 🔴 | |
| parking_groups | parking_groups | 🔴 | |
| access_devices / access_device_groups / access_device_sync_logs / access_rules | access_devices (+sync_logs) | 🔴 | Đồng bộ thiết bị ra vào. |

### WEB-FORM-06 · Biểu giá / Loại phí / Cấu hình phí
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| fee_types / fee_categories | fee_types | 🔴 | Chưa tạo (statement_lines hiện chỉ lưu `fee_type` dạng string). |
| price_lists / price_list_items / price_list_versions | fee_rates | 🔴 | Alias → `fee_rates`. |
| fee_formulas / fee_formula_versions / fee_variables / fee_formula_tests | fee_formulas (+versions) | 🔴 | |
| fee_scope_assignments / fee_scope_rules / fee_scopes / price_list_scope_overrides | fee_scope_assignments | 🔴 | Áp giá theo tòa/căn/nhóm. |
| fee_accounting_mappings | fee_accounting_mappings | 🔴 | |
| approval_workflows | approval_steps | 🔗🔴 | Dùng `approval_requests/approval_steps`. |

### WEB-FORM-07 · Kỳ phí / Chạy bảng kê / Duyệt phí
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| billing_periods | billing_periods | 🟡 | Có; thiếu `billing_period_settings`, `billing_sources`. |
| billing_runs / billing_run_items / billing_preview_rows | billing_runs (+items) | 🔴 | Chạy phí. |
| statements | statements | 🟡 | Có; thiếu `statement_warnings`. |
| statement_lines / statement_line_versions / statement_line_documents | statement_lines | 🟡 | Có bảng; thiếu versioning + cột (`fee_type_id`, qty, unit_price…). |
| statement_approvals | statement_approvals | 🔴 | Duyệt phát hành. |
| statement_publish_logs | statement_publish_logs | 🔴 | |
| fee_calculation_logs / fee_adjustment_logs | (gộp audit_logs) | 🔗 | |

### WEB-FORM-08 · Thanh toán / Đối soát / Công nợ
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| payments / payment_documents | payments | 🔴 | Chưa tạo. |
| payment_allocations | payment_allocations | 🔴 | **C8** — sổ phân bổ payment→statement/debt. |
| cash_shifts | cash_shifts | 🔴 | Ca thu ngân. |
| bank_accounts | bank_accounts | 🔴 | |
| bank_statement_imports / bank_statement_files | bank_statement_imports | 🔴 | |
| bank_transactions | bank_transactions | 🔴 | |
| reconciliation_matches / reconciliation_suggestions | reconciliation_matches | 🔴 | Đối soát. |
| debts | debts | 🟡 | Có; thiếu liên kết phân bổ. |
| debt_offsets | payment_allocations | 🔗 | **C8** alias. |
| wallet_balances / ledger_entries | (mới) | 🔴 | Ví/sổ cái — cân nhắc Tier sau. |

---

## ROUND 2 — Operations

### WEB-FORM-09 · Thu chi / Quỹ / Duyệt chi
| Canonical | Trạng thái | Ghi chú |
|---|---|---|
| cash_vouchers, payment_requests, funds, fund_transactions, fund_transparency_reports | 🔴 | Toàn bộ chưa tạo (Tier 3). |
| approval_requests / approval_steps (cho duyệt chi nhiều cấp) | 🔴 | `expense_approvals` → dùng engine duyệt chung. |

### WEB-FORM-10 · Thông báo / Sự kiện / Khảo sát
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| notifications | notifications | 🔴 | |
| notification_targets | notification_audiences | 🔗🔴 | **C5**. |
| notification_channels | notification_channels | 🔴 | |
| notification_logs | notification_delivery_logs | 🔗🔴 | |
| events | events | 🔴 | |
| surveys / survey_questions | polls (+poll_options) | 🔗🔴 | Alias surveys→polls. |
| approval_requests | approval_requests | 🔴 | Duyệt gửi thông báo. |

### WEB-FORM-11 · Phản ánh / SLA / Điều phối
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| feedback_requests | feedback_requests | 🟡 | **C3** — có; thiếu cột (sla, source, assignee…). |
| feedback_categories | feedback_categories | ✅ | |
| feedback_assignments / feedback_comments | (như tên) | 🔴 | Chưa tạo. |
| feedback_sla_events | sla_events | 🔗✅ | **C4** — đã có (polymorphic). |
| work_order_feedback_links | (FK trên work_orders) | 🔗✅ | `work_orders.feedback_request_id` đã có. |

### WEB-FORM-12 · Form Builder
| Canonical | Trạng thái | Ghi chú |
|---|---|---|
| dynamic_forms, form_sections, form_fields, form_versions, form_workflows, form_submissions, form_submission_values | 🔴 | Toàn bộ chưa tạo (Tier 4). Module lớn, nên tách giai đoạn riêng. |

### WEB-FORM-13 · Work Order / Checklist
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| work_orders | work_orders | 🟡 | Có; thiếu cột (contractor, cost, scheduled_at…). |
| work_order_assignments | work_order_assignments | 🔴 | |
| work_order_checklists | work_order_checklists (+items) | 🔴 | |
| work_order_evidence | work_order_attachments | 🔗🔴 | **C6**. |
| work_order_signatures | work_order_signatures | 🔴 | |
| sla_events | sla_events | ✅ | |
*(3 màn 13-02/13-04 + 14-02 + 10-02 thiếu ảnh — `missing_need_user_reupload`.)*

### WEB-FORM-14 · Nhà thầu / Hợp đồng / Quyết toán
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| contractors | contractors | 🔴 | |
| contractor_contracts | contracts | 🔗🔴 | **C7** — có `contractor_id`. |
| contract_packages, contract_progress_reports, contract_acceptances, contractor_settlements | (như tên) | 🔴 | |

### WEB-FORM-15 · Thiết bị / Tài sản / Bảo trì
| Canonical | Trạng thái | Ghi chú |
|---|---|---|
| assets (+asset_documents, asset_locations), maintenance_plans, maintenance_schedules | 🔴 | Chưa tạo. |
| maintenance_work_orders | work_orders | 🔗 | Phiếu bảo trì = work_order loại maintenance (cân nhắc dùng chung). |

### WEB-FORM-16 · IOC / Camera / Đồng hồ / Cảnh báo
| Bảng (gói) | Canonical | Trạng thái | Ghi chú |
|---|---|---|---|
| technical_alerts | ioc_alerts | 🔗✅ | **C10** — đã có. |
| alert_actions | alert_actions | 🔴 | |
| iot_devices, device_points, device_sync_logs | iot_devices (+con) | 🔴 | |
| cameras | cameras | 🔴 | |
| meter_readings | meters (+meter_readings) | 🔴 | |

---

## ROUND 3 — Ecosystem / Portal / SaaS / AI  (gần như toàn bộ 🔴 TODO)

| WEB-FORM | Domain | Canonical (Tier) | Trạng thái |
|---|---|---|---|
| 17 | Marketplace / NCC / Loyalty | service_providers, marketplace_products/orders, loyalty_accounts (+transactions, vouchers), provider_settlements (T5) | 🔴 |
| 18 | HQ đa tòa / Chính sách dùng chung | company_policies (+versions, scopes), shared_price_lists, shared_forms, staff_assignments, multi_building_metrics | 🔴 |
| 19 | Portal CĐT/BQT/Nhà thầu/Cư dân-web | owner_portal_reports, bqt_reports, contractor_portal_tasks, resident_web_sessions, portal_access_logs (panel `/portal` riêng) | 🔴 |
| 20 | SuperAdmin / Billing SaaS | saas_plans, subscriptions, subscription_invoices (**C2**), tenant_modules, usage_metering, support_tickets, data_fix_requests (T4) | 🔴 |
| 21 | Tích hợp / API / Webhook | integration_connections, payment_gateway_configs, notification_channel_configs, api_clients, webhook_endpoints, integration_logs (T4) | 🔴 |
| 22 | X2AI / Automation / Governance | knowledge_sources (**alias** ai_knowledge_sources), automation_workflows (**alias** ai_workflows), ai_suggestions (**có**, alias ai_proposals), ai_action_logs, ai_policy_checks, automation_runs (T6) | 🟡 |
| 23 | Bàn giao / Bảo hành / Punch-list | handover_batches, handover_units, handover_checklists, handover_punch_items, warranty_requests, warranty_acceptances (T5) | 🔴 |
| 24 | An ninh / Tuần tra / Khách / SOS | patrol_routes, patrol_checkpoints, security_incidents, visitor_registrations (**C12**, +visitor_passes), sos_alerts, security_alert_actions (T3) | 🔴 |

---

## Chi tiết "chỉ thêm cột" cho các bảng Round 1 đã có (ưu tiên làm trước)

Các bảng dưới đây **đã tồn tại nhưng quá mỏng** so với form tạo/sửa — đây là phần "thêm cột" chính:

- **`tenants`** (hiện: `code,name`) → cần: `tax_code`, `phone`, `email`, `address`, `manager_user_id`, `plan`, `logo`(media), `primary_color`/`secondary_color`, `app_config`(json), `status`.
- **`projects`** (hiện: `code,name`) → cần: `address`, `legal_no`, `developer`, `handover_date`, `contact_*`, `module_config`(json), `status`.
- **`buildings`** → cần: `block_id?`, `address`, `floor_count`, `status`.
- **`statement_lines`** → cần: `fee_type_id`, `quantity`, `unit_price`, `period`, thay cho `fee_type` string.
- **`feedback_requests`** → cần: `source`, `assignee_id`, `sla_due_at`, `resolved_at`, `description`.
- **`work_orders`** → cần: `contractor_id?`, `assignee_id`, `scheduled_at`, `cost`, `type`.
- **`vehicles` / `access_cards`** → tách `vehicle_owners` / `card_assignments` nếu cần nhiều người dùng/căn.

> Quy tắc: **không sửa migration cũ đã chạy** — mỗi bổ sung là 1 migration mới (`*_extend_*` / `*_create_*`).

---

## Thứ tự dựng đề xuất (vertical slice, bám ENTITY_PRIORITY)

1. **Hoàn thiện Tier 1** (đang dở): thêm cột `tenants`/`projects`/`buildings`; tạo `companies`, `blocks`, `apartment_status_histories`, `staff_profiles`, `teams`, `user_role_scopes`, `resident_kyc`. → khớp WEB-FORM-01/02/03.
2. **Tier 2 tài chính cốt lõi:** `fee_types`/`fee_rates`/`fee_formulas` → `billing_runs` → `statement_approvals`/`publish_logs` → `payments`/`payment_allocations`/`receipts`. → WEB-FORM-06/07/08.
3. **Tier 2 còn lại:** notifications + feedback con + amenities/visitor. → WEB-FORM-10/11.
4. **Tier 3 vận hành:** work_order con, approval engine, funds/cash_vouchers, security/patrol. → WEB-FORM-09/13/24.
5. **Tier 4:** import, integration, contractors/contracts, assets/IoT, form-builder, SaaS billing. → WEB-FORM-04/14/15/16/20/21/12.
6. **Tier 5/6:** marketplace, handover/warranty, HQ đa tòa, portal, X2AI governance. → WEB-FORM-17/18/19/22/23.

Mỗi slice: migration (thêm) → model/relation/enum → seeder (đúng `SEED_DATA_CATALOG`) → Filament form khớp ảnh → verify theo `VIEW_ACCEPTANCE_CRITERIA`.
