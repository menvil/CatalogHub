# Category Core & Hierarchy Foundation — Phase 19.1

ADR-0003 owns the architecture. This describes the permanent hierarchy/lifecycle/revision foundation used by the global Attribute and assignment consumers. The [pre-launch consolidation policy](pre-launch-schema-consolidation.md) applies: disposable databases run `php artisan migrate:fresh --seed`; future migrations freeze once valuable data exists.

## Current structural integrity

The following shapes are checked by runtime actions and current-state diagnostics:

| Shape | Existing evidence | Handling |
| --- | --- | --- |
| Roots and nested Categories | Nullable parent FK; hierarchy/model tests; demo categories are roots | Preserve IDs and parentage; validate resulting ancestry in actions |
| Duplicate/non-contiguous sibling positions | No position uniqueness; factory defaults to 0; unsigned positions allow gaps | Report; complete-set explicit reorder repairs ordering; no migration normalization |
| Self-parent and cycles | FK checks existence only; no ancestry constraint | Report deterministic cycles; reject unsafe structural actions |
| Archived Categories | Existing Draft/Active/Archived enum and status fixtures | Archive preserves all dependent rows; restore only to Draft |
| Products and Site selections | Product SET NULL FK and SiteCategory RESTRICT; Product/Site fixture tests | Archive only; never delete/detach via Category actions |
| Draft/Reviewed/Approved/Archived schemas | Existing enum/status actions and fixtures | Revision-bound review/approval; actor/time record actual lifecycle actions |
| Flat AttributeSections | Initial flat-shape constraint and same-Category assignment FK | Domain/DB rejects nesting; no legacy flattening workflow |

Initial installation requires no repair, reconciliation or finalization command.

## Hierarchy transaction and revision contract

`CategoryHierarchyScope` has a string primary key (`root`, `parent:<Category ID>`) and a monotonic revision. No NULL uniqueness or implicit nonexistent root row is involved. The root scope starts at revision 0; Category actions create child scopes in their transaction. Scope keys contain stable IDs. Ordinary Category deletion is disabled in the workflow.

Every structural action first writes `root.revision += 0`, then locks root. Root is also the tree mutex: serializing whole-tree structural writes prevents opposite concurrent reparents from independently validating an ancestry that another transaction is changing. It does not increment root revision for unrelated child scopes. After root, old/new scope keys are deduplicated/sorted lexically, created under that shared lock, then locked; affected sibling rows are locked in ascending ID order. Per-scope revisions increment only for a successful structural change. Contiguous positions are zero-based. Create/reparent reject ambiguous sibling ordering; only explicit complete-set reorder repairs it. Reparent compacts the old scope and appends to the new scope.

PostgreSQL/MariaDB hold row write locks until transaction completion. SQLite's first write obtains its shared-file writer lock before any snapshot reads; it does not depend on SQLite implementing `FOR UPDATE`. Actions retry transactional concurrency errors through Laravel's bounded transaction attempts. There are no advisory locks, database-specific runtime SQL, path columns or inherited schemas. Hierarchy mutations trade parallelism between unrelated branches for simple safe whole-tree ancestry validation. This is deliberate foundation scope, not a throughput claim.

Expected revisions are required for create/reorder and both scopes of reparent. Reparent also checks the supplied old-parent snapshot. No-op reorder/reparent leave revisions and audit unchanged. Canonical identity updates allow name/slug only; structural and lifecycle fields are rejected. Changed slug has no redirect side effect. Temporary Filament form orchestration calls explicit reparent and identity actions in one transaction; Filament's independent relationship saver and transaction wrapper are disabled so they cannot bypass actions or open SQLite read snapshots before the first lock write.

## Lifecycle and schema revision

Category: Draft → Active; Draft/Active → Archived; Archived → Draft. Commands already at their target are no-ops. Activate cannot resurrect Archived, and Restore cannot activate it. Category lifecycle does not change schema approval or Site selections. Archive retains Category/Product/child/Section/assignment/value/translation/Site identities and leaves reusable global definitions/options intact. Resource deletion capabilities are always false and no Delete action exists.

Schema revision starts at 1. Draft schemas start with empty review/approval attribution. Review/approval use an expected schema revision (explicit token, or the supplied Category snapshot for existing callers), a Category write lock, and a fresh validator snapshot. Review requires Draft; approval requires Reviewed at the same current revision. Their actors/times are actual attributed facts. Approved → Archived remains the existing archive transition. Explicit restore returns Archived to Draft and clears all attribution. Schema lifecycle changes do not increment structural schema revision.

