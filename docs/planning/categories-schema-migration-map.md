# Categories / Schema — initial schema and dependency map

[ADR-0003](../architecture/adr/0003-categories-schema-ownership.md) defines ownership; the [roadmap](roadmap-v2-screen-driven.md) defines serial phases. [Pre-launch schema consolidation](../architecture/pre-launch-schema-consolidation.md) intentionally replaces the hypothetical deployed-legacy migration plan. No valuable Category/Attribute data was deployed. Disposable environments run `php artisan migrate:fresh --seed`; there is no finalization step.

After the first valuable environment exists, migrations are immutable and future changes use new forward migrations preserving data.

## Initial creation order

| Creation | Final contract |
| --- | --- |
| Central Categories/Products and Category-owned Sections | Stable identities, independent lifecycle/schema approval; Sections structurally flat with no parent column/self FK; archive-first workflows |
| MeasurementDimensions/MeasurementUnits | Existing catalog, unique unit `(id, dimension_id)` supporting composite identity |
| `2026_07_09_000023_000000_create_attribute_definitions_table.php` | Global code uniqueness, reference name/type, complete compatible relational measurement pair; no local ownership |
| `2026_07_09_000023_100000_create_attribute_options_table.php` | Global definition-owned stable option codes/labels/order/visibility |
| `2026_07_09_000023_200000_create_category_attribute_assignments_table.php` | Unique Category/definition, nullable same-Category Section, position/local required/visible/searchable/sortable |
| `2026_07_09_000023_300000_create_attribute_identity_scopes_table.php` | Singleton ID-only row mutex for normal runtime serialization |
| `2026_07_09_000023_400000_create_category_comparison_attributes_table.php` | Explicit same-Category assignment rows with order/visibility |
| Display rules/Product values | Canonical definition references and unchanged typed/range/unit/provenance fact fields |
| Typed translation create migrations | Owner+Locale-ID unique identity from birth; denormalized Locale code for runtime context |
| Attribute mappings | Source/Category/raw-key scope; nullable assignment with same-Category FK; no independent definition pointer |
| Normalized drafts | Generic initial `schema_version=1`; candidate assignment+canonical identity validated by runtime service |
| Projections/search | Initial contract/revision/status/checksum/freshness columns; normal SiteSyncService rebuild |
| Facet definitions | Attribute assignment+same-Category FK; Brand/Rating require null assignment; portable source-shape constraint |

The four Phase 19.2/19.3 expand/cutover migrations are removed. No migration creates then drops obsolete Definition Category/Section/flags/free-text measurements/canonical_code, old mapping/facet pointers, crosswalk/reconciliation/rollback tables or deployment state.

## Membership and facts

| Dependent owner | Authoritative reference | Integrity |
| --- | --- | --- |
| CategoryAttributeAssignment | Category + global Definition | Unique pair; restrictive deletes; composite Section/Category FK |
| Product fact | Product + global Definition | Unique Product/definition; definition FK RESTRICT; Product FK CASCADE; runtime Category membership and removal guard under shared mutex |
| AttributeMapping | Category + nullable assignment | Composite assignment/Category FK; null/unmapped remains supported |
| Attribute FacetDefinition | Category + assignment | Composite FK; DB source-shape check; facet code owns public query key |
| Brand/Rating FacetDefinition | Category, no assignment | Source-shape check prevents fabricated attribute membership |
| CategoryComparisonAttribute | Category + assignment | Unique pair, composite membership FK, deterministic position/id |
| Draft candidate | Assignment ID + Definition ID/code + relational measurement evidence | Validate against resulting Product Category inside approve/publish transaction |
| ContentRelation related_type=attribute | Global Definition ID | Global selector; Content dependency blocks unsafe canonical edits/removal |
| Attribute/Option translations | Canonical owner + Locale ID | Global reuse never duplicates translation per Category |
| Section translations | Category-local Section + Locale ID | Local fan-out |
| Unit translations | MeasurementUnit + Locale ID | Existing measurement domain |

## Normal runtime dependency/concurrency contract

The ID-only Attribute identity scope serializes membership-changing schema writes, Specs, Import mappings/approval/publish and projection rebuilds. It is not deployment state. CategoryLock then locks affected Category IDs deterministically; schema review/approval requires current revision. Core hierarchy mutations retain their independent root/sibling mutex/revisions and ancestry guards.

Global name/option/display-rule semantic changes fan out to all assigned Categories and derived state; local assignment/facet/comparison changes invalidate their Category. Dependency guards prevent changing canonical type/measurement or removing membership under Product/import/draft/facet/comparison/content dependencies. Ordinary canonical/option codes are immutable. Product unit strings remain stored snapshots.

## Screen gates

19.1 hierarchy/schema revisions, 19.2 global/assignment foundation and 19.3 consumer convergence are backend prerequisites for CA-016. No UI screen is delivered by this consolidation. Later facet/comparison presentation, typed localization and SEO templates remain at their separately owned phases. Next only after PR #615 merges: 19.4 CA-016 Categories List.
