---
screen_id: CA-011
context: central-admin
purpose: Operate and locate canonical Brands with their derived catalog health.
roles: authorized Central Admin catalog user
route: /admin/central/brands
viewports: desktop=1440x1000;mobile=390x844
fixture: brands-list-v2
regions: central-shell;header-actions;summary-metrics;operational-filters;brand-health-table;row-actions;pagination
actions: new-brand;view-detail;edit-brand;search;filter-country;filter-status;filter-category-coverage;filter-translation;filter-quality;sort;paginate;clear-filters
states: default;empty;filtered-empty
permissions: catalog.brands.manage
responsive: Desktop exposes the full eight-column operational table; mobile converts each row to an identity/status/translation-quality card and hides secondary counts while preventing page-level overflow.
out_of_scope: imports;bulk-actions;site-projection;publication;sync;historical-trends;global-shell
reference_version: brands-list-final-convergence-v2
---

# CA-011 — Brands List

## Product contract

CA-011 is an operational Brands index, not a lifecycle mutation surface. Access requires `catalog.brands.manage`. `New Brand` opens CA-013; each row's keyboard-usable overflow menu contains only View (CA-012) and Edit (CA-013). Lifecycle mutation remains on CA-012. No suitable Brand import destination currently exists—the stable Imports route is a Product artifact workflow—so the screen does not expose a dead `Import Brands` action.

The screen follows the prototype hierarchy inside the existing Central Admin shell: header/actions, summary metrics, one filter surface, dense Brand health table, and paginator.

## Prototype mapping

| Prototype element | Existing domain source | Decision |
|---|---|---|
| Total/Active cards | `CentralBrand` and `CentralBrandStatus` | Implemented with current-state counts. |
| Media card | exact global primary `brand_logo` Shared Media assignment | Adapted to `With Logos`; only a usable `Ready` presentation counts. |
| Missing Translations | active `Locale` + `BrandTranslation` | Implemented; a Brand counts when at least one active Locale has an absent/Missing translation. `Outdated` alone is not Missing. |
| Needs Review | `CentralBrandQualityEvaluator` | Adapted to derived `Needs attention`; it is never a lifecycle status. |
| Region | canonical `Country` reference | Adapted to Country; no market-region model is implied. |
| Language / Market | Brand translation status over active Locales | Adapted to Translation. |
| Brand/logo rows | canonical Brand + Brand logo presenter | Implemented with usable image or honest initial fallback. |
| Categories / Products | non-archived Product relations and their direct Categories | Implemented as grouped derived counts, never stored counters. |
| Media number | current approved media contract only has canonical logo identity | Adapted to Logo Health (`Logo ready`, `Missing`, `Unavailable`). |
| Row actions | existing View/Edit routes | Implemented as an accessible overflow menu. |
| Sites, row checkboxes, monthly growth, richer global shell | no approved owning domain/workflow, analytics source, or screen ownership | Intentionally deferred/omitted. |

## Summary metrics

- **Total Brands**: every canonical Brand.
- **Active**: Brands whose lifecycle is `CentralBrandStatus::Active`; percentage uses Total Brands.
- **With Logos**: Brands whose exact canonical global primary `brand_logo` resolves through the Shared Media presenter to `MediaDeliveryState::Ready`; arbitrary assignments do not count.
- **Missing Translations**: Brands with `missing > 0` in the active-Locale translation summary. Missing includes absent rows and explicit Missing status; Outdated is distinct.
- **Needs attention**: Brands whose authoritative `CentralBrandQualityEvaluator` result is `CentralBrandQualityState::NeedsAttention`.

Percentages are derived from the current database and omitted when Total Brands is zero. There are no historical snapshots or decorative trends.

## Filters and URL state

Search matches normalized canonical name, slug, and authoritative Parent Company name through `CentralBrandOwnership → Organization` without joining duplicate rows into the paginator. Country filters by the canonical country reference. Status supports Draft, Active, and Archived. Category Coverage supports Has coverage and No coverage based on non-archived Products with a direct Category. Translation supports Complete, Missing, Outdated, and Needs attention. Quality supports Complete and Needs attention.

Translation Complete requires at least one active Locale and no Missing/absent or Outdated locale. Translation Needs attention is Missing/absent or Outdated. Quality uses the evaluator result rather than a second list-only algorithm.

Search, filters, sort, direction, per-page, and page live in the query string. Sorting is database-backed for Brand, Products, Status, and Updated, with Brand ID as the stable tie-breaker. Filter/sort/page links retain compatible active parameters; Clear filters resets the list route.

## Table and coverage semantics

Desktop columns are Brand, Category Coverage, Products, Status, Translation Coverage, Logo Health, Updated, and Actions. Brand contains canonical logo/fallback, name, slug, and Parent Company when present. Product count excludes archived Products. Category count is the number of distinct direct Categories among those Products.

Translation coverage denominator is active Locales only. `MachineTranslated`, `HumanReviewed`, and `Approved` are covered; absent/Missing and `Outdated` are incomplete. The percentage is computed in memory from batched translation rows and is never persisted. With zero active Locales, the table shows a neutral em dash rather than 100%.

Quality is displayed beside translation coverage so lifecycle remains visually and semantically separate. CA-011 and CA-012 both consume the same evaluator output.

## Read architecture and performance

`CentralBrandListReadModelQuery` composes the database-backed Brand paginator with a bounded set of bulk reads: active Locales once, all relevant translations once, exact canonical logo assignments/variants once, Product counts in the page query, and one grouped distinct-Category count for the current page. `CentralBrandQualityBatchQuery` constructs the existing evaluator inputs and invokes `CentralBrandQualityEvaluator` in memory; `CentralBrandQualityQuery` delegates its single-Brand result to that same path. Query-count regression covers 1 versus 20 Brands and quality parity with CA-012.

No schema or cached percentage/count/health columns are introduced.

## Responsive and empty states

At 390px the summary and filters stack, the table header and secondary desktop columns are suppressed, and each row retains Brand identity, lifecycle, translation percentage, Quality, and its action menu. The screen owns no page-level horizontal overflow. Database-empty state explains the catalog is empty and offers New Brand; filtered-empty state offers Clear filters. Zero metrics remain numeric zero without divide-by-zero output.

## Intentional prototype differences

- **Sites**: deferred until a future authoritative Site Brand projection exists.
- **Needs Review**: mapped to derived `Needs attention`, not lifecycle.
- **Language / Market**: active-Locale translation semantics; no fake Market domain.
- **Media numeric count**: replaced with canonical Logo Health because no broader approved Brand media metric exists.
- **Checkboxes**: omitted because no approved bulk Brand workflow exists.
- **Monthly growth**: omitted because no authoritative historical analytics source exists.
- **Global shell**: unchanged because it is outside CA-011 ownership.

## Visual reference

The original `pictures/1. Central Admin/1.3. Brands/CA-011 — Brands List.png` remains the composition/design source. The reviewed desktop and mobile regression references in `docs/ui/visual-references.json` use persisted `brands-list-v2`; they are evidence of the converged implementation, not the source of the design decision.
