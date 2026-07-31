---
name: x2bms-domain-seed-contract-delivery
description: Use when planning, implementing, reviewing, refactoring, seeding, testing, or completing any X2-BMS module involving Laravel, Filament, Flutter, residents, apartments, buildings, fees, payments, feedback, visitors, amenities, handover, warranty, community, marketplace, notifications, BQL operations, multi-tenancy, permissions, APIs, dashboards, or mobile contracts.
---
# X2-BMS Domain, Seed and Contract Delivery

## Goal

Deliver one production-shaped vertical slice at a time so Claude can move quickly without creating disconnected CRUD, empty screens, fake dashboards or incompatible mobile APIs.

## Core delivery equation

```text
Fast delivery = narrow scope × explicit contract × deterministic seed × reusable framework × automated gates
```

Filament accelerates back-office only after the domain and data contract are clear.

## Required inputs

Before implementation, inspect:

- Existing migrations, models, enums and factories.
- Tenant/project/building/apartment scoping mechanism.
- Policies, gates, roles and permissions.
- Existing Filament panels/resources/pages/widgets.
- API routes, Resources/DTOs and error format.
- Flutter models, repositories, state management and routes.
- Existing seeders and demo accounts.
- Tests and CI commands.

Do not assume versions or architecture from documentation when source differs.

## Mandatory artifacts per module

Create under `docs/modules/<module-key>/`:

1. `MODULE_BRIEF.md`
2. `DOMAIN_CONTRACT.md`
3. `DATA_SCOPE_MATRIX.md`
4. `STATE_MACHINE.md` when status transitions exist
5. `FILAMENT_DECISION.md`
6. `SEED_MANIFEST.md`
7. `API_CONTRACT.md`
8. `TEST_MATRIX.md`
9. `IMPLEMENTATION_PLAN.md`
10. `ACCEPTANCE_EVIDENCE.md`

Use templates from `docs/delivery/templates/`.

**Artifact theo tầng — chỉnh khi cài vào repo x2bms 2026-07-31.** Repo có ~100 màn; bắt
đủ 10 artifact cho mọi màn CRUD danh mục sẽ không ai duy trì nổi, và repo này **đã có
drift tài liệu**. Áp theo mức rủi ro:

| Loại module | Artifact bắt buộc |
|---|---|
| **Tiền, quyền, danh tính, kiểm duyệt** | Đủ 10 + **G9 + G10** |
| Nghiệp vụ có state machine (phản ánh, work order, booking) | 1, 2, 3, 4, 6, 7, 8, 10 |
| CRUD danh mục / master data | 1, 3, 6, 8 (BRIEF + SCOPE + SEED + TEST) |

Áp đúng tinh thần "Never select Filament merely because it is available" cho cả artifact:
đừng viết artifact chỉ vì template có sẵn.

## Workflow

### Phase 0 — Audit

1. Locate existing implementation and duplicates.
2. Identify source of truth for every important field.
3. Map current route → UI → query → model → table.
4. Record gaps, incompatible naming and risky migrations.
5. Decide whether to extend, refactor or replace.

Output only audit and plan until the module boundary is stable.

### Phase 1 — Domain contract

Define:

- Aggregate/root entities.
- Entity relationships and cardinality.
- Invariants and validation.
- Status/state transitions.
- Commands and business outcomes.
- Events/notifications.
- Audit requirements.
- Ownership and system of record.

For resident identity, distinguish at minimum:

```text
User account
Resident profile
Apartment
Household relationship
Ownership/occupancy role
Verification request
Access grant
```

Do not collapse these concepts into one table for UI convenience.

### Phase 2 — Data scope and security

Define access at these levels when applicable:

```text
Platform
Tenant
Project/urban area
Building/block
Floor
Apartment
Household relationship
Record ownership/assignment
```

For each role/action, state:

- Can list?
- Can view?
- Can create?
- Can update?
- Can transition state?
- Can export?
- Can view personal/financial data?

Every scoped query must have negative tests proving that forbidden records are not returned.

### Phase 3 — Persistence

Implement or adjust:

