# Attribute Correction Workflow

Task: P00-007 - Document Attribute Correction Workflow  
Area: Product / Workflow  
Status: Phase 0 discovery artifact

## Purpose

Define how incorrect product attributes are corrected while preserving the
Central Catalog ownership model.

Global corrections update canonical data after Central review. Local
market-specific differences are handled as overrides and do not silently mutate
canonical specs.

This document is not a change request model, moderation queue implementation,
versioning service, or admin UI.

## Actors

- Central fix: `catalog_editor`, `data_manager`, `central_admin`
- Site proposal: `site_admin`, `content_editor`
- Review: `central_admin` or authorized reviewer

## Trigger

An attribute value is found to be wrong, incomplete, misleading, unsupported by
evidence, or different for a specific market.

## Preconditions

- Product exists in Central Catalog.
- Attribute belongs to the product category schema.
- Reporter can provide old value, proposed value, reason, and evidence URL when
  possible.
- Site Admin understands whether the issue is global or local.

## Required Request Data

- product reference;
- attribute reference;
- old value;
- proposed value;
- canonical unit if numeric;
- reason;
- evidence URL or source note;
- reporter;
- reviewer;
- global correction or local market-specific difference;
- affected sites if known.

## Scenarios

### Central Admin Fixes Canonical Attribute

1. `catalog_editor` opens Central Product Detail.
2. Incorrect canonical value is identified.
3. Evidence is attached or existing source/import history is referenced.
4. New canonical value is entered.
5. Validation checks schema, type, enum, unit, and required status.
6. Reviewer approves if required by policy.
7. Product version increments.
8. Affected site products are marked stale.
9. Central to Site sync rebuilds projections.

### Site Admin Proposes Correction

1. `site_admin` finds a suspected canonical error in Site Admin or public page.
2. Site Admin creates a correction request instead of editing canonical specs.
3. Request includes old value, proposed value, evidence URL, and reason.
4. Request state becomes `submitted`.
5. Central reviewer triages and moves it to `under review`.

### Central Admin Reviews Correction Request

1. Reviewer compares request with canonical product, source history, and evidence.
2. Reviewer checks whether the proposed change is global or market-specific.
3. Reviewer may ask for more evidence, approve, reject, or convert to local
   override recommendation.

### Correction Approved

1. Request state becomes `approved`.
2. Canonical product value changes.
3. Product version increments.
4. Request is marked `applied`.
5. Affected sites become stale.
6. Sync and projection rebuild are triggered.

### Correction Rejected

1. Request state becomes `rejected`.
2. Reviewer records rejection reason.
3. Canonical product remains unchanged.
4. Site Admin may use an allowed local override only if the difference is
   presentation or market-specific.

### Local Market Override Instead Of Global Correction

1. Reviewer determines the proposed value is not globally true.
2. Request is rejected as canonical correction or converted into a local override
   recommendation.
3. Site Admin records market-specific reason.
4. Projection is rebuilt for that site/market only.
5. Canonical Central product remains unchanged.

## Edge Cases

- Evidence contradicts current import source: reviewer decides whether evidence
  supersedes import data.
- Attribute no longer exists in schema: request is blocked until schema decision.
- Proposed numeric value has incompatible unit: request returns to reporter or
  data manager for normalization.
- Duplicate requests exist: reviewer merges or links requests.
- Product is deprecated: correction may be rejected unless needed for historical
  projection accuracy.
- Correct value differs by market: use local override path with explicit reason.
- Correction changes comparison/facet behavior: affected projections and search
  documents become stale.

## Output

- Approved canonical correction with product version increment, or rejected
  request with reason.
- Affected sites marked stale when canonical data changes.
- Local override recommendation when value is market-specific.

## Future Phase Dependencies

- Phase 3: product versioning and canonical product edit workflows.
- Phase 4: schema validation and normalized values.
- Phase 10: correction request UX and stale status in Site Admin.
- Phase 13: public projections reflecting accepted corrections.

## Phase 0 Boundaries

Included:

- actors, trigger, preconditions;
- global correction vs local override distinction;
- change request lifecycle;
- version and stale projection impact;
- edge cases.

Excluded:

- database models;
- queues;
- versioning implementation;
- admin components;
- public frontend changes.
