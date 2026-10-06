# Pre-launch schema consolidation

CatalogHub has no valuable deployed Category, Attribute, Product, Import or projection data. PR #615 intentionally rewrites pre-launch migration history so the final global Attribute/Category assignment architecture is the initial schema. Disposable developer databases must run `php artisan migrate:fresh --seed`.

## Schema freeze rule

After the first environment contains valuable non-disposable CatalogHub data, existing migrations are immutable. Every subsequent schema change must use a new forward migration preserving deployed data.

## Artifact classification before removal

| Class | Artifacts | Decision |
| --- | --- | --- |
| A: permanent architecture | Global AttributeDefinition/Options; CategoryAttributeAssignment; Category-owned Sections; relational measurement pair; assignment-based Import mappings/Facets/Comparison; typed Locale-ID translations; final factories/seeders | Keep final tables, relationships and portable constraints; create them directly. |
| A: permanent consumers | Specs, draft build/approve/publish, normalizers, facet/search, Product/Category projections, comparison, preview/validation/export/clone, Content selector | Keep target membership and canonical identity validation. |
| B: runtime safety | CategoryAccess, audit allowlists, CategoryLock/SchemaRevision, dependency guards, immutable option codes, translation owner locks, stale detection/translation observer, SiteSyncService | Keep authorization, transactions, no-op suppression, archive checks, audit rollback and freshness fan-out. |
| B: membership serialization | AttributeIdentityLock and singleton scope row | Keep a transactional row mutex before Category/definition locks. Specs/import writes and assignment removal must serialize; rebuilds share the same mutex. Remove deployment/advisory locks and all version/write-epoch state. |
| B: persisted payload contract | Draft, projection/search, schema/export payload versions | Normalize to generic initial `schema_version=1`. Identity-bearing persisted/queued payloads require an explicit contract; no historical conversion or dual reader remains. |
| C: legacy identity conversion | Definition/option crosswalks, old-code reservations, LegacyAttributeBackfill, globalization diagnostics, canonical merge preflight, reconciliation writer/Locale reconciliation and plans/receipts/decisions | Delete. No independent runtime feature needs historical local identities. Code remains globally unique; ordinary canonical code is immutable. |
| C: deployment transition | Phase 19.2 expand/backfill migrations; Phase 19.3 expand/cutover migrations; consumer gate, epochs, readiness marker, rollback rows, finalization service/command | Delete; original create migrations produce target schema. Bootstrap needs no follow-up command. |
| C: reproducibility adapters | SchemaCutoverV2 queries/mutex, runtime adapters, SHA manifest and frozen-contract test | Delete. Refactor draft validation to a permanent target-model service. |
| C: obsolete fixtures/tests | Legacy factory states, v1 conversion/cutover/reconciliation/inverse rollback/finalization tests and cutover races | Delete or replace with final schema and ordinary runtime tests. Keep cross-consumer acceptance, translation freshness, Content Select and runtime concurrency coverage. |

No Category UI phase starts here. This is consolidation of the existing Phase 19.3 backend, in the same PR #615.
