# CatalogHub v2 Approved Visual Reference Manifest

| Field | Value |
| --- | --- |
| Manifest version | 1.0.0 |
| Status | Proposed; artifact versioning incomplete |
| Owner | CatalogHub Product Owner |
| Approver | `TBD — approver must be named` |
| Approval date | `TBD — YYYY-MM-DD` |
| Acceptance mode | Semantic/manual for MVP; strict pixel diff is not required |

## Reproducibility status

The CA/SA PNGs listed below exist in the working copy under `pictures/`, but
that directory is not committed in the current MR baseline. Therefore every
SHA-256 is intentionally marked **REQUIRED BEFORE IMPLEMENTATION — NOT
VERSIONED**. Native dimensions were read from the local files and are inventory
evidence only.

Before a screen work package starts, its approved PNG must be committed (or
placed in an approved immutable artifact store), its SHA-256 recorded here, and
its capture metadata/seed fixture confirmed. Until then, automated visual
regression is **not reproducible** and must not be claimed. A Markdown discovery
entry is scope evidence, not a substitute for a visual artifact.

The intended viewport initially equals the image's native pixel dimensions.
Product/design must confirm whether browser chrome was excluded before approval.
Locale is the visible admin reference locale (`en`). Each scenario ID requires
a deterministic seeder that reproduces the visible populated state and any
state named for that ID in the registry.

## Severity levels

- **Severity 1:** wrong product surface, wrong navigation, wrong workspace/site
  context, missing primary action, or wrong visual style family.
- **Severity 2:** missing secondary block, wrong state, incorrect data density,
  or inconsistent component usage.
- **Severity 3:** minor spacing, copy, icon, or non-blocking polish mismatch.

Severity 1 blocks merge. Severity 2 blocks merge unless Product and Design record
an explicit, dated exception. Severity 3 may be accepted manually with an owner
and follow-up decision; it must not be silently ignored.

## MVP semantic/manual procedure

For each screen, the reviewer records actor, workspace, immutable Site context
when applicable, route, seed scenario, viewport, artifact SHA, primary action
result, and deviations by severity. Semantic acceptance checks surface,
navigation, context, hierarchy, visible states, data density, component family,
and primary actions. It is not a pixel-diff percentage.

## Central Admin and Site Admin references

