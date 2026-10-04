# Brands final section acceptance and closure

**Brands CA-011…CA-015 — COMPLETE.** Final converged / accepted in Phase 18.6. Next section: **Categories / Schema**, outside this MR.

Audit date: 2026-10-04. Starting point: current `develop`, `be2817ca`, after merged Phase 18.5 / PR #610. This is acceptance of the existing module, not a redesign or a new domain phase.

## Findings and closure changes

The audit opened every Brand screen, checked the five screen contracts, routes/permissions, domain/quality/ownership documentation, fixture consistency and all 32 approved reference hashes before changing product code.

| Finding | Resolution |
|---|---|
| CA-011 enabled its six-control one-row filter grid at 1280px; with sidebar and active Clear it overflowed by 17px. | Use the existing compact three-column grid at 1280px; enable one-row filters at 1440px. KPI/pagination breakpoints and all approved screenshot layouts stay unchanged. Extend the existing filter regression to 1280px. |
| Existing journey covered List → Overview → Edit → Overview but stopped before Media/Translations. | Extend that test through Media → Translations → Overview → Brands, checking identity, owner, lifecycle, logo, Quality/translation context, active tabs and breadcrumbs. |
| Long CA-015 canonical slug could stretch the header beyond the viewport. | Bound and wrap the existing header metadata/actions; cover persisted long slug/locale labels at all five widths without GET writes. |
| Modal initialization put the native Close button at tabindex=-1, so Shift+Tab from the next control could escape. | Retain native control tab order; tabindex=-1 is applied only to the dialog fallback. The existing Brand journey checks Tags/Parent Company/logo replacement/lifecycle focus cycling, Escape and opener restoration. |
| No section-level browser check opened all five screens at every required width. | Add one read-only responsive acceptance scenario, including both Create and Edit, at 1440/1280/1024/768/390. |
| Domain docs still listed final Brand UI convergence as future work; CA-012 gap table described superseded cards; roadmap used generic Brand read models/fixtures and an unreal Create route. | Record final screen/section acceptance, replace closed gaps with current mappings, document the existing batch Quality/KPI read and actual routes/fixtures/permissions, and mark only Brands complete. |
| Reported CA-015 defensive message mismatch. | Already fixed by merged #610, with a query/HTTP regression. No further change is needed. |

No Brands-specific TODO/FIXME or unused view/helper/hook requiring removal was found. Historical phase decisions and genuine future-domain exclusions remain documented rather than being deleted as implementation debt. No speculative cleanup was introduced.

## Cross-screen acceptance

| Screen | Accepted operational contract |
|---|---|
| [CA-011](screens/CA-011-brands-list.md) | Real KPIs; combined search/Country/lifecycle/coverage/translation/Quality filters; global Clear; stable sorting/pagination; contained desktop table and mobile cards; Ready/Missing/Unavailable logo; empty/filtered-empty states. |
| [CA-012 — Brand Detail](screens/CA-012-brand-detail.md) | Canonical identity/profile, shared logo, Organization owner, explicit Tags, derived Category/Product context, authoritative Quality and existing repair destinations, safe entity provenance and explicit legal lifecycle actions. |
| [CA-013](screens/CA-013-brand-create-edit.md) | Shared Create/Edit form for canonical scalar fields only; active Country plus retained inactive assignment; existing Organization ownership workflow; field-associated validation; read-only lifecycle and logo context. |
| [CA-014](screens/CA-014-brand-media-logo.md) | Current Primary logo, compact details/variants and honest delivery states; upload and 24-item Shared Media picker open only by explicit action; replacement is atomic; removal retains the MediaAsset/files. |
| [CA-015](screens/CA-015-brand-translations.md) | Explicit Source → Target menus; all six fields with read-only reference on the left; copy field/all with explicit Save; dirty draft protection; Missing target/source and Outdated source; explicit approval/outdated actions, real activity and independent LTR/RTL. |

