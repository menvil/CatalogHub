# New Category Onboarding Workflow

Task: P00-005 - Document New Category Onboarding Workflow  
Area: Product / Workflow  
Status: Phase 0 discovery artifact

## Purpose

Define how CatalogHub adds a new product category to Central Catalog before the
category becomes usable by local sites.

Example category: `Monitors`.

This document describes the workflow only. It does not create migrations,
models, schema tables, admin components, import parsers, or production UI.

## Actors

- Primary: `central_admin`
- Supporting: `catalog_editor`, `data_manager`, `translator`, `import_operator`
- Consumers after approval: `site_admin`, `content_editor`

## Trigger

A business decision is made to support a new category, or an import source
contains products that do not fit an existing approved category.

## Preconditions

- Central Admin IA exists.
- Roles and ownership boundaries are defined.
- Category is not a duplicate of an existing canonical category.
- Source samples or product examples are available.

## Happy Path

1. Create category.
   - Define canonical category name, parent placement, internal code, and owner.
   - Initial state: `draft`.
2. Define attribute sections.
   - For `Monitors`: Display, Dimensions & Weight, Ports, Energy, Ergonomics.
3. Define attribute definitions.
   - Add canonical attributes such as screen size, resolution, panel type,
     refresh rate, response time, brightness, HDMI ports, DisplayPort ports,
     weight, VESA mount, and energy class.
4. Define enum options.
   - Normalize options such as panel type (`IPS`, `VA`, `OLED`) and resolution
     (`1920x1080`, `2560x1440`, `3840x2160`) when enum usage is appropriate.
5. Define units.
   - Assign canonical units such as inch, hertz, millisecond, nit, kilogram,
     watt, and millimeter.
6. Configure facets.
   - Mark high-value attributes as filterable: screen size, resolution, refresh
     rate, panel type, brand, price range, and availability.
7. Configure comparison layout.
   - Choose which attributes appear in compare tables and how sections are
     ordered.
8. Configure SEO templates.
   - Define category, listing, and product SEO patterns without hard-coding
     local site copy.
9. Configure mapping rules.
   - Map known source fields and aliases to canonical attributes and units.
10. Run test import.
    - Use a small sample to validate raw to normalized mapping.
11. Review normalized sample.
    - Inspect parsed units, enum mappings, missing fields, duplicate candidates,
      and media assumptions.
12. Approve schema.
    - `central_admin` approves the category schema once validation warnings are
      resolved or explicitly accepted.
13. Publish category for site usage.
    - Category becomes eligible for Site Admin category scope selection and
      projection generation.

## Error And Edge Cases

- Category duplicates an existing category: stop onboarding and merge discovery
  into the existing category.
- Attribute belongs to another category: move it to a shared schema concept only
  if future schema design supports reuse.
- Unit cannot be parsed reliably: keep category in draft and require mapping
  rule refinement.
- Enum option is source-specific noise: normalize to canonical option or mark as
  unmapped.
- Test import produces too many missing required values: schema cannot be
  approved until required fields or mappings are adjusted.
- SEO template conflicts with local override strategy: keep canonical template
  generic and move site-specific text to Site Admin.
- Approved schema change would break existing projections: create a future
  compatibility requirement for Phase 4/10 rather than implementing it in Phase 0.

## Output

- Draft category converted to approved Central category.
- Attribute sections and definitions documented.
- Units, enum options, facets, comparison layout, SEO templates, and mapping
  assumptions documented.
- Category becomes eligible for imports and site usage after Central approval.

## Boundaries

- Central approval is required before the category can be used by sites.
- Site Admin may enable the category only after Central publishes it for site
  usage.
- Site Admin cannot change canonical category schema directly.
- Public Demo Site cannot read draft category schema.

## Future Phase Dependencies

- Phase 4: Category Schema UX and backend.
- Phase 9: Import System and Import UX.
- Phase 10: Site category enablement and projection workflows.
- Phase 13: Public category, listing, product, and compare pages.

## Definition Check

- Actor, trigger, preconditions, happy path, edge cases, output, and future
  phases are documented.
- Attribute sections, facets, comparison, SEO, mapping, test import, and Central
  approval boundary are included.
- No implementation is added in Phase 0.