`SchemaRevision::mutate` is the one current-schema invalidation runner. It authorizes, locks Category, rejects Archived, compares deterministic fingerprints of Sections, assignments, canonical definitions/options, facets and comparison rows, and increments revision once only when content changes. Timestamps and translations are excluded. Reviewed/Approved return to Draft; all reviewed/approved revision, actor and time metadata is cleared. Audit writes share that transaction. Clone locks source/target Categories in ascending order, copies flat local Sections/assignments, reuses global meanings, invalidates target only and cannot restore an archived target. Empty-source/empty-target clone is a no-op.

Section, assignment, canonical definition/option, display-rule, facet and comparison writes use the same revision contract. Local changes invalidate their Category; canonical meaning/option changes invalidate every assigned Category. Clone reuses global definition and option IDs. Option hard deletion rejects; hiding preserves existing facts. Lifecycle actions are MarkReviewed/Approve/Archive/Restore.

Section/assignment/option position validation uses the portable INTEGER upper bound 2147483647. Ungrouped assignment order and cross-Section moves are scoped to the Category; global definitions do not own positions.

The fingerprint is an internal change detector, not an audit snapshot or content version store. Query objects read fixed column allowlists; mutation locks remain in transaction-owning services/actions. Invalidation records a bounded `SchemaMutationOrigin` enum and entity ID. No translation bodies, Product values or config JSON enter audit.

## Permissions, audit and diagnostics

`CategoryAccess` reuses existing PermissionMatrix and AuthorizationService: active actor, Central panel/page admission, owning Category/schema capability, and Central mutation for writes. Role grants are unchanged. Schema-only actors can directly reach the temporary schema route without Category capability; Category-only actors cannot read that editor or call its mutations. No read initializes revision/scope state. Category status/schema status controls are non-dehydrated read-only fields; lifecycle uses explicit header actions. Livewire carries a locked schema revision and reloads it after its own successful mutation; concurrent external writes cause stale review/approval rejection.

Implemented AuditAction names are the seven `catalog.category.*` core events and five `catalog.category.schema.*` lifecycle/invalidation events reserved in ADR-0003. Every event has an action-specific allowlist and null Central audit Site. Root reorder uses the actual hierarchy-scope record as subject because there is no root Category; parent identity/reference, ordered Category IDs and scope revision identify the intent. Other Category/schema events use Category. No GET, rejected operation or no-op audit; audit failure rolls back content/order/revision/lifecycle together. Section/global Attribute/assignment/option, facet and comparison mutations use their implemented allowlisted events; see the audit contract.

Run `php artisan catalog:diagnose-category-hierarchy` against the operator-selected database. It reads IDs, roots/nesting, archived/schema states, Product/Site usage, nested Sections and deterministic cycle/self-parent/missing-parent/order issues. Exit 1 means issues require a separately reviewed repair, exit 0 means no detected Category hierarchy issue. The command does not repair or audit. Initial Section constraints reject nesting; the diagnostic may report constraint-bypassing corruption without repairing it.

Tests cover action invariants, safe archive, every existing invalidation path, no-ops/rejections, attribution, stale revisions, read-only GETs, permission isolation, existing temporary forms, audit rollback and initial schema reset/recreate. Coordinated two-process tests cover root reorder, same-parent scope creation, opposite reparent and review-versus-mutation. SQLite uses a temporary shared-file copy in these tests; PostgreSQL/MariaDB run the same tests through `composer test:database-boundaries`.

## Current backend boundaries

Category Core & Hierarchy Foundation is complete. [Global Attribute foundation](attribute-globalization-foundation.md) and [Schema Consumer Convergence](schema-consumer-convergence.md) extend the same revision/audit/permissions infrastructure. Schema operations use the permanent membership row mutex before ascending Category locks; core hierarchy/lifecycle retains its own Category/root locks. No Definition-local ownership or compatibility mirror exists. Projection freshness and facet/comparison revision paths are implemented.

[Pre-launch consolidation](pre-launch-schema-consolidation.md) makes the target schema initial; disposable databases run `php artisan migrate:fresh --seed`. Category hierarchy safety, archive-first behavior and normal mutation concurrency remain. Next after PR #615 merges: 19.4 CA-016 Categories List; no screen is completed here.
