# Global Attribute + Category Assignment Foundation — COMPLETE (Phase 19.2)

The final foundation is CatalogHub's initial database architecture. [Pre-launch schema consolidation](pre-launch-schema-consolidation.md) supersedes the former expand/backfill deployment approach: no valuable Category/Attribute dataset was deployed. Disposable databases must run `php artisan migrate:fresh --seed`. After the first valuable environment, existing migrations are immutable and changes require forward migrations.

## Canonical and local ownership

`AttributeDefinition` owns `id`, globally unique stable `code`, reference `name`, `data_type`, nullable `measurement_dimension_id`, nullable `canonical_measurement_unit_id`, and timestamps. It has no Category, Section, layout position, local booleans, free-text measurement fields or transitional code. Ordinary code identity is immutable. Type/dimension/unit edits with dependencies require an explicit future data migration; names may change under normal audit/revision fan-out.

`CategoryAttributeAssignment` owns `id`, `central_category_id`, `attribute_definition_id`, nullable `attribute_section_id`, `position`, `is_required`, `is_visible`, `is_searchable`, `is_sortable`, and timestamps. Category/definition is unique. The composite Section/Category FK guarantees placement in the same Category on SQLite, PostgreSQL and MariaDB. Null Section means Ungrouped. Category/definition/Section deletes are restricted while assigned. No filter/comparison boolean exists on assignments.

`AttributeSection` is Category-owned, with stable code, reference name, local order/display/collapse/visibility. Sections have one visible level, enforced in domain validation and portable DB constraints. Category hierarchy remains separate and may be nested safely.

Options are global through their definition. ID, code, label, position, visibility and typed translations are retained. Ordinary option code changes and hard deletion reject; hide with `is_visible=false`. Hidden options cannot be chosen/import-matched as new facts, while existing facts remain readable with their labels. There is no crosswalk, historical code reservation or reconciliation feature.

## Measurements

Reuse MeasurementDimension and MeasurementUnit. An unmeasured definition has both IDs null. A measured definition requires a complete pair, integer/decimal type and a unit belonging to its dimension. Composite unit/dimension FK and shape CHECK (equivalent SQLite insert/update guards) enforce this at DB level; GlobalAttributeValidation enforces it in actions. Product raw/typed/range/unit/confidence/provenance snapshots are facts and are not recalculated by schema changes.

## Mutation contract

Global create/update and assign/configure/move/unassign actions require active Central panel/page admission, `catalog.schema.manage` and `central.mutation.execute`. They use transactions, deterministic locks, bounded audit, no-op suppression and the existing SchemaRevision infrastructure. Archived schema mutation rejects.

AttributeIdentityLock is a permanent transaction row mutex in `attribute_identity_scopes`, containing only the singleton ID. It serializes membership discovery, global code creation, schema mutations, Specs/import writes and projection rebuilds before Category/definition locks. A row write obtains SQLite's writer lock; PostgreSQL/MariaDB hold its transactional row lock. No deployment lock, version gate, epoch or transaction listener is needed.

Global changes lock assigned Categories in ascending ID order and invalidate each once. Local assignment changes invalidate their Category. Reviewed/Approved return to Draft, clearing review/approval metadata; no-op and rejected writes invalidate nothing. Audit failure rolls back every mutation and affected revision.

Assignment removal checks Product facts, mappings, active drafts, facets/comparison, display rules and Content dependencies. Hiding remains available. Future SEO bindings must extend this dependency boundary when that domain exists.

Audit events: `catalog.attribute.created/updated`, `catalog.category.attribute.assigned/configured/moved/unassigned`. Safe snapshots contain bounded IDs/codes/reference names/type/measurement/local settings/changed fields/counts, never Product values, translation bodies or draft JSON.

## Initial schema and fixtures

Create migrations directly define global meanings and local assignments; original typed translation tables use owner/Locale-ID uniqueness. Normal factories express reusable definitions with explicit Category assignments, assignment-valid facts/mappings/facets/comparison and current draft payloads. No legacy fixture architecture or backfill command is required.

Consumer ownership is documented in [Schema Consumer Convergence](schema-consumer-convergence.md). No CA screen is completed by this foundation.
