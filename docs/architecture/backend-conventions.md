# Backend conventions

This guide makes the Section 0 backend boundaries enforceable without imposing a repository or CQRS layer on simple code.

- Data, DTO, and value-object types are transport-free: they do not import `Illuminate\\Http\\Request` or read request globals.
- Domain code does not import Filament or HTTP controller namespaces. Presentation adapts a domain result; it does not become a domain dependency.
- Orchestrating mutations live in `*Action` classes. Their parameters carry the actor, target site, and requested state explicitly; actions own authorization and transactions.
- Read boundaries live in `*Query` classes and are read-only. A local-site query receives `Site` explicitly and applies a named site scope.
- Expected application failures use the typed exceptions documented in [error-boundaries.md](error-boundaries.md). Unexpected failures retain the request ID and normal logging path.
- Request correlation is request-scoped and audit rows use the same value; see [request-correlation.md](request-correlation.md).

The PHPStan rules under `tools/phpstan` enforce presentation validation, authorization, Eloquent, mutation, transaction, raw-SQL, and pagination boundaries. Unit architecture tests cover dependency direction, boundary naming, and representative violations. These checks deliberately avoid line counts and implementation-size rules.

When a new exception to a rule is necessary, first prefer a named action, query, scope, policy, or value object. An unavoidable raw-SQL exception follows [raw-sql-exceptions.md](raw-sql-exceptions.md); temporary static-analysis debt follows [technical-debt-suppressions.md](technical-debt-suppressions.md). Architecture rule suppressions are not allowed.
