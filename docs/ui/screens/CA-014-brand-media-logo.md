---
screen_id: CA-014
context: central-admin
purpose: Manage the one canonical global primary Brand logo through Shared Media Core.
roles: authorized Central Admin catalog user
route: /admin/central/brands/{brand}/media (GET); /admin/central/brands/{brand}/media/logo (POST, DELETE); /admin/central/brands/{brand}/media/logo/assign (POST)
viewports: desktop=1440x1000,1280x900;intermediate=1024x900;tablet=768x1024;mobile=390x844
fixture: brand-media-v7
regions: central-shell;breadcrumbs;page-header;brand-tabs;primary-logo-workspace;logo-actions;asset-details;generated-variants;replacement-modal;bounded-shared-media-picker-modal;confirmation-modal;flash-feedback
actions: upload-logo;assign-existing-logo;remove-logo-from-brand;confirm;cancel
states: no-logo;ready;processing;failed;unavailable;validation-error
permissions: catalog.brands.manage; media.manage additionally gates Shared Media selection and MediaAsset destination
responsive: Primary logo and Asset details stay side by side at 1440 and 1280px. At 1024px and below they stack before metadata becomes narrow. Inside Primary logo the preview and compact action rail use a 2/3–1/3 row from 768px upward and stack on mobile. The on-demand bounded picker uses 4/4/3/3/1 columns at 1440/1280/1024/768/390 without horizontal overflow.
out_of_scope: svg;additional-brand-roles;dark-light;hero-og;localized-media;site-media;market-media;media-completeness;alt-text;edit-image;asset-deletion;generic-dam;variant-retry;orphan-purge
reference_version: final-convergence-v7
---

# CA-014 — Brand Media / Logo

CA-014 is the finished Brand logo workspace. Its primary task is deliberately narrow: inspect the current logo and, when necessary, replace it with a secure upload or a compatible existing `MediaAsset`. Upload and Shared Media selection stay out of the default reading flow until the user requests them. The approved Brand domain still contains exactly one media role: `brand_logo`.

## Canonical assignment

`CentralBrandMediaQuery` remains the authoritative selector shared by CA-012, CA-014, and derived Brand Quality. The logo is the one `MediaAssignment` with:

- `entity_type = central_brand` and the target Brand ID;
- `role = brand_logo`;
- null `locale`, `site_id`, and `market_id`;
- `visibility = global`;
- `is_primary = true`.

Position is a deterministic ordering field rather than a selector predicate. Assignment mutations normalize the canonical row to position zero, while reads select the exact global primary context and use position then ID only as a defensive tie-breaker.

The database uniqueness constraint and transactionally locked Actions prevent competing primaries. CA-014 adds no Brand columns, media tables, uploader, storage, assignment role, locale, Site, or market projection.

## Final workspace composition

The page header follows CA-012/013 with Brand breadcrumbs, `Brand Media`, the exact Brand-aware Shared Media subtitle, `View Brand`, and only Overview / Media / Translations navigation. The desktop workspace is a roughly 65/35 grid. Primary logo and the full-height Asset details rail share a top and bottom edge at wide widths. Inside Primary logo, a contained wordmark-safe neutral preview (`object-fit: contain`) occupies two thirds of the working row and a compact action rail occupies the remaining third at 768px and above. Compact Global, Primary, and real delivery-state badges live in the card header.

The action rail exposes `Replace logo`/`Upload logo` and `Choose from media`; neither mutation surface is rendered into the normal page flow. Replacement opens the established dialog with the atomic-replacement explanation, the real ingest constraints, a labelled file control, Cancel, and an explicit submit action. Choosing a file alone never mutates the assignment. `Remove logo from brand` is a quiet danger link on the row below the preview/actions pair and still requires confirmation. Asset details use a readable two-column metadata table for filename, MIME, dimensions, size, status, source, timestamps, public UUID, and `Brand · Primary logo`. Storage paths, object keys, buckets, signed-URL internals, and credentials are never rendered. `Open MediaAsset` uses the existing safe admin destination.

Generated variants is a compact read-only section inside Primary logo. Existing `brand_logo_128`, `brand_logo_256`, and `brand_logo_512` records form one compact desktop row. Each card keeps its status at the upper right, a maximum 64×56 contained preview at the lower right, and the safe `Open variant` action aligned with the preview's lower edge. Absence is a short `No generated variants yet` state. A usable normalized master remains Ready while asynchronous variants are absent or processing; variant absence never becomes logo failure.

