# CANONICAL_ENTITY_MAP — X2-BMS

> Gate deliverable. **No migration may be written until a table appears here.**
> Resolves the divergent naming across the 3 handoff packages
> (`APP_RESIDENT_BQL`, `WEB_ADMIN`, `WEB_ADMIN_WEB_ACTION`) into one schema.
>
> Source of truth: `handoff/X2_BMS_MASTER_HANDOFF_20260628`
> — the three `ENTITY_MAP.yaml`, `SCREEN_DB_MAPPING.yaml`, `MASTER_MERGE_NOTES.md`,
> and the global `ENTITY_PRIORITY.md`.
>
> Rule (`MASTER_MERGE_NOTES`): **Do not duplicate entities.** If resident app, BQL queue,
> web admin list and web action form all touch the same concept, they map to ONE canonical table.

## Legend

- **Canonical table** — the single table name we create in migrations.
- **Model** — Eloquent model (English, domain-driven; never a screen code).
- **Aliases** — names used by one or more packages that map onto the canonical table. Do **not** create these as separate tables.

---

## 0. Resolved naming conflicts (read first)

These are the cases where the three packages genuinely disagreed. Decision + rationale:

| # | Concept | Package names seen | Canonical decision | Rationale |
|---|---|---|---|---|
| C1 | **Resident fee bill** (monthly maintenance/parking/utility statement) | App: `invoices`, `invoice_items` · WebAdmin/Action: `statements`, `statement_lines` (+ `billing_periods`, `billing_runs`) | **`statements` / `statement_lines`** | WebAdmin owns finance depth; "statement" = bảng kê phí kỳ. App's `invoices` is a presentation alias of the same row. Avoids clashing with SaaS invoice (C2). |
| C2 | **SaaS bill to tenant company** | WebAdmin: `tenant_invoices`, `tenant_invoice_lines` · WebAction: `subscription_invoices` | **`subscription_invoices` / `subscription_invoice_lines`** | Clearly distinct from resident statements; ties to `subscriptions`/`saas_plans`. Tier 4, not needed for pilot. |
| C3 | **Resident complaint / phản ánh** | App + WebAction: `feedback_requests` · WebAdmin: `feedback` | **`feedback_requests`** (Model `FeedbackRequest`) | 2 of 3 packages + most descriptive (a ticket/request). `feedback` is alias. |
| C4 | **SLA breach/clock events** | App: `sla_policies` · WebAdmin: `sla_events` · WebAction: `feedback_sla_events` | config = **`sla_policies`**, runtime = **`sla_events`** (polymorphic: feedback_request / work_order) | One shared SLA engine, not per-module. `feedback_sla_events` → `sla_events`. |
| C5 | **Notification recipients** | WebAdmin: `notification_audiences` · WebAction: `notification_targets` | **`notification_audiences`** (+ `notification_channels`, `notification_delivery_logs`) | `notification_targets` is alias. |
| C6 | **Work order evidence/attachments** | WebAdmin: `work_order_attachments` · WebAction: `work_order_evidence` | **`work_order_attachments`** | Single attachments table (photos/docs/signatures refs). `work_order_evidence` is alias. |
| C7 | **Contractor contract** | WebAdmin: `contracts` · WebAction: `contractor_contracts` | **`contracts`** (has `contractor_id`) | Shorter, contractor is a FK not a name prefix. |
| C8 | **Payment ↔ debt/statement allocation** | WebAdmin: `debt_allocations` · WebAction: `payment_allocations`, `debt_offsets` | **`payment_allocations`** (payment → statement_line/debt) | One allocation ledger. `debt_allocations`/`debt_offsets` are aliases. |
| C9 | **Audit trail** | both `audit_logs` and `activity_logs` everywhere | **`audit_logs`** = mandated business/admin audit (RBAC, finance, approval, security, PII, AI). `activity_logs` = optional generic activity feed | The non-negotiable audit rule binds to `audit_logs`. |
| C10 | **Technical/IOC alert** | WebAdmin: `ioc_alerts` (+ `incident_logs`) · WebAction: `technical_alerts` (+ `alert_actions`) | **`ioc_alerts`** (+ `alert_actions`) | `technical_alerts` → `ioc_alerts`. Security incidents stay separate (`security_incidents`). |
| C11 | **Resident ↔ apartment link** | App: `resident_apartment_links` / `resident_apartment_relations` · WebAdmin: `resident_apartment_relations` · WebAction: `apartment_resident_relations` | **`resident_apartment_relations`** | Most common spelling. |
| C12 | **Visitor/guest** | App: `visitor_passes`, `visitor_profiles` · WebAction: `visitor_registrations` | **`visitor_registrations`** (the request) + **`visitor_passes`** (the issued QR/pass) | Two real entities, not one — register then issue pass. |

---

## 1. Tier 1 — Foundation (must build first)

