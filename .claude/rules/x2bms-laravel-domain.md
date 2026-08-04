---
description: Laravel domain and application-layer rules for X2-BMS.
globs:
  - "app/**/*.php"
  - "database/**/*.php"
  - "routes/**/*.php"
---
# X2-BMS Laravel Domain Rules

- Keep controllers thin.
- Keep Filament Resources thin.
- Put business use cases in named Actions/Application Services.
- Use policies and scoped queries consistently; UI visibility is not authorization.
- Prefer explicit enums/value objects for stable business states.
- Add database constraints for invariants that must survive every code path.
- Use transactions for multi-record state changes.
- Make retryable commands idempotent.
- Use events/jobs only when asynchronous behavior is justified and testable.
- Use Resources/DTOs for API; do not expose Eloquent models directly.
- Any query returning tenant/business data must show its scope mechanism in code and tests.
- `withoutGlobalScopes()` only in console (seed/migration/command) or when code re-applies an explicit scope right after; never on a web/API path serving tenant data unscoped. Platform-admin cross-tenant access is gated on `is_platform_admin` only (see ADR `docs/adr/ADR-001-tenant-scope-discipline.md`).
- Cross-aggregate targeting (audience/assignment pickers) must be validated server-side against the actor's accessible scope, not just filtered in the form; cover with a `MUST_NOT_LEAK` test (tenant A cannot read/act on tenant B; platform admin can).
- Dashboard totals must come from query/service classes that can be tested independently.
