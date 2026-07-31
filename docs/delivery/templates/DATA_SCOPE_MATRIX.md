# Data Scope Matrix — <module-key>

| Role | Tenant | Project | Building | Apartment | Own/assigned record | List | View | Create | Update | Transition | Export |
|---|---|---|---|---|---|---|---|---|---|---|---|

## Query scoping mechanism

## Sensitive fields

## Support/admin temporary access

## Negative isolation scenarios

- Other tenant: `MUST_NOT_LEAK_TENANT`
- Other project: `MUST_NOT_LEAK_PROJECT`
- Other building: `MUST_NOT_LEAK_BUILDING`
- Other apartment: `MUST_NOT_LEAK_APARTMENT`
