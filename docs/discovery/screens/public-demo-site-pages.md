# Public Demo Site Page Inventory

Task: P00-014 - List Public Demo Site Pages  
Area: UX / Inventory  
Status: Phase 0 discovery artifact

## Purpose

List public demo site pages needed to validate projected catalog data, search,
facets, offers, reviews, leads, content, and responsive behavior.

This inventory is not frontend implementation, routing, Blade templates,
production CSS, or API design.

## Must-Have Pages

| ID | Page | Purpose | Data source | Primary CTA | SEO relevance | Responsive concerns |
| --- | --- | --- | --- | --- | --- | --- |
| PUB-001 | Home Page: Multi-category | Entry for broad portal with multiple enabled categories. | Site settings, homepage blocks, category projections, product projections. | Browse category, search. | High: portal root and category discovery. | Preserve category scanability on mobile; avoid oversized blocks. |
| PUB-002 | Home Page: Single-category | Entry for a focused category vertical. | Category projection, top products, guides, offers. | View products, compare. | High: category-focused root page. | Facet preview and top products must stack cleanly. |
| PUB-003 | Category Page | Explain category and route to listing/content. | Site category projection, SEO content, guides. | View listing. | High: category landing. | Keep intro concise before product access on mobile. |
| PUB-004 | Product Listing Page | Browse, filter, and sort projected products. | `site_search_documents`, offers, facets. | Open product, compare. | High: long-tail listing/facet pages when allowed. | Desktop facets vs mobile drawer; no layout shift. |
| PUB-005 | Product Detail Page | Show projected product detail with offers/reviews/leads. | `site_product_projections`, offers, reviews, content. | View offer, compare, submit lead/review. | High: product SEO. | Gallery, specs, offers, and CTAs must remain accessible. |
| PUB-006 | Compare Page | Compare compatible products by category schema. | Product projections, comparable attributes, offers. | Add product, open offer. | Medium: comparison queries. | Wide table needs mobile grouping or horizontal strategy. |
| PUB-022 | Listing with Desktop Facets | Desktop listing state with persistent facet controls. | Search documents and facet metadata. | Filter products. | High for indexable listing patterns. | Must not apply to mobile drawer layout. |
| PUB-023 | Listing with Mobile Facet Drawer | Mobile listing state with drawer-based filters. | Search documents and facet metadata. | Apply filters. | High as responsive behavior for listing. | Drawer must expose selected filters and clear action. |
| PUB-046 | Offers Table | Product or price section showing normalized offers. | Market offers, price source status, merchant data. | Open offer or submit lead. | Medium: price intent. | Table must collapse into readable offer cards on mobile. |
| PUB-057 | Repair Lead Form | Lead capture page for service/repair/quote intent. | Site form config, product/category context. | Submit lead. | Medium: conversion landing. | Form labels, validation, and privacy text must fit mobile. |

## Additional Page Groups

| Group | Pages | Purpose |
| --- | --- | --- |
| Search | Search Results Page, no-result state | Validate catalog/content search and typo/empty handling. |
| Content | Article Page, Guide Page, FAQ Page | Validate SEO content connected to products/categories. |
| Reviews | Review Form Page, review submitted state | Validate public review intake and moderation boundary. |
| Leads | Lead Form Page variants | Validate inquiry, repair, availability, and quote flows. |
| System | 404, 500, maintenance, legal/privacy | Validate safe public fallback states. |

## Data Source Rules

- Public pages read projections and public content, not draft Central products.
- Product/listing/search pages use `site_product_projections`,
  `site_category_projections`, and `site_search_documents`.
- Offers blocks use normalized market offers; no checkout/cart/order flow exists
  in MVP core.
- Local SEO overrides may shape public metadata without changing canonical data.

## Future Phase Dependencies

- Phase 11: template/theme support.
- Phase 13: Public Demo Site v1.
- Phase 10: Site Admin configuration feeding projections.
- Phase 3/4/9: Central catalog, schemas, imports, and normalized values.

## Phase 0 Boundaries

Included:

- public page IDs and groups;
- purpose;
- data source;
- primary CTA;
- SEO relevance;
- responsive concerns.

Excluded:

- frontend implementation;
- visual design;
- CSS;
- controllers;
- APIs;
- tests.
