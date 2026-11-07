# Backend Module Pattern

Business features live under `app/Modules/{Module}`.

```text
app/Modules/{Module}/Controllers
app/Modules/{Module}/Requests
app/Modules/{Module}/Resources
app/Modules/{Module}/Services
app/Modules/{Module}/Repositories
app/Modules/{Module}/Models
app/Modules/{Module}/Support
```

## Controllers

- Accept the HTTP request.
- Use Form Requests for validation.
- Call service methods.
- Return resources or JSON responses.

## Services

- Own business logic and workflow decisions.
- Coordinate repositories and side effects.
- Enforce module rules.

## Repositories

- Own database reads and writes.
- Return models, collections, paginators, or DTO-ready data.
- Keep business rules in services, not repositories.

