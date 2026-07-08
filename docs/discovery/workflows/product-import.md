# Product Import Workflow

Task: P00-006 - Document Product Import Workflow  
Area: Product / Workflow  
Status: Phase 0 discovery artifact

## Purpose

Define the product import workflow from external source data to reviewed Central
Catalog products and site projection rebuilds.

Imports must never publish directly to public products. New and changed products
pass through raw storage, mapping, normalization, duplicate detection, review,
and Central approval.

This document is not an import parser implementation, queue implementation,
database schema, media downloader, or admin UI.

## Actors

- Primary: `import_operator`
- Supporting: `data_manager`, `catalog_editor`, `media_manager`
- Approver: `central_admin` or authorized reviewer

## Trigger

An operator starts an import from a configured source, uploaded artifact, feed,
manual file, or serialized PHP source export.

## Preconditions

- Target category exists and has an approved or reviewable schema.
- Import source is known or explicitly added as a new source.
- Mapping rules exist or can be created during review.
- Storage for raw artifacts is available in the future implementation.

## Pipeline

1. Select import source.
   - Choose feed, uploaded file, API source, manual dataset, or serialized PHP
     files adapter.
2. Create import batch.
   - Record source, category, operator, timestamp, and expected mode.
3. Store import artifacts.
   - Preserve original files or response payloads for audit and replay.
4. Store raw products.
   - Extract raw product rows without changing source values.
5. Map raw fields.
   - Connect source fields to canonical product identity, brand, category, and
     category attributes.
6. Normalize attributes.
   - Convert source values into canonical values, enums, booleans, and text.
7. Parse units.
   - Store numeric values as canonical value plus canonical unit.
8. Download media.
   - Queue media asset fetches and track variant generation status later.
9. Detect duplicates.
   - Compare candidate products using identity, brand, model, identifiers, specs,
     source URLs, and media.
10. Create normalized drafts.
    - Produce reviewable product drafts with confidence and issue metadata.
11. Review errors.
    - Surface unmapped fields, parse failures, missing required values, media
      failures, and duplicate candidates.
12. Approve or reject drafts.
    - Reviewer accepts, rejects, or sends drafts back for mapping changes.
13. Publish approved products to Central Catalog.
    - Approved drafts become Central products or product version updates.
14. Trigger site sync/projection rebuild.
    - Affected sites are marked stale and projection jobs are queued by the
      future sync system.

## Serialized PHP Files Adapter

The serialized PHP adapter is treated as a source adapter, not as a shortcut into
Central products.

Adapter responsibilities:

- read serialized PHP artifacts;
- preserve original artifact content;
- decode records into raw product rows;
- report malformed records;
- expose source field names and source values to the mapping layer;
- attach source file and record identifiers for audit.

Adapter must not:

- write Central products directly;
- bypass duplicate detection;
- normalize values without mapping visibility;
- download media outside the import pipeline;
- publish public projections.

## Error And Edge Cases

- Source unavailable: batch remains failed or retryable, no Central writes.
- Artifact malformed: raw artifact is retained and row-level errors are shown.
- Unknown category: batch cannot advance past setup.
- Missing mapping: batch enters `needs mapping`.
- Unit parse ambiguity: normalized draft requires review.
- Duplicate candidate detected: reviewer must choose merge, update, reject, or
  create new product with justification.
- Media download fails: draft may be approved only if media is optional or media
  issue is explicitly accepted.
- Required attribute missing: draft cannot publish until fixed or schema rule is
  changed through the category workflow.
- Approved update affects sites: products become stale until projection rebuild.

## Output

- Import batch audit trail.
- Stored raw artifacts and raw product rows.
- Mapping issues and normalization issues.
- Reviewed normalized drafts.
- Approved Central products or rejected drafts.
- Sync/projection rebuild trigger for affected sites.

## Future Phase Dependencies

- Phase 4: schemas, units, mapping constraints.
- Phase 9: Import System and Import UX.
- Phase 10: projection rebuild visibility in Site Admin.
- Phase 13: public pages consuming rebuilt projections.

## Phase 0 Boundaries

Included:

- workflow stages;
- raw layer;
- mapping and normalization;
- review queue;
- media download status;
- serialized PHP source adapter rules.

Excluded:

- parser code;
- queue jobs;
- storage drivers;
- migrations;
- models;
- admin components;
- public product writes.
