# Schema Consumer Convergence — Phase 19.3

Implementation base: develop `2fea92e80f2623cf50e411e33d9ee2069601c637`, the merged PR #614 tree. **Schema Consumer Convergence — COMPLETE.** Final verification and CI/head evidence are recorded in the PR report. ADR-0003 governs ownership. [The current-consumer inventory](schema-consumer-convergence-inventory.md) was recorded before code changes; Phase 19.0 was not treated as a complete inventory.

## Final ownership and contraction

`AttributeDefinition.code` is the globally unique canonical identity. Definition IDs remain stable unless an operator explicitly approves a retained target. Definitions own reference name, canonical type, relational dimension/unit and global options/translations. `CategoryAttributeAssignment` owns category membership, nullable local Section, position, required, visibility, searchability and sortability. Its unique Category/definition key and Section/Category composite FK remain restrictive. Ungrouped assignments are valid. Category lifecycle remains archive-first.

| Old authority | New authority | Old field removed? |
| --- | --- | --- |
| Definition `central_category_id` | Assignment `central_category_id` | Yes |
| Definition `attribute_section_id` | Assignment `attribute_section_id` | Yes |
| Definition `position` | Assignment `position` | Yes |
| Definition `is_required` | Assignment `is_required` | Yes |
| Definition `is_visible` | Assignment `is_visible` | Yes |
| Definition `is_searchable` | Assignment `is_searchable` | Yes |
| Definition `is_sortable` | Assignment `is_sortable` | Yes |
| Definition `is_filterable` | Real `FacetDefinition` | Yes |
| Definition `is_comparable` | Explicit `CategoryComparisonAttribute` | Yes |
| Definition `dimension` | `measurement_dimension_id` | Yes |
| Definition `canonical_unit` | `canonical_measurement_unit_id` | Yes |
| Transitional `canonical_code` | Globally unique Definition `code` | Yes |
| Mapping independent `attribute_definition_id` | `category_attribute_assignment_id` → definition | Yes |
| Attribute facet independent `attribute_definition_id` | Same-Category assignment → definition | Yes |
| Typed translation owner + locale code key | Owner + `locale_id` | Obsolete code unique keys removed; string evidence retained |
| Section `parent_id` | Flat visible Sections | Column retained for historical evidence; CHECK requires null |

The old Category/code unique key, definition/Category supporting unique, definition Category FK, Section/Category FK, Category/Section/position index and filter/comparison indexes are removed. The old mapping definition/Category FK and transitional assignment/Category/definition FK/index are replaced by assignment/Category membership. Facet membership uses an assignment/Category composite FK. Assignment Category/definition/Section keys and measurement composite keys remain. Product values continue referencing definitions; their definition FK becomes restrictive.

Definition/option crosswalk history, identity scope, reconciliation receipts and reviewed Section/Locale identity evidence remain durable. Translation locale strings, Product fact unit strings, source references, confidence and provenance remain historical facts. No general runtime path mirrors retired ownership fields.

## Migrations and preflight

1. `2026_10_06_000001_expand_schema_consumer_v2.php` adds the consumer gate, facet membership, minimal comparison rows, reviewed reconciliation receipts/evidence, and derived identity-version/schema-revision markers. Facet assignment backfill is exact Category + definition membership. It retains existing IDs/options/site overrides.
2. `2026_10_06_000002_cut_over_schema_consumers.php` takes the identity deployment lock, proves the complete preflight, snapshots inverse identity evidence, repoints approved targets, converts publishable drafts, proves Product fact preservation, marks existing derived output stale, then contracts obsolete authority. It records `cutover_ready_for_finalization=true` only after DDL/constraint verification. Populated upgrades stay at `consumer_version=0`; an empty fresh install activates version 2 immediately without semantic decisions or manual finalization.

`catalog:diagnose-attribute-globalization --actor=ID` is read-only. After the v2 expansion, it reports the v2 preflight even while the database is still version 1. The explicitly historical v1 query only inventories databases before the expansion. Deterministic JSON contains target consumer version, write epoch, counts, Product fact checksum, `cutover_ready`, and sorted blocker classes/identities. Exit 1 means a blocker exists. It does not repair data or serialize Product values/translation bodies/draft JSON into the report.

Blockers cover unresolved/missing definition targets, target chains, future duplicate code and historical vocabulary reservations, missing assignments, Product merge collisions and typed value/option/unit shape, option maps/owner collisions and duplicate historical option-code evidence, Locale collisions, mapping membership, active draft identity, facet membership/type/option vocabulary, display-rule scope/unit conflicts, Content target/link collisions, comparison membership, and nested Sections. Old filter/comparison flags without explicit owned authority require a reviewed decision. Matching names/codes do not establish equivalence.