| Canonical table | Model | Aliases / notes |
|---|---|---|
| `tenants` | Tenant | management company / SaaS customer |
| `companies` | Company | operating company under a tenant |
| `projects` | Project | urban area / khu đô thị |
| `buildings` | Building | |
| `blocks` | Block | optional block within project (WebAction) |
| `floors` | Floor | |
| `areas` | Area | zones / common areas (App `areas`) |
| `apartments` | Apartment | |
| `apartment_status_histories` | ApartmentStatusHistory | |
| `users` | User | staff + resident login accounts |
| `user_role_scopes` | UserRoleScope | scope of a role to tenant/project/building (alias: `user_scopes`, `user_roles`) |
| `staff_profiles` | StaffProfile | alias: `staff_users`, `employee_profiles` |
| `residents` | Resident | alias: `resident_profiles` (merge profile fields here or 1:1) |
| `resident_apartment_relations` | ResidentApartmentRelation | see C11 |
| `departments` | Department | |
| `teams` | Team | |
| `roles` | Role | |
| `permissions` | Permission | |
| `role_permissions` | (pivot) | |
| `audit_logs` | AuditLog | see C9 — mandated audit |
| `activity_logs` | ActivityLog | optional generic activity |
| `media` | Media | shared polymorphic attachments |

## 2. Tier 2 — Resident experience MVP

| Canonical table | Model | Aliases / notes |
|---|---|---|
| `notifications` | Notification | |
| `notification_audiences` | NotificationAudience | C5; alias `notification_targets` |
| `notification_channels` | NotificationChannel | |
| `notification_delivery_logs` | NotificationDeliveryLog | alias `notification_logs` |
| `notification_reads` | NotificationRead | App per-user read state |
| `emergency_alerts` | EmergencyAlert | resident-facing emergency banner |
| `billing_periods` | BillingPeriod | kỳ phí |
| `billing_runs` | BillingRun | chạy bảng kê |
| `billing_run_items` | BillingRunItem | |
| `fee_types` | FeeType | |
| `fee_rates` | FeeRate | alias `price_lists`/`price_list_items` (WebAction form-06) |
| `fee_formulas` | FeeFormula | + `fee_formula_versions`, `fee_rules`, `fee_scope_assignments` |
| `statements` | Statement | C1 — resident fee bill; alias `invoices` |
| `statement_lines` | StatementLine | alias `invoice_items` |
| `statement_approvals` | StatementApproval | |
| `statement_publish_logs` | StatementPublishLog | |
| `debts` | Debt | công nợ |
| `payments` | Payment | |
| `payment_methods` | PaymentMethod | |
| `payment_allocations` | PaymentAllocation | C8; aliases `debt_allocations`, `debt_offsets` |
| `receipts` | Receipt | biên lai |
| `qr_payment_tokens` | QrPaymentToken | |
| `amenities` | Amenity | |
| `amenity_slots` | AmenitySlot | |
| `amenity_bookings` | AmenityBooking | |
| `booking_qr_passes` | BookingQrPass | |
| `feedback_requests` | FeedbackRequest | C3 — phản ánh; alias `feedback` |
| `feedback_categories` | FeedbackCategory | |
| `feedback_comments` | FeedbackComment | |
| `feedback_attachments` | FeedbackAttachment | |
| `feedback_assignments` | FeedbackAssignment | |
| `feedback_status_histories` | FeedbackStatusHistory | |
| `service_evaluations` | ServiceEvaluation | resident rating after resolution |
| `visitor_registrations` | VisitorRegistration | C12 |
| `visitor_passes` | VisitorPass | C12 — issued QR pass; alias `qr_passes` |
| `access_logs` | AccessLog | |
| `intercom_events` | IntercomEvent | |
| `package_deliveries` | PackageDelivery | |

## 3. Tier 3 — BQL operations MVP

| Canonical table | Model | Aliases / notes |
|---|---|---|
| `work_orders` | WorkOrder | |
| `work_order_assignments` | WorkOrderAssignment | |
| `work_order_checklists` | WorkOrderChecklist | |
| `work_order_checklist_items` | WorkOrderChecklistItem | alias `checklist_items` |
| `work_order_attachments` | WorkOrderAttachment | C6; alias `work_order_evidence` |
| `work_order_signatures` | WorkOrderSignature | e-sign; alias `e_signatures` |
| `sla_policies` | SlaPolicy | C4 config |
| `sla_events` | SlaEvent | C4 runtime (polymorphic) |
| `shifts` | Shift | + `duty_rosters` |
| `ioc_alerts` | IocAlert | C10; alias `technical_alerts` |
| `alert_actions` | AlertAction | |
| `approval_requests` | ApprovalRequest | alias `expense_approvals`, `statement_approvals` link |
| `approval_steps` | ApprovalStep | alias `approval_flows` |
| `payment_requests` | PaymentRequest | đề nghị chi |
| `cash_vouchers` | CashVoucher | phiếu thu/chi; alias `cash_receipts` |
| `funds` | Fund | + `fund_transactions`, `fund_transparency_reports` |
| `bank_accounts` | BankAccount | |
| `bank_transactions` | BankTransaction | + `bank_statement_imports` |
| `reconciliation_matches` | ReconciliationMatch | |
| `patrol_routes` | PatrolRoute | alias `security_patrol_routes` |
| `patrol_checkpoints` | PatrolCheckpoint | + `patrol_sessions` |
| `security_incidents` | SecurityIncident | alias `incident_reports` |
| `sos_alerts` | SosAlert | alias `sos_events`, `sos_cases` |
| `vehicles` | Vehicle | + `vehicle_registrations` |
| `access_cards` | AccessCard | + `card_assignments` |
| `access_devices` | AccessDevice | + `access_device_sync_logs` |
| `cameras` | Camera | |

