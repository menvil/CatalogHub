# Central To Site Sync Workflow

Task: P00-010 - Document Central to Site Sync Workflow  
Area: Product / Sync  
Status: Phase 0 discovery artifact

## Purpose

Define what happens when Central Catalog changes need to propagate to local site
projections.

Public runtime reads projections, search documents, and sitemap state. It must
not assemble public pages through heavy joins against draft Central data.

This document does not implement queues, projection builders, conflict services,
database tables, or public rendering.

## Actors

- Change origin: `central_admin`, `catalog_editor`, `data_manager`,
  `translator`, `media_manager`, `price_manager`, `import_operator`
- Consumer/monitor: `site_admin`
- System actor in future phases: sync/projection worker

## Trigger

A Central entity changes in a way that may affect one or more sites.

Examples:

- product attribute changed;
- translation changed;
- media assignment changed;
- category schema changed;
- price offer changed.

## Preconditions

- Central entity exists and is in a state that can affect sites.
- Sites have enabled categories, locales, markets, or price sources related to
  the changed entity.
- Product/category publication workflow has already defined site eligibility and
  visibility states.

## Happy Path

1. Central entity changed.
   - Product, category, schema, translation, media, or price source is updated.
2. Version incremented.
   - Product/schema/translation/media/source version or equivalent change marker
     is updated in future implementation.
3. Affected sites detected.
   - System identifies sites by enabled category, visible products, market,
     locale, media usage, price source usage, and template compatibility.
4. Site products marked stale.
   - Affected site products/categories/search docs are marked stale with reason.
5. Projection jobs created.
   - Rebuild jobs are scheduled for product, category, search, and sitemap
     effects as needed.
6. Conflicts detected.
   - Local overrides and incompatible schema/template assumptions are compared
     against the Central change.
7. Projection rebuilt.
   - Site projection combines canonical data, site settings, market, locale,
     media, local overrides, SEO, and offers.
8. Search documents updated.
   - Search/facet documents are refreshed when searchable content or filterable
     data changes.
9. Sitemap updated.
   - URL visibility, slug, and indexability changes update sitemap output.
10. Sync log written.
    - Future implementation records entity, change reason, affected sites,
      stale status, jobs, conflicts, and result.

## Scenario Notes

### Product Attribute Changed

- Affects product projection.
- May affect listing facets, compare tables, search documents, and related
  content blocks.
- Local market-specific overrides may create conflict warnings.

### Translation Changed

- Affects locales using that translation.
- Missing translation state may clear or become stale if source text changes.
- Search documents for language-specific content may need rebuild.

### Media Assignment Changed

- Affects product detail media gallery, listing cards, homepage blocks, and
  social sharing images.
- Missing variant or failed processing keeps media status warning active.

### Category Schema Changed

- Affects products in the category, facet definitions, comparison layout, import
  mappings, public listing filters, and template compatibility.
- Breaking schema changes must surface as conflicts or compatibility warnings.

### Price Offer Changed

- Affects offer blocks, best offer, price filters, price sort, and products
  without offers counters.
- Does not introduce checkout, cart, payments, or delivery management.

## Stale Status

Stale markers should explain:

- entity changed;
- stale reason;
- affected site;
- affected projection type;
- last built version;
- target version;
- rebuild status;
- conflict status if any.

## Error And Edge Cases

- No affected sites: write sync log only.
- Projection rebuild fails: keep stale state and expose retry.
- Local override conflicts with Central change: projection may use override but
  Site Admin must see conflict.
- Category disabled on site: no public projection required.
- Product hidden/excluded: search and sitemap should not publish it.
- Translation missing after change: site policy decides fallback, hidden, or
  warning state.
- Price source stale: product projection may still render with offer warning or
  no-offer state.

## Output

- Affected sites detected.
- Site products/categories/search documents marked stale.
- Projection rebuild jobs created.
- Conflicts and sync logs recorded.
- Public projection, search documents, and sitemap updated after successful
  rebuild.

## Future Phase Dependencies

- Phase 3: Central product/version changes.
- Phase 4: category schema changes.
- Phase 9: import-driven Central changes.
- Phase 10: sync dashboard and stale status.
- Phase 13: public projection readers.

## Phase 0 Boundaries

Included:

- lifecycle and scenarios;
- affected site detection rules;
- stale status concept;
- projection/search/sitemap rebuild concept;
- sync logs and conflict visibility.

Excluded:

- queue implementation;
- projection builder code;
- search index integration;
- sitemap generator;
- database migrations;
- admin UI.
