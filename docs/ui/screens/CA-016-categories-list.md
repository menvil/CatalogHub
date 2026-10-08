---
screen_id: CA-016
context: central-admin
purpose: Locate and operate canonical Categories with derived hierarchy, schema, usage and Locale context.
roles: Central Admin and Catalog Editor with Category capability and active Central admission
route: /admin/central/categories
viewports: desktop=1440x1000;medium=1024x900;tablet=768x1024;mobile=390x844
fixture: categories-list-v1
regions: central-shell;header-actions;summary-metrics;discovery-filters;category-table;row-actions;pagination
actions: new-category;edit-existing-category;open-existing-schema;activate;archive;restore;search;filter-level;filter-lifecycle;filter-schema;filter-locale;filter-site-selection;filter-translation;sort;paginate;clear-filters
states: default;empty;filtered-empty;read-only;no-active-locales;validation-error;forbidden
permissions: catalog.categories.manage;central.panel.access;central.page.access;central.mutation.execute for writes;catalog.schema.manage for schema destination
responsive: Dense desktop table; contained horizontal table scrolling at medium/tablet; intentional labeled cards and sort controls below 640px; viewport-aware menus and shared navigation drawer.
out_of_scope: CA-017;CA-018-redesign;CA-019-redesign;CA-020-plus;imports;bulk-actions;publication;translation-editing;schema-editing;analytics;shared-shell-redesign
reference_version: categories-schema-prototype-v1
---

# CA-016 — Categories List

Phase 19.4 owns the operational canonical Category registry. Its source is the approved Categories / Schema prototype, adapted under ADR-0003 and the visual gap audit. The deterministic implementation baselines have Product Owner acceptance and are compared by the normal visual regression suite.

## Product contract and route ownership

One controller/view at `/admin/central/categories` serves all visible Categories navigation. `CentralNavigationRegistry` has exactly one Categories entry with `catalog.categories.manage`. The Filament resource registers no independent navigation or table. Its old `/admin/central/central-categories` index only authorizes and redirects, preserving query parameters. Framework index URL generation, breadcrumbs and editor return destinations target CA-016.

Existing `/admin/central/central-categories/create`, `/{record}/edit`, and `/{record}/schema` continue to serve working temporary infrastructure. Their owning visual phases are not implemented here. Category source identity is readable text, without a fake View link. Final identity-to-detail navigation attaches in **19.5 CA-017**, after that screen exists. The existing Locale translation route remains independently bound and authorized; CA-016 introduces no translation destination.

## Summary metrics

All summary cards describe the whole canonical registry, independently of discovery filters and pagination:

| Card | Exact definition |
| --- | --- |
| Total Categories | Every `central_categories` row, including Archived. |
| Active | `CentralCategoryStatus::Active`; current Active / Total percentage, rounded to one decimal, only for a nonzero denominator. |
| With Schema | Category has at least one `CategoryAttributeAssignment`. Neither Sections alone nor schema approval is assignment presence. |
| Missing Translations | At least one active Locale has no CategoryTranslation row or explicit `TranslationStatus::Missing`. Outdated alone is not Missing. |
| Needs Review | `CategorySchemaStatus::Draft`, including Categories without assignments. Draft denotes structural schema work/review required. Reviewed, Approved and Archived keep their distinct meanings. |

No quality score, persisted count, validation-derived status, historical trend, growth arrow or KPI menu is introduced. Optional validator issue counts are deliberately omitted; no validator runs per row.

## Prototype mapping

| Prototype concept | Implemented mapping |
| --- | --- |
| Header → five cards → filters → dense table → paginator | Same information hierarchy in the existing Central shell. |
| Category icons/colors | One shared fallback `squares-2x2` icon; no persisted identity decoration. |
| Parent column / child arrow | Root or derived Level plus explicit Parent context beside source name/slug. |
| Attributes | Local assignment count, never global definition or inherited count. |
| Sites | **Selected by Sites**, independent of public availability. |
| Market / Language | Explicit active Locale by ID, displayed code; no Category Market. |
| Needs Review lifecycle badge | Separate Schema status column/filter and schema Draft summary. |
| With Schema readiness implication | Assignment presence, separately labeled lifecycle. |
| Monthly +3.1%, historical arrows, KPI ellipses | Omitted. |
| Bulk checkboxes, Import Categories, New dropdown | Omitted; one authorized New Category link. |

