# Central Admin Screen Inventory

Task: P00-012 - List Key Central Admin Screens  
Area: UX / Inventory  
Status: Phase 0 discovery artifact

## Purpose

List the must-have Central Admin screens for the first design and implementation
rounds. Central Admin screens manage canonical truth and operational catalog
quality. They do not manage local site presentation.

This inventory is not UI implementation, routing, Filament configuration, or a
component spec.

## Must-Have Screens

| ID | Screen | Purpose | Primary actor | Primary actions | Key data | Empty/error states |
| --- | --- | --- | --- | --- | --- | --- |
| CA-001 | Central Dashboard | Show operational health across catalog, imports, sync, translations, price sources, and alerts. | `central_admin` | Open queues, inspect alert, start import, review stale projections. | KPIs, import status, sync status, catalog health, price source status. | Empty queues, failed import, stale sync, critical alert. |
| CA-003 | Product Detail | Inspect and edit canonical product object. | `catalog_editor` | Edit identity/specs, inspect versions, review conflicts, view site status. | Product identity, brand, category, specs, media, translations, versions. | Missing required spec, conflict, no media, no translation. |
| CA-019 | Category Schema Builder | Define category sections, attributes, units, facets, comparison, and schema validation. | `data_manager` | Add section, add attribute, configure flags, approve schema. | Category, sections, attributes, units, enum options, rules. | Invalid schema, unmapped unit, incompatible change, empty section. |
| CA-036 | Import Wizard | Start and configure an import batch. | `import_operator` | Select source, upload/select artifact, choose category, start batch. | Source, category, file/feed metadata, mapping status. | Missing source, invalid file, unknown category, source unavailable. |
| CA-038 | Normalized Draft Review | Compare raw product data with normalized draft before Central publish. | `import_operator` | Approve, reject, edit mapping, inspect duplicate, view errors. | Raw fields, normalized values, confidence, errors, duplicates, media status. | Unmapped fields, parse failure, duplicate conflict, media failure. |
| CA-039 | Mapping Rules Editor | Maintain source-to-canonical mapping rules. | `data_manager` | Map field, define transform, test mapping, save rule. | Source fields, canonical attributes, unit rules, enum mappings. | Missing canonical target, invalid transform, low confidence sample. |
| CA-044 | Media Library | Manage media assets, variants, assignments, and quality issues. | `media_manager` | Upload, assign, generate variant, flag issue, replace asset. | Assets, variants, product/category assignments, source metadata. | Processing failed, missing variant, broken URL, duplicate asset. |
| CA-052 | Translation Dashboard | Manage global translations and missing locale coverage. | `translator` | Translate, approve suggestion, filter missing, mark reviewed. | Locales, source labels, translated labels, product/category context. | Missing translation, stale source, rejected suggestion, empty queue. |
| CA-060 | Change Requests Queue | Review Site to Central correction requests and reusable contributions. | `central_admin` | Triage, approve, reject, request evidence, apply change. | Request type, evidence, old/proposed value, reporter, reviewer. | Missing evidence, duplicate request, conflict, blocked by schema. |
| CA-066 | Price Sources List | Monitor and configure central price sources and offer quality. | `price_manager` | Add source, test source, disable source, inspect offer errors. | Source health, last run, offer counts, error counts, markets. | Source failing, stale offers, no credentials, malformed feed. |

## Supporting Central Screens

- Product list and filters.
- Brand list and brand detail.
- Category tree and category detail.
- Unit list and unit detail.
- Conflict list and conflict detail.
- Snapshot/export list.
- Users and role assignment.
- Central settings.

## Central Boundary

These screens may expose site publication status and stale projection signals,
but they should not become Site Admin tools for local SEO, homepage blocks,
review moderation, or lead processing.

## Future Phase Dependencies

- Phase 2: Admin UI Kit.
- Phase 3: Central Catalog Core.
- Phase 4: Category Schema UX and backend.
- Phase 9: Import System and Import UX.
- Phase 10: sync/status visibility consumed by Site Admin.

## Phase 0 Boundaries

Included:

- screen IDs;
- purpose;
- primary actor;
- actions;
- key data;
- empty/error states.

Excluded:

- production UI;
- routes;
- controllers;
- Livewire/Filament resources;
- database implementation.
