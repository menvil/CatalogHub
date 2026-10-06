# Phase 19.3 final consumer inventory

Final ownership audit includes the pre-launch consolidation after reviewed head `fa1dd0ab8e5d3f0cde9750336910d906712d57dd`. [Classification and policy](pre-launch-schema-consolidation.md) was recorded before deleting transition infrastructure. There is no historical runtime exception for retired Definition fields.

| Runtime owner / concrete entry points | Final source / behavior |
| --- | --- |
| SaveProductSpecsAction; ProductAttributeValueValidator; MissingRequiredAttributesResolver; GroupedSpecsPreviewBuilder; ProductSpecsEditor | Product Category assignments; canonical types/options/relational measurements; required/layout local; exact facts preserved |
| SaveCentralProductAction; Product resource Category updates | Membership validation and shared mutex; no orphan Category move |
| SaveAttributeMappingAction; AttributeMappingService; AttributeMappingResource | Assignment pointer + explicit Category; reviewed canonical meaning derived from assignment; null supported |
| AttributeNormalizer; Boolean/Enum/MultiEnum/Number/Unit normalizers | Global canonical type/options/measurement; candidate includes assignment/canonical identity; hidden new enum matching rejects |
| DraftAttributeIdentity; Approve/Publish/RejectNormalizedProductDraft actions | Initial schema_version=1; strict assignment/definition/code/option/unit evidence; atomic resulting-Category publish |
| ImportService/importers; ProcessImportBatch/Drafts jobs | Jobs reload stable batch/draft IDs; no alternate identity version or converter |
| SaveCategoryFacetAction; FacetDefinition rules/resolvers; FacetQueryBuilder; FacetOption; SiteFacetOverride | Same-Category Attribute assignment or no assignment for Brand/Rating; stable FacetDefinition.code query keys |
| SaveCategoryComparisonAction; CategoryComparisonAttribute; ComparisonViewModelBuilder/PublicComparisonQuery | Explicit assignment rows, deterministic order/visibility, typed equality/missing semantics, 2–4 same-Category Products |
| ProductProjectionBuilder | Assignment layout/settings + global meaning; visible options plus referenced hidden codes; initial generic contract |
| CategoryProjectionBuilder | Real Sections/assignments/FacetDefinition/comparison rows; no flag fallback |
| SearchDocumentBuilder | Assignment searchable/sortable, FacetDefinition filters; hidden specs still extractable |
| CategorySchemaValidator/PreviewBuilder/FingerprintQuery | Final assignments/measurement/options/facets/comparison, revision-bound review/approval |
| CloneCategorySchemaAction | Copies flat local Sections/assignments/config; reuses global definition/options IDs |
| ExportCategorySchemaAction; Attributes/AttributeValues/Translations JSONL exporters; SnapshotGenerationService | Initial schema_version=1; distinct global meaning/local config; immutable retained snapshots |
| Content RelationsRelationManager | Global canonical name/code/id ordering; actual mounted Select preload/create regression |
| Typed translation readers/stats/missing/outdated/source hashes; Save/Approve/MarkOutdated actions | Canonical owner+Locale ID; owner lock and Locale reload; no collision-repair subsystem |
| SchemaTranslationProjectionObserver; ProjectionStaleDetector | Product-only localized-copy invalidation; Category-local or shared assignment-based schema translation fan-out |
| AttributeDisplayRule; parser/resolver/converter/formatter; UnitTranslation | Existing relational catalog; no second Unit subsystem; historical Product snapshots readable |
| GlobalAttributeWriter/GlobalOptionMutation/CategoryAssignmentWriter; temporary schema forms | One final writer authority, immutable ordinary codes, dependency guards, bounded audit, revisions/archived/no-op checks |
| AttributeIdentityLock; CategoryLock; SchemaRevision | Permanent transaction row mutex → ascending Category locks → identity rows; no deployment gate/epoch |
| SiteSyncService; stale/public queries | Normal deterministic rebuild, current active schema_version/revision/checksum/status; no stale result exposed as current |
| Factories/seeders | Global definitions + explicit valid assignments, assignment-based mapping/facet/comparison/facts, current draft contract; no obsolete local fixture |

Repository-wide checks cover app, Filament closures, commands/reports/exporters/queue jobs, factories, seeders and tests. Shared words such as Section/Option/assignment `position`/`is_visible`, Facet `is_filterable`, and Product fact `canonical_unit` belong to those distinct owners. They are not Definition-local authority.

Removed classes/tables have no independent permanent feature: crosswalks, legacy backfill/compatibility/diagnostics, reconciliation/merge/flattening decisions, frozen cutover contracts/manifest, deployment versions/epochs/rollback rows and finalizer. Category hierarchy diagnostics remain a current-state read-only integrity tool.
