# P00-003 — Domain, Database And Seeder Inventory

Snapshot date: 2026-08-04  
Inventory basis: 75 Eloquent models, 98 migrations, 84 fresh SQLite tables, 57 factories and 10 seeder classes. The fresh schema exposes 146 foreign keys.

## Required foundation entities

All primary keys below are auto-incrementing integers. Status values are stored as strings unless noted.

| Product term | Actual model / table | Key columns | Direct model relations | Foundation assessment |
| --- | --- | --- | --- | --- |
| Site | `App\Models\Site` / `sites` | `market_id`, `theme_id`, `code`, `domain`, `mode`, `default_locale`, `status`, `settings_json` | market, theme; features, locales, categories, products, overrides, blocks, facets, users, leads, content; price sources | usable; broad aggregate and JSON settings require later ownership discipline |
| User | `App\Models\User` / `users` | `site_id`, `email`, `role` | site | usable; one nullable assigned site does not model a multi-site active-context lifecycle |
| Product | `App\Models\CentralCatalog\CentralProduct` / `central_products` | brand/category FKs, name, model, slug, status, version | brand, category, variants, attributes, translations, versions | usable canonical product foundation |
| Brand | `App\Models\CentralCatalog\CentralBrand` / `central_brands` | name, slug, status | products | usable; no media/translation relation declared on the model |
| Category | `App\Models\CentralCatalog\CentralCategory` / `central_categories` | parent FK, name, slug, position, status, schema status | parent/children, products, sections, attributes, translations | usable canonical hierarchy/schema root |
| Locale | `App\Models\Locale` / `locales` | code/language/region, direction, active/default flags, position | none declared | usable lookup; inverse translation/site relations are absent |
| Market | `App\Models\Market` / `markets` | code, country/currency/default locale/timezone, status, `config_json` | none declared | usable lookup; `default_locale` is a string, not a locale FK |
| Media | `App\Models\MediaAsset` / `media_assets` | integer ID plus UUID, disk/path, MIME/size/dimensions/checksum/status | variants, assignments, sources | usable media identity; UUID is an alternate public identity |

## Schema areas

| Area | Tables |
| --- | --- |
| Framework | users, password resets, sessions, cache/locks, jobs/batches/failures, notifications, migrations |
| Canonical catalog/schema | central brands/categories/products/variants/attribute values/versions; attribute sections/definitions/options/display rules |
| Localization/units | locales, translation tables, measurement dimensions/units, market unit preferences |
| Import pipeline | import sources/batches/artifacts, raw products, mappings, normalized drafts/errors, duplicate candidates |
| Site/projection/theme | markets, sites, site locales/categories/features/products/overrides; themes/manifests/templates/blocks; product/category/search/sitemap projections and projection jobs/logs/conflicts/facets |
| Media/content/engagement | media assets/variants/assignments/sources/manifests; content/translations/relations; reviews, leads |
| Pricing/sync/export | price sources/credentials/logs, external mappings/raw offers, merchants/offers/history/clicks, site price sources, sync logs/conflicts, change requests, catalog snapshots |

Every non-framework table has an Eloquent model. The nine tables without application models are Laravel infrastructure tables: cache, cache locks, failed jobs, job batches, jobs, migrations, notifications, password reset tokens and sessions. No domain model points to a missing table in the fresh schema.

## Factories and seeders

- Factories cover 57 models, including all eight required foundation entities.
- `DatabaseSeeder` runs dimension, metric unit, imperial unit, block registry and public demo seeders, then ensures `test@example.com` exists.
- The public demo delegates to multi-category, monitor and keyboard site seeders and intentionally reruns idempotent unit/block seeders.
- Fresh SQLite seed result: 1 user, 1 market, 1 locale, 3 sites, 12 central products, 3 brands, 3 categories and 2 media assets.

## Recorded integrity findings

- Mixed identity is deliberate but not uniform: domain PK/FKs are integers; media assets, catalog snapshots and projection jobs also expose UUID strings; several import/offer IDs are external strings.
- Generic polymorphic columns have no database FK by design (`entity_id`, `related_id`, `candidate_id`, `target_id`, `document_id`). Their integrity depends on application contracts.
- `media_assignments.site_id`, `media_assignments.market_id`, `media_variants.site_id` and `media_variants.market_id` are nullable integer scopes without foreign keys. This is the clearest missing-FK group found.
- `markets.default_locale` and `sites.default_locale` are strings rather than FKs to `locales`; consistency is application-enforced.
- The schema has 49 status-like columns. Most domains use distinct backed enums in models, but allowed values are generally not constrained by the database and several media/import surfaces use raw strings.
- There are 79 JSON/payload-style columns. Seed data does not prove an oversized row: observed maxima were 2,440 bytes for product projections, 2,295 for search documents and 1,059 for category projections. Raw import payloads, projection payloads and snapshot manifests remain unbounded high-growth candidates.

## Fresh migration record

The isolated command completed with exit code 0:

```bash
baseline_db="$(mktemp /tmp/cataloghub-phase01.XXXXXX)"
DB_CONNECTION=sqlite DB_DATABASE="$baseline_db" php artisan migrate:fresh --seed --force --no-ansi
```

It ran 98 migrations, created 84 non-SQLite-internal tables and seeded the counts above. The project-configured PostgreSQL database was not touched. Schema verification lives in `tests/Feature/Database/CoreSchemaSmokeTest.php`; no migration, model, factory or seeder was changed.
