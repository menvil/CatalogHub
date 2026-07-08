# Local Override Workflow

Task: P00-008 - Document Local Override Workflow  
Area: Product / Workflow  
Status: Phase 0 discovery artifact

## Purpose

Define how Site Admin can adapt product presentation for a specific site without
modifying Central Catalog canonical truth.

This workflow is documentation only. It does not implement override storage,
projection jobs, conflict detection, admin forms, or frontend rendering.

## Actors

- Primary: `site_admin`
- Supporting: `content_editor`, `media_manager`
- Reviewer when needed: `central_admin`

## Trigger

A site needs local presentation, SEO, visibility, media, or content changes for
an approved Central product.

## Preconditions

- Product exists and is approved in Central Catalog.
- Product is eligible for the site through category and market scope.
- Site Admin has access to the site.
- Requested change is local presentation or market adaptation, not a hidden
  canonical correction.

## Allowed Overrides

- local title;
- local slug;
- meta title;
- meta description;
- intro text;
- visibility;
- local media assignment;
- homepage usage;
- local SEO content.

## Forbidden Direct Edits

Site Admin must not directly override:

- canonical product identity;
- canonical brand;
- canonical category;
- canonical specs, except market-specific override with explicit reason;
- global translations;
- category schema;
- product version history.

If the requested change is globally true, Site Admin must create a correction
request instead of using a local override.

## Happy Path

1. Site Admin opens local product context.
2. Current projection is shown with canonical source and local override fields.
3. Site Admin selects an allowed override type.
4. Site Admin enters local value and reason when required.
5. System validates that the field is local and site-scoped.
6. Override is saved as site-local data.
7. Product projection is marked stale.
8. Projection rebuild is queued by the future sync system.
9. Public site displays the updated projection after rebuild.

## Market-Specific Spec Exception

Some facts may differ by market, packaging, regulatory region, or available
variant. These are not normal direct spec edits.

Required conditions:

- explicit market-specific reason;
- affected market and site are recorded;
- evidence or source note is attached;
- override is visible as local/market-specific in admin review;
- Central canonical product remains unchanged;
- future conflict detection can compare override against Central updates.

Examples:

- package contents differ by country;
- warranty term differs by market;
- power plug type differs by region.

## Projection Impact

Local override changes affect:

- `site_product_projections`;
- `site_search_documents` when searchable text, visibility, or facets are
  impacted;
- sitemap when slug or visibility changes;
- homepage blocks when homepage usage changes;
- product detail display when media, title, SEO, or intro text changes.

Local overrides do not change:

- Central product record;
- product version;
- canonical schema;
- global translations;
- other sites.

## Error And Edge Cases

- Product is not eligible for the site: override is blocked.
- Product is hidden or excluded: override may be saved but remains non-public
  until visibility allows publication.
- Slug conflict: Site Admin must choose another local slug.
- Central update changes an overridden field: projection becomes stale and may
  show an override conflict warning.
- Override attempts to change canonical specs without market reason: block and
  route to correction request.
- Local media asset unavailable: projection remains stale or uses fallback media.
- Missing translation: local SEO content may fill presentation gaps but must not
  replace global translation workflow.

## Output

- Site-scoped override record in future implementation.
- Product projection marked stale and rebuilt.
- Public display changes only for the target site/market/locale.
- Central Catalog remains untouched.

## Future Phase Dependencies

- Phase 10: Site Admin product visibility, overrides, sync dashboard.
- Phase 11: Template/theme rendering of overridden fields.
- Phase 13: Public product page consuming projections.

## Phase 0 Boundaries

Included:

- allowed override list;
- forbidden direct edit list;
- market-specific exception rules;
- projection impact;
- Central/Site boundary.

Excluded:

- override database schema;
- projection job implementation;
- conflict resolver implementation;
- Livewire/Filament UI;
- public templates.
