---
screen_id: CA-014
context: central-admin
purpose: Manage the one canonical global primary Brand logo through Shared Media Core.
roles: authorized Central Admin catalog user
route: /admin/central/brands/{brand}/media (GET); /admin/central/brands/{brand}/media/logo (POST, DELETE); /admin/central/brands/{brand}/media/logo/assign (POST)
viewports: desktop=1440x1000,1280;intermediate=1024;tablet=768x1024;mobile=390x844
fixture: brand-media-v4
regions: central-shell;breadcrumbs;page-header;brand-tabs;primary-logo-workspace;asset-details;generated-variants;replacement-modal;bounded-shared-media-picker;confirmation-modal;flash-feedback
actions: upload-logo;replace-logo;choose-shared-media;assign-existing-logo;remove-logo-from-brand;confirm;cancel
states: no-logo;ready;processing;failed;unavailable;validation-error;picker-open
permissions: catalog.brands.manage; media.manage additionally gates Shared Media selection and MediaAsset destination
responsive: The unified Primary logo/Asset details workspace stays side by side at 1440, 1280, and 1024px, then becomes one column at tablet width. Mobile order is Primary logo, actions, Asset details, then Generated variants. The picker remains a separate modal with 4/3/2/1-column results and no page-level overflow.
out_of_scope: svg;additional-brand-roles;dark-light;hero-og;localized-media;site-media;market-media;media-completeness;alt-text;edit-image;asset-deletion;generic-dam;variant-retry;orphan-purge
reference_version: final-convergence-v4
---

# CA-014 — Brand Media / Logo

CA-014 is the finished Brand logo workspace. Its primary task is deliberately narrow: inspect the current logo and, when necessary, replace it with a secure upload or a compatible existing `MediaAsset`. The screen is no longer presented as an always-open Shared Media warehouse. The approved Brand domain still contains exactly one media role: `brand_logo`.

## Canonical assignment

`CentralBrandMediaQuery` remains the authoritative selector shared by CA-012, CA-014, and derived Brand Quality. The logo is the one `MediaAssignment` with:

- `entity_type = central_brand` and the target Brand ID;
- `role = brand_logo`;
- null `locale`, `site_id`, and `market_id`;
- `visibility = global`;
- `is_primary = true` and position zero.

The database uniqueness constraint and transactionally locked Actions prevent competing primaries. CA-014 adds no Brand columns, media tables, uploader, storage, assignment role, locale, Site, or market projection.

## Final workspace composition

The page header follows CA-012/013 with Brand breadcrumbs, `Brand Media`, the exact Brand-aware Shared Media subtitle, `View Brand`, and only Overview / Media / Translations navigation. One Primary logo workspace contains a roughly 70/30 desktop composition: the preview and actions on the left, compact Asset details aligned at the same top edge on the right. The preview is a 256px contained, wordmark-safe neutral stage (`object-fit: contain`) rather than a cropped or viewport-filling media canvas. Compact Global, Primary, and real delivery-state badges live in the card header.

The action row contains the primary `Replace logo`, secondary `Choose from media`, and a compact overflow menu. Its user-facing danger action is `Remove logo from brand`; it is deliberately outside the primary visual flow. The upload form and asset library are absent from the default page flow. Asset details show only filename, MIME, dimensions, size, status, source, timestamps, public UUID, and the readable `Brand · Primary logo` assignment. Storage paths, object keys, buckets, signed-URL internals, and credentials are never rendered. `Open MediaAsset` uses the existing safe admin destination.

Generated variants is a compact read-only section immediately below the unified workspace. Existing `brand_logo_128`, `brand_logo_256`, and `brand_logo_512` records form one compact desktop row and show purpose, dimensions, format, state, and a safe open link when deliverable. Absence is a short `No generated variants yet` state. A usable normalized master remains Ready while asynchronous variants are absent or processing; variant absence never becomes logo failure.