| Screen ID | Filename | SHA-256 | Native width | Native height | Intended viewport | Locale | Seed scenario | Visual acceptance mode |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| CA-001 | `pictures/1. Central Admin/1.1. Dashboard/CA-001 — Dashboard.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-001-POPULATED` | semantic/manual MVP |
| CA-002 | `pictures/1. Central Admin/1.2. Products/CA-002 — Products List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-002-POPULATED` | semantic/manual MVP |
| CA-003 | `pictures/1. Central Admin/1.2. Products/CA-003 — Product Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-003-POPULATED` | semantic/manual MVP |
| CA-004 | `pictures/1. Central Admin/1.2. Products/CA-004 — Product Create:Edit.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-004-POPULATED` | semantic/manual MVP |
| CA-005 | `pictures/1. Central Admin/1.2. Products/CA-005 — Product Variants.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-005-POPULATED` | semantic/manual MVP |
| CA-006 | `pictures/1. Central Admin/1.2. Products/CA-006 — Product Specs Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-006-POPULATED` | semantic/manual MVP |
| CA-007 | `pictures/1. Central Admin/1.2. Products/CA-007 — Product Media Manager.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-007-POPULATED` | semantic/manual MVP |
| CA-008 | `pictures/1. Central Admin/1.2. Products/CA-008 — Product Translations.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-008-POPULATED` | semantic/manual MVP |
| CA-009 | `pictures/1. Central Admin/1.2. Products/CA-009 — Product Version History.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-009-POPULATED` | semantic/manual MVP |
| CA-010 | `pictures/1. Central Admin/1.2. Products/CA-010 — Product Data Quality View.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-010-POPULATED` | semantic/manual MVP |
| CA-011 | `pictures/1. Central Admin/1.3. Brands/CA-011 — Brands List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-011-POPULATED` | semantic/manual MVP |
| CA-012 | `pictures/1. Central Admin/1.3. Brands/CA-012 — Brand Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-012-POPULATED` | semantic/manual MVP |
| CA-013 | `pictures/1. Central Admin/1.3. Brands/CA-013 — Brand Create:Edit.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-013-POPULATED` | semantic/manual MVP |
| CA-014 | `pictures/1. Central Admin/1.3. Brands/CA-014 — Brand Media : Logo.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-014-POPULATED` | semantic/manual MVP |
| CA-015 | `pictures/1. Central Admin/1.3. Brands/CA-015 — Brand Translations.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-015-POPULATED` | semantic/manual MVP |
| CA-016 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-016 — Categories List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-016-POPULATED` | semantic/manual MVP |
| CA-017 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-017 — Category Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-017-POPULATED` | semantic/manual MVP |
| CA-018 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-018 — Category Create:Edit.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-018-POPULATED` | semantic/manual MVP |
| CA-019 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-019 — Category Schema Builder.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-019-POPULATED` | semantic/manual MVP |
| CA-020 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-020 — Attribute Sections Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-020-POPULATED` | semantic/manual MVP |
| CA-021 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-021 — Attribute Definitions Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-021-POPULATED` | semantic/manual MVP |
| CA-022 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-022 — Attribute Options Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-022-POPULATED` | semantic/manual MVP |
| CA-023 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-023 — Category Facets Config.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-023-POPULATED` | semantic/manual MVP |
| CA-024 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-024 — Category Comparison Config.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-024-POPULATED` | semantic/manual MVP |
| CA-025 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-025 — Category SEO Templates.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-025-POPULATED` | semantic/manual MVP |
| CA-026 | `pictures/1. Central Admin/1.4. Categories : Schema/CA-026 — Category Translation Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-026-POPULATED` | semantic/manual MVP |
| CA-027 | `pictures/1. Central Admin/1.5. Units : Measurements/CA-027 — Measurement Dimensions.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-027-POPULATED` | semantic/manual MVP |
| CA-028 | `pictures/1. Central Admin/1.5. Units : Measurements/CA-028 — Measurement Units.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-028-POPULATED` | semantic/manual MVP |
| CA-029 | `pictures/1. Central Admin/1.5. Units : Measurements/CA-029 — Unit Aliases.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-029-POPULATED` | semantic/manual MVP |
| CA-030 | `pictures/1. Central Admin/1.5. Units : Measurements/CA-030 — Unit Translations.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-030-POPULATED` | semantic/manual MVP |
| CA-031 | `pictures/1. Central Admin/1.5. Units : Measurements/CA-031 — Market Unit Preferences.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1536 | 1024 | 1536×1024 (confirm) | en | `VR-CA-031-POPULATED` | semantic/manual MVP |
| CA-032 | `pictures/1. Central Admin/1.5. Units : Measurements/CA-032 — Attribute Display Rules.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-032-POPULATED` | semantic/manual MVP |
| CA-033 | `pictures/1. Central Admin/1.6. Imports/CA-033 — Import Sources.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-033-POPULATED` | semantic/manual MVP |
| CA-034 | `pictures/1. Central Admin/1.6. Imports/CA-034 — Import Batches List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-034-POPULATED` | semantic/manual MVP |
| CA-035 | `pictures/1. Central Admin/1.6. Imports/CA-035 — Import Batch Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-035-POPULATED` | semantic/manual MVP |
| CA-036 | `pictures/1. Central Admin/1.6. Imports/CA-036 — Import Wizard.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-036-POPULATED` | semantic/manual MVP |
| CA-037 | `pictures/1. Central Admin/1.6. Imports/CA-037 — Raw Product Viewer.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-037-POPULATED` | semantic/manual MVP |
| CA-038 | `pictures/1. Central Admin/1.6. Imports/CA-038 — Normalized Draft Review.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-038-POPULATED` | semantic/manual MVP |
| CA-039 | `pictures/1. Central Admin/1.6. Imports/CA-039 — Mapping Rules Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-039-POPULATED` | semantic/manual MVP |
| CA-040 | `pictures/1. Central Admin/1.6. Imports/CA-040 — Unmapped Fields.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-040-POPULATED` | semantic/manual MVP |
| CA-041 | `pictures/1. Central Admin/1.6. Imports/CA-041 — Duplicate Candidates.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-041-POPULATED` | semantic/manual MVP |
| CA-042 | `pictures/1. Central Admin/1.6. Imports/CA-042 — Normalization Errors.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-042-POPULATED` | semantic/manual MVP |
| CA-043 | `pictures/1. Central Admin/1.6. Imports/CA-043 — Media Download Errors.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-043-POPULATED` | semantic/manual MVP |
| CA-044 | `pictures/1. Central Admin/1.7. Media Library/CA-044 — Media Library.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-044-POPULATED` | semantic/manual MVP |
| CA-045 | `pictures/1. Central Admin/1.7. Media Library/CA-045 — Media Asset Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-045-POPULATED` | semantic/manual MVP |
| CA-046 | `pictures/1. Central Admin/1.7. Media Library/CA-046 — Media Upload.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-046-POPULATED` | semantic/manual MVP |
| CA-047 | `pictures/1. Central Admin/1.7. Media Library/CA-047 — Media Variants Preview.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-047-POPULATED` | semantic/manual MVP |
| CA-048 | `pictures/1. Central Admin/1.7. Media Library/CA-048 — Media Assignments.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-048-POPULATED` | semantic/manual MVP |
| CA-049 | `pictures/1. Central Admin/1.7. Media Library/CA-049 — Localized Media Manager.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-049-POPULATED` | semantic/manual MVP |
| CA-050 | `pictures/1. Central Admin/1.7. Media Library/CA-050 — Media Sources : Licenses.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-050-POPULATED` | semantic/manual MVP |
| CA-051 | `pictures/1. Central Admin/1.7. Media Library/CA-051 — Media Integrity Report.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-051-POPULATED` | semantic/manual MVP |
| CA-052 | `pictures/1. Central Admin/1.8. Translations/CA-052 — Translation Dashboard.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-052-POPULATED` | semantic/manual MVP |
| CA-053 | `pictures/1. Central Admin/1.8. Translations/CA-053 — Missing Translations.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-053-POPULATED` | semantic/manual MVP |
| CA-054 | `pictures/1. Central Admin/1.8. Translations/CA-054 — Outdated Translations.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-054-POPULATED` | semantic/manual MVP |
| CA-055 | `pictures/1. Central Admin/1.8. Translations/CA-055 — Product Translation Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-055-POPULATED` | semantic/manual MVP |
| CA-056 | `pictures/1. Central Admin/1.8. Translations/CA-056 — Category Translation Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-056-POPULATED` | semantic/manual MVP |
| CA-057 | `pictures/1. Central Admin/1.8. Translations/CA-057 — Attribute Translation Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-057-POPULATED` | semantic/manual MVP |
| CA-058 | `pictures/1. Central Admin/1.8. Translations/CA-058 — Unit Translation Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-058-POPULATED` | semantic/manual MVP |
| CA-059 | `pictures/1. Central Admin/1.8. Translations/CA-059 — Bulk Translation Review.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-059-POPULATED` | semantic/manual MVP |
| CA-060 | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-060 — Change Requests Queue.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-060-POPULATED` | semantic/manual MVP |
| CA-061 | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-061 — Change Request Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-061-POPULATED` | semantic/manual MVP |
| CA-062 | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-062 — Correction Diff Viewer.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-062-POPULATED` | semantic/manual MVP |
| CA-063 | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-063 — Conflicts List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-063-POPULATED` | semantic/manual MVP |
| CA-064 | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-064 — Conflict Resolver.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-064-POPULATED` | semantic/manual MVP |
| CA-065 | `pictures/1. Central Admin/1.9. Change Requests : Conflicts/CA-065 — Data Source Comparison.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-065-POPULATED` | semantic/manual MVP |
| CA-066 | `pictures/1. Central Admin/1.10. Price Sources/CA-066 — Price Sources List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1672 | 941 | 1672×941 (confirm) | en | `VR-CA-066-POPULATED` | semantic/manual MVP |
| CA-067 | `pictures/1. Central Admin/1.10. Price Sources/CA-067 — Price Source Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1672 | 941 | 1672×941 (confirm) | en | `VR-CA-067-POPULATED` | semantic/manual MVP |
| CA-068 | `pictures/1. Central Admin/1.10. Price Sources/CA-068 — Price Source Create:Edit.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1672 | 941 | 1672×941 (confirm) | en | `VR-CA-068-POPULATED` | semantic/manual MVP |
| CA-069 | `pictures/1. Central Admin/1.10. Price Sources/CA-069 — Price Source Credentials.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1672 | 941 | 1672×941 (confirm) | en | `VR-CA-069-POPULATED` | semantic/manual MVP |
| CA-070 | `pictures/1. Central Admin/1.10. Price Sources/CA-070 — Price Sync Logs.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1672 | 941 | 1672×941 (confirm) | en | `VR-CA-070-POPULATED` | semantic/manual MVP |
| CA-071 | `pictures/1. Central Admin/1.10. Price Sources/CA-071 — Raw Price Offers Viewer.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1672 | 941 | 1672×941 (confirm) | en | `VR-CA-071-POPULATED` | semantic/manual MVP |
| CA-072 | `pictures/1. Central Admin/1.10. Price Sources/CA-072 — External Product Mapping.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1672 | 941 | 1672×941 (confirm) | en | `VR-CA-072-POPULATED` | semantic/manual MVP |
| CA-073 | `pictures/1. Central Admin/1.10. Price Sources/CA-073 — Mapping Approval Queue.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1672 | 941 | 1672×941 (confirm) | en | `VR-CA-073-POPULATED` | semantic/manual MVP |
| CA-074 | `pictures/1. Central Admin/1.10. Price Sources/CA-074 — Price Source Error Report.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1672 | 941 | 1672×941 (confirm) | en | `VR-CA-074-POPULATED` | semantic/manual MVP |
| CA-075 | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-075 — Snapshots List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-075-POPULATED` | semantic/manual MVP |
| CA-076 | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-076 — Snapshot Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-076-POPULATED` | semantic/manual MVP |
| CA-077 | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-077 — Create Snapshot.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-077-POPULATED` | semantic/manual MVP |
| CA-078 | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-078 — Export History.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-078-POPULATED` | semantic/manual MVP |
| CA-079 | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-079 — Media Manifest Viewer.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-079-POPULATED` | semantic/manual MVP |
| CA-080 | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-080 — Backup Status.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-080-POPULATED` | semantic/manual MVP |
| CA-081 | `pictures/1. Central Admin/1.11. Snapshots : Export : Backup/CA-081 — Restore Checklist.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-081-POPULATED` | semantic/manual MVP |
| CA-082 | `pictures/1. Central Admin/1.12. Users : Roles/CA-082 — Users List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-082-POPULATED` | semantic/manual MVP |
| CA-083 | `pictures/1. Central Admin/1.12. Users : Roles/CA-083 — User Create:Edit.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-083-POPULATED` | semantic/manual MVP |
| CA-084 | `pictures/1. Central Admin/1.12. Users : Roles/CA-084 — Roles & Permissions.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-084-POPULATED` | semantic/manual MVP |
| CA-085 | `pictures/1. Central Admin/1.12. Users : Roles/CA-085 — Activity Log.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-CA-085-POPULATED` | semantic/manual MVP |
| SA-001 | `pictures/2. Site Admin/SA-001 — Site Dashboard.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-001-POPULATED` | semantic/manual MVP |
| SA-002 | `pictures/2. Site Admin/SA-002 — Site Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-002-POPULATED` | semantic/manual MVP |
| SA-003 | `pictures/2. Site Admin/SA-003 — Domain Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-003-POPULATED` | semantic/manual MVP |
| SA-004 | `pictures/2. Site Admin/SA-004 — Market Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-004-POPULATED` | semantic/manual MVP |
| SA-005 | `pictures/2. Site Admin/SA-005 — Locale Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-005-POPULATED` | semantic/manual MVP |
| SA-006 | `pictures/2. Site Admin/SA-006 — Currency Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-006-POPULATED` | semantic/manual MVP |
| SA-007 | `pictures/2. Site Admin/SA-007 — SEO Defaults.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-007-POPULATED` | semantic/manual MVP |
| SA-008 | `pictures/2. Site Admin/SA-008 — Enabled Categories.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-008-POPULATED` | semantic/manual MVP |
| SA-009 | `pictures/2. Site Admin/SA-009 — Category Visibility.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-009-POPULATED` | semantic/manual MVP |
| SA-010 | `pictures/2. Site Admin/SA-010 — Category Local SEO.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-010-POPULATED` | semantic/manual MVP |
| SA-011 | `pictures/2. Site Admin/SA-011 — Category Local Media.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-011-POPULATED` | semantic/manual MVP |
| SA-012 | `pictures/2. Site Admin/SA-012 — Category Page Preview.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-012-POPULATED` | semantic/manual MVP |
| SA-013 | `pictures/2. Site Admin/SA-013 — Site Products List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-013-POPULATED` | semantic/manual MVP |
| SA-014 | `pictures/2. Site Admin/SA-014 — Product Visibility Manager.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-014-POPULATED` | semantic/manual MVP |
| SA-015 | `pictures/2. Site Admin/SA-015 — Product Local Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-015-POPULATED` | semantic/manual MVP |
| SA-016 | `pictures/2. Site Admin/SA-016 — Product Local SEO Override.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-016-POPULATED` | semantic/manual MVP |
| SA-017 | `pictures/2. Site Admin/SA-017 — Product Local Media Override.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-017-POPULATED` | semantic/manual MVP |
| SA-018 | `pictures/2. Site Admin/SA-018 — Product Local Title:Slug Override.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-018-POPULATED` | semantic/manual MVP |
| SA-019 | `pictures/2. Site Admin/SA-019 — Product Projection Preview.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-019-POPULATED` | semantic/manual MVP |
| SA-020 | `pictures/2. Site Admin/SA-020 — Products Without Projection.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-020-POPULATED` | semantic/manual MVP |
| SA-021 | `pictures/2. Site Admin/SA-021 — Stale Products.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-021-POPULATED` | semantic/manual MVP |
| SA-022 | `pictures/2. Site Admin/SA-022 — Theme Selection.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-022-POPULATED` | semantic/manual MVP |
| SA-023 | `pictures/2. Site Admin/SA-023 — Theme Compatibility Check.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-023-POPULATED` | semantic/manual MVP |
| SA-024 | `pictures/2. Site Admin/SA-024 — Theme Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-024-POPULATED` | semantic/manual MVP |
| SA-025 | `pictures/2. Site Admin/SA-025 — Homepage Blocks Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-025-POPULATED` | semantic/manual MVP |
| SA-026 | `pictures/2. Site Admin/SA-026 — Layout Templates Preview.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-026-POPULATED` | semantic/manual MVP |
| SA-027 | `pictures/2. Site Admin/SA-027 — Block Config Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-027-POPULATED` | semantic/manual MVP |
| SA-028 | `pictures/2. Site Admin/SA-028 — Feature Flags.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-028-POPULATED` | semantic/manual MVP |
| SA-029 | `pictures/2. Site Admin/SA-029 — Sync Dashboard.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-029-POPULATED` | semantic/manual MVP |
| SA-030 | `pictures/2. Site Admin/SA-030 — Sync Logs.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-030-POPULATED` | semantic/manual MVP |
| SA-031 | `pictures/2. Site Admin/SA-031 — Projection Jobs.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-031-POPULATED` | semantic/manual MVP |
| SA-032 | `pictures/2. Site Admin/SA-032 — Projection Errors.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-032-POPULATED` | semantic/manual MVP |
| SA-033 | `pictures/2. Site Admin/SA-033 — Manual Sync Product.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-033-POPULATED` | semantic/manual MVP |
| SA-034 | `pictures/2. Site Admin/SA-034 — Manual Sync Category.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-034-POPULATED` | semantic/manual MVP |
| SA-035 | `pictures/2. Site Admin/SA-035 — Manual Sync Whole Site.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-035-POPULATED` | semantic/manual MVP |
| SA-036 | `pictures/2. Site Admin/SA-036 — Create Correction Request.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-036-POPULATED` | semantic/manual MVP |
| SA-037 | `pictures/2. Site Admin/SA-037 — My Correction Requests.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-037-POPULATED` | semantic/manual MVP |
| SA-038 | `pictures/2. Site Admin/SA-038 — Correction Request Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-038-POPULATED` | semantic/manual MVP |
| SA-039 | `pictures/2. Site Admin/SA-039 — Site Price Sources.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-039-POPULATED` | semantic/manual MVP |
| SA-040 | `pictures/2. Site Admin/SA-040 — Offer Provider Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-040-POPULATED` | semantic/manual MVP |
| SA-041 | `pictures/2. Site Admin/SA-041 — External Widget Config.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-041-POPULATED` | semantic/manual MVP |
| SA-042 | `pictures/2. Site Admin/SA-042 — Local Offers List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-042-POPULATED` | semantic/manual MVP |
| SA-043 | `pictures/2. Site Admin/SA-043 — Local Offer Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-043-POPULATED` | semantic/manual MVP |
| SA-044 | `pictures/2. Site Admin/SA-044 — Products Without Offers.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-044-POPULATED` | semantic/manual MVP |
| SA-045 | `pictures/2. Site Admin/SA-045 — Price Freshness Report.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-045-POPULATED` | semantic/manual MVP |
| SA-046 | `pictures/2. Site Admin/SA-046 — Price Coverage Dashboard.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-046-POPULATED` | semantic/manual MVP |
| SA-047 | `pictures/2. Site Admin/SA-047 — Reviews List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-047-POPULATED` | semantic/manual MVP |
| SA-048 | `pictures/2. Site Admin/SA-048 — Review Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-048-POPULATED` | semantic/manual MVP |
| SA-049 | `pictures/2. Site Admin/SA-049 — Review Moderation Queue.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-049-POPULATED` | semantic/manual MVP |
| SA-050 | `pictures/2. Site Admin/SA-050 — Review Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-050-POPULATED` | semantic/manual MVP |
| SA-051 | `pictures/2. Site Admin/SA-051 — Leads List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-051-POPULATED` | semantic/manual MVP |
| SA-052 | `pictures/2. Site Admin/SA-052 — Lead Detail.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-052-POPULATED` | semantic/manual MVP |
| SA-053 | `pictures/2. Site Admin/SA-053 — Lead Status Board.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-053-POPULATED` | semantic/manual MVP |
| SA-054 | `pictures/2. Site Admin/SA-054 — Lead Form Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-054-POPULATED` | semantic/manual MVP |
| SA-055 | `pictures/2. Site Admin/SA-055 — Lead Notifications Settings.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-055-POPULATED` | semantic/manual MVP |
| SA-056 | `pictures/2. Site Admin/SA-056 — Content List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1536 | 1024 | 1536×1024 (confirm) | en | `VR-SA-056-POPULATED` | semantic/manual MVP |
| SA-057 | `pictures/2. Site Admin/SA-057 — Article Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1491 | 1055 | 1491×1055 (confirm) | en | `VR-SA-057-POPULATED` | semantic/manual MVP |
| SA-058 | `pictures/2. Site Admin/SA-058 — Guide Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1536 | 1024 | 1536×1024 (confirm) | en | `VR-SA-058-POPULATED` | semantic/manual MVP |
| SA-059 | `pictures/2. Site Admin/SA-059 — FAQ Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1536 | 1024 | 1536×1024 (confirm) | en | `VR-SA-059-POPULATED` | semantic/manual MVP |
| SA-060 | `pictures/2. Site Admin/SA-060 — Content Translation Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-060-POPULATED` | semantic/manual MVP |
| SA-061 | `pictures/2. Site Admin/SA-061 — Content Relations.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-061-POPULATED` | semantic/manual MVP |
| SA-062 | `pictures/2. Site Admin/SA-062 — Polls List.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-062-POPULATED` | semantic/manual MVP |
| SA-063 | `pictures/2. Site Admin/SA-063 — Poll Editor.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-063-POPULATED` | semantic/manual MVP |
| SA-064 | `pictures/2. Site Admin/SA-064 — Poll Results.png` | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | 1448 | 1086 | 1448×1086 (confirm) | en | `VR-SA-064-POPULATED` | semantic/manual MVP |

## Public reference gaps

These are the only numbered Public definitions evidenced in the repository.
They have discovery descriptions but no committed PNG. Their visual work
packages are blocked until the required artifacts and capture metadata are
approved. Unresolved PUB IDs in the screen registry have neither an approved
definition nor a visual reference and are not duplicated here as if references
existed.

| Screen ID | Filename | SHA-256 | Native width | Native height | Intended viewport | Locale | Seed scenario | Visual acceptance mode |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| PUB-001 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-001.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 1440×900 (to approve) | en | `VR-PUB-001-POPULATED` | semantic/manual MVP |
| PUB-002 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-002.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 1440×900 (to approve) | en | `VR-PUB-002-POPULATED` | semantic/manual MVP |
| PUB-003 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-003.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 1440×900 (to approve) | en | `VR-PUB-003-POPULATED` | semantic/manual MVP |
| PUB-004 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-004.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 1440×900 (to approve) | en | `VR-PUB-004-POPULATED` | semantic/manual MVP |
| PUB-005 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-005.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 1440×900 (to approve) | en | `VR-PUB-005-POPULATED` | semantic/manual MVP |
| PUB-006 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-006.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 1440×900 (to approve) | en | `VR-PUB-006-POPULATED` | semantic/manual MVP |
| PUB-022 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-022.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 1440×900 (to approve) | en | `VR-PUB-022-POPULATED` | semantic/manual MVP |
| PUB-023 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-023.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 375×812 (to approve) | en | `VR-PUB-023-POPULATED` | semantic/manual MVP |
| PUB-046 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-046.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 1440×900 (to approve) | en | `VR-PUB-046-POPULATED` | semantic/manual MVP |
| PUB-057 | **REQUIRED BEFORE IMPLEMENTATION: `PUB-057.png`** | **REQUIRED BEFORE IMPLEMENTATION — NOT VERSIONED** | TBD | TBD | 1440×900 (to approve) | en | `VR-PUB-057-POPULATED` | semantic/manual MVP |

## Artifact update gate

A manifest update that replaces a placeholder must include the committed
filename, SHA-256, native dimensions, intended viewport, locale, deterministic
seed scenario, approver, approval date, and changelog/version increment. Changing
an image without changing its digest is not an approval process.