The persisted Samsung Brand (#20) drives the complete section journey. CA-011 and CA-012 agree on Samsung / samsung, Samsung Electronics Co., Ltd., Active, 80% Quality, 75% translation coverage, eight current Products and five derived Categories. Edit uses the same owner/profile/lifecycle; Overview/Edit/Media use the same logo assignment; CA-015 shows the same Brand and actual active-locale row states. Zotac remains the bilingual CA-015 visual fixture; Apple remains the CA-013/014 primary reference. Fixtures are not hardcoded view data.

Overview / Media / Translations share one permission-aware subnav with the correct active tab. Edit remains its own workflow with Back to Overview. Brand-first breadcrumb links return to the same Brand or Brands list; translators receive text instead of inaccessible Brand links. There is no duplicate scalar, owner, logo or translation editor. Quality issue destinations remain profile → Edit, logo → Media and active-locale translation → Translations.

## Permission acceptance

Authorization uses the existing route middleware and permission matrix; no new permission or role is introduced.

| Actor / capability | Read/UI behavior | Server mutation boundary |
|---|---|---|
| CentralAdmin / SuperAdmin | All approved Brand surfaces and actions. | Existing catalog, media and translation capabilities. |
| CatalogEditor / Brand manager | List, Overview, Create/Edit, lifecycle, owner, Tags, provenance and Media; no Translations tab/issue CTA without translation capability. | `catalog.brands.manage`; Shared Media reuse additionally requires `media.manage`. |
| Translator / Translation manager | CA-015 only; inaccessible Overview/Media/View Brand links are absent. | `translations.manage` and `central.mutation.execute` for Save/Approve/Outdated. |
| Authorized translation viewer without central mutation capability | Reference/target content, locale states and activity remain readable; target is read-only and mutation/copy actions absent. | Mutation routes independently reject the missing capability. |
| Guest, denied/disabled actor, Site-only actor without approved Central access | No protected Brand read or mutation capability. | Authentication, Central access and owning permissions are enforced on direct requests. |

The existing contract has no separate read-only Brand-management permission. Closure does not invent that role or loosen `catalog.brands.manage`. Per-screen feature tests verify direct denied requests, permission-aware links and media reuse's additional boundary; the read-only translation test verifies UI/backend parity.

## Responsive and accessibility acceptance

All five screens, including Create and Edit, are opened at 1440×1000, 1280×900, 1024×900, 768×1024 and 390×844. The section browser test asserts a successful response, one page heading, correct screen and no page-level horizontal overflow. Table scrolling remains contained inside CA-011. Existing component/screen tests retain responsive ordering, action wrapping and modal/picker coverage.

Supplemental local acceptance uses persisted long slug/URLs/email, Organization and locale names, filename and multiline translation content at the same five widths. Read navigation preserves Brand, ownership, translation, assignment and audit records. Existing RTL acceptance uses English LTR → Arabic RTL with independent content direction. Native reference text is escaped and preserves line breaks.

Labels, accessible button names, textual statuses, form-error associations, keyboard selections, focus styles, disabled approval explanation and destructive confirmation remain in the established UI kit. The only shared UI change is the native tab-order initialization fix found in Brand dialogs; there is no generic design-system rewrite. Supplemental keyboard acceptance checks Tab/Shift+Tab confinement, Escape and returned opener focus for Tags, Parent Company, logo replacement and lifecycle confirmation. Existing tests cover cancellation, validation, copy confirmations and draft retention. Hidden native backing selects are excluded from accessible-control checks; their visible triggers/comboboxes retain associated labels. No accessibility framework is changed.

## Query and frozen architecture acceptance

- CA-011 uses a fixed set of batched reads for catalog-wide KPI/health filtering; operational rows and derived Category counts are page-scoped. The existing 1-versus-20 Brand query regression and Overview/list Quality parity remain authoritative. Catalog-wide health is intentionally not described as page-bounded data.
- CA-012 eager-loads owner/Country/Tags/source context, counts current Products and groups direct Category coverage. Active-locale translations and exact logo/variants use the same batch Quality path as CA-011; increasing locale count adds no per-locale query.
- CA-014 initial GET does not load compatible library candidates. Explicit picker GET uses stable 24-item pagination and eager variants; assignment is server-revalidated. No picker N+1 or hidden preload is added.
- CA-015 loads active locales and matching translations in bulk, eager approval actors and eight bounded real events. Source selection reuses those reads and performs no write. POST validates source with the existing minimal code check; no editor graph is loaded for redirects.

Canonical Brand remains language-neutral. Lifecycle is exactly Draft / Active / Archived. Quality is derived, read-only and non-persisted. Organization ownership, editorial Tags versus Product-derived Category coverage, ImportSource entity identities and one exact global primary Shared Media `brand_logo` retain their approved ownership. `BrandTranslation` alone owns localized name, tagline, short description, description, SEO title and SEO description.

**Canonical source hash ≠ selected source translation shown to the translator.** Source hash continues to use canonical name/slug only. Reference selection adds no master locale, persisted dependency or staleness propagation. Existing transaction locks, audit/no-op behavior, explicit workflow transitions and post-commit cache eviction remain unchanged.

Zero new database migrations, persistence fields, lifecycle/quality states, permissions, media roles or domain services are introduced.

## Verification and visual references

Repository verification entry points: `composer validate --strict`, `composer format:test`, `composer analyse`, `composer test`, `composer test:architecture`, `composer test:database-boundaries`, `composer test:database-schema`, `composer test:pagination-boundaries`, `composer test:query-contracts`, `composer test:browser`, `composer test:visual`, `npm run lint`, `npm run test:frontend`, `npm run build`, `composer audit --locked` and `npm audit --audit-level=high`.

Local verification passed: all four canonical PHP suites; 73 architecture contracts; 222 database-boundary cases (213 passed and nine expected SQLite-only skips); schema, pagination and query contracts; 51 Browser scenarios; 31 frontend tests; 36 PHP visual checks and 29 pinned Playwright comparisons. Strict Composer validation, Pint, PHPStan, lint, build and both dependency audits passed.

Local browser acceptance used this direct Playwright command because port 8014 belongs to an existing preview:

```bash
CATALOGHUB_BROWSER_PORT=8015 npx playwright test --config=playwright.config.mjs --project=browser
```

`composer test:browser` delegates to the npm script, which explicitly sets port 8014 and overrides an external `CATALOGHUB_BROWSER_PORT=8015`. Use the direct command above when 8014 is occupied; CI retains the unchanged Composer entry point.

Visual comparison uses the unchanged pinned Linux renderer. Native acceptance screenshots are review diagnostics, never replacement baselines. No approved PNG, threshold, Docker runner, Playwright configuration or visual environment image is changed. CI retains its full SQLite, PostgreSQL and MariaDB lanes, including exact locale codes, case-sensitive external identities, owner/translation uniqueness, Category/Tag queries and concurrency checks.

The PR description records the exact final commit and successful CI run after verification; that run is the completion gate. [Visual gap audit](brands-visual-gap-audit.md) records every implemented/mapped, intentionally deferred or rejected prototype divergence.

## Intentional future exclusions

SiteBrand/publication and Site/market visibility; localized Brand media and extra logo/media roles; AI/machine translation, translation memory and glossary; field-level provenance; Brand hierarchy/history/versions/rating; persisted completeness and public Brand SEO publishing remain excluded. Categories / Schema is the next section, not work in this closure branch.
