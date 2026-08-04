# CatalogHub v2 Authoritative Screen Registry

| Field | Value |
| --- | --- |
| Registry version | 1.0.0 |
| Status | Proposed; product approval required |
| Owner | CatalogHub Product Owner |
| Approver | `TBD — approver must be named` |
| Approval date | `TBD — YYYY-MM-DD` |
| Contract | `cataloghub-v2-product-contract.md` |

## Registry rules

This is the authoritative inventory for CatalogHub v2. It contains exactly
`CA-001` through `CA-085`, `SA-001` through `SA-064`, and `PUB-001`
through `PUB-080`. An ID is not permission to invent a screen: its name, type,
route, context, and reference must all be approved here before implementation.

Routes below are target route contracts, not evidence that v1 already implements
them. `{site}` is the immutable route-resolved `site_id` context. Public
routes are host-resolved to a Site.

The sentinel **BLOCKED — approved definition absent from repository** is not a
screen type or a placeholder feature. It records that the ID was requested as
part of the approved range but no local source defines it. That ID cannot enter a
work package, navigation, seed plan, or implementation until a product decision
updates this registry and the visual manifest.

## Central Admin

| Screen ID | Name | Surface | Type | Route | Workspace context | Site context required | Reference artifact | Roadmap phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| CA-001 | Dashboard | Central Admin | page | `/admin/central` | Central | no | `pictures/1. Central Admin/1.1. Dashboard/CA-001 — Dashboard.png` | Phase 01 |
| CA-066 | Price Sources List | Central Admin | page | `/admin/central/price-sources` | Central | no | `pictures/1. Central Admin/1.10. Price Sources/CA-066 — Price Sources List.png` | Phase 07 |
| CA-067 | Price Source Detail | Central Admin | page | `/admin/central/price-sources/{source}` | Central | no | `pictures/1. Central Admin/1.10. Price Sources/CA-067 — Price Source Detail.png` | Phase 07 |
| CA-068 | Price Source Create / Edit | Central Admin | page | `/admin/central/price-sources/{source?}/edit` | Central | no | `pictures/1. Central Admin/1.10. Price Sources/CA-068 — Price Source Create:Edit.png` | Phase 07 |
| CA-069 | Price Source Credentials | Central Admin | page | `/admin/central/price-sources/{source}/credentials` | Central | no | `pictures/1. Central Admin/1.10. Price Sources/CA-069 — Price Source Credentials.png` | Phase 07 |
| CA-070 | Price Sync Logs | Central Admin | page | `/admin/central/price-sources/sync-logs` | Central | no | `pictures/1. Central Admin/1.10. Price Sources/CA-070 — Price Sync Logs.png` | Phase 07 |
| CA-071 | Raw Price Offers Viewer | Central Admin | page | `/admin/central/price-sources/raw-offers` | Central | no | `pictures/1. Central Admin/1.10. Price Sources/CA-071 — Raw Price Offers Viewer.png` | Phase 07 |
| CA-072 | External Product Mapping | Central Admin | page | `/admin/central/price-sources/mappings` | Central | no | `pictures/1. Central Admin/1.10. Price Sources/CA-072 — External Product Mapping.png` | Phase 07 |
| CA-073 | Mapping Approval Queue | Central Admin | page | `/admin/central/price-sources/mapping-approvals` | Central | no | `pictures/1. Central Admin/1.10. Price Sources/CA-073 — Mapping Approval Queue.png` | Phase 07 |
| CA-074 | Price Source Error Report | Central Admin | page | `/admin/central/price-sources/errors` | Central | no | `pictures/1. Central Admin/1.10. Price Sources/CA-074 — Price Source Error Report.png` | Phase 07 |
| CA-075 | Snapshots List | Central Admin | page | `/admin/central/snapshots` | Central | no | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-075 — Snapshots List.png` | Phase 08 |
| CA-076 | Snapshot Detail | Central Admin | page | `/admin/central/snapshots/{snapshot}` | Central | no | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-076 — Snapshot Detail.png` | Phase 08 |
| CA-077 | Create Snapshot | Central Admin | page | `/admin/central/snapshots/create` | Central | no | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-077 — Create Snapshot.png` | Phase 08 |
| CA-078 | Export History | Central Admin | page | `/admin/central/exports` | Central | no | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-078 — Export History.png` | Phase 08 |
| CA-079 | Media Manifest Viewer | Central Admin | page | `/admin/central/snapshots/{snapshot}/media-manifest` | Central | no | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-079 — Media Manifest Viewer.png` | Phase 08 |
| CA-080 | Backup Status | Central Admin | page | `/admin/central/backups/status` | Central | no | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-080 — Backup Status.png` | Phase 08 |
| CA-081 | Restore Checklist | Central Admin | page | `/admin/central/restore/checklist` | Central | no | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-081 — Restore Checklist.png` | Phase 08 |
| CA-082 | Users List | Central Admin | page | `/admin/central/users` | Central | no | `pictures/1. Central Admin/1.12. Users : Roles/CA-082 — Users List.png` | Phase 08 |
| CA-083 | User Create / Edit | Central Admin | page | `/admin/central/users/{user?}/edit` | Central | no | `pictures/1. Central Admin/1.12. Users : Roles/CA-083 — User Create:Edit.png` | Phase 08 |
| CA-084 | Roles & Permissions | Central Admin | page | `/admin/central/roles` | Central | no | `pictures/1. Central Admin/1.12. Users : Roles/CA-084 — Roles & Permissions.png` | Phase 08 |
| CA-085 | Activity Log | Central Admin | page | `/admin/central/activity-log` | Central | no | `pictures/1. Central Admin/1.12. Users : Roles/CA-085 — Activity Log.png` | Phase 08 |
| CA-002 | Products List | Central Admin | page | `/admin/central/products` | Central | no | `pictures/1. Central Admin/1.2. Products/CA-002 — Products List.png` | Phase 02 |
| CA-003 | Product Detail | Central Admin | page | `/admin/central/products/{product}` | Central | no | `pictures/1. Central Admin/1.2. Products/CA-003 — Product Detail.png` | Phase 02 |
| CA-004 | Product Create / Edit | Central Admin | page | `/admin/central/products/{product?}/edit` | Central | no | `pictures/1. Central Admin/1.2. Products/CA-004 — Product Create:Edit.png` | Phase 02 |
| CA-005 | Product Variants | Central Admin | page | `/admin/central/products/{product}/variants` | Central | no | `pictures/1. Central Admin/1.2. Products/CA-005 — Product Variants.png` | Phase 02 |
| CA-006 | Product Specs Editor | Central Admin | page | `/admin/central/products/{product}/specs` | Central | no | `pictures/1. Central Admin/1.2. Products/CA-006 — Product Specs Editor.png` | Phase 02 |
| CA-007 | Product Media Manager | Central Admin | page | `/admin/central/products/{product}/media` | Central | no | `pictures/1. Central Admin/1.2. Products/CA-007 — Product Media Manager.png` | Phase 02 |
| CA-008 | Product Translations | Central Admin | page | `/admin/central/products/{product}/translations` | Central | no | `pictures/1. Central Admin/1.2. Products/CA-008 — Product Translations.png` | Phase 02 |
| CA-009 | Product Version History | Central Admin | page | `/admin/central/products/{product}/versions` | Central | no | `pictures/1. Central Admin/1.2. Products/CA-009 — Product Version History.png` | Phase 02 |
| CA-010 | Product Data Quality View | Central Admin | page | `/admin/central/products/quality` | Central | no | `pictures/1. Central Admin/1.2. Products/CA-010 — Product Data Quality View.png` | Phase 02 |
| CA-011 | Brands List | Central Admin | page | `/admin/central/brands` | Central | no | `pictures/1. Central Admin/1.3. Brands/CA-011 — Brands List.png` | Phase 02 |
| CA-012 | Brand Detail | Central Admin | page | `/admin/central/brands/{brand}` | Central | no | `pictures/1. Central Admin/1.3. Brands/CA-012 — Brand Detail.png` | Phase 02 |
| CA-013 | Brand Create / Edit | Central Admin | page | `/admin/central/brands/{brand?}/edit` | Central | no | `pictures/1. Central Admin/1.3. Brands/CA-013 — Brand Create:Edit.png` | Phase 02 |
| CA-014 | Brand Media  /  Logo | Central Admin | page | `/admin/central/brands/{brand}/media` | Central | no | `pictures/1. Central Admin/1.3. Brands/CA-014 — Brand Media : Logo.png` | Phase 02 |
| CA-015 | Brand Translations | Central Admin | page | `/admin/central/brands/{brand}/translations` | Central | no | `pictures/1. Central Admin/1.3. Brands/CA-015 — Brand Translations.png` | Phase 02 |
| CA-016 | Categories List | Central Admin | page | `/admin/central/categories` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-016 — Categories List.png` | Phase 03 |
| CA-017 | Category Detail | Central Admin | page | `/admin/central/categories/{category}` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-017 — Category Detail.png` | Phase 03 |
| CA-018 | Category Create / Edit | Central Admin | page | `/admin/central/categories/{category?}/edit` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-018 — Category Create:Edit.png` | Phase 03 |
| CA-019 | Category Schema Builder | Central Admin | page | `/admin/central/categories/{category}/schema` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-019 — Category Schema Builder.png` | Phase 03 |
| CA-020 | Attribute Sections Editor | Central Admin | page | `/admin/central/categories/{category}/schema/sections` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-020 — Attribute Sections Editor.png` | Phase 03 |
| CA-021 | Attribute Definitions Editor | Central Admin | page | `/admin/central/categories/{category}/schema/attributes` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-021 — Attribute Definitions Editor.png` | Phase 03 |
| CA-022 | Attribute Options Editor | Central Admin | page | `/admin/central/categories/{category}/schema/options` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-022 — Attribute Options Editor.png` | Phase 03 |
| CA-023 | Category Facets Config | Central Admin | page | `/admin/central/categories/{category}/facets` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-023 — Category Facets Config.png` | Phase 03 |
| CA-024 | Category Comparison Config | Central Admin | page | `/admin/central/categories/{category}/comparison` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-024 — Category Comparison Config.png` | Phase 03 |
| CA-025 | Category SEO Templates | Central Admin | page | `/admin/central/categories/{category}/seo-template` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-025 — Category SEO Templates.png` | Phase 03 |
| CA-026 | Category Translation Editor | Central Admin | page | `/admin/central/categories/{category}/translations` | Central | no | `pictures/1. Central Admin/1.4. Categories : Schema/CA-026 — Category Translation Editor.png` | Phase 03 |
| CA-027 | Measurement Dimensions | Central Admin | page | `/admin/central/measurements/dimensions` | Central | no | `pictures/1. Central Admin/1.5. Units : Measurements/CA-027 — Measurement Dimensions.png` | Phase 03 |
| CA-028 | Measurement Units | Central Admin | page | `/admin/central/measurements/units` | Central | no | `pictures/1. Central Admin/1.5. Units : Measurements/CA-028 — Measurement Units.png` | Phase 03 |
| CA-029 | Unit Aliases | Central Admin | page | `/admin/central/measurements/aliases` | Central | no | `pictures/1. Central Admin/1.5. Units : Measurements/CA-029 — Unit Aliases.png` | Phase 03 |
| CA-030 | Unit Translations | Central Admin | page | `/admin/central/measurements/translations` | Central | no | `pictures/1. Central Admin/1.5. Units : Measurements/CA-030 — Unit Translations.png` | Phase 03 |
| CA-031 | Market Unit Preferences | Central Admin | page | `/admin/central/measurements/market-preferences` | Central | no | `pictures/1. Central Admin/1.5. Units : Measurements/CA-031 — Market Unit Preferences.png` | Phase 03 |
| CA-032 | Attribute Display Rules | Central Admin | page | `/admin/central/measurements/attribute-display-rules` | Central | no | `pictures/1. Central Admin/1.5. Units : Measurements/CA-032 — Attribute Display Rules.png` | Phase 03 |
| CA-033 | Import Sources | Central Admin | page | `/admin/central/imports/sources` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-033 — Import Sources.png` | Phase 04 |
| CA-034 | Import Batches List | Central Admin | page | `/admin/central/imports/batches` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-034 — Import Batches List.png` | Phase 04 |
| CA-035 | Import Batch Detail | Central Admin | page | `/admin/central/imports/batches/{batch}` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-035 — Import Batch Detail.png` | Phase 04 |
| CA-036 | Import Wizard | Central Admin | page | `/admin/central/imports/new` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-036 — Import Wizard.png` | Phase 04 |
| CA-037 | Raw Product Viewer | Central Admin | page | `/admin/central/imports/raw-products/{rawProduct}` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-037 — Raw Product Viewer.png` | Phase 04 |
| CA-038 | Normalized Draft Review | Central Admin | page | `/admin/central/imports/drafts/{draft}/review` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-038 — Normalized Draft Review.png` | Phase 04 |
| CA-039 | Mapping Rules Editor | Central Admin | page | `/admin/central/imports/mappings` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-039 — Mapping Rules Editor.png` | Phase 04 |
| CA-040 | Unmapped Fields | Central Admin | page | `/admin/central/imports/unmapped-fields` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-040 — Unmapped Fields.png` | Phase 04 |
| CA-041 | Duplicate Candidates | Central Admin | page | `/admin/central/imports/duplicate-candidates` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-041 — Duplicate Candidates.png` | Phase 04 |
| CA-042 | Normalization Errors | Central Admin | page | `/admin/central/imports/errors/normalization` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-042 — Normalization Errors.png` | Phase 04 |
| CA-043 | Media Download Errors | Central Admin | page | `/admin/central/imports/errors/media` | Central | no | `pictures/1. Central Admin/1.6. Imports/CA-043 — Media Download Errors.png` | Phase 04 |
| CA-044 | Media Library | Central Admin | page | `/admin/central/media` | Central | no | `pictures/1. Central Admin/1.7. Media Library/CA-044 — Media Library.png` | Phase 05 |
| CA-045 | Media Asset Detail | Central Admin | page | `/admin/central/media/{asset}` | Central | no | `pictures/1. Central Admin/1.7. Media Library/CA-045 — Media Asset Detail.png` | Phase 05 |
| CA-046 | Media Upload | Central Admin | page | `/admin/central/media/upload` | Central | no | `pictures/1. Central Admin/1.7. Media Library/CA-046 — Media Upload.png` | Phase 05 |
| CA-047 | Media Variants Preview | Central Admin | page | `/admin/central/media/{asset}/variants` | Central | no | `pictures/1. Central Admin/1.7. Media Library/CA-047 — Media Variants Preview.png` | Phase 05 |
| CA-048 | Media Assignments | Central Admin | page | `/admin/central/media/assignments` | Central | no | `pictures/1. Central Admin/1.7. Media Library/CA-048 — Media Assignments.png` | Phase 05 |
| CA-049 | Localized Media Manager | Central Admin | page | `/admin/central/media/localized` | Central | no | `pictures/1. Central Admin/1.7. Media Library/CA-049 — Localized Media Manager.png` | Phase 05 |
| CA-050 | Media Sources  /  Licenses | Central Admin | page | `/admin/central/media/sources` | Central | no | `pictures/1. Central Admin/1.7. Media Library/CA-050 — Media Sources : Licenses.png` | Phase 05 |
| CA-051 | Media Integrity Report | Central Admin | page | `/admin/central/media/integrity` | Central | no | `pictures/1. Central Admin/1.7. Media Library/CA-051 — Media Integrity Report.png` | Phase 05 |
| CA-052 | Translation Dashboard | Central Admin | page | `/admin/central/translations` | Central | no | `pictures/1. Central Admin/1.8. Translations/CA-052 — Translation Dashboard.png` | Phase 05 |
| CA-053 | Missing Translations | Central Admin | page | `/admin/central/translations/missing` | Central | no | `pictures/1. Central Admin/1.8. Translations/CA-053 — Missing Translations.png` | Phase 05 |
| CA-054 | Outdated Translations | Central Admin | page | `/admin/central/translations/outdated` | Central | no | `pictures/1. Central Admin/1.8. Translations/CA-054 — Outdated Translations.png` | Phase 05 |
| CA-055 | Product Translation Editor | Central Admin | page | `/admin/central/translations/products/{product}` | Central | no | `pictures/1. Central Admin/1.8. Translations/CA-055 — Product Translation Editor.png` | Phase 05 |
| CA-056 | Category Translation Editor | Central Admin | page | `/admin/central/translations/categories/{category}` | Central | no | `pictures/1. Central Admin/1.8. Translations/CA-056 — Category Translation Editor.png` | Phase 05 |
| CA-057 | Attribute Translation Editor | Central Admin | page | `/admin/central/translations/attributes/{attribute}` | Central | no | `pictures/1. Central Admin/1.8. Translations/CA-057 — Attribute Translation Editor.png` | Phase 05 |
| CA-058 | Unit Translation Editor | Central Admin | page | `/admin/central/translations/units/{unit}` | Central | no | `pictures/1. Central Admin/1.8. Translations/CA-058 — Unit Translation Editor.png` | Phase 05 |
| CA-059 | Bulk Translation Review | Central Admin | page | `/admin/central/translations/review` | Central | no | `pictures/1. Central Admin/1.8. Translations/CA-059 — Bulk Translation Review.png` | Phase 05 |
| CA-060 | Change Requests Queue | Central Admin | page | `/admin/central/change-requests` | Central | no | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-060 — Change Requests Queue.png` | Phase 06 |
| CA-061 | Change Request Detail | Central Admin | page | `/admin/central/change-requests/{request}` | Central | no | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-061 — Change Request Detail.png` | Phase 06 |
| CA-062 | Correction Diff Viewer | Central Admin | page | `/admin/central/change-requests/{request}/diff` | Central | no | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-062 — Correction Diff Viewer.png` | Phase 06 |
| CA-063 | Conflicts List | Central Admin | page | `/admin/central/conflicts` | Central | no | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-063 — Conflicts List.png` | Phase 06 |
| CA-064 | Conflict Resolver | Central Admin | page | `/admin/central/conflicts/{conflict}` | Central | no | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-064 — Conflict Resolver.png` | Phase 06 |
| CA-065 | Data Source Comparison | Central Admin | page | `/admin/central/data-sources/compare` | Central | no | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-065 — Data Source Comparison.png` | Phase 06 |

## Site Admin

| Screen ID | Name | Surface | Type | Route | Workspace context | Site context required | Reference artifact | Roadmap phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SA-001 | Site Dashboard | Site Admin | page | `/admin/sites/{site}` | Site | yes | `pictures/2. Site Admin/SA-001 — Site Dashboard.png` | Phase 01 |
| SA-002 | Site Settings | Site Admin | page | `/admin/sites/{site}/settings` | Site | yes | `pictures/2. Site Admin/SA-002 — Site Settings.png` | Phase 09 |
| SA-003 | Domain Settings | Site Admin | page | `/admin/sites/{site}/settings/domain` | Site | yes | `pictures/2. Site Admin/SA-003 — Domain Settings.png` | Phase 09 |
| SA-004 | Market Settings | Site Admin | page | `/admin/sites/{site}/settings/market` | Site | yes | `pictures/2. Site Admin/SA-004 — Market Settings.png` | Phase 09 |
| SA-005 | Locale Settings | Site Admin | page | `/admin/sites/{site}/settings/locales` | Site | yes | `pictures/2. Site Admin/SA-005 — Locale Settings.png` | Phase 09 |
| SA-006 | Currency Settings | Site Admin | page | `/admin/sites/{site}/settings/currency` | Site | yes | `pictures/2. Site Admin/SA-006 — Currency Settings.png` | Phase 09 |
| SA-007 | SEO Defaults | Site Admin | page | `/admin/sites/{site}/settings/seo` | Site | yes | `pictures/2. Site Admin/SA-007 — SEO Defaults.png` | Phase 09 |
| SA-008 | Enabled Categories | Site Admin | page | `/admin/sites/{site}/categories` | Site | yes | `pictures/2. Site Admin/SA-008 — Enabled Categories.png` | Phase 09 |
| SA-009 | Category Visibility | Site Admin | page | `/admin/sites/{site}/categories/visibility` | Site | yes | `pictures/2. Site Admin/SA-009 — Category Visibility.png` | Phase 09 |
| SA-010 | Category Local SEO | Site Admin | page | `/admin/sites/{site}/categories/{category}/seo` | Site | yes | `pictures/2. Site Admin/SA-010 — Category Local SEO.png` | Phase 09 |
| SA-011 | Category Local Media | Site Admin | page | `/admin/sites/{site}/categories/{category}/media` | Site | yes | `pictures/2. Site Admin/SA-011 — Category Local Media.png` | Phase 09 |
| SA-012 | Category Page Preview | Site Admin | page | `/admin/sites/{site}/categories/{category}/preview` | Site | yes | `pictures/2. Site Admin/SA-012 — Category Page Preview.png` | Phase 09 |
| SA-013 | Site Products List | Site Admin | page | `/admin/sites/{site}/products` | Site | yes | `pictures/2. Site Admin/SA-013 — Site Products List.png` | Phase 10 |
| SA-014 | Product Visibility Manager | Site Admin | page | `/admin/sites/{site}/products/visibility` | Site | yes | `pictures/2. Site Admin/SA-014 — Product Visibility Manager.png` | Phase 10 |
| SA-015 | Product Local Detail | Site Admin | page | `/admin/sites/{site}/products/{product}` | Site | yes | `pictures/2. Site Admin/SA-015 — Product Local Detail.png` | Phase 10 |
| SA-016 | Product Local SEO Override | Site Admin | page | `/admin/sites/{site}/products/{product}/seo` | Site | yes | `pictures/2. Site Admin/SA-016 — Product Local SEO Override.png` | Phase 10 |
| SA-017 | Product Local Media Override | Site Admin | page | `/admin/sites/{site}/products/{product}/media` | Site | yes | `pictures/2. Site Admin/SA-017 — Product Local Media Override.png` | Phase 10 |
| SA-018 | Product Local Title / Slug Override | Site Admin | page | `/admin/sites/{site}/products/{product}/title-slug` | Site | yes | `pictures/2. Site Admin/SA-018 — Product Local Title:Slug Override.png` | Phase 10 |
| SA-019 | Product Projection Preview | Site Admin | page | `/admin/sites/{site}/products/{product}/projection` | Site | yes | `pictures/2. Site Admin/SA-019 — Product Projection Preview.png` | Phase 10 |
| SA-020 | Products Without Projection | Site Admin | page | `/admin/sites/{site}/products/without-projection` | Site | yes | `pictures/2. Site Admin/SA-020 — Products Without Projection.png` | Phase 10 |
| SA-021 | Stale Products | Site Admin | page | `/admin/sites/{site}/products/stale` | Site | yes | `pictures/2. Site Admin/SA-021 — Stale Products.png` | Phase 10 |
| SA-022 | Theme Selection | Site Admin | page | `/admin/sites/{site}/themes` | Site | yes | `pictures/2. Site Admin/SA-022 — Theme Selection.png` | Phase 11 |
| SA-023 | Theme Compatibility Check | Site Admin | page | `/admin/sites/{site}/themes/compatibility` | Site | yes | `pictures/2. Site Admin/SA-023 — Theme Compatibility Check.png` | Phase 11 |
| SA-024 | Theme Settings | Site Admin | page | `/admin/sites/{site}/themes/{theme}/settings` | Site | yes | `pictures/2. Site Admin/SA-024 — Theme Settings.png` | Phase 11 |
| SA-025 | Homepage Blocks Editor | Site Admin | page | `/admin/sites/{site}/homepage/blocks` | Site | yes | `pictures/2. Site Admin/SA-025 — Homepage Blocks Editor.png` | Phase 11 |
| SA-026 | Layout Templates Preview | Site Admin | page | `/admin/sites/{site}/layouts/preview` | Site | yes | `pictures/2. Site Admin/SA-026 — Layout Templates Preview.png` | Phase 11 |
| SA-027 | Block Config Editor | Site Admin | page | `/admin/sites/{site}/homepage/blocks/{block}/edit` | Site | yes | `pictures/2. Site Admin/SA-027 — Block Config Editor.png` | Phase 11 |
| SA-028 | Feature Flags | Site Admin | page | `/admin/sites/{site}/features` | Site | yes | `pictures/2. Site Admin/SA-028 — Feature Flags.png` | Phase 11 |
| SA-029 | Sync Dashboard | Site Admin | page | `/admin/sites/{site}/sync` | Site | yes | `pictures/2. Site Admin/SA-029 — Sync Dashboard.png` | Phase 12 |
| SA-030 | Sync Logs | Site Admin | page | `/admin/sites/{site}/sync/logs` | Site | yes | `pictures/2. Site Admin/SA-030 — Sync Logs.png` | Phase 12 |
| SA-031 | Projection Jobs | Site Admin | page | `/admin/sites/{site}/sync/jobs` | Site | yes | `pictures/2. Site Admin/SA-031 — Projection Jobs.png` | Phase 12 |
| SA-032 | Projection Errors | Site Admin | page | `/admin/sites/{site}/sync/errors` | Site | yes | `pictures/2. Site Admin/SA-032 — Projection Errors.png` | Phase 12 |
| SA-033 | Manual Sync Product | Site Admin | page | `/admin/sites/{site}/sync/product` | Site | yes | `pictures/2. Site Admin/SA-033 — Manual Sync Product.png` | Phase 12 |
| SA-034 | Manual Sync Category | Site Admin | page | `/admin/sites/{site}/sync/category` | Site | yes | `pictures/2. Site Admin/SA-034 — Manual Sync Category.png` | Phase 12 |
| SA-035 | Manual Sync Whole Site | Site Admin | page | `/admin/sites/{site}/sync/site` | Site | yes | `pictures/2. Site Admin/SA-035 — Manual Sync Whole Site.png` | Phase 12 |
| SA-036 | Create Correction Request | Site Admin | page | `/admin/sites/{site}/corrections/create` | Site | yes | `pictures/2. Site Admin/SA-036 — Create Correction Request.png` | Phase 12 |
| SA-037 | My Correction Requests | Site Admin | page | `/admin/sites/{site}/corrections` | Site | yes | `pictures/2. Site Admin/SA-037 — My Correction Requests.png` | Phase 12 |
| SA-038 | Correction Request Detail | Site Admin | page | `/admin/sites/{site}/corrections/{request}` | Site | yes | `pictures/2. Site Admin/SA-038 — Correction Request Detail.png` | Phase 12 |
| SA-039 | Site Price Sources | Site Admin | page | `/admin/sites/{site}/price-sources` | Site | yes | `pictures/2. Site Admin/SA-039 — Site Price Sources.png` | Phase 13 |
| SA-040 | Offer Provider Settings | Site Admin | page | `/admin/sites/{site}/offers/providers` | Site | yes | `pictures/2. Site Admin/SA-040 — Offer Provider Settings.png` | Phase 13 |
| SA-041 | External Widget Config | Site Admin | page | `/admin/sites/{site}/offers/widget` | Site | yes | `pictures/2. Site Admin/SA-041 — External Widget Config.png` | Phase 13 |
| SA-042 | Local Offers List | Site Admin | page | `/admin/sites/{site}/offers` | Site | yes | `pictures/2. Site Admin/SA-042 — Local Offers List.png` | Phase 13 |
| SA-043 | Local Offer Editor | Site Admin | page | `/admin/sites/{site}/offers/{offer?}/edit` | Site | yes | `pictures/2. Site Admin/SA-043 — Local Offer Editor.png` | Phase 13 |
| SA-044 | Products Without Offers | Site Admin | page | `/admin/sites/{site}/offers/missing` | Site | yes | `pictures/2. Site Admin/SA-044 — Products Without Offers.png` | Phase 13 |
| SA-045 | Price Freshness Report | Site Admin | page | `/admin/sites/{site}/offers/freshness` | Site | yes | `pictures/2. Site Admin/SA-045 — Price Freshness Report.png` | Phase 13 |
| SA-046 | Price Coverage Dashboard | Site Admin | page | `/admin/sites/{site}/offers/coverage` | Site | yes | `pictures/2. Site Admin/SA-046 — Price Coverage Dashboard.png` | Phase 13 |
| SA-047 | Reviews List | Site Admin | page | `/admin/sites/{site}/reviews` | Site | yes | `pictures/2. Site Admin/SA-047 — Reviews List.png` | Phase 14 |
| SA-048 | Review Detail | Site Admin | page | `/admin/sites/{site}/reviews/{review}` | Site | yes | `pictures/2. Site Admin/SA-048 — Review Detail.png` | Phase 14 |
| SA-049 | Review Moderation Queue | Site Admin | page | `/admin/sites/{site}/reviews/moderation` | Site | yes | `pictures/2. Site Admin/SA-049 — Review Moderation Queue.png` | Phase 14 |
| SA-050 | Review Settings | Site Admin | page | `/admin/sites/{site}/reviews/settings` | Site | yes | `pictures/2. Site Admin/SA-050 — Review Settings.png` | Phase 14 |
| SA-051 | Leads List | Site Admin | page | `/admin/sites/{site}/leads` | Site | yes | `pictures/2. Site Admin/SA-051 — Leads List.png` | Phase 14 |
| SA-052 | Lead Detail | Site Admin | page | `/admin/sites/{site}/leads/{lead}` | Site | yes | `pictures/2. Site Admin/SA-052 — Lead Detail.png` | Phase 14 |
| SA-053 | Lead Status Board | Site Admin | page | `/admin/sites/{site}/leads/board` | Site | yes | `pictures/2. Site Admin/SA-053 — Lead Status Board.png` | Phase 14 |
| SA-054 | Lead Form Settings | Site Admin | page | `/admin/sites/{site}/leads/form-settings` | Site | yes | `pictures/2. Site Admin/SA-054 — Lead Form Settings.png` | Phase 14 |
| SA-055 | Lead Notifications Settings | Site Admin | page | `/admin/sites/{site}/leads/notifications` | Site | yes | `pictures/2. Site Admin/SA-055 — Lead Notifications Settings.png` | Phase 14 |
| SA-056 | Content List | Site Admin | page | `/admin/sites/{site}/content` | Site | yes | `pictures/2. Site Admin/SA-056 — Content List.png` | Phase 15 |
| SA-057 | Article Editor | Site Admin | page | `/admin/sites/{site}/content/articles/{item?}/edit` | Site | yes | `pictures/2. Site Admin/SA-057 — Article Editor.png` | Phase 15 |
| SA-058 | Guide Editor | Site Admin | page | `/admin/sites/{site}/content/guides/{item?}/edit` | Site | yes | `pictures/2. Site Admin/SA-058 — Guide Editor.png` | Phase 15 |
| SA-059 | FAQ Editor | Site Admin | page | `/admin/sites/{site}/content/faqs/{item?}/edit` | Site | yes | `pictures/2. Site Admin/SA-059 — FAQ Editor.png` | Phase 15 |
| SA-060 | Content Translation Editor | Site Admin | page | `/admin/sites/{site}/content/{item}/translations` | Site | yes | `pictures/2. Site Admin/SA-060 — Content Translation Editor.png` | Phase 15 |
| SA-061 | Content Relations | Site Admin | page | `/admin/sites/{site}/content/{item}/relations` | Site | yes | `pictures/2. Site Admin/SA-061 — Content Relations.png` | Phase 15 |
| SA-062 | Polls List | Site Admin | page | `/admin/sites/{site}/polls` | Site | yes | `pictures/2. Site Admin/SA-062 — Polls List.png` | Phase 15 |
| SA-063 | Poll Editor | Site Admin | page | `/admin/sites/{site}/polls/{poll?}/edit` | Site | yes | `pictures/2. Site Admin/SA-063 — Poll Editor.png` | Phase 15 |
| SA-064 | Poll Results | Site Admin | page | `/admin/sites/{site}/polls/{poll}/results` | Site | yes | `pictures/2. Site Admin/SA-064 — Poll Results.png` | Phase 15 |

## Public Local Site

Only the ten definitions below are evidenced by numbered discovery entries in
this repository. The other requested IDs are deliberately blocked rather than
being filled from unnumbered discovery prose. This is a closed, enumerated
scope.

| Screen ID | Name | Surface | Type | Route | Workspace context | Site context required | Reference artifact | Roadmap phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| PUB-001 | Home Page: Multi-category | Public Site | page | `/` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-002 | Home Page: Single-category | Public Site | page | `/` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-003 | Category Page | Public Site | page | `/categories/{categorySlug}` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-004 | Product Listing Page | Public Site | page | `/products` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-005 | Product Detail Page | Public Site | page | `/products/{productSlug}` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-006 | Compare Page | Public Site | page | `/compare` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-007 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-008 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-009 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-010 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-011 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-012 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-013 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-014 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-015 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-016 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-017 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-018 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-019 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-020 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-021 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-022 | Listing with Desktop Facets | Public Site | state | `/products?{facetQuery}` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-023 | Listing with Mobile Facet Drawer | Public Site | component | `/products?{facetQuery}` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-024 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-025 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-026 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-027 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-028 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-029 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-030 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-031 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-032 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-033 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-034 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-035 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-036 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-037 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-038 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-039 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-040 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-041 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-042 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-043 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-044 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-045 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-046 | Offers Table | Public Site | block | `embedded in PUB-005` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-047 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-048 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-049 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-050 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-051 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-052 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-053 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-054 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-055 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-056 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-057 | Repair Lead Form | Public Site | page | `/leads/repair` | Public runtime | yes | `docs/discovery/screens/public-demo-site-pages.md` | Phase 16 |
| PUB-058 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-059 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-060 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-061 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-062 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-063 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-064 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-065 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-066 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-067 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-068 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-069 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-070 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-071 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-072 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-073 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-074 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-075 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-076 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-077 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-078 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-079 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |
| PUB-080 | **BLOCKED — approved definition absent from repository** | Public Site | **BLOCKED** | **BLOCKED** | Public runtime | yes | **REQUIRED BEFORE IMPLEMENTATION** | Phase 16 |

## Public poll boundary

The registry contains no approved standalone Public Poll page. Public poll
rendering is therefore a **block/component inside an already approved public
page**, not a standalone public screen or route. SA-062 through SA-064 own poll
administration. A separate public poll page requires a contract and registry
change; it must not be inferred from those Site Admin screens.

## Completeness gate

Before any phase starts, its Screen Acceptance Matrix must match this registry
exactly. A changed name, type, route, surface, context rule, reference, or phase
requires an approved registry version and changelog entry first. Phase 16 is
blocked for every unresolved PUB row; the ten defined rows may only be sliced
into an earlier MR after a product decision explicitly permits partial Phase 16
acceptance.