The removed space is used by real schema and translation context, not invented features.

## Filter and URL semantics

Validated server-side GET parameters:

| Parameter | Meaning |
| --- | --- |
| `q` | Name or slug contains the search text; trimmed string, at most 255 characters. |
| `level` | Exact derived depth: Root = 0; arbitrary real deeper levels appear as Level N. Nonnegative portable integer. A nonexistent nonnegative level yields filtered empty. |
| `status` | Category Draft / Active / Archived. |
| `schema` | Schema Draft / Reviewed / Approved / Archived. |
| `locale` | Existing active Locale ID. Context selects exact row status for that Locale; it does not hide Categories or create translations. |
| `site` | Existing administrable, non-deleted Site ID; Category has a real SiteCategory row for that Site. |
| `translation` | Covered / Missing / Outdated in selected Locale, or across active Locales when no Locale is selected. Covered requires all scoped Locale rows in MachineTranslated, HumanReviewed or Approved; Missing requires at least one absent/Missing; Outdated requires at least one Outdated. |

Filters compose with AND. Clear filters removes search, discovery filters and page, preserving sort/direction/per-page. Root value zero remains a real active filter. The active-filter count includes Locale context. Changing discovery/sort/per-page resets page. Paginator links and lifecycle return URLs preserve validated query context. Arbitrary return URLs are not accepted.

Unknown enums, inactive/unknown Locale, archived/deleted/unknown Site, negative level/page, arrays in scalar fields, unsupported sort/direction/per-page reject via the repository's HTML validation redirect and error bag; no invalid unbounded read executes.

## Sorting and pagination

Database-backed sorting supports name, direct Products, assignments, canonical Facets, Selected by Sites, Category lifecycle, schema lifecycle and updated_at. The URL keys are `name`, `products`, `attributes`, `facets`, `sites`, `status`, `schema_status`, `updated_at`; direction is asc/desc. Every sort adds Category ID ascending as a unique tie-breaker. Name uses reference name; lifecycle sorts use existing enum string values. Desktop headers carry `aria-sort`; mobile has explicit Sort by and direction controls. Pagination is database-backed at **20 / 50 / 100**, default 20.

## Hierarchy

One minimal Category snapshot reads ID, parent ID, source name and lifecycle states. An iterative, memoized ancestry walk derives depth; roots start at zero. It has no maximum-depth assumption or recursive row query. A corrupt cycle/missing ancestry is bounded and presented as Hierarchy unavailable, never silently repaired on GET. Parent context is the direct parent's source name. No relationship is inherited, changed or persisted by a filter.

## Table columns and usage

Desktop columns: Category identity/hierarchy, Products, Attributes, Facets, Category status, Schema status, Selected by Sites, Translations, Updated, Actions.

- Products counts **all direct** `central_products.central_category_id = category.id` rows, including Draft/Active/Archived Products. Children never contribute.
- Attributes counts `CategoryAttributeAssignment` rows for that Category, including hidden assignments.
- Facets counts canonical `FacetDefinition.category_id` rows, including inactive/hidden config. No definition flags supply facets.
- Sites counts distinct Site IDs with a retained `SiteCategory.central_category_id` selection, where `Site::administrable()` permits Draft, Active or Suspended and SoftDeletes excludes deleted Sites. Archived Sites are excluded. `is_enabled` and `local_status` concern Site-owned display behavior and do not erase the relationship; disabled/hidden selections still count. No publication or membership/navigation permission is implied. No Site-owned screen link or Site mutation is added.
- Updated uses actual Category updated_at, shown as an absolute date with machine-readable datetime, including on mobile.

## Translation denominator and statuses

