# Database Index Audit

Audit date: 2026-07-16  
Scope: critical CatalogHub query paths through Phase 21. This is a schema/query review, not a substitute for production `EXPLAIN (ANALYZE, BUFFERS)` evidence.

## Audited Tables

| Table | Critical access patterns | Existing coverage / action |
| --- | --- | --- |
| `central_products` | slug lookup; status/category/brand lists | Unique slug, status and relationship indexes. No speculative multi-column index added. |
| `central_categories` | slug, status/schema status, ordered tree | Unique slug and status/schema/position indexes. |
| `central_brands` | slug and status lists | Unique slug and status indexes. |
| `attribute_definitions` | category/section ordered schema; filter/comparison flags | Category+code identity and category/section/position plus flag indexes. |
| `central_product_attribute_values` | product+definition lookup; facet value lookup | Unique product+definition, definition, enum, unit/value indexes. |
| `media_assets` | UUID/checksum/path/status lookup | UUID/checksum identities, disk+path and status/type/source indexes. |
| `media_assignments` | scoped entity role lookup; reverse asset lookup | Entity+role+locale/site/market composites and media asset index. |
| `import_batches` | source/status history ordered by creation | Added `import_batches_source_status_created_idx`. |
| `raw_products` | batch/source processing; external ID/hash/status | Foreign-key, external ID, hash and status indexes. |
| `normalized_product_drafts` | one draft per raw record; batch status queue | Unique raw product, status and batch+status indexes. |
| `sites` | code/domain resolution and status/mode lists | Unique code/domain and status/mode indexes. |
| `site_products` | site+product identity; failed sync rows per site | Unique site+product; added `site_products_site_sync_status_idx`. |
| `site_product_projections` | site+locale+product identity; slug/status; stale product | Identity unique, site+locale+slug, site+status, central product and checksum indexes. |
| `site_search_documents` | site+locale status/type listing and product identity | Identity unique and site+locale+status/type; price/stock update indexes support filters. JSON facet plans require database-specific measurement. |
| `market_offers` | site-market product offer summary and status | Added market+product+status+currency composite; existing source/status, merchant/product/source identity and freshness indexes remain. |
| `price_history` | offer timeline by checked time | Offer+checked and checked-at indexes. |
| `price_source_sync_logs` | latest successful/failed run per source | Added source+status+finished composite. |
| `central_change_requests` | status queue ordered by creation; site/product queues | Added status+created composite; existing site/status and product/status indexes. |
| `sync_logs` | site/status/operation timelines and product lookup | Site/status/operation composites with created time and product index. |
| `catalog_snapshots` | status/type history ordered by creation and UUID lookup | Status/type+created composites, UUID unique and creator index. |

## Added Indexes

Migration `2026_07_16_000010_add_readiness_query_indexes.php` adds five bounded indexes that match concrete application queries. It deliberately avoids indexing JSON payloads, low-value counters, every timestamp, or every single foreign key combination.

## Production Verification

1. Capture slow query fingerprints for public listing/product, import queue, corrections, price summary, sync logs, and snapshots.
2. Run database-native `EXPLAIN` against representative cardinality and parameter distributions.
3. Confirm the planner selects the intended index and measure write amplification/index size.
4. Check duplicate/redundant indexes after the migration on the production database engine.
5. Remove or adjust an index only in a reviewed migration with before/after evidence.

## Known Gaps

- JSON facet filtering/search strategy varies by database engine and needs production PostgreSQL plan evidence before adding GIN/expression indexes.
- Text search indexes are not introduced by this audit; existing search documents remain the bounded read model.
- Small fixture tests validate schema presence, not selectivity or planner behavior.

