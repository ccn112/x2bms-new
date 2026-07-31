---
description: Implement one planned X2-BMS vertical slice through domain, seed, API, Filament/mobile contract and tests.
argument-hint: <module-key> [slice-key]
---
Use the `x2bms-domain-seed-contract-delivery` skill and all X2-BMS rules.

Implement only the approved slice for `$ARGUMENTS`.

Required order:

1. Confirm plan and unresolved blockers.
2. Persistence and constraints.
3. Domain/application service.
4. Policies and scoped queries.
5. Deterministic seed.
6. API contract and implementation.
7. Filament adapter if selected.
8. Flutter contract or fixture update if selected.
9. Tests.
10. Acceptance evidence.

Do not widen scope opportunistically. Do not mark complete until tests and seed run from a clean database. Update `ACCEPTANCE_EVIDENCE.md` and report gates passed/blocked.