The migration resolves no live application service or Eloquent owner model. Its preflight, merge checks and draft converter live in `App\Queries\SchemaCutoverV2`; the frozen mutex lives in `App\Services\SchemaCutoverV2`. They use the fixed schema through the database connection. Runtime entry points adapt to these same immutable v2 contracts. `FrozenSchemaConsumerV2ContractTest` pins both migrations and all four contract files by SHA-256 and rejects live application imports/container resolution. A future consumer version must create new files rather than change the v2 interpretation. SiteSyncService is deliberately outside this historical contract.

## Explicit reconciliation and deployment

The production sequence is **backup → expand → preflight/reviewed reconciliation → contract → rebuild/finalize → verify → resume writers/workers**. Stop schema/spec/import writers and queue workers before expansion and retain that deployment pause through successful finalization; maintenance mode may cover public reads until rebuilt output is ready. Take a restorable backup. Apply the expansion first with `artisan migrate --path=database/migrations/2026_10_06_000001_expand_schema_consumer_v2.php --force`, inspect diagnostics, and review an explicit plan. Migration-only deterministic pointer movement invents no actor.

`catalog:reconcile-attribute-globalization PLAN.json --actor=ID` defaults to dry-run; add `--apply` only for the reviewed plan. Both require an active Central schema actor and mutation admission. A plan contains `version: 1`, the diagnostic `expected_write_epoch`, and explicit `definitions` (possibly empty). Supported decisions:

- definitions: legacy ID, retained canonical ID, approved unique code; optional exact relational measurement pair;
- options: legacy option ID and reviewed retained option ID;
- sections: Section ID, expected parent ID, explicit flat position; IDs/translations stay intact;
- facets: explicit `omit` for a legacy flag lacking a real facet;
- comparison: explicit assignment ID list establishing base rows;
- translations: allowlisted typed owner table, translation ID, expected Locale ID and target Locale ID.

Locale corrections preserve old code, text, status, hash and approval fields. They never combine/delete rows or choose a winner. Changes that still collapse rows fail transactionally. Swapping occupied Locale identities or changing type/unit meanings with dependent facts requires a separately reviewed migration; the command does not perform implicit conversions. Ambiguous data remains a blocker.

Every decision is validated in one transaction under the shared identity mutex, followed by ordered affected-Category locks. Archived affected schemas reject semantic decisions. Audit failure rolls back all decisions and revisions. Durable plan-hash receipts make exact replay idempotent, including after cutover. Original legacy ID/code evidence is never overwritten. Option-only plans discover both source and target owners. After a clean report, apply `php artisan migrate --path=database/migrations/2026_10_06_000002_cut_over_schema_consumers.php --force`. Then run `php artisan catalog:finalize-schema-consumer-v2 --actor=ID`. This authorized post-DDL operation holds the same deployment mutex and scope-row transaction, rechecks preflight, rebuilds all configured Site/Locale Category/Product/Search output through existing SiteSyncService, verifies target-version row counts, and activates consumer version 2 only after success. Any rebuild/verification failure rolls back the whole finalization, keeps output stale and writers paused. A repeat after successful activation is a no-op. An incomplete contraction lacks the persisted readiness marker and cannot be finalized. Re-run diagnostics and verify public v2 output before restarting workers/writes. No migration actor or semantic decision is invented by deterministic contraction.

## Consumer version 2

Normalized candidates carry assignment ID, canonical definition ID/code and relational measurement IDs alongside the existing typed value, option code and source-unit facts. Approval/publish require v2 inside the same transaction/lock. Pending-review and approved v1 drafts convert only through exact Category-scoped crosswalks. Published/rejected/failed v1 drafts remain historical and cannot be republished under v2 accidentally. Queue jobs here carry batch/draft/cursor IDs, not serialized attribute meanings; referenced drafts are reloaded and version-validated.

Product Specs derive membership/required/layout from assignments. Existing hidden option values remain readable; hiding does not delete facts. Category changes reject orphaning stored or incoming facts. Omitted fields/no-op saves preserve raw, numeric/range, canonical/source-unit, confidence and provenance snapshots. Cutover hashes all Product fact fields except reviewed identity/code repointing, checks counts and refuses any change to other facts.

Mappings derive definitions solely through same-Category assignments; null/unmapped records retain their meaning. Attribute normalizers generate v2 candidates. Attribute-backed facets use assignments; Brand/Rating facets use no fabricated assignment. Filter extraction uses stable **FacetDefinition.code**, including hidden ordinary specs. Searchability and sortability independently come from assignments. There is no `is_filterable` fallback.

