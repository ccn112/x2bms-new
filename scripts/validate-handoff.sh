#!/usr/bin/env bash
set -euo pipefail
ROOT="${1:-$(cd "$(dirname "$0")/.." && pwd)}"
required=(
  "$ROOT/README.md"
  "$ROOT/START-HERE.md"
  "$ROOT/CLAUDE_X2BMS_DELTA.md"
  "$ROOT/.claude/skills/x2bms-domain-seed-contract-delivery/SKILL.md"
  "$ROOT/.claude/commands/x2bms-audit-and-plan.md"
  "$ROOT/.claude/commands/x2bms-deliver-slice.md"
  "$ROOT/.claude/commands/x2bms-verify-slice.md"
  "$ROOT/templates/DOMAIN_CONTRACT.md"
  "$ROOT/templates/SEED_MANIFEST.md"
  "$ROOT/docs/03_VERTICAL_SLICE_GATES.md"
)
for file in "${required[@]}"; do
  [[ -s "$file" ]] || { echo "MISSING_OR_EMPTY: $file"; exit 1; }
done
grep -q '^name: x2bms-domain-seed-contract-delivery$' "$ROOT/.claude/skills/x2bms-domain-seed-contract-delivery/SKILL.md"
grep -q 'MUST_NOT_LEAK' "$ROOT/.claude/skills/x2bms-domain-seed-contract-delivery/SKILL.md"
grep -q 'Filament' "$ROOT/docs/02_FILAMENT_DECISION_MATRIX.md"
echo "VALIDATION: PASS"
