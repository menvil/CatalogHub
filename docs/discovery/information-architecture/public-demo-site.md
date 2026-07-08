# Public Demo Site Information Architecture

Task: P00-004 - Map Public Demo Site Information Architecture  
Area: UX / IA  
Status: Phase 0 discovery artifact

## Purpose

The Public Demo Site validates how Central Catalog data, site settings, market,
locale, media, local overrides, SEO, offers, reviews, and lead flows become a
public projection.

This document maps public pages only. It does not define frontend routes,
Blade templates, controllers, production CSS, API endpoints, or a deployed demo.

## Public Data Model Assumption

Public runtime reads projections:

```txt
central data
+ site settings
+ market
+ locale
+ media
+ local overrides
+ SEO
= site projection
```

Expected projection sources:

- `site_product_projections`;
- `site_category_projections`;
- `site_search_documents`.

## Demo Configurations

| Demo | Type | Purpose | Required categories | Notes |
| --- | --- | --- | --- | --- |
| Tech multi-category demo | Multi-category | Validate broad navigation, homepage modules, search, compare, and category discovery. | Monitors, Keyboards, Coffee Machines or similar. | Homepage emphasizes category hubs and cross-category search. |
| Monitors single-category demo | Single-category | Validate deep category filters, specs, comparison, offers, and guides. | Monitors. | Homepage behaves like a category-focused buying guide. |
| Keyboards single-category demo | Single-category | Validate another single-category vertical with different facets and spec sections. | Keyboards. | Useful for testing enum-heavy attributes and content modules. |

## Multi-Category vs Single-Category

Multi-category public site:

- home page highlights multiple category entry points;
- navigation exposes category groups;
- search spans all enabled categories;
- compare may be constrained by category after product selection;
- content may include cross-category guides.

Single-category public site:

- home page behaves like a category landing page;
- navigation is narrower and more filter-focused;
- search defaults to one category;
- compare is category-native;
- guides and FAQ are category-specific.

## Sitemap

| Page | Purpose | Key data | Primary CTA | Important states |
| --- | --- | --- | --- | --- |
| Home Page: Multi-category | Entry point for a broad tech portal. | Enabled categories, featured products, guides, offers, homepage blocks. | Browse category, search, compare. | Empty category, missing block item, stale projection. |
| Home Page: Single-category | Entry point for a focused vertical. | Category projection, top products, facets preview, guides, offers. | View best products, start comparison. | No visible products, no offers, missing translations. |
| Category Page | Explain category and guide users into listings/content. | Category projection, SEO copy, top facets, guides. | View products, read guide. | Category disabled, incomplete schema. |
| Product Listing Page | Browse products with filters and sorting. | Search documents, facets, offers, ratings. | Open product, compare, filter. | No results, filter conflict, stale offers. |
| Product Detail Page | Show projected product facts and conversion blocks. | Product projection, media, specs, reviews, offers, related content. | View offers, compare, submit lead/review. | No offers, no reviews, missing media. |
| Compare Page | Compare products within compatible category/schema. | Product projections, comparable specs, offers. | Add/remove product, open offer. | Incompatible category, missing comparable value. |
| Search Results Page | Search projected catalog and content. | Search documents, categories, products, articles. | Open result, filter result. | No results, typo suggestion, stale index. |
| Article Page | Support informational SEO and buying decisions. | Local content, related products/categories. | Open product/listing, submit lead. | Draft not public, related product hidden. |
| Guide Page | Structured buying guide tied to category/use case. | Guide content, comparison blocks, selected products. | Compare products, browse listing. | Missing referenced product, outdated guide. |
| FAQ Page | Answer common category/site questions. | FAQ content, related category/product links. | Open product/listing, submit lead. | Empty FAQ, unpublished item. |
| Lead Form Page | Collect inquiry, repair, quote, or availability leads. | Site context, product/category context, form config. | Submit lead. | Validation errors, spam detected, product hidden. |
| Review Form Page | Collect user ratings and review content. | Product projection, review form config. | Submit review. | Moderation required, duplicate review, product hidden. |
| System Pages | Handle error, empty, legal, and utility pages. | Site settings, legal content, status messages. | Return home/search. | 404, 500, maintenance, no locale. |

## Required Page Relationships

- Home pages link to category, listing, search, article, and guide pages.
- Category pages link to listing, guides, FAQ, and top products.
- Listing pages link to product detail, compare, offers, and lead flows.
- Product detail pages link to compare, reviews, offers, leads, similar products,
  and related content.
- Search results can return products, categories, articles, guides, and FAQ.

## SEO Requirements

- Product, category, listing, article, guide, and FAQ pages must be projection
  based and indexable when the site marks them public.
- Draft, hidden, excluded, or stale-private products must not appear publicly.
- Multi-category and single-category home pages need separate SEO assumptions.
- Local overrides may affect SEO fields without altering canonical product data.

## Future Phase Dependencies

- Phase 10: Sites, Markets, Portal Admin UX.
- Phase 11: Template / Theme System v1.
- Phase 13: Public Demo Site v1.
- Phase 3, Phase 4, and Phase 9 provide canonical catalog, schema, and import
  foundations consumed by projections.

## Phase 0 Boundaries

Included:

- public sitemap;
- multi-category and single-category separation;
- demo configuration assumptions;
- page purpose and state inventory.

Excluded:

- frontend implementation;
- Blade templates;
- production CSS;
- controllers;
- APIs;
- database migrations;
- deployment.
