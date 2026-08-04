# Roadmap v1 Baseline

Audit date: 2026-08-04

Code baseline: `develop` at `c8b485ccd161ddfe40ae43f1a09522b894926c78`

Purpose: reconciliation input for Roadmap v2, not a production-readiness certificate.

## Evidence and interpretation

This baseline was produced from the current `app/`, `database/`, `resources/`,
`routes/`, `tests/`, and `docs/` trees and from the approved screen references in
`pictures/`. A class, migration, route, or passing unit test is evidence of an
implemented foundation; it is not by itself evidence that the corresponding
approved screen is complete or usable.

For this audit, a screen is considered reconciled only when all of the following
exist together:

1. a reachable screen matching its approved screen ID and workspace;
2. representative seeded demo data, including non-happy-path states;
3. working primary actions and persistence;
4. permission and active-site isolation tests;
5. functional screen tests;
6. reviewed visual evidence against the approved reference.

The current repository has substantial evidence for the first five items in
selected areas, but it does not yet provide that complete evidence chain for the
full approved screen map.

## Repository inventory

| Area | Current inventory | Audit observation |
| --- | ---: | --- |
| Application PHP files | 612 | Includes actions, domains, Filament, models, queries, services, policies, jobs, and HTTP code. |
| Domain files | 34 | Concentrated in projections, public-site composition, SEO, and themes. |
| Filament files | 140 | 34 resources, 92 resource pages, 6 standalone pages, widgets, and support code. |
| Models | 75 | Broad persistence model across Central, Site, projection, pricing, content, and operations. |
| Actions | 60 | Covers lifecycle changes, imports, media, translations, corrections, pricing, reviews, leads, sites, sync, and versions. |
| Services | 89 | Includes import, pricing, export, media, translation, unit, facet, site, and health services. |
| Migrations | 98 | Represents the v1 data model through snapshots/readiness work. |
| Seeders | 10 | Strongest for units, block registry, and three public demo sites; limited for admin operational screens. |
| Registered admin routes | 122 | Includes Filament and custom Central routes; route count does not equal approved-screen coverage. |
| Named public routes | 8 | Home, category, listing, product, compare, article, search, and offer redirect. |
| PHP test files | 486 | Broad unit/feature coverage; browser-level visual regression is not present. |
| Approved admin references | 149 | 85 Central Admin images and 64 Site Admin images. |

Runtime dependencies currently declare PHP 8.5, Laravel 13.8, Filament 5.6,
Tailwind 4, and Vite 8. The application registers one Filament panel with ID
`admin` at `/admin`.

## Current architecture

The v1 architecture already contains the main data flow expected by the product
model:

```text
Central models and actions
        |
        v
site eligibility + local overrides + market/locale/theme configuration
        |
        v
product/category/search/sitemap projections
        |
        v
host- and locale-resolved Public Local Site
```

This is a useful foundation for v2. The principal mismatch is in the admin
presentation and context model: Central and Site capabilities are mixed in one
Filament navigation tree, while Site operations are mostly exposed as child
pages of a `SiteResource` record rather than as a persistent Site Admin
workspace selected through a working site switcher.

## Implemented subsystems

