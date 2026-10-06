# Category Core & Hierarchy Foundation — Phase 19.1

Implementation base: develop `864ea5e4aeca0267ff24954e0fce27413d23644a`, after PR #612. ADR-0003 owns the architecture. No screen or global Attribute implementation belongs to this foundation.

## Inventory before implementation

Repository fixtures and constraints, not production row counts, establish these possible persisted shapes:

| Shape | Existing evidence | Handling |
| --- | --- | --- |
| Roots and nested Categories | Nullable parent FK; hierarchy/model tests; demo categories are roots | Preserve IDs and parentage; validate resulting ancestry in actions |
| Duplicate/non-contiguous sibling positions | No position uniqueness; factory defaults to 0; unsigned positions allow gaps | Report; complete-set explicit reorder repairs ordering; no migration normalization |
| Self-parent and cycles | FK checks existence only; no ancestry constraint | Report deterministic cycles; reject unsafe structural actions |
| Archived Categories | Existing Draft/Active/Archived enum and status fixtures | Archive preserves all dependent rows; restore only to Draft |
| Products and Site selections | Product SET NULL FK and SiteCategory RESTRICT; Product/Site fixture tests | Archive only; never delete/detach via Category actions |
| Draft/Reviewed/Approved/Archived schemas | Existing enum/status actions and fixtures | Preserve legacy states with initial revision; unknown actor/time stay null |
| Flat AttributeSections | Initial flat-shape constraint and same-Category assignment FK | Domain/DB rejects nesting; no legacy flattening workflow |

No production database has been inspected or repaired. Migrations add revision state without modifying names, slugs, parentage, sibling positions, schema content or lifecycle states.

## Hierarchy transaction and revision contract

`CategoryHierarchyScope` has a string primary key (`root`, `parent:<Category ID>`) and a monotonic revision. No NULL uniqueness or implicit nonexistent root row is involved. The migration seeds root and every existing Category's child scope at revision 0. It preserves even invalid historical parentage/order. New Category actions create the child scope in their transaction. Scope keys deliberately contain stable IDs without a new Category FK: old physical-FK tests remain unchanged, and ordinary deletion is disabled in the workflow.

Every structural action first writes `root.revision += 0`, then locks root. Root is also the tree mutex: serializing whole-tree structural writes prevents opposite concurrent reparents from independently validating an ancestry that another transaction is changing. It does not increment root revision for unrelated child scopes. After root, old/new scope keys are deduplicated/sorted lexically, created under that shared lock, then locked; affected sibling rows are locked in ascending ID order. Per-scope revisions increment only for a successful structural change. Contiguous positions are zero-based. Create/reparent reject ambiguous legacy ordering; only explicit complete-set reorder repairs it. Reparent compacts the old scope and appends to the new scope.

PostgreSQL/MariaDB hold row write locks until transaction completion. SQLite's first write obtains its shared-file writer lock before any snapshot reads; it does not depend on SQLite implementing `FOR UPDATE`. Actions retry transactional concurrency errors through Laravel's bounded transaction attempts. There are no advisory locks, database-specific runtime SQL, path columns or inherited schemas. Hierarchy mutations trade parallelism between unrelated branches for simple safe whole-tree ancestry validation. This is deliberate foundation scope, not a throughput claim.

Expected revisions are required for create/reorder and both scopes of reparent. Reparent also checks the supplied old-parent snapshot. No-op reorder/reparent leave revisions and audit unchanged. Canonical identity updates allow name/slug only; structural and lifecycle fields are rejected. Changed slug has no redirect side effect. Legacy form orchestration calls explicit reparent and identity actions in one transaction; Filament's independent relationship saver and transaction wrapper are disabled so they cannot bypass actions or open SQLite read snapshots before the first lock write.

## Lifecycle and schema revision

Category: Draft → Active; Draft/Active → Archived; Archived → Draft. Commands already at their target are no-ops. Activate cannot resurrect Archived, and Restore cannot activate it. Category lifecycle does not change schema approval or Site selections. Archive retains Category/Product/child/Section/definition/option/value/translation/Site identities. Resource deletion capabilities are always false and no Delete action exists.

Schema revision starts at 1. The additive migration binds legacy Reviewed to reviewed revision 1, and legacy Approved to both reviewed/approved revision 1; actor/time remain null. Archived/Draft metadata stays null. Review/approval use an expected schema revision (explicit token, or the supplied Category snapshot for existing callers), a Category write lock, and a fresh validator snapshot. Review requires Draft; approval requires Reviewed at the same current revision. Their actors/times are new attributed facts, not backfilled history. Approved → Archived remains the existing archive transition. Explicit restore returns Archived to Draft and clears all attribution. Schema lifecycle changes do not increment structural schema revision.

