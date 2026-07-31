---
description: Filament implementation rules for X2-BMS back-office and BQL operations.
globs:
  - "app/Filament/**/*.php"
---
# X2-BMS Filament Rules

- A Resource is an adapter to the application/domain layer, not the domain itself.
- Use form/table schemas for standard administration; use a custom page for workflow or cross-aggregate operations.
- Every action must call an Action/Application Service and display domain errors safely.
- Query scoping and authorization must work even when routes/actions are called directly.
- Relation Managers must not bypass aggregate boundaries or expose data outside user scope.
- Avoid overloading one Resource with many unrelated tabs and actions.
- Use widgets only after defining metric query, scope, refresh behavior and seed scenario.
- Do not hardcode counts, amounts, trend data, status labels or demo records in widgets.
- Define empty, forbidden and error states, not only the happy list.
- Do not use Filament for resident consumer journeys, community feed, marketplace, chat or smart-home interaction.
