# Public Page Performance Budget

This budget is a launch guardrail, not a claim about measurements that have not been taken in the target environment. Record p50/p95 results against production-like data before checking the readiness item.

## Server and Payload Targets

Targets apply to a warm application, warm configuration/routes/views, and a representative projection dataset. They exclude client network latency and external widgets.

| Page | Maximum application DB queries | p95 server response target | Maximum initial HTML | Primary read model |
| --- | ---: | ---: | ---: | --- |
| Home | 20 | 500 ms | 200 KiB | Site config, homepage blocks, category/product projections |
| Category/listing | 25 | 750 ms | 300 KiB | Category projection and paginated search documents |
| Product detail | 25 | 500 ms | 250 KiB | Product projection, current offers/reviews/content summaries |
| Compare (up to 4 products) | 30 | 750 ms | 300 KiB | Product projections and bounded offer summaries |
| Search results | 25 | 750 ms | 300 KiB | Site search documents and facets |

Budgets are per request. A query executed once per result card is an N+1 defect even when a small fixture happens to remain below the numeric limit.

## Projection-First Rules

- Public product, listing, compare, and search rendering must not join central catalog tables to assemble display data.
- Localized titles, specs, SEO, media, facets, and search/sort values come from site projections/search documents.
- Current offer/review/content queries must be bounded and scoped by site/product identifiers.
- Admin screens may read canonical tables directly, but must paginate and must not time out under representative data.
- Projection rebuilds happen outside public request rendering.

## Images and Assets

- Cards use a `thumbnail`/`card` derivative; product heroes use the bounded hero/detail variant.
- Never serve original uploads in listings, search results, comparison rows, or homepage cards.
- Include explicit width/height or an aspect-ratio box to limit layout shift.
- Lazy-load below-the-fold images and do not preload every card image.
- Vite assets are content-hashed and may be cached immutably; HTML caching must respect site/locale/content changes.

## Query and Cache Expectations

- Resolve site and locale once per request.
- Eager-load bounded relationships or read denormalized projection payloads; do not query attributes/media per card.
- Cache stable navigation/theme/site configuration with an explicit invalidation path.
- Paginate listing/search results and cap comparison product count.
- Keep public request paths free of external API calls, import work, media generation, and projection rebuilds.

## Measurement Procedure

1. Seed or restore a production-like catalog without real credentials or personal data.
2. Warm framework caches and execute each page once before sampling.
3. Capture at least 30 requests per page at expected concurrency; report p50, p95, error rate, query count, HTML bytes, and peak memory.
4. Inspect query fingerprints for central joins, N+1 patterns, unbounded sorts, and missing indexes.
5. Run browser tooling separately for LCP, CLS, INP, asset transfer, and image variant usage.
6. Record hardware, database size, release commit, cache state, and accepted exceptions.

## Initial Audit

- Existing public page tests explicitly verify that product/listing rendering does not query `central_products` in the covered paths.
- Listing cards have a scaling contract proving that query count does not grow
  from one to twenty cards and remains within the current request ceiling.
- Product offers have a scaling contract proving that query count does not grow
  from one to ten offers and remains within the current request ceiling.
- Other public smoke tests exercise projection-driven routes; production-like
  timing and query ceilings for home, compare, and search remain measurement gaps.
- Vite produces content-hashed production assets.
- Production-like p95 latency, full payload sizes, cache hit rate, and browser Core Web Vitals remain measurement gaps.

If a page exceeds a budget, document the query/asset cause and owner. Do not raise the budget solely to make an unexplained regression pass.
