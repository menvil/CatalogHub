---
screen_id: CA-012
context: central-admin
purpose: Inspect a canonical Brand, its derived quality, lifecycle, classification, external provenance, metadata and usage.
roles: authorized Central Admin catalog user
route: /admin/central/brands/{brand} (GET); /admin/central/brands/{brand}/tags (PATCH); /admin/central/brands/{brand}/external-identities (POST); /admin/central/brands/{brand}/external-identities/{identity} (PATCH, DELETE); /admin/central/brands/{brand}/activate (POST); /admin/central/brands/{brand}/archive (POST); /admin/central/brands/{brand}/restore (POST)
viewports: desktop=1440x1000;tablet=768x1024;mobile=390x844
fixture: brand-detail-v9
regions: central-shell;breadcrumbs;page-header;status-context;brand-tabs;brand-identity;general-information;online-presence;parent-company;record-metadata;quality-completeness;translation-summary;usage;category-coverage;recent-products;quality-issues;classification;external-identities;tag-modal;external-identity-modal;confirmation-modal;flash-feedback
actions: edit-brand;edit-profile-issue;manage-logo-issue;edit-translation-issue;manage-tags;save-tags;cancel-tags;add-identity;edit-identity;remove-identity;activate-brand;archive-brand;restore-brand;confirm;cancel
states: complete;needs-attention;draft;active;archived;tag-empty;coverage-empty;provenance-empty;no-active-import-sources;inactive-source;validation-error;status-action-error
permissions: catalog.brands.manage;translations.manage for CA-015 navigation/issue/summary CTA
responsive: Wide desktop uses independent 68/32 main and operational stacks, a three-column Identity overview and a balanced Portfolio/Classification row; 1024 and 768 use a bounded single-column dashboard with a two-column internal overview; mobile uses a one-column overview and deliberately orders Overview, Health, Issues, Portfolio, Classification, Recent Products and External identities, keeps dialogs inside 390×844, and prevents page-level overflow.
out_of_scope: manual-brand-category-editing;field-level-provenance;source-management;translation-editing;brand-filtered-product-index;site-projections;audit-history;global-tag-management;granular-brand-permissions;delete;hard-delete;soft-delete
reference_version: v8-final
---

# CA-012 — Brand Detail

## Contract

Brand Detail is the final canonical overview for a Central Brand. Its header shows the escaped canonical name, slug context, lifecycle, derived Quality state, primary `Edit Brand`, and only the lifecycle actions valid for the current state. Breadcrumbs are `Central Admin → Brands → {Brand name}`, with the current Brand rendered as plain text. Overview, Media and permission-aware Translations remain navigation—not dead prototype buttons.

The 1440 composition follows the original CA-012 hierarchy without copying future domains. A strong Brand header keeps lifecycle and derived Quality visibly separate; Edit and current-state lifecycle controls share the page action area. The dominant `Identity and contact` overview has three internal columns: contained canonical logo with its directly associated Manage logo link; compact read-only Parent Company/Country/Founded/color/web/contact fields; and Brand summary with Product/Category/Translation metrics plus Created, Updated and Record ID. There is no technical `Brand profile` eyebrow and no separate Record card. Parent Company comes only from `CentralBrandOwnership.organization`; an absent relation says `No Parent Company`, and CA-012 offers no ownership mutation. URLs pass through `SafePresentationUrl`; accepted HTTP(S) values are external links with `target=_blank` and `noopener noreferrer`, while unsafe legacy values remain escaped plain text. Null profile values use an em dash. Country translations retain exact locale → base language → canonical English fallback.

Brand Health and Issues form the independent operational rail. Identity overview, the paired Product Portfolio/Classification row, Recent Products and External identities form the main stack. A tall card in either stack never pushes the next card in the other stack down. Below the wide-desktop breakpoint the wrappers use `display: contents` and explicit region ordering to produce the stable 1024/768/mobile sequence documented in the frontmatter. Usage remains a database count of current non-archived Products and never persists a Product count on the Brand.

## Product context

Product Portfolio shows only three honest snapshot values: current non-archived Product count, distinct derived Category count and active-Locale Translation coverage. It intentionally contains no Category chips. Classification exclusively owns the grouped derived Category chips/counts and the editorial Tag chips, so the same taxonomy is not repeated across the page and no Brand↔Category mutation is implied.