At 1024px the outer workspace becomes a single column before the metadata table gets cramped; Primary logo still keeps its internal preview/action row. At 768px and mobile the order is Primary logo (including actions and variants), then Asset details. Mobile stacks preview and actions, keeps controls full-width, wraps long IDs, and has no horizontal overflow. Replacement, Shared Media selection, and destructive confirmation all reuse the established accessible modal runtime.

## Upload and atomic replacement

`Replace logo` (or `Upload logo` in the empty state) opens a compact dialog and posts to the existing `UploadCentralBrandLogoAction` only after explicit confirmation. The accepted contract remains JPEG, PNG, or WebP; maximum 20 MiB, 8000 pixels per side, and 16 megapixels. Detection is based on decoded content rather than filename or client MIME. SVG, GIF, AVIF, corrupt data, and mismatched content are not promised or accepted.

Shared `MediaService` ingest and secure validation complete before the canonical assignment changes. Any validation, decode, storage, persistence, or audit failure preserves the old assignment and reopens the usable replacement dialog with an associated error. A successful assignment is the only point at which success is reported. A post-commit variant-dispatch failure remains visible and is not described as a rolled-back assignment. Replacement retains the old `MediaAsset` and stored file.

## Shared Media reuse and query behavior

`Choose from media` is shown only to actors with the existing `media.manage` permission. It opens a dedicated Shared Media dialog and only then loads a stable server-paginated page of 24 compatible assets; a normal CA-014 GET does not query or render the candidate set. Search remains bounded to filename, checksum, and numeric asset ID, and pagination keeps the picker open.

Cards use the existing safe URL resolver and show only thumbnail, filename, MIME, dimensions, and `Use as logo`. The assigned asset is ordered onto the first page, has `aria-current=true`, a visible `Current` marker, and a disabled `Current logo` action. Server-side assignment revalidates raster type, active status, allowed MIME, and physical delivery. After success the picker closes and the refreshed preview, details, and variants reflect the new assignment; reopening the picker shows the new current marker. Candidate variants are eager-loaded in one relation query; the on-demand page does not introduce an N+1.

## Delivery and empty states

- Ready displays the best ready semantic variant, then the normalized master fallback.
- Processing states that the assignment remains while a usable file becomes available; it never claims Ready.
- Failed and Unavailable explain the real condition and leave Replace / Choose recovery actions visible.
- No logo uses `No primary logo assigned`, immediate Upload / Choose actions, a compact neutral Asset details state, and a compact Variants state.

The deterministic primary fixture is an Apple-like active Brand backed by a persisted transparent PNG `MediaAsset`, exact canonical assignment, real dimensions/size/source/timestamps, generated variants, and 23 additional compatible picker candidates. Together with the current asset this produces the bounded 24-card picker page when requested. Separate references cover empty, 1440, 1280, 1024, tablet, mobile, and the open picker dialog. No screenshot-only metadata is hardcoded in the view.

## Permissions, removal, and audit

Existing authorization is unchanged. Actors allowed to open the Brand workspace see safe current media and metadata. `catalog.brands.manage` continues to gate upload/replace/remove; `media.manage` additionally gates library selection and the generic MediaAsset destination. No new permission exists.

`Remove logo from brand` is a keyboard-accessible danger link below the preview/action row and requires the established confirmation modal. It deletes only the exact canonical `MediaAssignment`; the confirmation explicitly states that the Shared Media asset and files remain available. CA-014 never offers Brand-specific asset deletion. Assign/replace continue to emit `catalog.brand.logo.assigned`; removal emits `catalog.brand.logo.removed`; no-op assignment emits no audit record. Audit payloads contain semantic IDs and role only, never storage internals.

## Intentional prototype divergences

- Multiple media/logo roles: deferred; the Brand domain has only `brand_logo`.
- Dark / Light, wordmark, favicon, app icon, hero, OG, social, localized assets: not implemented.
- Media Completeness and required-role counts: not authoritative for the current Brand model.
- Site assignments: no `SiteBrand` or Brand-media projection domain exists.
- Alt text completeness/editor: not part of the CA-014 contract.
- Edit image and manual variant generation/retry: not part of the current workflow.
- Delete asset: CA-014 manages assignment, not Shared Media asset deletion.
- Generic library management, tagging, bulk actions, arbitrary role assignment, and asset cleanup remain outside this focused workspace.
