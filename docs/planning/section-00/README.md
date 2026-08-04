# Section 0 — Platform Foundation Architecture Baseline

Task: P00-006 — Publish Section Zero Architecture Baseline  
Phase: 0.1 — Repository Baseline And Architecture Audit  
Snapshot code base: 2026-08-04 at `29a7374` (`origin/develop`)  
Status: audit implemented; final local checks and dependency audits are green.

## Inventories

- [P00-001 Runtime and framework versions](baseline/runtime-versions.md)
- [P00-002 Routes, entry points and panels](baseline/routes-and-panels.md)
- [P00-003 Domain, database and seeders](baseline/domain-and-database.md)
- [P00-004 Tests, quality tools and CI](baseline/tests-and-ci.md)
- [P00-005 Reproducible check results](baseline/check-results.md)
- [Architecture Decision Record index](../../architecture/adr/README.md)

## Current architecture in one page

- Runtime: PHP 8.5 / Laravel 13.24 / Filament 5.7 / Livewire 4.3; Node 26 and Vite 8.2; PostgreSQL 18.4 is the production database contract.
- HTTP: 148 local/testing routes and 146 production routes. Public traffic is host/site-resolved with locale-prefixed catalog routes.
- Admin: one Filament `admin` panel. Central and Site Admin are logical contexts inside the same panel; Site Admin is implemented by record-child pages below `/admin/sites/{record}`.
- Data: 75 Eloquent models, 98 migrations and 84 fresh tables. The isolated fresh SQLite seed succeeds and provides three deterministic demo sites.
- Quality: 490 PHPUnit files (486 pre-audit plus four baseline contracts), PHPStan level 5, Pint, Vite build, SQLite full tests, MariaDB/PostgreSQL boundary jobs and dependency audits. Browser/visual regression and frontend lint/unit gates are absent. Current Composer and npm dependency audits pass.

## Context boundaries

| Context | Current boundary | Important limitation |
| --- | --- | --- |
| Public Local Site | host resolved to Site, route locale, projection-first controllers/views | no route-level domain constraint; correctness depends on `SiteContextResolver` |
| Central Admin | `admin` Filament panel plus custom `/admin` and `/central` controllers | route/layout ownership is mixed |
| Site Admin | `SiteResource` record-child URLs, policies, `site_id` query scoping | not a persistent workspace/panel; shares Central navigation and guard |

## Blockers map

These items are recorded only. Phase 0.1 does not authorize their implementation.

| ID | Blocker | Owner task ID | Status / scope |
| --- | --- | --- | --- |
| S0-B01 | Site Admin is not a persistent workspace and the displayed site switcher has no active-context lifecycle. | `P00-007` (proposed) | open; out of Phase 0.1; establish workspace/context before Site screen work |
| S0-B02 | Central/Site admin routes and layouts have mixed ownership across Filament, custom controllers and unused parallel shells. | `P00-008` (proposed) | open; out of Phase 0.1; reconcile ownership before route moves |
| S0-B03 | Browser journey and deterministic visual comparison infrastructure is absent for the shared admin shell. | `P00-009` (proposed) | open; out of Phase 0.1; add acceptance harness before visual implementation |

Other findings—non-blocking rollback exercises, no coverage threshold, nullable media scope IDs without FKs, string locale references, unbounded JSON payloads and unpinned tool patch versions—must receive separate atomic task IDs before any change.

The dependency advisories initially recorded as S0-B04 were resolved outside Phase 0.1 by the separately reviewed Dependabot MR #551 and #554 before this report was published.

## Inputs for the next phase

1. Use the current one-panel/context map; do not assume three Laravel panels.
2. Use the recorded table/model names and integer identity model; do not introduce schema changes implicitly.
3. Treat the current check results as the comparison baseline and keep environment failures distinct from repository failures.
4. Allocate a task ID for every blocker/finding selected for remediation. An inventory finding is not authorization to fix it.

The audit describes the current code, not the target architecture in the product roadmap. No dependency, route, panel, schema, CI or business behavior was changed.
