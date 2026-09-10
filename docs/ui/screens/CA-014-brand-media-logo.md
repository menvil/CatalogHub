---
screen_id: CA-014
context: central-admin
purpose: Manage the one canonical global primary Brand logo through Shared Media Core.
roles: authorized Central Admin catalog user
route: /admin/central/brands/{brand}/media (GET); /admin/central/brands/{brand}/media/logo (POST, DELETE); /admin/central/brands/{brand}/media/logo/assign (POST)
viewports: desktop=1440x1000,1280;intermediate=1024;tablet=768x1024;mobile=390x844
fixture: brand-media-v6
regions: central-shell;breadcrumbs;page-header;brand-tabs;primary-logo-workspace;inline-upload;asset-details;generated-variants;bounded-shared-media-picker;confirmation-modal;flash-feedback
actions: upload-logo;assign-existing-logo;remove-logo-from-brand;confirm;cancel
states: no-logo;ready;processing;failed;unavailable;validation-error
permissions: catalog.brands.manage; media.manage additionally gates Shared Media selection and MediaAsset destination
responsive: Primary logo and Asset details stay side by side at 1440 and 1280px. At 1024px and below they stack before metadata becomes narrow. Inside Primary logo the preview and upload panel use a 1/3–2/3 row from 768px upward and stack on mobile. The bounded picker uses 8/8/4/4/1 columns at 1440/1280/1024/768/390 without horizontal overflow.
out_of_scope: svg;additional-brand-roles;dark-light;hero-og;localized-media;site-media;market-media;media-completeness;alt-text;edit-image;asset-deletion;generic-dam;variant-retry;orphan-purge
reference_version: final-convergence-v6
---

# CA-014 — Brand Media / Logo

CA-014 is the finished Brand logo workspace. Its primary task is deliberately narrow: inspect the current logo and, when necessary, replace it with a secure upload or a compatible existing `MediaAsset`. Shared Media selection remains a bounded supporting section rather than a separate DAM workflow. The approved Brand domain still contains exactly one media role: `brand_logo`.

## Canonical assignment

`CentralBrandMediaQuery` remains the authoritative selector shared by CA-012, CA-014, and derived Brand Quality. The logo is the one `MediaAssignment` with:

- `entity_type = central_brand` and the target Brand ID;
- `role = brand_logo`;
- null `locale`, `site_id`, and `market_id`;
- `visibility = global`;
- `is_primary = true` and position zero.

The database uniqueness constraint and transactionally locked Actions prevent competing primaries. CA-014 adds no Brand columns, media tables, uploader, storage, assignment role, locale, Site, or market projection.

## Final workspace composition

The page header follows CA-012/013 with Brand breadcrumbs, `Brand Media`, the exact Brand-aware Shared Media subtitle, `View Brand`, and only Overview / Media / Translations navigation. The desktop workspace is a roughly 65/35 grid. Primary logo and the full-height Asset details rail share a top and bottom edge at wide widths. Inside Primary logo, a contained wordmark-safe neutral preview (`object-fit: contain`) occupies one third of the working row and the inline upload panel occupies the remaining two thirds at 768px and above. Compact Global, Primary, and real delivery-state badges live in the card header.

The upload panel contains one large `Upload Photo` file control, the atomic-replacement explanation, and the real ingest constraints. Choosing a file submits directly; there is no replacement popup or duplicate submit step. `Remove logo from brand` is a quiet danger link on the row below the preview/upload pair and still requires confirmation. Asset details use a readable two-column metadata table for filename, MIME, dimensions, size, status, source, timestamps, public UUID, and `Brand · Primary logo`. Storage paths, object keys, buckets, signed-URL internals, and credentials are never rendered. `Open MediaAsset` uses the existing safe admin destination.

Generated variants is a compact read-only section inside Primary logo. Existing `brand_logo_128`, `brand_logo_256`, and `brand_logo_512` records form one compact desktop row. Each card keeps its status at the upper right, a maximum 64×56 contained preview at the lower right, and the safe `Open variant` action aligned with the preview's lower edge. Absence is a short `No generated variants yet` state. A usable normalized master remains Ready while asynchronous variants are absent or processing; variant absence never becomes logo failure.

