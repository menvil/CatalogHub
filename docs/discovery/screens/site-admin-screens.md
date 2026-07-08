# Site Admin Screen Inventory

Task: P00-013 - List Key Site Admin Screens  
Area: UX / Inventory  
Status: Phase 0 discovery artifact

## Purpose

List must-have Site Admin screens for managing local portals, projections,
visibility, overrides, offers, reviews, leads, content, and theme setup.

This inventory does not define production UI, routes, Filament resources,
Livewire components, database models, or permission implementation.

## Must-Have Screens

| ID | Screen | Purpose | Primary actor | Primary actions | Key data | Empty/error states |
| --- | --- | --- | --- | --- | --- | --- |
| SA-001 | Site Dashboard | Monitor local portal health and projection readiness. | `site_admin` | Inspect stale products, open leads/reviews, retry sync, review source status. | Site identity, market, locale, visible/hidden/stale counts, leads, reviews. | No products, stale projections, failed price source, missing translations. |
| SA-013 | Site Products List | Manage local product visibility and status. | `site_admin` | Show, hide, exclude, filter stale/no-offer products, open preview. | Site products, visibility, category, offers, translations, projection state. | Empty category, hidden all, stale, no offers, slug conflict. |
| SA-019 | Product Projection Preview | Inspect projected public product before or after publication. | `site_admin` | Preview projection, compare canonical/local fields, request rebuild. | Product projection, local overrides, media, offers, SEO, sync version. | Projection missing, stale, conflict, missing media. |
| SA-022 | Theme Selection | Choose a site theme from compatible options. | `site_admin` | Select theme, preview, save draft, apply. | Theme manifest, supported templates, enabled features. | No compatible theme, invalid manifest, missing template. |
| SA-023 | Theme Compatibility Check | Verify theme support for site categories, blocks, and features. | `site_admin` | Run check, inspect warnings, block apply when incompatible. | Enabled categories, templates, block registry, feature requirements. | Incompatible category, missing block, unsupported feature. |
| SA-025 | Homepage Blocks Editor | Configure local homepage blocks. | `content_editor` | Add block, reorder, select products/categories/content, publish. | Blocks, references, visibility, locale content. | Empty homepage, missing referenced item, invalid block config. |
| SA-029 | Sync Dashboard | Track Central to Site sync and projection jobs. | `site_admin` | Retry failed rebuild, filter stale items, inspect sync log. | Sync logs, stale reasons, job status, conflicts, versions. | Failed job, stuck queue, conflict, no affected items. |
| SA-039 | Site Price Sources | Manage site-specific price sources and manual offers. | `price_manager` | Add/disable source, review status, add manual offer, inspect failures. | Source health, offers, markets, last run, error count. | Source failed, stale offers, no offers, malformed feed. |
| SA-047 | Reviews List | Moderate public reviews for the local site. | `moderator` | Approve, reject, hide, flag, respond. | Review text, rating, product, author metadata, moderation status. | No reviews, spam, duplicate review, abusive content. |
| SA-051 | Leads List | Process public lead submissions. | `support_operator` | Assign, mark contacted, close, export, flag spam. | Lead type, product/category context, contact data, status. | No leads, spam, invalid contact, duplicate lead. |

## Supporting Site Screens

- Site settings overview.
- Markets/locales coverage.
- Enabled categories.
- Local override editor.
- Correction request list/detail.
- Content article/guide/FAQ editors.
- Polls list/detail.

## Site/Central Boundary

Site Admin screens may show canonical values and Central sync status, but must not
allow direct edits to canonical product identity, brand, category, schema, or
canonical specs. Canonical changes use correction requests.

## Future Phase Dependencies

- Phase 10: Sites, Markets, Portal Admin UX.
- Phase 11: Template / Theme System v1.
- Phase 13: Public Demo Site v1.
- Phase 3/4/9 provide Central data, schemas, and imports consumed by the site.

## Phase 0 Boundaries

Included:

- screen IDs;
- must-have screens;
- purpose, actor, actions, key data;
- empty/error states;
- Site/Central boundary.

Excluded:

- production UI;
- routes;
- controllers;
- policies;
- Filament/Livewire resources;
- frontend templates.
