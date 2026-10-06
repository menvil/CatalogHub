# Phase 19.3 current consumer inventory

Audit base: `2fea92e80f2623cf50e411e33d9ee2069601c637` (merged PR #614); the tree equals Phase 19.2 head `9f6b79d0aeb0d3d0eef549de10b20cc16cf90421`. Inventory recorded before runtime changes. Line numbers below refer to this base, not the implementation head.

## Authority and indirect consumers

| Owner | Current source | Target source / contraction dependency |
| --- | --- | --- |
| Product Specs validator/save/editor/required/preview | Category definitions, local flags, free measurement strings | Category assignments and relational canonical measurement; facts stay unchanged |
| Import mapping service, mapping resource, normalizers | Mapping definition pointer + definition Category | Mapping assignment; canonical definition derived, same-Category FK |
| Draft approval/publish | v1 JSON candidates with optional definition ID/code; publish checks definition Category | active v2 assignment + canonical identity under identity mutex |
| SerializedPhpProductImporter | Calls registered typed importer contract; does not construct attribute JSON | Importer output must supply target identity; reject unsupported active drafts |
| ProcessImportBatch/Drafts/Publish jobs | Batch/draft IDs and media cursor, no attribute identity serialized | Resolve current persisted version inside transaction, no fabricated job identity version |
| Facets, FacetOption, SiteFacetOverride | Facet definition pointer, Category-local rule; existing facet query keys | Attribute-source assignment; preserve source variants, facet IDs/codes/options/overrides |
| Product projection | visible Sections/local definitions/visible options only | assignment layout, global meaning, historical hidden options; independent extraction for facets/comparison/search |
| Category projection | is_filterable/is_comparable on definitions | real FacetDefinition/comparison base rows, Sections/assignments |
| Search document | flattened visible specs and old flags; Attribute code used as facet key | searchable/sortable assignment; facet code with fact extracted through assignment |
| Public comparison | union of all projected specs | explicit comparison rows and typed equality, same Category/Site/Locale |
| Schema validator/preview/fingerprint/export | local definition fields, nested Sections, v1 export | assignments + canonical definitions + relational measurements + facets/comparison; v2 export |
| Schema clone | duplicates definitions/options and nested parent links | clone flat local Sections/assignments, reuse global IDs/options |
| Content relations/selectors | canonical ID but label/scoping uses compatibility Category | global canonical ID; explicit transactional repoint with duplicate detection |
| Attribute/Option/Section/Unit translations/hash/export | typed owners, Locale IDs + old code keys | global Attribute/Option identity; local Section identity; preserve text/status/hash/approval; preflight Locale collisions |
| Display rules/Unit normalizers/formatting | global definition IDs but free strings | existing measurement domain relational IDs; immutable Product unit snapshots |
| Global/assignment/legacy schema actions | compatibility mirroring on either path | single authoritative target writer; no local definition writes |
| Direct Filament mapping/facet/display rule writes | ORM resources bypass schema revision/identity serialization | existing forms use authorized backend mutation primitives |
| SiteSync/stale queries/public projection queries | central timestamp freshness, Category-only invalidation | target version/schema revision and Category-to-Product fan-out; rebuild current derived rows |
| Catalog JSONL snapshots | local definition export | versioned global/local rows; historical snapshots remain immutable |
| Factories/seeders | default local definition and independent Product fixture | default global meaning, explicit assignments; legacy states restricted to migration tests |

Newly confirmed gaps beyond the Phase 19.0 map: direct resource writers; mutable Phase 19.2 mirroring/fingerprint bridge; active draft approval without identity mutex; hidden options excluded by projection; no ungrouped projection bucket; Category invalidation omits Category Products; same Attribute/facet code assumption; ID-only import jobs (no attribute identity to version). No production equivalence, counts, or flattening decisions are inferred from code.

## Concrete reference index

This includes indirect relations, payload readers, fixtures and test contracts. Every listed hit is review data; each change must be checked against its implementation.

| Current file | Base reference lines |
| --- | --- |
| `app/Actions/CategorySchema/AssignAttributeToCategoryAction.php` | 5, 16 |
| `app/Actions/CategorySchema/CloneCategorySchemaAction.php` | 7, 8, 37, 43, 49, 73, 74 |
| `app/Actions/CategorySchema/Concerns/ValidatesAttributeDefinitionData.php` | 6, 9, 35 |
| `app/Actions/CategorySchema/CreateAttributeDefinitionAction.php` | 5, 7, 8, 10, 16, 18, 23, 30, 32, 36, 39, 44, 46, 50 |
| `app/Actions/CategorySchema/CreateAttributeOptionAction.php` | 6, 7, 8, 10, 17, 22, 29, 31, 34, 44, 47, 51, 54, 59, 61, 65, 66 |
| `app/Actions/CategorySchema/CreateAttributeSectionAction.php` | 6, 15, 20, 27, 41, 42, 47, 53, 55, 59 |
| `app/Actions/CategorySchema/CreateGlobalAttributeDefinitionAction.php` | 5, 9, 14 |
| `app/Actions/CategorySchema/DeleteAttributeOptionAction.php` | 6, 7, 12, 14, 22, 24, 27 |
| `app/Actions/CategorySchema/DeleteAttributeSectionAction.php` | 6, 7, 12, 14, 22, 24, 27, 31, 35 |
| `app/Actions/CategorySchema/ExportCategorySchemaAction.php` | 5, 6, 7, 31, 40, 56 |
| `app/Actions/CategorySchema/MoveAttributeDefinitionAction.php` | 6, 7, 8, 13, 15, 22, 24, 25, 28, 31, 32, 36, 50, 52, 55, 58, 64, 75, 81, 88, 94 |
| `app/Actions/CategorySchema/UpdateAttributeDefinitionAction.php` | 5, 8, 11, 17, 19, 24, 31, 33, 67, 79, 80, 81 |
| `app/Actions/CategorySchema/UpdateAttributeOptionAction.php` | 6, 7, 8, 15, 20, 24, 25, 31, 33, 36, 46, 50 |
| `app/Actions/CategorySchema/UpdateAttributeSectionAction.php` | 6, 12, 17, 24, 26, 39, 40 |
| `app/Actions/CategorySchema/UpdateGlobalAttributeDefinitionAction.php` | 5, 9, 14 |
| `app/Actions/Imports/PublishNormalizedProductDraftToCentralAction.php` | 7, 14, 34, 99, 104, 123, 131, 135, 138, 139, 141, 147, 160 |
| `app/Actions/ProductAttributes/SaveProductSpecsAction.php` | 8, 26, 33, 42 |
| `app/Actions/Translations/DetectOutdatedTranslationsAction.php` | 6, 7, 8, 32, 33, 34, 52, 53, 54 |
| `app/Actions/Translations/SaveAttributeOptionTranslationAction.php` | 6, 8, 14, 19, 21, 26, 36 |
| `app/Actions/Translations/SaveAttributeSectionTranslationAction.php` | 6, 8, 14, 19, 21, 26, 36 |
| `app/Actions/Translations/SaveAttributeTranslationAction.php` | 6, 19, 24, 27 |
| `app/Contracts/Imports/AttributeValueNormalizerInterface.php` | 6, 10, 13 |
| `app/Data/Facets/FacetDefinitionData.php` | 8, 11, 33 |
| `app/Domains/Projections/Builders/CategoryProjectionBuilder.php` | 10, 56, 63, 64, 68, 69, 179 |
| `app/Domains/Projections/Builders/ProductProjectionBuilder.php` | 13, 14, 124, 185, 197, 284, 347, 414, 429 |
| `app/Domains/Projections/Builders/SearchDocumentBuilder.php` | 192 |
| `app/Domains/PublicSite/ComparisonViewModelBuilder.php` | 8, 43 |
| `app/Exceptions/CategorySchema/CannotDeleteAttributeSectionException.php` | 7 |
| `app/Exceptions/CategorySchema/CannotManageAttributeOptionException.php` | 7 |
| `app/Exceptions/CategorySchema/CannotMoveAttributeDefinitionException.php` | 7 |
| `app/Filament/Resources/AttributeDisplayRuleResource.php` | 34, 52 |
| `app/Filament/Resources/AttributeMappingResource.php` | 6, 52, 71, 73 |
| `app/Filament/Resources/CentralCategoryResource/Pages/CategorySchemaBuilder.php` | 8, 9, 10, 11, 12, 15, 17, 18, 19, 25, 116, 124, 135, 146, 148, 154, 157, 167, 169, 178, 180, 187, 189, 285, 291 |
| `app/Filament/Resources/CentralProductResource/Pages/ProductSpecsEditor.php` | 8, 84, 94, 121, 129, 158 |
| `app/Filament/Resources/ContentItemResource/RelationManagers/RelationsRelationManager.php` | 6, 110 |
| `app/Filament/Resources/FacetDefinitionResource/Pages/CreateFacetDefinition.php` | 3, 5, 8, 10 |
| `app/Filament/Resources/FacetDefinitionResource/Pages/EditFacetDefinition.php` | 3, 5, 8, 10 |
| `app/Filament/Resources/FacetDefinitionResource/Pages/ListFacetDefinitions.php` | 3, 5, 9, 11 |
| `app/Filament/Resources/FacetDefinitionResource.php` | 7, 8, 10, 27, 29, 66, 86, 158, 159, 160 |
| `app/Filament/Resources/NormalizedProductDraftResource.php` | 89 |
| `app/Filament/Resources/SiteProductProjectionResource.php` | 105 |
| `app/Http/Controllers/CentralAdmin/TranslationEditorController.php` | 5, 6, 12, 13, 18, 19, 20, 71, 83, 90, 102, 109, 121 |
| `app/Http/Controllers/Public/CompareController.php` | 5, 20 |
| `app/Http/Controllers/Public/ProductController.php` | 74 |
| `app/Http/Requests/CentralAdmin/Translations/SaveAttributeOptionTranslationRequest.php` | 5, 9, 24 |
| `app/Http/Requests/CentralAdmin/Translations/SaveAttributeSectionTranslationRequest.php` | 5, 9, 24 |
| `app/Http/Requests/CentralAdmin/Translations/SaveAttributeTranslationRequest.php` | 5, 25 |
| `app/Models/AttributeDisplayRule.php` | 5, 13, 53, 57 |
| `app/Models/CentralCatalog/AttributeDefinition.php` | 9, 22, 40, 42, 50, 52, 70, 71, 79, 80, 88, 89, 97, 98, 106, 107, 115, 116, 124, 125, 135, 159, 163, 167, 171, 179, 187 |
| `app/Models/CentralCatalog/AttributeDefinitionCrosswalk.php` | 9 |
| `app/Models/CentralCatalog/AttributeIdentityScope.php` | 9 |
| `app/Models/CentralCatalog/AttributeOption.php` | 5, 6, 15, 18, 24, 26, 34, 36, 48, 49, 57, 61, 65, 69 |
| `app/Models/CentralCatalog/AttributeOptionCrosswalk.php` | 9 |
| `app/Models/CentralCatalog/AttributeSection.php` | 5, 6, 27, 29, 39, 41, 54, 55, 71, 79, 87, 91, 101, 105 |
| `app/Models/CentralCatalog/CategoryAttributeAssignment.php` | 11, 33, 36, 39, 42 |
| `app/Models/CentralCatalog/CentralCategory.php` | 85, 89, 93, 95, 97 |
| `app/Models/CentralCatalog/CentralProductAttributeValue.php` | 14, 75, 79 |
| `app/Models/ContentRelation.php` | 6, 42 |
| `app/Models/FacetDefinition.php` | 7, 9, 30, 43, 45, 48, 50, 69, 70, 78, 79, 87, 88, 101, 104 |
| `app/Models/FacetOption.php` | 59, 62 |
| `app/Models/Imports/AttributeMapping.php` | 5, 18, 27, 61, 64 |
| `app/Models/Imports/NormalizedProductDraft.php` | 27, 44, 67 |
| `app/Models/SiteFacetOverride.php` | 46, 49 |
| `app/Models/Translations/AttributeOptionTranslation.php` | 6, 9, 16, 18, 21, 23, 34, 37 |
| `app/Models/Translations/AttributeSectionTranslation.php` | 6, 9, 16, 18, 21, 23, 34, 37 |
| `app/Models/Translations/AttributeTranslation.php` | 6, 15, 34, 37 |
| `app/Queries/Attributes/AttributeGlobalizationDiagnosticsQuery.php` | 7, 8, 9, 10, 14, 17, 18, 39, 41, 52, 70, 81, 83, 99, 100, 101, 102, 103, 104, 105, 117, 120, 158, 159, 162, 168, 169, 177, 189, 203, 204, 218, 223, 232 |
| `app/Queries/Categories/CategoryHierarchyDiagnosticsQuery.php` | 5, 57 |
| `app/Queries/Categories/CategorySchemaFingerprintQuery.php` | 5, 6, 7, 18, 20, 24, 25, 27, 28 |
| `app/Queries/Translations/MissingTranslationsQuery.php` | 6, 7, 8, 83, 84, 85 |
| `app/Queries/Translations/OutdatedTranslationsQuery.php` | 6, 7, 68, 69 |
| `app/Queries/Translations/TranslationEditorQuery.php` | 5, 6, 7, 11, 12, 30, 35, 40 |
| `app/Rules/Facets/ValidFacetDefinitionRule.php` | 8, 13, 106, 108, 111 |
| `app/Services/AttributeGlobalization/AttributeDependencies.php` | 6, 7, 10, 18, 22, 30, 37, 47, 48, 61, 67 |
| `app/Services/AttributeGlobalization/AttributeIdentityLock.php` | 5, 8, 12, 13, 18 |
| `app/Services/AttributeGlobalization/AttributeIdentityReservation.php` | 5, 6, 7, 11, 15, 19, 29, 33, 34, 40, 42, 46, 55 |
| `app/Services/AttributeGlobalization/CategoryAssignmentWriter.php` | 9, 10, 25, 28, 31, 32, 33, 34, 38, 155, 161, 187, 188 |
| `app/Services/AttributeGlobalization/GlobalAttributeWriter.php` | 9, 10, 22, 25, 29, 36, 45, 49, 51, 53, 78, 83, 87, 100 |
| `app/Services/AttributeGlobalization/LegacyAttributeBackfill.php` | 5, 6, 7, 8, 24, 27, 31, 33, 37, 38, 39, 40, 46, 47, 48, 52, 54, 60, 61, 62, 63, 66, 67 |
| `app/Services/AttributeGlobalization/LegacyAttributeCompatibility.php` | 6, 7, 8, 9, 18, 22, 25, 36, 37, 42, 43, 48, 49, 52, 53, 60, 66, 77 |
| `app/Services/Categories/CategoryLock.php` | 6, 15 |
| `app/Services/CategorySchema/CategorySchemaPreviewBuilder.php` | 5, 6, 29, 36 |
| `app/Services/CategorySchema/CategorySchemaValidator.php` | 20, 35 |
| `app/Services/CategorySchema/SchemaRevision.php` | 10, 11, 15, 40, 44, 58, 69 |
| `app/Services/Export/AttributeValuesJsonlExporter.php` | 20 |
| `app/Services/Export/AttributesJsonlExporter.php` | 6, 7, 8, 23, 42, 67, 71 |
| `app/Services/Export/TranslationsJsonlExporter.php` | 7, 8, 40, 48, 52 |
| `app/Services/Facets/CategoryFacetConfigResolver.php` | 5, 7, 12, 15, 22 |
| `app/Services/Facets/FacetQueryBuilder.php` | 6, 91, 117, 165, 173, 202, 210, 446, 457, 464, 471 |
| `app/Services/Facets/SiteFacetConfigResolver.php` | 5, 17, 33, 34, 41, 52, 54, 55 |
| `app/Services/Imports/AttributeMappingService.php` | 5, 18, 26, 62 |
| `app/Services/Imports/AttributeNormalizer.php` | 7, 15 |
| `app/Services/Imports/Normalizers/BooleanNormalizer.php` | 8, 19, 25 |
| `app/Services/Imports/Normalizers/EnumNormalizer.php` | 8, 9, 15, 18, 24, 39, 46, 57, 91 |
| `app/Services/Imports/Normalizers/MultiEnumNormalizer.php` | 8, 14, 20 |
| `app/Services/Imports/Normalizers/NumberNormalizer.php` | 8, 12, 20 |
| `app/Services/Imports/Normalizers/UnitNormalizer.php` | 10, 23, 31 |
| `app/Services/ProductAttributes/CanonicalValuePreviewer.php` | 7, 22 |
| `app/Services/ProductAttributes/GroupedSpecsPreviewBuilder.php` | 7, 44, 93, 108, 159, 173 |
| `app/Services/ProductAttributes/MissingRequiredAttributesResolver.php` | 6, 7, 15, 20, 28, 30, 35, 39, 44, 49, 57, 73 |
| `app/Services/ProductAttributes/ProductAttributeValueValidator.php` | 7, 28, 35, 56, 58, 64, 75, 98, 101, 124, 150, 182, 198, 222, 249, 284, 298, 340, 356 |
| `app/Services/Translations/TranslationCompletenessService.php` | 6, 7, 8, 14, 15, 115, 116, 117 |
| `app/Services/Translations/TranslationLocaleIdentity.php` | 5, 6, 30 |
| `app/Services/Translations/TranslationResolver.php` | 6, 7, 8, 13, 14, 121, 123, 125, 126, 129, 130 |
| `app/Services/Translations/TranslationSourceHashService.php` | 5, 6, 7, 39, 48, 56 |
| `app/Services/Translations/TranslationStatsService.php` | 7, 8, 76, 77 |
| `resources/views/filament/resources/central-product-resource/pages/product-specs-editor.blade.php` | 85 |
| `database/factories/AttributeDefinitionFactory.php` | 5, 11, 13, 15, 19 |
| `database/factories/AttributeDisplayRuleFactory.php` | 6, 20 |
| `database/factories/AttributeOptionFactory.php` | 5, 6, 11, 13, 15, 22 |
| `database/factories/AttributeOptionTranslationFactory.php` | 6, 8, 11, 12, 14, 19 |
| `database/factories/AttributeSectionFactory.php` | 5, 11, 13, 15 |
| `database/factories/AttributeSectionTranslationFactory.php` | 6, 8, 11, 12, 14, 19 |
| `database/factories/AttributeTranslationFactory.php` | 6, 19 |
| `database/factories/CategoryAttributeAssignmentFactory.php` | 5, 17 |
| `database/factories/CentralProductAttributeValueFactory.php` | 5, 22, 31 |
| `database/factories/ContentRelationFactory.php` | 6, 55, 59 |
| `database/factories/FacetDefinitionFactory.php` | 8, 10, 14, 15, 17, 25, 60, 61, 65 |
| `database/factories/FacetOptionFactory.php` | 5, 20 |
| `database/factories/NormalizedProductDraftFactory.php` | 34 |
| `database/factories/SiteFacetOverrideFactory.php` | 5, 19 |
| `database/seeders/Demo/PublicDemoSeeder.php` | 10, 11, 115, 149, 162, 188, 229, 318 |
| `tests/Feature/Actions/AttributeOptionActionsTest.php` | 5, 6, 7, 9, 10, 11, 17, 29, 33, 45, 49, 59, 63, 65, 73, 76, 80, 88, 94, 102, 105, 106, 111, 119, 122, 129, 146, 149, 151, 164, 167, 168, 172, 180, 183, 186, 188, 196, 199, 202, 212, 215, 218, 220, 225, 228, 229 |
| `tests/Feature/Actions/CategorySchemaStatusActionsTest.php` | 14, 15, 70, 73 |
| `tests/Feature/Actions/CloneCategorySchemaActionTest.php` | 9, 10, 11, 32, 36, 45, 65, 71, 82, 83, 90, 106, 111, 115, 122, 140 |
| `tests/Feature/Actions/CreateAttributeDefinitionActionTest.php` | 5, 7, 8, 15, 27, 29, 52, 53, 57, 66, 70, 79, 83, 92, 93, 96, 100 |
| `tests/Feature/Actions/CreateAttributeSectionActionTest.php` | 5, 6, 13, 27, 48, 52, 64, 76, 89, 92, 99, 100, 105 |
| `tests/Feature/Actions/DeleteAttributeSectionActionTest.php` | 5, 6, 7, 8, 14, 26, 28, 35, 36, 41, 43, 49, 50, 55, 57 |
| `tests/Feature/Actions/DetectOutdatedTranslationsActionTest.php` | 11, 12, 13, 20, 21, 82, 83, 92, 93, 230, 234, 251, 254, 256, 257, 275, 278, 280, 281 |
| `tests/Feature/Actions/ExportCategorySchemaActionTest.php` | 8, 9, 10, 26, 32, 40, 55 |
| `tests/Feature/Actions/MoveAttributeDefinitionActionTest.php` | 5, 6, 7, 8, 14, 27, 28, 30, 34, 39, 53, 54, 55, 56, 58, 65, 66, 67, 68, 70, 79, 80, 81, 86, 88, 93, 94, 99, 101, 106, 107, 112, 115, 121, 122, 123, 127, 130, 132, 134 |
| `tests/Feature/Actions/PublishNormalizedProductDraftToCentralActionTest.php` | 11, 37, 43, 54, 56, 68, 93, 132, 143, 154, 184, 185, 208, 224, 228, 237, 250, 258, 259 |
| `tests/Feature/Actions/SaveProductSpecsActionTest.php` | 7, 8, 24, 33, 55, 80, 102, 127, 129, 146, 153, 158, 160, 172, 177, 179, 186 |
| `tests/Feature/Actions/UpdateAttributeDefinitionActionTest.php` | 5, 7, 8, 9, 16, 28, 35, 60, 62, 74, 75, 76, 80, 89, 93, 97 |
| `tests/Feature/Actions/UpdateAttributeSectionActionTest.php` | 5, 6, 13, 25, 31, 52, 54, 68, 69, 73, 81, 85 |
| `tests/Feature/Admin/AttributeTranslationEditorTest.php` | 5, 6, 7, 20, 31, 40, 60 |
| `tests/Feature/Admin/MappingRulesEditorTest.php` | 8, 26, 35, 48, 93, 95, 102, 109 |
| `tests/Feature/Admin/NormalizedDraftReviewTest.php` | 41 |
| `tests/Feature/Attributes/AttributeGlobalizationConcurrencyTest.php` | 6, 10, 11, 12, 17, 18, 19, 55, 86, 91, 103, 119, 133, 134, 137, 147, 148, 149, 172, 184, 185, 186 |
| `tests/Feature/Attributes/AttributeGlobalizationReviewRegressionTest.php` | 6, 7, 8, 9, 14, 15, 16, 24, 45, 46, 48, 51, 64, 65, 67, 72, 89, 101, 102, 104, 105, 133, 134, 138, 152, 154, 170, 171, 172, 178, 182, 183, 184, 211, 231, 232, 233 |
| `tests/Feature/Attributes/AttributeIdentityIntegrityTest.php` | 6, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17, 29, 36, 37, 38, 40, 41, 42, 48, 50, 51, 52, 53, 61, 62, 64, 65, 67, 68, 79, 80, 82, 83, 84, 85, 89, 92, 93, 96, 98, 111, 113, 121, 122, 124, 125, 126, 127, 129, 136, 137, 144, 146 |
| `tests/Feature/Attributes/GlobalAttributeFoundationTest.php` | 6, 7, 8, 11, 12, 14, 17, 20, 21, 22, 23, 29, 54, 56, 62, 63, 66, 70, 94, 101, 116, 118, 119, 152, 169, 199, 217, 220, 239, 253, 256, 264, 266, 279, 286, 294, 298, 306, 307, 309, 315, 316, 318, 323, 325, 334, 354, 355, 368, 371, 387, 391, 392, 393, 394, 395, 397, 403, 417, 422, 456, 466, 467, 469, 477, 478, 482, 484, 494, 495, 496, 499, 512, 537, 551 |
| `tests/Feature/Categories/CategoryDiagnosticsTest.php` | 6, 42, 43 |
| `tests/Feature/Categories/CategoryFoundationConcurrencyTest.php` | 5, 97 |
| `tests/Feature/Categories/CategoryLifecycleTest.php` | 12, 13, 14, 20, 60, 61, 62, 63, 65, 68 |
| `tests/Feature/Categories/CategoryPermissionsTest.php` | 5, 35, 51, 86, 103 |
| `tests/Feature/Categories/CategorySchemaRevisionTest.php` | 8, 9, 10, 11, 12, 14, 16, 17, 18, 21, 24, 25, 26, 92, 96, 97, 98, 99, 102, 103, 104, 105, 106, 107, 108, 109, 110, 171, 172, 173, 175, 176, 177, 178, 193, 216, 217, 231, 253, 254, 255, 260, 261, 262, 263, 264, 265, 266, 270, 283, 284, 285, 286, 289, 291, 304, 305, 306, 307, 310, 321, 322, 323, 325, 328 |
| `tests/Feature/Content/ContentAttributeRelationTest.php` | 8, 27, 38, 61 |
| `tests/Feature/Database/AttributeDefinitionsMigrationTest.php` | 5, 6, 13, 64, 68 |
| `tests/Feature/Database/AttributeGlobalizationMigrationTest.php` | 5, 6, 7, 12, 52, 57, 58, 71, 72, 74, 81, 87, 89, 92, 93, 110, 168, 169, 188 |
| `tests/Feature/Database/AttributeMappingsMigrationTest.php` | 5, 27, 53, 69, 78, 91, 97, 113, 119 |
| `tests/Feature/Database/AttributeOptionsMigrationTest.php` | 9, 18, 34, 37 |
| `tests/Feature/Database/AttributeSectionsMigrationTest.php` | 5, 12, 51, 55, 63, 64 |
| `tests/Feature/Database/CentralProductAttributeValuesSchemaTest.php` | 20 |
| `tests/Feature/Database/FacetDefinitionsSchemaTest.php` | 9, 19, 38 |
| `tests/Feature/Database/NormalizedProductDraftsMigrationTest.php` | 29 |
| `tests/Feature/Domains/Projections/CategoryProjectionBuilderTest.php` | 9, 10, 43, 44 |
| `tests/Feature/Domains/Projections/ProductProjectionBuilderTest.php` | 7, 8, 66, 71, 76, 87, 96, 104, 128 |
| `tests/Feature/Domains/Projections/ProductProjectionBuilderTranslationTest.php` | 7, 8, 9, 16, 17, 34, 38, 47, 51, 61, 93, 100, 105, 135, 141 |
| `tests/Feature/Domains/Projections/ProductProjectionBuilderUnitFormattingTest.php` | 7, 8, 39, 40, 53, 69, 81, 82, 99, 116, 117, 154, 155, 173 |
| `tests/Feature/Domains/Projections/SearchDocumentBuilderTest.php` | 27 |
| `tests/Feature/Export/AttributesJsonlExporterTest.php` | 6, 7, 8, 23, 24, 30 |
| `tests/Feature/Facets/FacetQueryBuilderBooleanTest.php` | 7, 38, 61 |
| `tests/Feature/Facets/FacetQueryBuilderEnumTest.php` | 8, 10, 83, 87, 88, 112, 116, 117, 138, 142, 143 |
| `tests/Feature/Facets/FacetQueryBuilderRangeTest.php` | 9, 11, 82, 86, 87 |
| `tests/Feature/Filament/CategorySchemaBuilderTest.php` | 8, 9, 52, 58 |
| `tests/Feature/Filament/FacetDefinitionResourceTest.php` | 8, 9, 10, 11, 13, 19, 25, 27, 32, 33, 34, 42, 51, 60, 85, 88, 101, 104, 115, 122, 125, 137 |
| `tests/Feature/Filament/ProductSpecsEditorTest.php` | 8, 9, 10, 73, 78, 84, 94, 126, 129, 150, 153, 174, 177, 200, 202, 212, 233, 235, 245, 267, 270, 290, 293, 313, 315, 370, 372, 406, 409, 429, 432, 451, 454, 475, 480, 492, 510, 512, 528, 538, 540, 554 |
| `tests/Feature/Imports/ImportDraftPostProcessingTest.php` | 50 |
| `tests/Feature/Imports/ImportServiceTest.php` | 168 |
| `tests/Feature/Imports/ProcessImportDraftsJobTest.php` | 164 |
| `tests/Feature/Localization/TranslationModelsTest.php` | 6, 7, 8, 13, 14, 65, 69, 80, 83, 95, 98, 151, 152 |
| `tests/Feature/Models/AttributeDataTypeTest.php` | 6, 17, 27 |
| `tests/Feature/Models/AttributeDefinitionTest.php` | 5, 6, 11, 18, 20, 31, 50 |
| `tests/Feature/Models/AttributeFlagsTest.php` | 5, 15, 26, 27, 29, 36, 37, 39 |
| `tests/Feature/Models/AttributeOptionTest.php` | 5, 6, 10, 16, 20, 29, 32, 39 |
| `tests/Feature/Models/AttributeOrderingTest.php` | 5, 6, 16, 18, 22, 34, 36, 40 |
| `tests/Feature/Models/AttributeSectionOrderingTest.php` | 5, 10, 18, 19, 30, 31 |
| `tests/Feature/Models/AttributeSectionTest.php` | 5, 10, 17, 25, 26, 37, 45 |
| `tests/Feature/Models/AttributeSemanticFlagsTest.php` | 5, 15, 30, 31, 32, 33, 34, 41, 42, 43, 44 |
| `tests/Feature/Models/CentralProductAttributeValueRelationshipTest.php` | 5, 38, 48 |
| `tests/Feature/Models/CentralProductAttributeValueTest.php` | 5, 19, 23, 46, 52 |
| `tests/Feature/Models/FacetDefinitionTest.php` | 8, 10, 16, 22, 23, 25, 34, 53, 68, 76, 86, 95, 105, 106, 107, 109, 118, 119, 121 |
| `tests/Feature/Public/ComparePageTest.php` | 59, 87 |
| `tests/Feature/Public/ProductDetailPageTest.php` | 36 |
| `tests/Feature/Services/CanonicalValuePreviewerTest.php` | 5, 32, 50, 59, 80, 99, 121 |
| `tests/Feature/Services/CategorySchemaPreviewBuilderTest.php` | 6, 7, 8, 21, 26, 36, 51, 52, 54, 55, 56 |
| `tests/Feature/Services/CategorySchemaValidatorTest.php` | 6, 7, 8, 21, 33, 36, 47, 50, 61, 76 |
| `tests/Feature/Services/GroupedSpecsPreviewBuilderTest.php` | 5, 6, 7, 24, 29, 107, 120, 121, 122, 145, 150, 152, 165, 167 |
| `tests/Feature/Services/MissingRequiredAttributesResolverTest.php` | 5, 6, 139, 141, 154, 159, 161, 174, 179, 180 |
| `tests/Feature/Services/ProductAttributeValueValidatorTest.php` | 6, 7, 8, 35, 278, 281, 295, 298, 332, 337, 339, 348 |
| `tests/Feature/Smoke/ProjectionSmokeTest.php` | 7, 8, 29, 33, 69 |
| `tests/Feature/Smoke/PublicSiteSmokeTest.php` | 56 |
| `tests/Feature/Units/AttributeDisplayRuleTest.php` | 6, 28, 39, 46, 64, 68, 79, 90, 93, 103 |
| `tests/Feature/View/Components/DesktopFilterSidebarTest.php` | 5, 47, 49 |
| `tests/Feature/View/Components/MobileFilterDrawerTest.php` | 5, 17 |
| `tests/Unit/Facets/FacetDefinitionValidationTest.php` | 8, 9, 14, 20, 26, 42, 54, 59, 70, 76, 86, 92, 102, 108, 118, 128, 136, 139, 150, 156, 177 |
| `tests/Unit/Imports/AttributeMappingServiceTest.php` | 5, 24, 30, 50, 56, 59, 73 |
| `tests/Unit/Imports/AttributeNormalizerTest.php` | 8, 16, 32 |
| `tests/Unit/Imports/Normalizers/BooleanNormalizerTest.php` | 6, 62, 64 |
| `tests/Unit/Imports/Normalizers/EnumNormalizerTest.php` | 6, 7, 8, 32, 49, 55, 74, 75, 79, 94, 97, 100 |
| `tests/Unit/Imports/Normalizers/MultiEnumNormalizerTest.php` | 6, 7, 50, 79, 81, 90 |
| `tests/Unit/Imports/Normalizers/NumberNormalizerTest.php` | 6, 41, 76, 78 |
| `tests/Unit/Imports/Normalizers/UnitNormalizerTest.php` | 6, 62, 99, 101 |
| `tests/Unit/Models/NormalizedProductDraftTest.php` | 24, 31 |
| `tests/Unit/Services/CategoryFacetConfigResolverTest.php` | 8, 10, 23, 27, 31, 32, 46, 51, 53 |
| `tests/Unit/Services/SiteFacetConfigResolverTest.php` | 6, 22, 34, 39 |

## Additional verified consumers during implementation

Typed translation stats/editor/missing/outdated queries, Product translation saves/source hashes, Unit/Attribute translation projection fan-out, direct Product form/table Category writers, existing Facet/Mapping resource pages, JSONL snapshot section metadata and schema export were verified against current code. Identity-bearing output now uses v2; ID-only importer/media jobs reload persisted state instead of gaining an invented identity field. Unit formatting consumes existing relational catalog models; Product unit strings remain snapshots. The only remaining legacy ownership reads are the explicitly historical v1 inventory/backfill/resolver and v2 cutover preflight/reconciliation paths. The compatibility mirror is removed.

Draft rejection is also an import write: its transaction now takes the identity gate before the draft lock, preventing active-to-historical status changes from racing cutover conversion. It uses the same active Central Product mutation admission as draft approval/publish.

`EnumNormalizer` also serializes `metadata.option_id`. Active v1 conversion now repoints that specific identity through the explicit option crosswalk while retaining label/provenance evidence; v2 validates option ID and code consistency. Arbitrary metadata is not rewritten.


## PR #615 final-head review sweep

The Content relation Attribute Select was a newly confirmed post-contraction consumer: its preload options still ordered definitions by retired Category/position columns. It now orders global canonical name/code/id, with a mounted Livewire form test that executes the options query before relation creation.

ProductTranslation was missing from derived-output invalidation despite ProductProjectionBuilder consuming localized title/subtitle/descriptions. The shared observer now invalidates only the owning Product/search output on meaningful text/status saves or deletion. Category and shared schema translation fan-out remains intact and tested.

The final retired-column search includes app code, Filament closures, commands/reports, exporters, queue jobs, views and normal factories/seeders. Remaining old Definition field references are confined to historical pre-v2 inventory/backfill and immutable v2 migration preflight against pre-contraction rows; target reads use assignments/relational measurement identity. Matching Section/Option/Facet local fields and immutable Product unit snapshots are separate owners, not retired Definition authority. The historical migration no longer invokes mutable runtime services/models; post-DDL finalization owns rebuild/activation.
