---
description: Audit one X2-BMS module and produce a complete vertical-slice plan without implementing it.
argument-hint: <module-key>
---
Use the `x2bms-domain-seed-contract-delivery` skill.

Audit module `$ARGUMENTS`.

Do not change production code in this command. Create or update:

- `docs/modules/$ARGUMENTS/MODULE_BRIEF.md`
- `docs/modules/$ARGUMENTS/DOMAIN_CONTRACT.md`
- `docs/modules/$ARGUMENTS/DATA_SCOPE_MATRIX.md`
- `docs/modules/$ARGUMENTS/STATE_MACHINE.md` if needed
- `docs/modules/$ARGUMENTS/FILAMENT_DECISION.md`
- `docs/modules/$ARGUMENTS/SEED_MANIFEST.md`
- `docs/modules/$ARGUMENTS/API_CONTRACT.md`
- `docs/modules/$ARGUMENTS/TEST_MATRIX.md`
- `docs/modules/$ARGUMENTS/IMPLEMENTATION_PLAN.md`

Find existing implementation, duplicates, migrations, policies, API and Flutter dependencies. Select the smallest demonstrable vertical slice and list exact files expected to change. End with blockers and a go/no-go recommendation.
