# Full Regression Checklist

Use this checklist for the Phase 21 release candidate. Automated results apply only to the tested commit/environment; manual checks remain unchecked until a release owner verifies them in staging or the controlled production target.

## Automated Release Gate

```bash
composer test
npm run build
php artisan migrate:fresh --seed --force
php artisan test --group=smoke
```

- [x] Full PHP test suite passes.
- [x] Production frontend assets build successfully.
- [x] Fresh schema and seed data complete successfully.
- [x] Smoke group passes from an isolated test database.
- [ ] Working tree is clean and the tested commit is the release candidate.

Run record (2026-07-16, P21-023 feature branch):

- `composer test`: passed, 1,464 tests and 5,222 assertions.
- `npm run build`: passed; Vite reported only the optional `fontaine` fallback-font optimization notice.
- `php artisan migrate:fresh --seed --force --quiet`: passed.
- `php artisan test --group=smoke`: passed, 8 tests and 39 assertions.
- The final release-candidate cleanliness and repeat gate remain intentionally unchecked for P21-024.

## Phase Coverage

### Phase 0 — Discovery

- [ ] Discovery roles, information architecture, screens, workflows, and wireframes exist under `docs/discovery/`.
- [ ] Product/domain decisions still match the implemented launch scope.

### Phase 1 — Technical Foundation

- [ ] Laravel boots with the supported PHP/database/Redis stack.
- [ ] CI, local infrastructure, migrations, queues, cache, and health baseline work.

### Phase 2 — Admin UI Kit

- [ ] Central Admin shell, responsive behavior, tokens, and reusable UI patterns render.

### Phase 3 — Central Catalog

- [ ] Brands, categories, products, variants, lifecycle actions, and relationships work.

### Phase 4 — Category Schema Builder

- [ ] Sections, attribute definitions/options, ordering, visibility, and schema validation work.

### Phase 5 — Units

- [ ] Measurement dimensions/units, conversion, formatting, and market preferences pass tests.

### Phase 6 — Product Specs

- [ ] Canonical attribute editing, typed values, validation, and grouped preview work.

### Phase 7 — Translations

- [ ] Translation dashboard, queues/editor, fallback, status, and outdated detection work.

### Phase 8 — Media

- [ ] Upload, source metadata, assignments, variants, resolution, and media library work.

### Phase 9 — Imports

- [ ] Import source/batch/artifact/raw/error/draft flows work with no production network dependency in tests.
- [ ] Import smoke test passes.

### Phase 10 — Sites Admin

- [ ] Markets, sites, locales, categories, features, settings, and scoped Site Admin access work.

### Phase 11 — Themes

- [ ] Theme manifests, layouts, homepage blocks, compatibility, and selection work.

### Phase 12 — Projections

- [ ] Product/category projections, search documents, sitemap records, jobs/logs/conflicts, and stale detection work.
- [ ] Projection smoke test persists a usable localized bundle.

### Phase 13 — Public Demo

- [ ] Public home, listing, product, compare/search paths and projection-first rendering work.
- [ ] Public smoke tests pass.

### Phase 14 — Facets

- [ ] Facet definitions/options/overrides, filters, sorting, active filters, and SEO safety work.

### Phase 15 — Reviews and Leads

- [ ] Review/lead submissions, consent, moderation/scoping, notifications, and public rate limits work.

### Phase 16 — Content

- [ ] Content items/translations/relations and public content blocks/pages work.

### Phase 17 — Price Sources

- [ ] Credentials, source sync pipeline/logs, external mappings, offers, freshness, retry, and queue status work.

### Phase 18 — Offer and Search UX

- [ ] Offer widgets, merchant links, price history/freshness, listing price fields, and search UI work.

### Phase 19 — Sync, Corrections, and Versions

- [ ] Version history, correction review/apply, stale products, sync dashboard/logs/conflicts, resolution, and rebuild integration work.

### Phase 20 — Backup, Export, and Snapshot UX

- [ ] Private JSONL snapshot generation/history/download and all entity exporters work.
- [ ] Media integrity, checksum verification, missing-media report, restore checklist, and backup widget work.

### Phase 21 — Production Readiness

- [ ] Production configuration, security headers/rate limits/sessions, health checks, smoke tests, audits, and runbooks are reviewed.
- [ ] Admin navigation and role-boundary tests pass.
- [ ] Production readiness checklist contains target-environment evidence or explicit accepted risks.

## Manual Staging Checks

- [ ] Verify production-like environment variables without exposing values.
- [ ] Inspect HTTPS security headers and session cookie flags in a browser/client.
- [ ] Verify queue workers, scheduler, storage, external-source health, logging, and optional error reporter.
- [ ] Exercise Central Admin and Site Admin with separate role accounts.
- [ ] Verify public responsive layout, image variants, forms, SEO tags, sitemap, search, and offers.
- [ ] Generate/download/verify a private snapshot and complete the restore dry-run checklist.
- [ ] Measure representative pages against the performance budget.
- [ ] Review open conflicts, stale projections, failed imports/jobs/sources, missing media, and known data issues.

## Known Issues and Limitations

- External error reporting is provider-neutral and disabled until a provider/SDK, DSN, and scrubbing test are approved.
- Phase 21 has no distributed tracing, infrastructure worker heartbeat, automatic restore, or production-like latency result.
- Catalog snapshots are portable exports, not database/object-storage backups.
- JSON facet/full-text database indexing still requires production PostgreSQL query-plan evidence.
- There is one shared Filament panel; authorization relies on resource/page/route guards and site query scoping.
- A public visitor account/viewer role is not implemented.

## Release Sign-off

- [ ] Release owner records commit/tag, date, environment, and automated command output location.
- [ ] Security/data/operations risks have named owners or explicit acceptance.
- [ ] Deployment and rollback owners confirm the runbooks.
- [ ] Go/no-go decision and observation window are recorded.
