# Platform User Roles

Task: P00-001 - Define Platform User Roles  
Area: Product / Discovery  
Status: Phase 0 discovery artifact

## Purpose

This document defines the user roles for CatalogHub and the boundaries between
Central Admin, Site Admin, and Public Site responsibilities.

Phase 0 records product decisions only. This is not a permissions matrix, policy
implementation, database model, Laravel gate, middleware, Filament resource, or
production UI specification.

## Ownership Model

Central Catalog owns canonical truth:

- product identity;
- brand;
- category;
- attribute schema;
- normalized attribute values;
- canonical media;
- global translations;
- product versions;
- accepted corrections;
- import quality.

Site / Portal owns presentation and market adaptation:

- visibility;
- local slug;
- local SEO;
- enabled categories;
- enabled locales;
- market;
- currency;
- theme;
- homepage blocks;
- local media assignments;
- local offers;
- leads;
- reviews.

Public Site users consume projected site data and may submit leads, reviews, and
feedback. They never modify Central Catalog records directly.

## Role Groups

### Central Roles

| Role | Access area | Main tasks | Must not do | Future phases |
| --- | --- | --- | --- | --- |
| `super_admin` | Full platform administration | Manage platform-level settings, users, roles, sites, and emergency operational controls. | Bypass review workflows for routine catalog edits; use public runtime as an admin interface. | Phase 1, Phase 2, Phase 10 |
| `central_admin` | Central Admin | Own canonical catalog operations, approve schema changes, approve corrections, resolve conflicts, supervise imports and sync. | Manage local presentation as if it were canonical data; edit site-only content unless acting through Site Admin scope. | Phase 3, Phase 4, Phase 9, Phase 10 |
| `catalog_editor` | Products, brands, categories, attributes | Edit canonical product identity, category placement, specs, brand relations, and product quality metadata. | Publish directly to public pages; create local SEO overrides; change site visibility rules. | Phase 3, Phase 4 |
| `data_manager` | Data quality, normalization, units, mappings | Maintain normalized values, units, enum options, mapping rules, duplicate detection, and data quality queues. | Approve business publishing decisions alone; modify site themes or homepage blocks. | Phase 4, Phase 9 |
| `import_operator` | Imports and import review | Create batches, inspect raw data, resolve mapping errors, approve or reject normalized drafts when authorized. | Import directly into public products; skip review for new products; change canonical schema without approval. | Phase 9 |
| `translator` | Global translations | Maintain translated attribute names, enum labels, unit labels, product content translations, and translation queues. | Change numeric canonical values; use translation fields for market-specific specs. | Phase 3, Phase 4, Phase 11, Phase 13 |
| `media_manager` | Media Library | Manage media assets, variants, assignments, quality issues, global and locale-specific media. | Store media as language-specific product columns; replace canonical specs with image-only evidence. | Phase 3, Phase 9, Phase 13 |
| `price_manager` | Price sources and offers layer | Configure price sources, monitor feed health, review normalized offers, and maintain market offer rules. | Implement checkout, cart, payment, or delivery workflows in core catalog scope. | Phase 10, later price source phases |

### Site Roles

| Role | Access area | Main tasks | Must not do | Future phases |
| --- | --- | --- | --- | --- |
| `site_admin` | Site Admin for assigned portals | Manage local portal settings, market, locale, visibility, local overrides, sync status, offers, reviews, leads, and content. | Edit canonical product identity, brand, category, or canonical specs directly. | Phase 10, Phase 11, Phase 13 |
| `content_editor` | Site content and local SEO | Manage articles, guides, FAQ, homepage content blocks, intro text, meta titles, and meta descriptions. | Modify canonical specs; approve global corrections; manage platform roles. | Phase 10, Phase 11, Phase 13 |
| `moderator` | Reviews and user-generated content | Review, approve, reject, flag, or hide public reviews and abusive content for a site. | Change product facts to match a review; approve central data corrections. | Phase 10, Phase 13 |
| `support_operator` | Leads and support queues | Process leads, contact requests, support follow-ups, and public feedback routing. | Edit catalog data; manage price sources; change visibility or theme settings. | Phase 10, Phase 13 |

### Public Roles

| Role | Access area | Main tasks | Must not do | Future phases |
| --- | --- | --- | --- | --- |
| `public_visitor` | Public Demo Site | Browse categories, search, compare products, read guides, inspect offers, and view projected product details. | Access admin screens; mutate catalog data; see drafts or stale private data. | Phase 13 |
| `lead_submitter` | Public lead forms | Submit repair, quote, availability, or inquiry forms connected to a site and product context. | Create offers; alter product availability; access lead queue. | Phase 13 |
| `review_author` | Public review forms | Submit product reviews, ratings, and optional evidence for moderation. | Publish reviews without moderation when moderation is required; change product facts directly. | Phase 13 |

## Boundary Rules

- Central roles may change canonical catalog truth only through approved Central
  workflows.
- Site roles may adapt presentation and market behavior but must use correction
  requests for canonical changes.
- Public roles interact only with projections and public forms.
- Direct Public Site writes to Central Catalog are forbidden.
- Numeric values are stored as canonical value plus canonical unit; translations
  affect labels, not numbers.
- Media is managed as assets, variants, and assignments, not as per-language
  product image columns.

## MVP Core Boundary

Included for MVP discovery:

- role names and responsibilities;
- admin area ownership;
- public role intent;
- prohibited actions;
- future phase dependencies.

Not included in Phase 0:

- permissions implementation;
- database schema;
- authentication flows;
- authorization middleware;
- Laravel policies;
- Filament resources;
- production UI.

## Review Checklist

- Central roles are separate from Site roles.
- Public roles are separate from admin roles.
- Canonical ownership remains in Central Catalog.
- Site roles cannot directly edit canonical specs.
- The document remains a discovery artifact and contains no implementation.