| Subsystem | Current implementation evidence | Current UI evidence | Baseline assessment |
| --- | --- | --- | --- |
| Platform and authentication | Laravel application, Filament authentication, `UserRole`, permission matrix, named gates, policies, health/security/runtime checks. | One `/admin` panel and login. | Foundation exists. Target workspace navigation and complete role-management UI do not. |
| Admin shell and UI kit | Admin CSS tokens, Blade layouts, reusable cards, badges, drawers, modal, tabs, diff, media, translation, and site-context components. | Dev UI kit and visual-smoke routes; separate Central/Site Blade layouts. | Reusable primitives exist, but the custom shells are not the actual shared Filament shell. Several shell controls are disabled placeholders. |
| Central catalog | Central product, variant, brand, and category models; resources; archive/restore and version actions; factories and tests. | Product list/create/edit/detail/specs/version pages; brand/category CRUD. | Core records are operable. Many approved detail, variant, media, translation, and quality compositions remain absent or visually divergent. |
| Category schema and units | Sections, definitions, options, facets, measurement dimensions/units, market preferences, display rules, validation, ordering, approval, clone, and export actions. | Schema builder plus generic resources for facets and units. | Strong domain base. Approved screen-specific editors, comparison/SEO configuration, and cohesive workflow need reconciliation. |
| Product specs | Typed attribute values, validation, missing-required checks, canonical previews, and grouped previews. | Dedicated Filament specs editor. | Functional slice exists; it is not yet accepted against CA-006 and related reference states. |
| Translations | Product/category/attribute/section/option/unit models, save/approve/outdated actions, resolver/statistics/coverage queries. | Dashboard, missing/outdated pages, and dedicated custom editors. | Broad implementation exists. Navigation is split between Filament and custom routes, and bulk review/reference fidelity is incomplete. |
| Media | Assets, variants, assignments, sources, generation job, integrity checker, queries, and assign/update actions. | Custom library/detail/upload/product manager plus missing-media resource. | Backend and selected screens exist. The eight-screen Central Media workflow is not fully represented. |
| Imports | Sources, batches, artifacts, raw products, mappings, normalized drafts, errors, duplicates, jobs, normalizers, importer, and review/publish actions. | Import wizard and resources for batches, raw data, mappings, drafts, errors, and duplicates. | One of the strongest v1 verticals, but approved queue/detail/error screens and demo states are not fully reconciled. |
| Markets and sites | Markets, sites, locales, categories, products, features, overrides, SEO override actions, site creation/update actions, policies, and site scoping. | Market/Site resources and record-scoped settings/dashboard/product/override pages. | Data foundation exists. A persistent Site Admin workspace and functional site switcher do not. |
| Themes and homepage blocks | Theme/manifest/template/block models, registries, validators, activation and block actions. | Theme selection and homepage block editor under a Site record. | Useful domain implementation exists; the seven approved Site Theme screens need dedicated screen treatment and visual acceptance. |
| Projections and sync | Product/category/search/sitemap builders, stale detection, jobs/logs/conflicts, sync services/commands, and resolution actions. | Projection resources, sync dashboard, logs, conflicts, and stale-product resource. | Strong processing foundation. Current UI mixes Central and Site concerns and lacks the complete manual-sync/error workspace from the references. |
| Facets and comparison | Facet definitions/options/overrides, query builder, URL handling, public comparison builder. | Central facet resource and public listing/compare views. | Behavior is tested, but Central category configuration and approved public responsive states need end-to-end acceptance. |
| Reviews and leads | Models, public Livewire forms, moderation/status actions, policy scoping, notification, and rate limits. | List resources with row actions; public embedded forms. | Core flows exist. Approved detail, queue, settings, status-board, and notification screens do not. |
| Content | Content items, translations, relations, types, editor form support, resolver, public content query and page. | Generic Filament content resource with article/guide/FAQ variants and relation managers. | Content foundation exists. Dedicated approved editors and translation/relations composition need refit. Poll administration is not represented by the current content model. |
| Pricing and offers | Sources, encrypted credentials, adapters, fetch/normalize/match/update jobs, mappings, raw and market offers, history, freshness, widgets, queries, and click tracking. | Central resources plus Site record pricing previews/reports. | Deep backend coverage exists. Central and Site pricing ownership must be separated in the UI and completed against both screen groups. |
| Corrections, conflicts, versions | Central change requests, Site correction requests, product versions, sync conflicts/logs, approve/reject/apply and resolution actions. | Central review/diff/conflict screens and Site request list/create paths. | Business flow exists in pieces. Two request models and mixed navigation increase lifecycle-drift risk. |
| Snapshots, export, backup checks | JSONL exporters, snapshot generation/history/download, manifests/checksums, integrity command, widgets, and runbooks. | Generation, list/detail, restore checklist, missing media, backup widget. | Export/snapshot tooling exists. It must not be described as full backup/restore, and the approved history/manifest/status screens need reconciliation. |
| Public Local Site | Host/locale site resolver, projection-first queries, theme layout resolver, home/category/listing/product/compare/search/content views, offers, reviews, and leads. | Responsive Blade pages and component tests for key states. | A usable demo foundation exists. Public page coverage is incomplete relative to the approved inventory and has no formal visual acceptance evidence. |

## Seed-data baseline

`DatabaseSeeder` currently creates measurement data, the block registry, a test
user, and the public demo dataset. `PublicDemoSeeder` creates:

- three sites: one multi-category site and two single-category sites;
- three categories and three brands;
- twelve products with a small typed schema;
- limited demo media and generated projections.

This proves deterministic public projection setup. It does not populate the
approved admin references with representative imports, translation queues,
media failures, price-source failures, corrections, conflicts, snapshots,
users/roles, reviews, leads, content, or polls. The default test user also does
not provide a full role-by-role workspace demo.

## Test and acceptance baseline

The repository has extensive model, service, action, query, Filament, public,
authorization, architecture, and smoke tests. The v0.2.1 release document records
1,464 passing tests on 2026-07-16 for a previous release commit. That historical
run is evidence for that commit only; Roadmap v2 must record fresh results per
phase.

Current visual QA consists primarily of rendered-markup assertions and a manual
responsive checklist. There is no checked-in browser screenshot comparison for
all approved CA/SA screens. Therefore the current test volume must not be used as
proof that the product matches the visual references.

## Principal reconciliation findings

1. **The one-admin premise is already structurally possible.** There is one
   Filament panel, not a separate application per site.
2. **The two-workspace premise is not implemented as a product shell.** Central
   and Site resources share navigation; Site pages are record-scoped utilities.
3. **The site switcher is not functional.** The Blade component renders labels
   only, while `users.site_id` primarily models one assigned site rather than an
   authorized site set plus active context.
4. **The real shell and the designed shell have diverged.** Custom Central/Site
   layouts and UI-kit components exist beside the Filament panel instead of
   composing one production admin experience.
5. **Visible completion trails backend completion.** Many v1 domain actions and
   tests are reusable, but a generic CRUD resource is not equivalent to its
   approved dashboard, detail, editor, queue, report, or preview.
6. **Admin demo data is insufficient for screen acceptance.** Current seeds
   cannot demonstrate most empty, warning, error, permission, and workflow
   states shown in the references.
7. **Visual acceptance is unproven.** The approved 85 Central and 64 Site images
   have not been turned into a repeatable per-screen acceptance gate.
8. **Readiness language must stay scoped.** Existing release/runbook evidence is
   valuable, but unchecked staging items and known limitations prevent a blanket
   production-ready claim for the v2 product surfaces.

## Baseline conclusion

Roadmap v1 produced a broad domain and persistence platform with many tested
operations. Roadmap v2 should preserve verified business behavior, refit the
admin presentation around one shell and two workspaces, complete only the
approved screen map, and require seeded data plus functional, permission, test,
and visual evidence at the end of every phase.
