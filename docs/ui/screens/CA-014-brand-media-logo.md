---
screen_id: CA-014
context: central-admin
purpose: Manage a Brand's canonical global identity media through Shared Media Core.
roles: authorized Central Admin catalog user
route: /admin/central/brands/{brand}/media (GET); /admin/central/brands/{brand}/media/logo (POST, DELETE); /admin/central/brands/{brand}/media/logo/assign (POST)
viewports: desktop=1440x1000;mobile=390x844
fixture: brand-media-v2
regions: central-shell;breadcrumbs;page-header;brand-tabs;current-identity;asset-information;generated-variants;upload-form;bounded-asset-selector;confirmation-modal;flash-feedback
actions: upload-logo;replace-logo;assign-existing-logo;remove-logo;confirm;cancel
states: no-asset;ready;processing;failed;unavailable;validation-error
permissions: catalog.brands.manage; media.manage additionally gates Media Library selection
responsive: Preview, metadata, variant cards, forms, long filenames and semantic identifiers wrap without page-level horizontal overflow at 390px.
out_of_scope: svg;additional-brand-roles;localized-media;site-media;market-media;publication;generic-dam;variant-retry;orphan-purge
reference_version: v2
---

# CA-014 — Brand Media / Identity

CA-014 is the mutation workspace for canonical Brand identity media. Phase 14 supports exactly one semantic role: `brand_logo`. The repository contract and approved executable design do not establish another Brand role, so wordmark, symbol, dark/light, hero, OG and similar speculative roles are not created.

## Canonical assignment

`CentralBrandMediaQuery` is the authoritative selector shared by CA-012, CA-014 and derived Brand Quality. The primary logo is the one `MediaAssignment` with:

- `entity_type = central_brand` and the target Brand ID;
- `role = brand_logo`;
- `locale`, `site_id` and `market_id` all null;
- `visibility = global`;
- `is_primary = true`.

The database primary-per-context uniqueness constraint prevents competing primary assignments for the same entity, role and context. Mutation Actions lock the Brand and existing context assignment, normalize the canonical assignment to position zero/global, and never rely on the UI to choose an arbitrary row.

## Workspace and states

The screen presents the current assignment, a real usable preview or an honest empty/unavailable state, safe master metadata, and the existing `brand_logo_128`, `brand_logo_256`, and `brand_logo_512` variants. Variants are read-only Shared Media outputs; their real status, dimensions and safe delivery action are shown without synthesizing missing variants or exposing storage paths.

Delivery distinguishes no assignment, ready, processing, failed, and unavailable. An assigned active asset is usable only when `BrandLogoPresenter` can resolve a ready semantic variant or its stored master. Missing storage, a non-active asset, or no deliverable master/variant produces an unavailable state rather than a broken `<img>`.

## Upload, replace, reuse and remove

Upload and replace pass the browser file through the shared `MediaService` secure ingest pipeline. Accepted input is JPEG, PNG or WebP, with a maximum 20 MiB, 8000 pixels per side, and 16 million pixels. Detection uses decoded content, not the client extension or MIME. GIF, SVG, AVIF, corrupt data and mismatched content are rejected. Shared Media has no approved SVG sanitizer, so SVG remains deferred.

Ingest completes before assignment mutation. A validation, decode, storage or persistence failure leaves the previous assignment and usable logo intact. Successful replace transactionally updates the canonical assignment and queues the existing Brand Logo variant profile after commit. Replacement and removal do not physically delete the prior/shared `MediaAsset`; lifecycle and orphan cleanup remain Shared Media responsibilities.

Actors who also have the existing `media.manage` permission receive a bounded, server-paginated selector for active, available compatible raster assets. It accepts only a semantic MediaAsset ID and revalidates type, status, MIME and delivery server-side. This is not a generic DAM, folder browser or bulk media workflow. Actors with only `catalog.brands.manage` retain upload/replace/remove access but do not see the Media Library selector.

Remove requires confirmation and deletes only the exact canonical assignment. Cancel clears dialog state without mutation. Removing a logo does not change Brand lifecycle and is permitted for Active Brands.

## Quality and audit

The screen links directly to Phase 13 derived quality semantics. No assignment yields `brand_logo_missing`; an assignment without usable delivery yields `brand_logo_unusable`; a successful repair removes the issue on the next CA-012 read; removal restores `brand_logo_missing`. There is no stored score recalculation.

Initial assignment and replace emit `catalog.brand.logo.assigned`; remove emits `catalog.brand.logo.removed`. Audit is in the mutation transaction, no-op assignment emits nothing, and failure rolls the mutation back. Payloads contain the semantic role and MediaAsset ID only—never paths, signed URLs, credentials or temporary upload metadata.

## Deferred

Localized/site/market assignments, Site publication or synchronization, additional Brand identity roles, external URL acquisition, SVG, generic Media Library/DAM expansion, manual variant editing/retry, and physical asset cleanup are outside CA-014 Phase 14.
