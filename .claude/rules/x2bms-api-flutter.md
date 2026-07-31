---
description: Versioned API and Flutter contract rules for X2-BMS.
globs:
  - "routes/api.php"
  - "app/Http/**/*.php"
  - "lib/**/*.dart"
---
# X2-BMS API and Flutter Rules

- Version mobile APIs and preserve backward compatibility for released clients.
- Define typed request/response contracts and stable error codes.
- API payloads represent user jobs, not raw database tables.
- Include stable IDs, timestamps, status and permitted actions needed by mobile.
- Validate tenant and business scope server-side for every request.
- Flutter must handle loading, empty, error, forbidden and offline/retry states.
- Do not duplicate authoritative business validation only in Flutter.
- Deep links and push payloads must resolve through permission-checked APIs.
- Any breaking contract change requires versioning or migration plan.
- Seed scenarios must be sufficient to render and test each mobile state.