- Migrations.
- Foreign keys and unique constraints.
- Composite indexes matching scoped queries.
- Enums/value objects/casts.
- Models and explicit relationships.
- Soft delete/history strategy.
- Audit fields.

Avoid generic JSON columns for core searchable business data unless an ADR justifies them.

### Phase 4 — Deterministic seed

Create realistic Vietnamese scenario data, not lorem ipsum.

Each seed pack must include:

- Tenant/project/building/apartment hierarchy.
- Users and role assignments.
- Happy path records.
- Pending/blocked/overdue/error states.
- Permission boundary records.
- Cross-scope marker records such as `MUST_NOT_LEAK`.
- Dates and amounts useful for dashboards.
- Stable keys for test lookup.

Seed must be rerunnable or resettable and must never depend on random values for assertions.

### Phase 5 — Application layer

Business rules belong in:

- Application services.
- Actions/commands.
- Domain services.
- State transition handlers.
- Jobs/listeners where asynchronous.

Controllers, Filament actions and Flutter repositories should orchestrate these services, not reimplement rules.

### Phase 6 — API contract

Define before wiring mobile:

- Versioned endpoint.
- Request DTO and validation.
- Response Resource/DTO.
- Pagination/filter/sort rules.
- Error envelope and error codes.
- Permission and scope behavior.
- Idempotency for retryable commands.
- Concurrency/version checks where needed.
- Deep-link identifiers and notification payload.

API must not mirror database columns blindly.

### Phase 7 — Filament decision

Choose exactly one:

1. **Filament Resource** — standard CRUD/back-office.
2. **Filament Resource + Relation Managers** — aggregate management with bounded relations.
3. **Custom Filament Page/Widget** — operational dashboard or cross-aggregate workflow.
4. **Custom Livewire inside Filament** — interaction not expressible safely by schemas.
5. **API/Flutter only** — resident/mobile consumer experience.
6. **Separate web frontend** — high-interaction feed/chat/marketplace/public portal.

Never select Filament merely because it is available.

### Phase 8 — Flutter contract

When mobile-facing, define:

- DTOs and serialization.
- Repository methods.
- State and loading/empty/error/offline states.
- Route and deep link.
- Permission-dependent UI.
- Optimistic update or retry behavior.
- Push notification action.
- Analytics events where used.

Flutter screen work begins only after seed and API contract are usable.

### Phase 9 — Tests

Minimum test set:

- Migration/schema assertions where valuable.
- Unit tests for domain rules.
- Feature tests for commands and state transitions.
- Policy/permission tests.
- API contract tests.
- Cross-tenant and cross-scope negative tests.
- Seeder smoke test.
- Filament page/resource test for critical actions.
- Regression test for the user journey.

### Phase 10 — Evidence and closure

Record:

- Commands executed.
- Test output.
- Seed accounts and stable scenario keys.
- API examples.
- Screenshots or routes.
- Known limitations.
- Rollback steps.
- Next smallest slice.

## Filament acceleration rules

Use Filament aggressively for:

- Master data.
- Resident/apartment administration.
- Fee and amenity configuration.
- Back-office lists and filters.
- BQL operational queues.
- Controlled state actions.
- Internal reports and dashboards.

Do not use Filament as primary UX for:

- Resident home.
- Social/community feed.
- Marketplace.
- Chat/realtime collaboration.
- Consumer booking journey.
- Mobile payment journey.
- Rich smart-home control.

## Screen and flow discipline

For every screen:

1. Name the user job.
2. Name the source query/API.
3. Name seed scenarios that populate it.
4. Name actions and permission checks.
5. Name empty/loading/error/forbidden states.
6. Name the next screen/deep link.

A screen without these six mappings is not ready to build.

## Stop conditions

Stop implementation and update the plan when:

- Two existing models claim the same concept.
- Tenant or project scope is ambiguous.
- A destructive migration is required.
- UI demands a field without a source of truth.
- API contract conflicts with released Flutter usage.
- A state transition lacks authority or audit owner.
- Seed cannot represent the intended workflow.

## Output format after each run

Report:

```text
Module:
Slice:
Gates passed:
Gates blocked:
Files changed:
Seed scenarios:
Tests run:
Evidence:
Risks:
Next smallest slice:
```
