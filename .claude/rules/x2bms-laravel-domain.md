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
- Dashboard totals must come from query/service classes that can be tested independently.