Recent Products is a separate bounded read of at most five Draft/Active Products for the current Brand, ordered by `updated_at DESC, id DESC`. It eager-loads Category in a fixed query count and never loads the complete Brand Product collection. Rows show realistic persisted name, model/slug fallback, primary Category, lifecycle and varied deterministic UTC update time. Product names link to the stable Product detail route only for actors with `catalog.products.manage`. There is no thumbnail because the current media API has only a single-Product management read and no approved batch Product thumbnail presenter; storage is never read directly. There is no `View all products` CTA or Products tab because the current Product index does not expose an approved URL-stable Brand filter.

## Quality / Completeness

The Quality / Completeness card renders the authoritative derived `CentralBrandQualitySummary`: Complete or Needs attention, integer percentage, completed/total checks, and each unresolved issue. Every applicable check is equally weighted. The six base checks are country, website, founded year, support URL or contact email, primary color, and a usable exact global primary `brand_logo`. Every active Locale adds one translation check; inactive Locales do not affect the denominator. The exact formula and issue codes are documented in `docs/architecture/brand-quality.md`.

Each issue carries readable label/copy and an existing editor destination: canonical profile → CA-013, logo → CA-014, and selected active Locale → CA-015. The CTA is rendered only when the current actor has its existing permission. In particular, a catalog editor without `translations.manage` still sees missing/outdated translation issues but receives no CA-015 mutation link. Overview itself remains read-only: evaluation creates no Translation or MediaAssignment, writes no Brand/status/timestamp, dispatches no job, and records no audit event.

The bounded read model loads all active Locales, matching Brand translations, and the exact logo assignment/asset/variants without a query per Locale. Underlying profile, logo, or translation changes are reflected on the next request; no stored quality state or recalculation action exists. Quality does not block Activate, Archive, or Restore and contains no Site publication semantics.

The same already-bounded locale read produces a read-only translation summary: total active locales and counts for Approved, Human reviewed, Machine translated, Missing and Outdated. Absent rows count as Missing, and the completion percentage counts the three current states accepted by Brand Quality. No persisted summary or additional per-locale query is introduced. Authorized users can follow `Review translations`; mutation remains exclusively on CA-015.

## Classification

Classification groups two intentionally different concepts. Tags are explicit editorial state, displayed as neutral wrapping chips. `Manage tags` opens the shared modal with generic `x-ui.form.tag-input`: Enter/Add creates a chip, keyboard-operable `Remove {Tag}` controls remove one, hidden `tags[]` values submit, and Save is immediate explicit intent. Cancel or Escape closes without a native confirmation and restores the persisted reset snapshot, discarding unsaved chips, unfinished text, and client validation errors. Client normalization suppresses obvious duplicates and reports the 20-Tag limit; the server remains authoritative. PATCH success redirects to the same Detail `#classification` anchor with `Brand tags updated.`. Validation redirects back with old values, an immediately open dialog, and visible error; its reset snapshot remains the persisted Brand tags. Draft, Active, and Archived Brands are all manageable under `catalog.brands.manage`.

Current Category coverage is read-only and derived automatically from direct Category assignments of current Brand Products. A single grouped query includes Draft and Active Products, excludes Archived Products, counts exact Category assignments, and sorts count descending/name/ID. Classification shows the bounded Category chips with counts and offers no add/remove/checkbox/category assignment control. Tag empty copy is `No tags have been assigned to this Brand.`; coverage empty copy is `No category coverage.`.

## Provenance / External identities

External identities connect the canonical Brand to source-side records in configured `ImportSource` namespaces. The bounded eager-loaded list sorts by source name, source code, and opaque external ID. Each row shows the source name/code, active or inactive status, the external ID, and a safely presented `Open record` HTTP(S) link when one exists. Neither source configuration nor credentials are selected for the view or rendered. Existing inactive-source links remain visible and editable; only active sources are available for a new link.

Authorized Brand managers can add, edit, or unlink an identity. Add accepts an active Source, required external ID, and optional external record URL. Edit keeps Source read-only because Source and external ID jointly define the namespace. Remove uses the shared confirmation modal and makes clear that only the linkage is deleted. Cancel, Escape, and modal backdrop close restore persisted/default controls, clear unfinished edits, and clear client error state through the generic modal lifecycle. Server validation reopens only the relevant add/edit dialog with submitted values and visible errors; a later Cancel restores persisted state. Nested scoped binding and action ownership checks prevent cross-Brand mutation. Success returns to `#external-identities`.

