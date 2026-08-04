# P00-004 — Tests, Quality Tools And CI Inventory

Snapshot date: 2026-08-04  
Policy: inventory only; Composer/package scripts and workflows were not changed.

## Local suites and scripts

| Surface | Inventory | Entry command |
| --- | --- | --- |
| PHPUnit Unit | 61 files; includes 14 architecture files after Phase 0.1 verification | `php artisan test tests/Unit` |
| PHPUnit Feature | 429 files across database, admin, public, domain, services and smoke areas | `php artisan test tests/Feature` |
| Full PHP suite | 490 files (486 pre-audit plus four Phase 0.1 contracts); `phpunit.xml` defines Unit and Feature suites | `composer test` |
| Architecture contracts | architecture PHPUnit directory plus debt registry report | `composer test:architecture` |
| Static analysis | Larastan/PHPStan level 5; app, config, database, routes, tests and tools | `composer analyse -- --no-progress` |
| Formatting | Pint with Laravel preset | `composer format:test` (check), `composer format` (write) |
| Frontend build | Vite production build | `npm run build` |
| Frontend lint/unit tests | no scripts or configuration discovered | missing gate |
| Browser tests | no Dusk/Playwright/Cypress suite discovered | missing gate |
| Visual regression | one HTTP/markup visual-smoke test; no screenshot comparison suite | missing deterministic screenshot gate |

Special Composer suites select database-boundary, pagination-boundary and query-contract tests. One explicit test skip exists in `NormalizedProductDraftsMigrationTest` for SQLite-specific table rebuild behavior.

## CI matrix

The `CI` workflow runs for pull requests to `develop` and `main` and can be dispatched manually.

| Job | Runtime/service | Effective gates |
| --- | --- | --- |
| Code style | PHP 8.5 | strict Composer validation, install, Pint check |
| Architecture & static analysis | PHP 8.5 | architecture tests/report, PHPStan level 5, debt report |
| Frontend build | Node 26 | `npm ci`, Vite build, artifact upload |
| Tests (SQLite) | PHP 8.5 + PDO SQLite | isolated fresh seed, full `composer test` |
| Migrations (MariaDB) | PHP 8.5 + MariaDB 11.4 | fresh seed, approved DB-boundary suite, rollback exercise |
| Migrations (PostgreSQL) | PHP 8.5 + PostgreSQL 18.4 | runtime platform check, fresh seed, approved DB-boundary suite, rollback exercise |
| Dependency audit | PHP 8.5 + Node 26 | locked Composer audit, npm audit at high threshold |
| Summary | GitHub runner | reports all upstream results; does not execute quality checks itself |

The separate coverage workflow runs for pull requests to `main` (not `develop`) and manual dispatch. It creates Clover/JUnit artifacts with PCOV, but enforces no minimum percentage.

## False-green and missing-gate findings

- MariaDB and PostgreSQL rollback exercises use `continue-on-error: true`; a failed rollback is reported in outputs but does not fail its migration job.
- The CI summary uses `if: always()` for reporting. The upstream gate jobs still retain their failure states; branch protection must require those jobs rather than only the summary.
- Coverage has no threshold and is not run on pull requests targeting `develop`.
- Frontend lint, frontend unit/component tests, browser journeys and screenshot comparison are absent.
- PHPUnit does not declare a browser or architecture-named suite; architecture tests run because they are under Unit and through the dedicated Composer script.
- No snapshot auto-update command was found. `if: always()` occurrences outside summary jobs only collect/upload results. No muted-warning option was found in `phpunit.xml`.

## Dependency-audit transition observed during delivery

The initial audit at 2026-08-04 19:14 EEST found two failing dependency gates:

- `composer audit --locked` exits 1 because locked `guzzlehttp/guzzle 7.15.1` is affected by CVE-2026-69246 (high) and CVE-2026-69245 (medium), both reported on 2026-08-03.
- `npm audit --audit-level=high` exits 1 because locked `postcss 8.5.16` is affected by two high-severity source-map path/file-disclosure advisories.

The dependency changes were not made by Phase 0.1. Existing Dependabot MR #551 and MR #554 were separately reviewed, refreshed and merged into `develop` before this baseline MR was published. At 19:37 EEST, locked `postcss 8.5.25` and `guzzlehttp/guzzle 7.15.2` make both audit commands pass with zero advisories. This history is retained because live advisory data changed the baseline during the audit window.

## Reproduction commands

```bash
composer validate --strict
composer test:architecture
composer analyse -- --no-progress
composer format:test
composer test
npm ci
npm run build
composer audit --locked
npm audit --audit-level=high
```

The baseline run and exact exit codes are recorded in [check-results.md](check-results.md).
