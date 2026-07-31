---
description: Seed, fixture, isolation and acceptance test rules for X2-BMS.
globs:
  - "database/seeders/**/*.php"
  - "database/factories/**/*.php"
  - "tests/**/*.php"
---
# X2-BMS Seed and Testing Rules

- Use deterministic keys and values for assertions.
- Factories may randomize incidental fields; scenario seeders must not randomize business-critical fields.
- Separate base catalog seed from demo scenario seed.
- Include positive, pending, rejected, overdue and forbidden scenarios.
- Include `MUST_NOT_LEAK` records in another tenant/project/building/apartment scope.
- Every feature that lists or views records needs at least one negative isolation test.
- Seeders must be idempotent or provide an explicit reset command.
- Never depend on execution order unless dependencies are declared.
- CI must be able to migrate, seed and run the critical journey from a clean database.