Only active `Locale` IDs define the denominator. Rows are keyed by owning Category and Locale ID; code is display context. Covered = MachineTranslated + HumanReviewed + Approved. Missing = absent + explicit Missing. Outdated is separate, excluded from Covered and not counted as Missing. The row shows covered/total and a rounded percentage, plus missing/outdated counts. Selecting a Locale shows its exact status; absence presents Missing without writing a row. With zero active Locales, show **No active Locales** and no percentage, and Missing Translations is zero. These semantics match the accepted active-Locale Brand list summary without introducing Brand quality or field-completeness semantics.

## Row actions and authorization

A keyboard-usable shared action menu includes only supported, authorized destinations/actions. Edit and New require Category capability plus Central mutation; Schema requires separate `catalog.schema.manage` and Central admission. Category-only operators have no Schema link. Read-only actors retain the complete list with no mutation menu unless a separately authorized Schema read destination exists.

| Category lifecycle | Legal list commands |
| --- | --- |
| Draft | Activate, Archive |
| Active | Archive |
| Archived | Restore to Draft |

All commands use existing ActivateCentralCategoryAction / ArchiveCentralCategoryAction / RestoreCentralCategoryAction, including their locks, transactions, audit and legal-state checks. Shared keyboard/focus-trapped confirmations precede commands; Archive also requires accepted server-side confirmation. State changes preserve schema approval, children, Products, assignments, translations and Site selections. Existing target-state no-ops remain idempotent. Stale illegal Activate/Restore requests reject rather than bypass domain actions.

| Actor | List | Mutations | Schema destination |
| --- | --- | --- | --- |
| Central Admin / Super Admin with active Central admission | Allow | Allow with Central mutation | Allow with schema capability |
| Catalog Editor with Category capability | Allow | Allow with Central mutation | Deny by existing role mapping |
| Category capability without Central mutation | Allow | Deny; controls absent | Only independently allowed schema read |
| Guest / disabled / Site-only / Moderator / Translator without Category capability | Deny | Deny | Independent owning policy |
| Schema-only / Product-only / Brand-only | Deny | Deny | Schema-only direct existing schema route remains allowed |
| Category capability without Central panel or page admission | Deny | Deny | Deny |

No hard delete, bulk edit, import, duplication, schema approval shortcut, translation mutation or Site mutation route/control exists. GET reads do not create rows, repair data, enqueue rebuilds or record mutations.

## Read architecture and performance

`CategoryListFiltersData`, `CategoryListRow`, `CategoryListSummary` and `CategoryListReadModelData` define prepared view data. `CategoryListReadModelQuery` owns the minimal tree, global summaries, eligible Site/Locale options, scalar aggregates, DB filtering/order/pagination and one page translation batch. The controller prepares authorized actions and presentation URLs; Blade only formats prepared data. Queries never load Product or projection records to count them. No second validator or materialized list state exists.

Immediately after loading the authoritative active-Locale and administrable-Site snapshots, the read model rejects selected IDs absent from those snapshots with the normal `locale` or `site` validation error. A filter that becomes ineligible after request validation never reaches row rendering. HTTP race regressions cover an inactive Locale and archived/deleted Sites.

Read-model query regression verifies the same **8 queries** for 1 and 25 Categories with translations and a populated page. HTTP rendering likewise records **8 queries for 1 and 25 Categories** (a regression ceiling of 12 includes admission/shell overhead). Stable pagination and literal aggregate SQL have explicit architecture registry entries and behavior tests.

## Fixture and empty/responsive states

`CategoryListFixture::create()` supplies 25 Categories (IDs 194001–194025) in `categories-list-v1`. Root Electronics → Displays → Gaming Monitors → Esports Monitors reaches depth 3; Office Monitors is another child. All Category/schema states exist; Coffee Machines and Mice have no assignments/products/Site selections. Six Categories share two global Definitions, one local Specifications Section and one Ungrouped assignment each, one Brand facet each, and selections across Draft/Active/Suspended/Archived Sites (the Draft selection is disabled/hidden). Products are direct persisted rows, including Archived Products. Coffee translations are absent, Gaming has explicit Missing and Esports Outdated; remaining fixture translations are HumanReviewed over each active Locale. Updated dates range from 7 October back through 13 September 2026.

