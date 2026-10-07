# Schema Consumer Convergence — COMPLETE (Phase 19.3)

PR #615 is based on develop `2fea92e80f2623cf50e411e33d9ee2069601c637`. The pre-launch consolidation follows reviewed head `fa1dd0ab8e5d3f0cde9750336910d906712d57dd` in the same branch/PR. CatalogHub has no valuable deployed Category/Attribute/Product/Import dataset. The target architecture is the initial schema, as explicitly authorized in [the consolidation policy](pre-launch-schema-consolidation.md).

Bootstrap disposable environments with:

```sh
php artisan migrate:fresh --seed
```

The application is ready immediately. There is no legacy preflight, reconciliation, contraction, consumer pause/version gate or finalization command. After the first valuable non-disposable environment, existing migrations are frozen and every change uses a new forward migration preserving data.

## Final ownership

| Former authority | Initial authoritative owner | Old field exists? |
| --- | --- | --- |
| Definition Category/Section | CategoryAttributeAssignment Category/nullable Section | No |
| Definition position/required/visible/searchable/sortable | Assignment local fields | No |
| Definition filterable | Real FacetDefinition with assignment for Attribute source | No |
| Definition comparable | CategoryComparisonAttribute | No |
| Definition free-text dimension/unit | Relational MeasurementDimension/MeasurementUnit pair | No |
| Transitional canonical_code/local Category+code uniqueness | Globally unique immutable Definition.code | No |
| Mapping independent definition pointer/composite compatibility FK | Assignment + explicit mapping Category, same-Category FK | No |
| Facet independent definition pointer | Assignment for Attribute source; null for Brand/Rating | No |
| Translation owner+locale-code uniqueness | Typed owner+locale_id | No; denormalized locale code remains for runtime display/resolution |

Global definitions/options are reused across Categories without copying meaning or translations. Sections, assignments, facets and comparison rows own Category-specific behavior. Product facts reference the global definition once per Product. That FK uses RESTRICT so direct definition deletion cannot remove facts; the Product FK retains CASCADE for deletion of the owning Product. Sections are structurally flat from birth, with no parent column, self FK or hierarchy relationships.

## Consumers

Specs reads/writes validate Product Category assignment membership, derive required/layout/presentation/search/sort from assignments and preserve all fact fields. Hidden stored options remain readable; new hidden selections reject. Imports map source+Category+raw key to a nullable assignment. Reviewed mappings derive canonical meaning from that assignment. Approve/publish validates candidate assignment, definition ID/code, type, option ID/code and relational measurement/unit evidence under the same transactional mutex. Resulting Product Category changes revalidate existing and incoming facts; failures roll back the whole publish.

Normalized drafts use the initial `schema_version=1` contract. Each candidate carries assignment ID, canonical definition ID/code and relational measurement identity, alongside the existing fact/provenance fields. Unsupported versions reject; there is no converter or alternate legacy reader. Jobs serialize batch/draft IDs and reload the persisted contract.

Attribute facets use a same-Category assignment FK; Brand/Rating require null assignment. FacetOption/SiteFacetOverride and public filter query keys remain owned by FacetDefinition. Search uses assignment searchable/sortable separately from filters keyed by FacetDefinition.code. Hidden specs can supply facets/comparison without becoming normal visible specs.

Product projections use assignment Section/order/local behavior and canonical meaning/options/measurement. Option vocabulary includes visible options plus hidden options referenced by this Product's fact. Category projections summarize real Sections/assignments/facets/comparison rows. Both and search use generic initial `schema_version=1`, current schema_revision, checksums, status and stale markers. Public queries serve active/current output only. Versioning has an independent persisted payload compatibility purpose; it is not deployment state and does not require activation.

Public comparison uses explicit CategoryComparisonAttribute rows with deterministic order/visibility and same-Category assignments. Existing 2–4 Product limits remain. Numeric canonical values, boolean false, enum codes, multi-enum sets and missing values use typed equality. No winner inference or later presentation fields are added.

Validator/preview/fingerprint/export consume assignments, canonical types/options/relational measurements and real facets/comparison. Schema export and JSONL snapshots explicitly separate global meanings from local configuration with `schema_version=1`. Clone copies local Sections/assignments/configuration and reuses the same global definition/options. Content Attribute selector orders canonical name/code/id; a mounted Livewire options/create test proves it works on the initial final schema.

## Runtime safety and freshness

AttributeIdentityLock remains a simple singleton row mutex with `acquire()` inside a transaction. Scope has only `id`; no write epochs, consumer gate, deployment/advisory lock, rollback state or release listeners exist. Lock order is mutex → ascending Category IDs → definition/assignment/Product/draft rows. Shared locking protects Specs/import writes versus assignment removal, global changes versus membership discovery, schema review versus facet/comparison edits, and rebuilds versus semantic mutation. Expected revisions reject stale local writers.

SchemaRevision remains the one semantic revision mechanism: local changes invalidate one Category, global semantic changes every assigned Category once. Reviewed/Approved become Draft; attribution clears; no-op does nothing; Archived rejects; audit failure rolls back all. Existing ProjectionStaleDetector fans Category changes into Category and Product/search output; SiteSyncService is the normal deterministic rebuild path, outside migration history.

Typed translations use owner/Locale-ID uniqueness from birth. Locale code is a denormalized snapshot used by existing runtime readers/display and refreshed on save. Owner lock and Locale reload serialize creation. ProductTranslation invalidates only its Product/search; CategoryTranslation invalidates Category and its Products/search; Attribute/Option/Unit fan out through all assignments, Section only locally. Save/Approve/MarkOutdated/deletion use the shared observer; true no-ops preserve freshness. Text/status/hash/approval fields remain unchanged by consolidation.

Permissions reuse active Central panel/page admission and schema management plus mutation capability for schema/facet/comparison/global writes. Product/Import writes keep Product module admission. Audit uses safe allowlisted IDs/codes/local flags/counts and existing transactional rollback, without payload bodies. Technical rebuilds retain operational ProjectionJob/ProjectionLog records.

## Verification and boundaries

Target tests cover global reuse/local isolation, membership, constraints, measurements/fact snapshots, options, imports, search/facets/comparison, clone/export, Content Select, translations/freshness, permissions/audit/revisions and real two-process normal mutation races. InitialAttributeArchitectureTest proves exact final columns, absent transition tables/state, target factories and migration reset/recreate. The same target database-boundary suites and fresh migrate+seed run on SQLite, PostgreSQL and MariaDB.

No historical migration upgrade/rollback-to-local model is supported or tested; developer data is disposable. Normal migration down reverses actual table creation. No runtime compatibility exception reads retired Definition columns.

Next after PR #615 merges: **19.4 CA-016 Categories List**. No CA-016/017/018/019 implementation, CA-023/024 UI, SEO templates, CA-026 workspace or duplicate Unit subsystem is introduced.