At 1024px the workspace retains its two-column geometry; at 768px Asset details moves below the preview and actions. At mobile width the order is header, tabs, Primary logo, actions, Asset details, then Generated variants. Dialogs are viewport-bounded, scroll internally, trap focus through the established modal runtime, close with Escape, and return focus to their trigger.

## Upload and atomic replacement

`Replace logo` (or `Upload logo` in the empty state) opens the repository modal pattern around the existing `UploadCentralBrandLogoAction`. The accepted contract remains JPEG, PNG, or WebP; maximum 20 MiB, 8000 pixels per side, and 16 megapixels. Detection is based on decoded content rather than filename or client MIME. SVG, GIF, AVIF, corrupt data, and mismatched content are not promised or accepted.

Shared `MediaService` ingest and secure validation complete before the canonical assignment changes. Any validation, decode, storage, persistence, or audit failure preserves the old assignment and keeps the replacement modal usable with an associated inline error. A successful assignment is the only point at which success is reported. A post-commit variant-dispatch failure remains visible and is not described as a rolled-back assignment. Replacement retains the old `MediaAsset` and stored file.

## Shared Media reuse and query behavior

`Choose from media` is available only under the existing `media.manage` permission and opens the `Choose from Shared Media` picker. The default CA-014 request does not execute the library query or serialize candidate cards. The explicit `picker=1` request lazily opens a wide, focus-managed modal and runs the existing stable server pagination at six compatible assets per page. Search remains bounded to filename, checksum, and numeric asset ID.

Cards use the existing safe URL resolver and show only thumbnail, filename, MIME, dimensions, and `Use as logo`. The assigned asset has `aria-current=true`, a visible `Current` marker, and a disabled `Current logo` action. Server-side assignment revalidates raster type, active status, allowed MIME, and physical delivery. After success the redirect drops picker state, returning to the compact workspace with updated preview, details, and variants. Candidate variants are eager-loaded in one relation query; the page does not introduce an N+1.

## Delivery and empty states

- Ready displays the best ready semantic variant, then the normalized master fallback.
- Processing states that the assignment remains while a usable file becomes available; it never claims Ready.
- Failed and Unavailable explain the real condition and leave Replace / Choose recovery actions visible.
- No logo uses `No primary logo assigned`, immediate Upload and Choose actions, a compact neutral Asset details state, and a compact Variants state.

The deterministic primary fixture is an Apple-like active Brand backed by a persisted transparent PNG `MediaAsset`, exact canonical assignment, real dimensions/size/source/timestamps, generated variants, and five compatible picker candidates. Separate coverage records the empty state, picker-open state, tablet, and mobile layouts. No screenshot-only metadata is hardcoded in the view.

## Permissions, removal, and audit

Existing authorization is unchanged. Actors allowed to open the Brand workspace see safe current media and metadata. `catalog.brands.manage` continues to gate upload/replace/remove; `media.manage` additionally gates library selection and the generic MediaAsset destination. No new permission exists.

`Remove logo from brand` is reached through the established keyboard-accessible overflow and requires the established confirmation modal. It deletes only the exact canonical `MediaAssignment`; the confirmation explicitly states that the Shared Media asset and files remain available. CA-014 never offers Brand-specific asset deletion. Assign/replace continue to emit `catalog.brand.logo.assigned`; removal emits `catalog.brand.logo.removed`; no-op assignment emits no audit record. Audit payloads contain semantic IDs and role only, never storage internals.

## Intentional prototype divergences

- Multiple media/logo roles: deferred; the Brand domain has only `brand_logo`.
- Dark / Light, wordmark, favicon, app icon, hero, OG, social, localized assets: not implemented.
- Media Completeness and required-role counts: not authoritative for the current Brand model.
- Site assignments: no `SiteBrand` or Brand-media projection domain exists.
- Alt text completeness/editor: not part of the CA-014 contract.
- Edit image and manual variant generation/retry: not part of the current workflow.
- Delete asset: CA-014 manages assignment, not Shared Media asset deletion.
- Generic library management, tagging, bulk actions, arbitrary role assignment, and asset cleanup remain outside this focused workspace.