`SchemaRevision::mutate` is the one current-schema invalidation runner. It authorizes, locks Category, rejects Archived, compares deterministic fingerprints of explicitly selected Section/definition/option semantic columns, and increments revision once only when content changes. Timestamps and translations are excluded. Reviewed/Approved return to Draft; all reviewed/approved revision, actor and time metadata is cleared. Audit writes share that transaction. Clone locks source/target Categories in ascending order, copies flat local Sections/assignments, reuses global meanings, invalidates target only and cannot restore an archived target. Empty-source/empty-target clone is a no-op.

Wired existing actions: Create/Update/DeleteAttributeSection; Create/Update/MoveAttributeDefinition; Create/Update/DeleteAttributeOption; CloneCategorySchema. Position changes are included through the existing update/move actions. No separate definition delete or section/option reorder action existed to wire. Lifecycle actions are MarkReviewed/Approve/Archive plus new Restore. Current schema ownership is global definitions plus Category assignments; see the consumer contract.

Existing Section/definition/option position validation now uses the portable INTEGER upper bound 2147483647: Laravel's PostgreSQL `unsignedInteger` is signed INTEGER, while the old 4294967295 bound caused even ordinary cross-section moves to fail during overflow checking. No existing positions or column types are migrated. Legacy MariaDB positions above the portable bound remain untouched; cross-section moves reject target sections containing positions at or above that bound. Existing same-section shifts do not provide this legacy-overflow guard. Sectionless source compaction is explicitly restricted to the current Category so moving a definition cannot reorder another Category's schema outside its invalidation lock.

The fingerprint is an internal change detector, not an audit snapshot or content version store. Query objects read fixed column allowlists; mutation locks remain in transaction-owning services/actions. Invalidation records a bounded `SchemaMutationOrigin` enum and entity ID. No translation bodies, Product values or config JSON enter audit.

## Permissions, audit and diagnostics

`CategoryAccess` reuses existing PermissionMatrix and AuthorizationService: active actor, Central panel/page admission, owning Category/schema capability, and Central mutation for writes. Role grants are unchanged. Schema-only actors can directly reach the legacy schema route without Category capability; Category-only actors cannot read that editor or call its mutations. No read initializes revision/scope state. Legacy Category status/schema status controls are non-dehydrated read-only fields; lifecycle uses explicit header actions. Livewire carries a locked schema revision and reloads it after its own successful mutation; concurrent external writes cause stale review/approval rejection.

Implemented AuditAction names are the seven `catalog.category.*` core events and five `catalog.category.schema.*` lifecycle/invalidation events reserved in ADR-0003. Every event has an action-specific allowlist and null Central audit Site. Root reorder uses the actual hierarchy-scope record as subject because there is no root Category; parent identity/reference, ordered Category IDs and scope revision identify the intent. Other Category/schema events use Category. No GET, rejected operation or no-op audit; audit failure rolls back content/order/revision/lifecycle together. Per-entity Section/global Attribute/option audit registries remain future phases, without fake global ownership here.

Run `php artisan catalog:diagnose-category-hierarchy` against the operator-selected database. It reads IDs, roots/nesting, archived/schema states, Product/Site usage, nested Sections and deterministic cycle/self-parent/missing-parent/order issues. Exit 1 means issues require a separately reviewed repair, exit 0 means no detected Category hierarchy issue. The command does not repair or audit. Nested Sections are inventory, not automatically flattened or treated as a Category cycle.

Tests cover action invariants, safe archive, every existing invalidation path, no-ops/rejections, attribution, stale revisions, read-only GETs, permission isolation, live legacy forms, audit rollback and additive/reversible backfill. Coordinated two-process tests cover root reorder, same-parent scope creation, opposite reparent and review-versus-mutation. SQLite uses a temporary shared-file copy in these tests; PostgreSQL/MariaDB run the same tests through `composer test:database-boundaries`.

## Current backend boundaries

Category Core & Hierarchy Foundation is complete. [Global Attribute foundation](attribute-globalization-foundation.md) and [Schema Consumer Convergence](schema-consumer-convergence.md) extend the same revision/audit/permissions infrastructure. Schema operations use the permanent membership row mutex before ascending Category locks; core hierarchy/lifecycle retains its own Category/root locks. No Definition-local ownership or compatibility mirror exists. Projection freshness and facet/comparison revision paths are implemented.

[Pre-launch consolidation](pre-launch-schema-consolidation.md) makes the target schema initial; disposable databases run `php artisan migrate:fresh --seed`. Category hierarchy safety, archive-first behavior and normal mutation concurrency remain. Next after PR #615 merges: 19.4 CA-016 Categories List; no screen is completed here.