Product projections carry v2 identity, assignments/local layout and exact typed facts. Their option vocabulary contains visible codes plus hidden codes actually referenced by that Product fact; stored hidden values keep their historical labels while unused hidden options are omitted. Category summaries come from Sections/assignments/global definitions, real facets and explicit comparison rows. Comparison preserves 2–4 same-Category/site/Locale constraints, explicit configured order, exact scalar/enum identity, multi-enum set equality, normalized numeric/common-unit semantics and distinct missing/zero/false/empty values. It infers no winner.

Schema validation/preview/fingerprinting and export use assignments and relational measurements. Export version 2 separates global definitions/options/measurement from local assignments/Sections and real facet/comparison references. Clone copies flat Sections and local assignment configuration while reusing global definition/option IDs. Facet/comparison cloning is outside the existing clone contract. Unresolved identity blocks clone.

Content Attribute selectors preload globally ordered name/code/id definitions on the contracted schema, with no Category-local ordering. Content keeps canonical definition references; reviewed merges repoint them transactionally after duplicate-link checks. Global Attribute/Option translations stay shared; Section translations remain local and Unit translations stay Unit-owned. Readers use Locale IDs. Source hashes use relational measurement identity. No historical snapshot file is rewritten.

## Locking, invalidation and rebuilding

Schema, Specs, Product Category changes, mapping writes, draft approval/rejection/publish, translation owner creation, reconciliation, schema review and derived rebuild use `AttributeIdentityLock`. SQLite obtains a real write lock on the scope row; PostgreSQL uses the scope row lock. MariaDB additionally obtains a connection advisory lock before the transaction row lock and holds it across implicit DDL commits. Connection transaction completion releases that lock; deployment holds it through contraction.

Local assignment/facet/comparison changes invalidate one Category via Phase 19.1 `SchemaRevision`. Global meaning/options/display edits lock and invalidate every assigned Category in sorted order. Reviewed/Approved becomes Draft, attribution clears; no-op invalidates nothing and audit failure rolls everything back. Source/Locale translation changes invalidate relevant derived outputs. ProductTranslation normal save/approve/outdated/delete events mark only the owning Product projections and product search documents stale; text/status no-ops do not invalidate. CategoryTranslation invalidates its Category and Products; shared Attribute/Option/Unit translations fan out across assigned Categories, while Section translations remain local. Removal guards include Category facts, mapping/drafts, facets, comparison, display and Content dependencies.

The existing Site sync/rebuild path produces v2 Category/Product/Search Site+Locale outputs with schema revisions and deterministic checksums. V1/mismatched-revision current output is rejected or marked stale. Canonical fan-out invalidates every affected Category and Product/search output; no second queue/version system is introduced. The historical migration never rebuilds or resolves SiteSyncService. The post-migration finalization command uses runtime projection services; rebuild failure prevents activation. Public readers require active v2 output and cannot return stale/v1 rows during the paused interval. Empty fresh migrate + seed needs no operator finalization. The cross-consumer acceptance fixture proves TVs/Monitors share canonical `screen_size` and enum options while respecting different local settings, real facet keys, hidden historical option rendering, translations, mappings, drafts, comparison and two idempotent rebuilds.

## Real rollback boundary

Technical rebuild/finalization does not advance the target write epoch. Before a post-cutover target write, `down()` restores the exact pre-cutover local columns/keys/pointers from persisted inverse evidence, reintroduces absorbed definition/option rows, restores active v1 draft payloads and marks derived data stale. It restores the state **after reviewed operator decisions and before cutover**; it does not undo those decisions or alter their audit/history.

Target tables, including Products, Product translations and Category review/lifecycle state, have write-epoch triggers covering direct SQL as well as backend actions. The portable Category lock’s unchanged `schema_revision + 0` is excluded; acquiring a lock alone does not close the inverse boundary. Any post-cutover target insert/update/delete closes the inverse boundary. Downgrade then refuses before destructive DDL and requires backup/replay or a separately proven inverse; recreating empty columns is not recovery.

SQLite schema-copy operations run with foreign keys managed outside the isolated DDL transaction and recreate retained triggers; PostgreSQL supports transactional DDL. MariaDB DDL cannot be atomically rolled back: the advisory lock plus persisted paused consumer version prevents partial state from becoming writable/active. An interrupted DDL deployment/inverse requires backup/recovery and investigation; no automatic resume or fabricated successful rollback is claimed. Full migration/cutover/reconciliation/inverse/concurrency tests exercise all three engines. CI tests the clean inverse before seed writes and separately proves the post-write refusal.

## Scope

Backend consumer convergence only. No CA-016, CA-023/024 UI, SEO template engine, CA-026 workspace or second Unit subsystem. Existing temporary forms have backend membership adapters. No visual baseline changes. Next: **19.4 CA-016 Categories List**.
