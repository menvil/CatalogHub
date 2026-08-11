# Section Zero Completion Report

Date: 2026-08-11
Task: P00-134 — Publish Section Zero Completion Report
Status: Section Zero acceptance complete; merge remains subject to review policy.

## Phase and task evidence registry

Every Section Zero task ID is covered exactly once by the contiguous ranges
below. Links point to current executable or maintained evidence, not the stale
Phase 0.1 description of the repository.

| Phase | Task range | Primary evidence |
| --- | --- | --- |
| 0.1 | `P00-001–P00-006` | [repository baseline](baseline/check-results.md) |
| 0.2 | `P00-007–P00-014` | [presentation contexts](../../architecture/presentation-contexts.md) |
| 0.3 | `P00-015–P00-024` | [site runtime fixtures](demo-sites.md) |
| 0.4 | `P00-025–P00-034` | [authorization contract](../../architecture/authorization.md) |
| 0.5 | `P00-035–P00-042` | [design-system foundation](../../design-system/README.md) |
| 0.6 | `P00-043–P00-050` | [Central shell contract](../../ui/screens/Z-002-central-admin-shell.md) |
| 0.7 | `P00-051–P00-058` | [Site Admin shell contract](../../ui/screens/Z-004-site-admin-shell.md) |
| 0.8 | `P00-059–P00-066` | [Public multi-site shell](../../ui/screens/Z-005-public-multi-shell.md) and [single-site shell](../../ui/screens/Z-006-public-single-shell.md) |
| 0.9 | `P00-067–P00-082` | [shared component contracts](../../design-system/components.md) |
| 0.10 | `P00-083–P00-090` | [authentication](../../ui/screens/Z-001-central-login.md) and [system-state](../../ui/screens/Z-007-system-errors.md) references |
| 0.11 | `P00-091–P00-096` | [fixture registry](../../ui/fixtures.md) and [approved references](../../ui/visual-references.json) |
| 0.12 | `P00-097–P00-104` | [backend conventions](../../architecture/backend-conventions.md) |
| 0.13 | `P00-105–P00-111` | [queue/scheduler checklist](phase-0.13-checklist.md) |
| 0.14 | `P00-112–P00-119` | [test suites and harnesses](../../testing/README.md) |
| 0.15 | `P00-120–P00-126` | [clean CI acceptance](../../ci/section-zero-ci-acceptance.md) |
| 0.16 | `P00-127–P00-134` | [personas](demo-users.md), [sites](demo-sites.md), and [fresh install](../../setup/fresh-install.md) |

## Actual implementation paths

| Capability | Canonical path |
| --- | --- |
| Foundation-only data graph | `database/seeders/FoundationDemoSeeder.php` |
| Deterministic personas | `database/seeders/FoundationDemoUsersSeeder.php` |
| Site, host, locale, and theme fixtures | `database/seeders/SiteFoundationSeeder.php` |
| One-command installer | `composer bootstrap:foundation` → `scripts/bootstrap-foundation.sh` |
| Install smoke verification | `app/Console/Commands/VerifyFoundationInstall.php` |
| Central/Site/Public browser flows | `tests/Browser/Acceptance/` |
| Exhaustive negative security flow | `tests/Feature/Acceptance/CrossContextSecurityAcceptanceTest.php` |
| Required CI gates | `.github/workflows/ci.yml` |
| Visual approvals | `docs/ui/visual-references.json` and `tests/Visual/baselines/` |

## Acceptance evidence

The exact fresh-install command passed against an empty temporary SQLite file:
98 migrations, three sites, eight users, six memberships, zero catalog records,
frozen npm install, production asset build, storage link, fixture verification,
and admin-route smoke. The production-guard regression proves that a forbidden
run exits before changing its configured database.

Targeted acceptance passes:

- persona/site/bootstrap Feature verification: 7 new tests;
- cross-context Feature security: 5 tests, 37 assertions;
- Playwright acceptance: 11 Central, Site, Public, and denied-path scenarios;
- no retries, sleep-based waits, random screenshots, or baseline updates.

The final clean local acceptance on 2026-08-11 produced:

- full PHPUnit: 2,018 tests, 8,103 assertions;
- architecture: 72 tests, 759 assertions, zero registered suppressions;
- PHPStan and Pint: zero errors;
- frontend lint/unit/build: 26 JavaScript files, one unit test, Vite production build;
- browser: 12 Playwright tests including the 11 Phase 0.16 scenarios;
- visual: 28 PHP tests with 578 assertions and one approved Playwright baseline;
- Composer and npm audits: zero known vulnerabilities.

The implementation head `67c403e` passed every required check in GitHub Actions
run [31535395243](https://github.com/menvil/CatalogHub/actions/runs/31535395243),
including the no-cache fresh PostgreSQL, MariaDB, browser, and visual jobs. Local
results do not waive or replace any branch-protection gate on later changes.

## Deviations and compatibility ownership

| Deviation | Reason and owner |
| --- | --- |
| P00-092–P00-095 have no individual commit subject | Their screen-contract work was delivered inside combined MR #571 (`P00-091–P00-096`). The executable fixture/reference evidence is present. Owner: Quality. |
| `sites.domain` remains beside `site_domains` | Existing URL generation still reads the synchronized compatibility column. Removal requires a separately scoped schema migration. Owner: Platform Architecture. |
| `Legacy Unit` remains a visible suite | Framework-booting historical tests were not mass-moved during Phase 0.14. They remain gated, not excluded. Owner: Quality. |
| Product-bearing `DatabaseSeeder` remains available | Existing demo catalog data is preserved for its earlier use cases; Section Zero acceptance deliberately selects `FoundationDemoSeeder`. Owner: Demo Data. |
| Browser acceptance uses fresh-install site IDs 1–3 | The IDs are deterministic only inside the isolated browser database and intentionally detect seed-order drift. They are not an application API. Owner: Quality. |

No new temporary adapter, broad architecture allowlist, muted failure, or unowned
TODO was introduced by Phase 0.16.

## Unresolved blockers

No blocking Section Zero defect remains open for Brands foundation work. The
following are explicitly outside this acceptance and remain later-section or
environment responsibilities:

- production deployment and secret provisioning;
- load testing and performance certification;
- catalog/business fixtures and Brands behavior;
- removal of the documented compatibility surfaces above.

## Brands handoff prerequisites

Brands can use the foundation unchanged when its work:

1. starts from `develop` after this MR and keeps all required checks enabled;
2. uses existing identifiers, status enums, application actions, transactions,
   domain errors, audit correlation, and site-scoped query conventions;
3. registers permissions in the existing registry instead of checking raw role
   strings or inventing another authorization system;
4. keeps Central, Site, and Public presentation ownership separate;
5. adds business migrations, factories, and tests in the Brands section rather
   than extending `FoundationDemoSeeder` with catalog data;
6. treats approved screenshots as immutable unless an explicit visual review
   accepts a new baseline.

Any prerequisite that requires changing these foundation contracts is a new
atomic task, not an implicit extension of Brands scope.
