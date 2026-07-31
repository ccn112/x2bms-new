---
description: Verify an X2-BMS slice against domain, seed, scope, API, Filament/mobile and acceptance gates.
argument-hint: <module-key> [slice-key]
---
Use the `x2bms-domain-seed-contract-delivery` skill.

Review `$ARGUMENTS` as an independent verifier.

Check:

- Contract matches implementation.
- No business rule is trapped in UI adapters.
- Seed is deterministic and covers all screen states.
- API is typed/versioned and scope-safe.
- Filament decision is appropriate.
- Cross-tenant/project/building/apartment negative tests exist.
- Dashboard values are traceable.
- Clean migrate + seed + test succeeds.
- Evidence is complete.

Create `docs/modules/<module-key>/VERIFICATION_REPORT.md` with PASS/FAIL per gate. Do not silently fix large problems; log them as actionable findings.
