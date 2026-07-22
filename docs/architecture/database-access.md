# Database Access

CatalogHub is Eloquent-first. PostgreSQL 18.4 or newer is the supported
production database; SQLite and MariaDB remain automated portability targets.
The goal is portable behavior and clear ownership, not the removal of every SQL
expression at the expense of correctness or performance. The version contract
is defined in [runtime-platform.md](runtime-platform.md).

## Allowed by default

- `Model::query()` and other Eloquent entry points;
- model relationships, scopes, `whereHas()`, `withCount()`, and subqueries built
  with Eloquent;
- `DB::transaction()` in Actions, Services, Jobs, and domain infrastructure;
- schema operations in migrations.

`Model::query()` returns an Eloquent Builder. It is not a low-level query-builder
escape hatch and must not be banned.

## Restricted

- `DB::table()`, direct selects, statements, and `DB::raw()`;
- `whereRaw()`, `selectRaw()`, `orderByRaw()`, and related methods;
- transactions and database orchestration inside controllers.

Low-level access must first be replaced with relationships, scopes, Eloquent
aggregates, or Query Objects when an equivalent implementation remains efficient.

## Raw SQL exceptions

An unavoidable expression must be isolated in an explicitly approved
persistence boundary under `app/Queries` that implements
`RawSqlPersistenceBoundary`. It needs:

1. parameter bindings for every dynamic value;
2. an exact allowlist entry containing class, owner methods, raw methods, and a
   concrete reason;
3. behavioral tests on every supported database;
4. a review confirming that an Eloquent alternative would be incorrect or less
   efficient.

Literal expressions with no values declare `bindings: literal_only`; PHPStan
verifies that the SQL argument is a literal string. Driver-aware expressions
assembled only from internal identifiers declare `bindings: internal_only`.
Expressions containing values declare `bindings: required`; PHPStan verifies
that the raw method receives a separate bindings argument.

`DB::transaction()` is not a raw query and remains allowed outside controllers.
Driver inspection may be used only in isolated compatibility code.

## Compatibility testing

Migration-only CI does not prove query compatibility. Queries involving JSON,
escaped `LIKE`, NULL ordering, conditional aggregates, or calculated price
sorting must run behavioral tests against SQLite, MariaDB, and PostgreSQL.

The canonical suite is:

```bash
composer test:database-boundaries
```

CI runs this command in the MariaDB and PostgreSQL compatibility jobs; the main
test job runs the same cases on SQLite. The architecture registry test requires
every behavior-test path from an approved raw-SQL entry to appear in this suite,
so a new exception cannot silently skip cross-database execution.

## Stable pagination

Eloquent pagination belongs in a Query Object implementing
`StablePaginationBoundary`. Every paginated method must have an exact entry in
`architecture.paginationBoundaries` with its unique final ordering column and a
behavior test that reads consecutive pages containing tied primary sort values.
The test must prove that rows remain deterministic and never overlap.

The canonical suite is:

```bash
composer test:pagination-boundaries
```

## Read-only and query-count contracts

Classes under `app/Queries` and `app/Policies` are read-only. Eloquent writes,
row locks, and database transactions are rejected there by PHPStan; atomic work
belongs in an Action transaction.

High-traffic read paths also need scaling tests that compare a small result set
with a larger one and assert that database query count does not grow per row.
Current listing-card and product-offer contracts run through:

```bash
composer test:query-contracts
```