At 1024px the outer workspace becomes a single column before the metadata table gets cramped; Primary logo still keeps its internal preview/upload row. At 768px and mobile the order is Primary logo (including upload and variants), Asset details, then Shared Media selection. Mobile stacks preview and upload, keeps controls full-width, wraps long IDs, and has no horizontal overflow. Only destructive confirmation uses the established modal runtime; upload and Shared Media selection are intentionally inline.

## Upload and atomic replacement

The inline `Upload Photo` control posts to the existing `UploadCentralBrandLogoAction` as soon as the user chooses a file. The accepted contract remains JPEG, PNG, or WebP; maximum 20 MiB, 8000 pixels per side, and 16 megapixels. Detection is based on decoded content rather than filename or client MIME. SVG, GIF, AVIF, corrupt data, and mismatched content are not promised or accepted.

Shared `MediaService` ingest and secure validation complete before the canonical assignment changes. Any validation, decode, storage, persistence, or audit failure preserves the old assignment and redisplays the inline upload panel with an associated error. A successful assignment is the only point at which success is reported. A post-commit variant-dispatch failure remains visible and is not described as a rolled-back assignment. Replacement retains the old `MediaAsset` and stored file.

## Shared Media reuse and query behavior

`Choose from Shared Media` is a full-width section below the main workspace for actors with the existing `media.manage` permission. It is always open by explicit product choice and runs stable server pagination at 24 compatible assets per page; it never loads the whole library. Search remains bounded to filename, checksum, and numeric asset ID, and pagination retains the section anchor.

Cards use the existing safe URL resolver and show only thumbnail, filename, MIME, dimensions, and `Use as logo`. The assigned asset is ordered onto the first page, has `aria-current=true`, a visible `Current` marker, and a disabled `Current logo` action. Server-side assignment revalidates raster type, active status, allowed MIME, and physical delivery. After success the refreshed preview, details, variants, and current marker all reflect the new assignment. Candidate variants are eager-loaded in one relation query; the page does not introduce an N+1.

## Delivery and empty states

- Ready displays the best ready semantic variant, then the normalized master fallback.
- Processing states that the assignment remains while a usable file becomes available; it never claims Ready.
- Failed and Unavailable explain the real condition and leave Replace / Choose recovery actions visible.
- No logo uses `No primary logo assigned`, the same inline upload control, a compact neutral Asset details state, a compact Variants state, and the bounded Shared Media section for authorized users.

The deterministic primary fixture is an Apple-like active Brand backed by a persisted transparent PNG `MediaAsset`, exact canonical assignment, real dimensions/size/source/timestamps, generated variants, and 23 additional compatible picker candidates. Together with the current asset this produces the bounded 24-card page. Separate references cover empty, 1440, 1280, 1024, tablet, mobile, and the anchored picker view. No screenshot-only metadata is hardcoded in the view.

## Permissions, removal, and audit

Existing authorization is unchanged. Actors allowed to open the Brand workspace see safe current media and metadata. `catalog.brands.manage` continues to gate upload/replace/remove; `media.manage` additionally gates library selection and the generic MediaAsset destination. No new permission exists.

`Remove logo from brand` is a keyboard-accessible danger link below the preview/upload row and requires the established confirmation modal. It deletes only the exact canonical `MediaAssignment`; the confirmation explicitly states that the Shared Media asset and files remain available. CA-014 never offers Brand-specific asset deletion. Assign/replace continue to emit `catalog.brand.logo.assigned`; removal emits `catalog.brand.logo.removed`; no-op assignment emits no audit record. Audit payloads contain semantic IDs and role only, never storage internals.

## Intentional prototype divergences

- Multiple media/logo roles: deferred; the Brand domain has only `brand_logo`.
- Dark / Light, wordmark, favicon, app icon, hero, OG, social, localized assets: not implemented.
- Media Completeness and required-role counts: not authoritative for the current Brand model.
- Site assignments: no `SiteBrand` or Brand-media projection domain exists.
- Alt text completeness/editor: not part of the CA-014 contract.
- Edit image and manual variant generation/retry: not part of the current workflow.
- Delete asset: CA-014 manages assignment, not Shared Media asset deletion.
- Generic library management, tagging, bulk actions, arbitrary role assignment, and asset cleanup remain outside this focused workspace.
