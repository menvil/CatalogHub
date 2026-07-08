# Product Publishing Workflow

Task: P00-009 - Document Product Publishing Workflow  
Area: Product / Workflow  
Status: Phase 0 discovery artifact

## Purpose

Define how an approved Central product becomes visible on local sites through
site eligibility, visibility state, and projection rebuilds.

Central approval means the product can be used by sites. It does not mean every
site must show it publicly.

This document is workflow discovery only. It does not implement statuses,
projection jobs, database tables, queues, or public frontend behavior.

## Actors

- Central approval: `central_admin`, `catalog_editor`, `import_operator`
- Site publishing: `site_admin`
- Supporting: `translator`, `price_manager`, `media_manager`

## Trigger

A product is approved in Central Catalog, or an approved Central product changes
in a way that affects site publication.

## Preconditions

- Product exists in Central Catalog.
- Product has a category and required canonical identity.
- Product is approved or otherwise eligible for site use.
- Site has enabled category/market/locale scope.

## Happy Path

1. Product approved in Central.
   - Product leaves import/review draft state and becomes canonical.
2. Product becomes eligible for sites.
   - Eligibility is based on category, status, market constraints, and future
     business rules.
3. Site category scope checked.
   - Site must have the product category enabled.
4. Market availability checked.
   - Product must be valid for the site market or must not be explicitly blocked.
5. Visibility rules applied.
   - Site status is evaluated as draft, hidden, excluded, or visible.
6. SiteProduct created/updated.
   - Future implementation records the site-specific product relation.
7. Projection job queued.
   - Product projection, search document, and sitemap effects are scheduled.
8. Product appears on public site after projection build.
   - Public runtime reads projection data, not draft Central records.

## Site Visibility Statuses

| Status | Meaning | Public visibility |
| --- | --- | --- |
| `draft` | Site is preparing local presentation or waiting for required local data. | Not public. |
| `hidden` | Product is eligible but intentionally hidden from public pages. | Not public. |
| `excluded` | Product is excluded from the site scope or business rules. | Not public. |
| `visible` | Product is approved, eligible, locally allowed, and projection is built. | Public after projection update. |

## Special Cases

### Products Without Offers

- Product may be visible if the site allows informational product pages.
- Offers block shows empty or lead-oriented state.
- Listing sorting and price filters must handle missing offer data.
- Site Admin dashboard should surface products without offers.

### Products Without Translations

- Product may remain draft, hidden, or visible with fallback depending on site
  policy.
- Missing translation must not mutate canonical values.
- Translation queue should be updated for required public locales.

### Products Without Media

- Product may use fallback imagery only if site policy allows it.
- Media quality warning should be visible in Central and Site Admin.

### Stale Projection

- Product remains public with last built projection unless future rules decide
  to hide stale critical data.
- Sync status must show stale reason and rebuild status.

## Error And Edge Cases

- Category not enabled for site: no visible site product.
- Market incompatible: product stays excluded or hidden.
- Product is Central draft: public site must never read it.
- Projection build fails: product remains stale or unavailable depending on
  previous public state.
- Local slug collision: visible state is blocked until resolved.
- Canonical product deprecated: Site Admin must review whether to hide, replace,
  or keep historical page.

## Output

- Approved Central product is eligible for site usage.
- Site-specific publication state is determined.
- Projection rebuild is queued.
- Public site reads updated projection only after rebuild.

## Future Phase Dependencies

- Phase 3: Central product lifecycle.
- Phase 9: import approval creates Central products.
- Phase 10: Site product visibility and sync controls.
- Phase 13: public product, listing, search, sitemap consumption.

## Phase 0 Boundaries

Included:

- publishing lifecycle;
- visibility statuses;
- category and market scope;
- projection job concept;
- products without offers/translations handling.

Excluded:

- database status columns;
- projection workers;
- sitemap implementation;
- frontend templates;
- admin UI implementation.
