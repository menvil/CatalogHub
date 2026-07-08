# Site To Central Correction Workflow

Task: P00-011 - Document Site to Central Correction Workflow  
Area: Product / Sync  
Status: Phase 0 discovery artifact

## Purpose

Define how local sites send reusable corrections and contributions back to
Central Catalog without directly mutating canonical data.

This workflow covers correction requests, translation suggestions, media
contributions, duplicate signals, new local product suggestions, and reusable
content proposals.

This document does not implement request tables, review queues, notifications,
media uploads, or Central write services.

## Actors

- Submitter: `site_admin`, `content_editor`, `moderator`, `support_operator`
- Reviewer: `central_admin`, `catalog_editor`, `data_manager`, `translator`,
  `media_manager`
- Consumer after approval: affected sites through Central to Site sync

## Trigger

A site team identifies information that could improve Central Catalog or reusable
platform content.

## Contribution Types

| Type | Purpose | Reviewer |
| --- | --- | --- |
| Attribute correction | Fix a suspected canonical product fact. | `central_admin`, `catalog_editor`, `data_manager` |
| Duplicate signal | Report two Central products that may represent the same item. | `catalog_editor`, `data_manager` |
| Translation suggestion | Improve global translated labels or product content. | `translator`, `central_admin` |
| Media contribution | Suggest reusable product/category media. | `media_manager`, `central_admin` |
| New local product suggestion | Propose a product seen locally but missing in Central. | `catalog_editor`, `import_operator` |
| Reusable content proposal | Suggest guide/FAQ/content that could become shared. | `content_editor`, `central_admin` |

## Lifecycle

```txt
draft
-> submitted
-> under review
-> approved / rejected
-> applied to central
-> affected sites stale
```

## Happy Path

1. Site user creates draft contribution.
   - Required context depends on contribution type.
2. Site user adds evidence.
   - Evidence may include URL, source note, screenshot reference, source file,
     product page, user feedback, or market observation.
3. Site user submits request.
   - State becomes `submitted`.
4. Central reviewer triages request.
   - State becomes `under review`.
5. Reviewer validates scope.
   - Reviewer decides whether the contribution is global, market-specific,
     duplicate, translation-only, media-only, or local-only.
6. Reviewer approves or rejects.
   - Approved requests continue to Central application.
   - Rejected requests retain reason and may suggest local override instead.
7. Approved contribution is applied to Central.
   - Central entity changes through the appropriate future workflow.
8. Affected sites become stale.
   - Central to Site sync detects affected sites and rebuilds projections.

## Rejected Path

Rejected requests must record:

- reviewer;
- rejection reason;
- whether more evidence is needed;
- whether the request is local-only;
- whether Site Admin should use a local override;
- whether a duplicate request already exists.

Canonical Central data remains unchanged on rejection.

## Required Data By Type

### Attribute Correction

- product;
- attribute;
- old value;
- proposed value;
- evidence URL/source note;
- reason;
- global vs market-specific assumption.

### Duplicate Signal

- suspected duplicate products;
- matching evidence;
- conflicting attributes;
- preferred canonical record if known.

### Translation Suggestion

- locale;
- source text;
- proposed translation;
- context;
- reviewer note if terminology is domain-specific.

### Media Contribution

- product/category context;
- media source;
- usage rights assumption;
- target assignment suggestion;
- locale/market specificity if any.

### New Local Product Suggestion

- site and market;
- product name/model;
- brand;
- source URL or evidence;
- category suggestion;
- reason it should be reusable.

### Reusable Content Proposal

- content type;
- target category/product context;
- source draft;
- reuse scope;
- localization needs.

## Error And Edge Cases

- Request is actually a local override: reject as Central correction and route
  Site Admin to local override workflow.
- Evidence is missing: keep under review or reject with evidence request.
- Contribution conflicts with approved Central data: reviewer decides using
  source confidence and may open conflict record.
- Duplicate request exists: merge or link requests.
- Approved request cannot be applied because schema is missing: block until
  category schema workflow resolves it.
- Translation suggestion changes meaning of numeric values: reject and preserve
  canonical numeric normalization.
- Media contribution lacks rights confidence: reject or require review before use.

## Output

- Contribution is rejected with reason, or approved and applied to Central.
- Applied contribution triggers Central to Site sync.
- Affected sites are marked stale when canonical or reusable shared data changes.

## Future Phase Dependencies

- Phase 3: Central product and version changes.
- Phase 4: schema-aware corrections.
- Phase 9: import and new product suggestion handling.
- Phase 10: Site Admin correction UX.
- Phase 13: public projections refreshed after accepted changes.

## Phase 0 Boundaries

Included:

- contribution types;
- lifecycle;
- required Central review;
- rejected path;
- sync trigger after application.

Excluded:

- persistence models;
- review queue implementation;
- notifications;
- media upload implementation;
- admin UI;
- public frontend.
