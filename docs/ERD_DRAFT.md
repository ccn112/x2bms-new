# ERD_DRAFT — X2-BMS (Tier 1–3 + pilot slice)

> Derived from `docs/CANONICAL_ENTITY_MAP.md`. Draft for owner review before building beyond the pilot.
> Notation: `*` = PK implicit `id`; `→` = FK (belongsTo). Every business table carries the tenancy chain
> `tenant_id → project_id → building_id → apartment_id` where applicable (row-level tenancy).

## Tenancy & org (Tier 1)

```
tenants (code, name)
  └─< projects (→tenant, code, name)
        └─< buildings (→tenant, →project, code, name, apartment_count)
              ├─< apartments (→tenant, →building, code, status)
              └─< departments (→tenant, →building, code, name)

users (→tenant?, →building?, name, title, is_platform_admin, email, password)
roles, permissions, role_permissions, user_role_scopes (→user, →role, scope=tenant|project|building)
residents (→tenant), resident_apartment_relations (→resident, →apartment, role=owner|tenant|member)
audit_logs (→tenant?, →building?, →user?, actor_name, action, subject_type, subject_id, description)
```

## Finance (Tier 2) — see conflict C1/C8

```
billing_periods (→tenant, →building, code, label, period_month, billed_amount, collected_amount, is_current)
  └─< statements (→billing_period, →apartment, total_amount, paid_amount, status)
        └─< statement_lines (→statement, fee_type, amount)
debts (→tenant, →building, →apartment, amount, due_date, is_overdue)
payments (→apartment, method, amount, paid_at)
  └─< payment_allocations (→payment, →statement_line|→debt, amount)   // canonical allocation ledger
fee_types, fee_rates
```
- **Resident bill = `statements`** (handoff App alias `invoices`). **SaaS bill = `subscription_invoices`** (Tier 4, separate).

## Feedback (Tier 2) — see conflict C3

```
feedback_categories (→tenant, code, name, color)
feedback_requests (→tenant, →building, →feedback_category, →apartment?, title, status, priority)   // alias: feedback
  ├─< feedback_comments (→feedback_request, →user, body)
  ├─< feedback_assignments (→feedback_request, →user/department)
  └─< feedback_status_histories (→feedback_request, from, to, →user)
service_evaluations (→feedback_request, rating)
```

## Operations (Tier 3) — see conflict C4/C6/C10

```
work_orders (→tenant, →building, →department?, →feedback_request?, code, title, status, priority, due_at)
  ├─< work_order_assignments (→work_order, →user)
  ├─< work_order_checklists (→work_order) ─< work_order_checklist_items
  ├─< work_order_attachments (→work_order)          // alias: work_order_evidence
  └─< work_order_signatures (→work_order, →user)
sla_policies (config) ; sla_events (→tenant, →building, subject morph = feedback_request|work_order, type, status)
ioc_alerts (→tenant, →building, source, severity, title, status) ─< alert_actions
```

## AI (Tier 6, partial for X2AI panel)

```
ai_suggestions (→tenant, →building?, context, title, detail, status)
ai_conversations ─< ai_messages ; ai_action_logs ; automation_workflows
```

## Status enums (domain)

- `FeedbackStatus`: new · assigned · in_progress · resolved · closed  (pending = new|assigned|in_progress)
- `WorkOrderStatus`: pending · in_progress · done · overdue
- `IocAlert.severity`: info · warning · critical
- `Statement.status`: issued · partial · paid

## Pilot slice (WEB-01-01) — implemented in M1

Tables created so far: `tenants, projects, buildings, apartments, departments, users(+scope), billing_periods, statements, statement_lines, debts, feedback_categories, feedback_requests, work_orders, sla_events, ioc_alerts, audit_logs, ai_suggestions`.

## Open items (confirm before Tier 2+ build)

1. `residents` single table vs `residents` + `resident_profiles` 1:1.
2. `payment_allocations` polymorphic target (statement_line vs debt) — confirm allocation granularity.
3. Keep `activity_logs` alongside `audit_logs`, or audit only.
4. SaaS billing naming `subscription_invoices` (Tier 4).