With no links, the card says `No external identities are linked to this Brand.`. If active sources exist it offers Add identity; otherwise it also explains `No active import sources are available.` and does not offer source creation. Source management, automatic canonical updates, matching confidence, observation history, and field-level lineage are absent.

The overview summary shows deterministic absolute Created/Updated UTC timestamps and Record ID alongside its three read metrics; there is no separate Record block. Lifecycle is represented once by the header badge and the valid current-state intents beside Edit Brand. Mutations continue to use explicit CSRF POST forms and `x-admin.confirmation-modal`:

- Draft: Activate Brand and Archive Brand.
- Active: Archive Brand.
- Archived: Restore Brand, with copy explaining that restore returns to Draft and activation remains separate.

Activate, Archive, and Restore delegate to `ActivateCentralBrandAction`, `ArchiveCentralBrandAction`, and `RestoreCentralBrandAction`. Successful commands redirect to the same detail route with the agreed flash message. A stale or malicious invalid transition preserves the action's `status` validation error, redirects to Detail, leaves persisted state unchanged, and presents the error directly below the page header.

The dedicated `catalog.brands.manage` permission protects canonical Brand reads and lifecycle commands. There is no generic status endpoint or status payload.

## States

- `draft`: lifecycle badge plus Activate and Archive header confirmations.
- `active`: lifecycle badge plus destructive Archive header confirmation.
- `archived`: historical canonical data remains visible; Restore confirmation explicitly describes Archived → Draft.
- `status-action-error`: the page-level lifecycle alert shows the status validation error returned by the Phase 2 action.
- `tag-empty`: explanatory copy and Manage tags remain visible.
- `coverage-empty`: explanatory derived copy appears without an assignment CTA.
- `tag-validation-error`: the editor reopens with old chip input and an associated error.
- `provenance-empty`: explanatory copy appears and Add identity is available only when an active source exists.
- `inactive-source`: the existing link remains visible, editable, and removable with an Inactive badge.
- `validation-error`: only the submitted external-identity dialog reopens with old values and associated errors; Cancel restores persisted values.
- `complete`: 100%, no unresolved issues, and explicit all-checks-complete copy.
- `needs-attention`: the derived score and concrete unresolved profile/media/translation issues are visible with authorized editor CTAs.

## Visual reference

The active desktop/tablet/mobile and archived-complete desktop `CA-012` entries in `docs/ui/visual-references.json` use `brand-detail-v9`. Active Samsung is the primary rich deterministic Needs attention state: usable canonical logo, Samsung Electronics Co., Ltd. ownership, Country/founded/website/color, five persisted editorial Tags, two real External identities, eight current realistically named Products across five derived Categories, and four active Locales split into Approved, Human reviewed, Machine translated and Outdated. Support/contact is intentionally absent to exercise the existing combined profile check. Quality therefore derives as 80% (8/10), with exactly one Profile issue and one exact-locale outdated Translation issue. Archived Sony remains the secondary deterministic fully populated, Organization-owned 100% Complete/no-issues state with usable logo and current translations for every active Locale. References were accepted only after side-by-side review against the immutable prototype, the pre-v9 Phase 18.2 result and the final three-column overview composition.

## Explicit non-goals

No source CRUD/configuration, field-level provenance, source observation history, inline media/translation/ownership mutation, Brand-filtered Product index, Site publication/projections, audit history, granular Brand permissions, Brand deletion, or soft deletion is introduced on CA-012. Published and Synced are not lifecycle states. Sites/Versions tabs and Site Coverage remain deferred. Hero, wordmark, symbol, dark/light, OG and localized/site media remain unsupported rather than appearing as false placeholders. Rating, Price Coverage and SEO Performance are absent without authoritative Brand-level sources. There is no standalone quality workflow, persisted score, bulk quality management, or CA-011 redesign.

## Brand logo and navigation

When a global primary `brand_logo` assignment exists, CA-012 presents it prominently but contained inside the identity surface; missing or unavailable media remains honest. The Brand sub-navigation contains Overview and Media for catalog users and adds Translations only when the current user has `translations.manage`. Logo management remains on CA-014 and translation editing remains on CA-015; Overview mutates neither.