## 4. Tier 4 — Web Admin / SaaS operation

| Canonical table | Model | Aliases / notes |
|---|---|---|
| `saas_plans` | SaasPlan | alias `plans` |
| `plan_features` | PlanFeature | |
| `subscriptions` | Subscription | + `subscription_items` |
| `subscription_invoices` | SubscriptionInvoice | C2; alias `tenant_invoices` |
| `subscription_invoice_lines` | SubscriptionInvoiceLine | alias `tenant_invoice_lines` |
| `tenant_modules` | TenantModule | module enablement / white-label |
| `usage_metering` | UsageMetering | alias `billing_usage` |
| `support_tickets` | SupportTicket | + `support_ticket_comments` |
| `data_fix_requests` | DataFixRequest | |
| `import_jobs` | ImportJob | + `import_files`, `import_mappings`, `import_rows`, `import_errors` |
| `export_jobs` | ExportJob | |
| `integration_connections` | IntegrationConnection | + `api_clients`, `webhook_endpoints`, `integration_logs` |
| `payment_gateway_configs` | PaymentGatewayConfig | |
| `contractors` | Contractor | |
| `contracts` | Contract | C7; alias `contractor_contracts` |
| `contract_packages` | ContractPackage | |
| `contract_acceptances` | ContractAcceptance | alias `acceptances` |
| `contractor_kpis` | ContractorKpi | |
| `contractor_settlements` | ContractorSettlement | |
| `assets` | Asset | + `asset_categories`, `asset_documents`, `asset_locations`, `asset_maintenance_logs` |
| `maintenance_plans` | MaintenancePlan | + `maintenance_schedules` |
| `meters` | Meter | + `meter_readings` |
| `iot_devices` | IotDevice | + `device_points`, `device_sync_logs` |
| `dynamic_forms` | DynamicForm | Form Builder: + `form_sections`, `form_fields`, `form_versions`, `form_workflows`, `form_submissions`, `form_submission_values` |

## 5. Tier 5 — Lifecycle & ecosystem

| Canonical table | Model | Aliases / notes |
|---|---|---|
| `handover_batches` | HandoverBatch | + `handover_units` |
| `handover_checklists` | HandoverChecklist | + `handover_punch_items` (alias `handover_defects`) |
| `warranty_requests` | WarrantyRequest | + `warranty_acceptances` |
| `community_posts` | CommunityPost | + `community_groups` |
| `events` | Event | + `event_registrations` |
| `polls` | Poll | + `poll_options`, `poll_votes` (alias `surveys`/`survey_questions`) |
| `marketplace_products` | MarketplaceProduct | alias `marketplace_listings` |
| `marketplace_orders` | MarketplaceOrder | + `order_items` |
| `service_providers` | ServiceProvider | + `service_orders`, `service_bookings`, `provider_settlements` |
| `loyalty_accounts` | LoyaltyAccount | + `loyalty_transactions`, `vouchers` |
| `real_estate_listings` | RealEstateListing | + `listing_media`, `listing_verifications`, `listing_inquiries`, `viewing_appointments` |
| `smart_home_accounts` | SmartHomeAccount | + `smart_devices`, `device_rooms`, `device_states`, `smart_locks`, `access_grants`, `smart_scenes`, `sensor_events`, `energy_readings` |

## 6. Tier 6 — AI / X2AI

| Canonical table | Model | Aliases / notes |
|---|---|---|
| `ai_conversations` | AiConversation | + `ai_messages` |
| `ai_requests` | AiRequest | |
| `ai_suggestions` | AiSuggestion | alias `ai_proposals` |
| `ai_approvals` | AiApproval | AI human-in-the-loop queue |
| `ai_action_logs` | AiActionLog | alias `ai_audit_traces` |
| `ai_policy_checks` | AiPolicyCheck | governance |
| `automation_workflows` | AutomationWorkflow | alias `ai_workflows`; + `automation_steps`, `automation_runs` |
| `knowledge_sources` | KnowledgeSource | alias `ai_knowledge_sources`; + `knowledge_documents`, `knowledge_chunks` |
| `prompt_templates` | PromptTemplate | |

---

## 7. Multi-tenancy columns (every business table)

Per `ARCHITECTURE.md` row-level tenancy. Add as applicable:
`tenant_id` → `project_id` → `building_id` → `apartment_id`. All business queries pass through scope by current RBAC.

## 8. Open items to confirm with owner

- C2 SaaS invoice naming (`subscription_invoices`) — confirm before Tier 4.
- Whether `residents` and `resident_profiles` are one table (1 row/resident) or split 1:1. Proposing **one** `residents` table.
- `activity_logs` vs `audit_logs` — keeping both; confirm `activity_logs` is wanted at all.