Isolated fixture summary: **25 total, 15 Active (60%), 6 With Schema, 2 Missing Translations, 10 Needs Review**. The shared browser harness also retains nine existing Category records, giving **34 total, 15 Active (44.1%), 6 With Schema, 11 Missing Translations, 19 Needs Review** over four active Locales. Assertions use these deterministic seeded records. Foundation/Brand fixtures retain their ownership and relationships.

Database empty explains there are no canonical Categories and offers New only when authorized. Filtered empty explains no match and offers Clear. An out-of-range page with matches offers First page, preserving filters, rather than claiming the registry is empty. Pagination link windows are clamped to the actual last page even for very large valid page numbers, so rendering remains bounded. Counts remain zero without fake rows. Zero Sites displays 0; zero Locale denominator is neutral.

- 1440×1000: dense operational table and five summary cards; all major usage columns present.
- 1024×900: three-column summary/filter grids; horizontal overflow stays inside the table surface.
- 768×1024: two-column summary/filter grids with deliberate full-width final card and search.
- 390×844: two-column summaries with final card spanning; stacked discovery controls, explicit sort controls; each Category becomes a two-column labeled card with full-width identity/translation, counts, statuses, Updated and accessible actions. Shared drawer remains usable.

Select/action overlays use existing viewport-aware positioning. No page-level horizontal overflow. Labels, table headers, textual badges, keyboard confirmations, visible focus and accessible paginator names apply at every viewport.

## Visual evidence and acceptance

Immutable source: `pictures/1. Central Admin/1.4. Categories : Schema/CA-016 — Categories List.png`, **1448×1086**, SHA-256 `c8e776138aa1356369fa2a48efb89f32540ac2d4234e4ee74a1406cba92094f2`, version `categories-schema-prototype-v1`.

The Product Owner accepted the deterministic implementation captures from reviewed head `11aa84424efae86149da6e13177a63950eea800b` on **2026-10-08**. They are promoted unchanged from [CI run 37650271919](https://github.com/menvil/CatalogHub/actions/runs/37650271919), [visual-diagnostics artifact 11496492586](https://github.com/menvil/CatalogHub/actions/runs/37650271919/artifacts/11496492586), using `categories-list-v1` and the pinned Linux renderer. Four default viewport baselines and the existing additional mobile cards capture are stored under `tests/Visual/baselines`, with SHA-256 sidecars and approved implementation entries in `docs/ui/visual-references.json`.

`npm run test:visual -- --grep CA-016` now compares these approved baselines unconditionally. A missing baseline or a visual mismatch fails the test; no candidate-only fallback remains. The existing 0.02 maximum differing-pixel ratio is unchanged. Baseline updates still follow the global visual diff review policy; the explicit Product Owner acceptance authorizes this promotion and the normal review guard. CA-016 visual acceptance is complete, consistent with Phase 19.4 remaining COMPLETE. No later screen is implemented.

| Approved baseline | SHA-256 |
| --- | --- |
| `ca-016__default__1440x1000.png` | `7a23da1296a528d47720d72cf8ce756c4ce145cc64691499271c784aa5e3a282` |
| `ca-016__default__1024x900.png` | `6fdd958b4b1f57866427dcfd6f42a449c7f781ba7c6e7e78fd01e1bb93700f1a` |
| `ca-016__default__768x1024.png` | `8bdb26efc62f056ce15e0751b91a11f2ec4a3ce9f08ac6e7aca09f8ec7e1d2fc` |
| `ca-016__default__390x844.png` | `ff9d8c08cd1e283353456ff6ccd41052d001a2283d6be8392c5a063f1c2340cc` |
| `ca-016__cards__390x844.png` | `7aa1d119339decc0876e1187cb71106efd053cf0575a20683c9d92de85f71905` |

The accepted composition retains the prototype’s information hierarchy, summary/table balance, discovery prominence, hierarchy context and action placement. Tablet scrolling stays inside the table surface, and the extra mobile reference covers readable Category cards. Rejected prototype concepts remain absent. The immutable source prototype is unchanged.
