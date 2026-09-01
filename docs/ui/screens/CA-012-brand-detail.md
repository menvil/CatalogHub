---
screen_id: CA-012
context: central-admin
purpose: Inspect a canonical Brand, its derived quality, lifecycle, classification, external provenance, metadata and usage.
roles: authorized Central Admin catalog user
route: /admin/central/brands/{brand} (GET); /admin/central/brands/{brand}/tags (PATCH); /admin/central/brands/{brand}/external-identities (POST); /admin/central/brands/{brand}/external-identities/{identity} (PATCH, DELETE); /admin/central/brands/{brand}/activate (POST); /admin/central/brands/{brand}/archive (POST); /admin/central/brands/{brand}/restore (POST)
viewports: desktop=1440x1000;mobile=390x844
fixture: brand-detail-v6
regions: central-shell;breadcrumbs;page-header;status-context;brand-tabs;brand-identity;general-information;parent-company;quality-completeness;translation-summary;external-identities;usage;category-coverage;classification;tags;quality-issues;record-metadata;lifecycle;tag-modal;external-identity-modal;confirmation-modal;flash-feedback
actions: edit-brand;edit-profile-issue;manage-logo-issue;edit-translation-issue;manage-tags;save-tags;cancel-tags;add-identity;edit-identity;remove-identity;activate-brand;archive-brand;restore-brand;confirm;cancel
states: complete;needs-attention;draft;active;archived;tag-empty;coverage-empty;provenance-empty;no-active-import-sources;inactive-source;validation-error;status-action-error
permissions: catalog.brands.manage;translations.manage for CA-015 navigation/issue/summary CTA
responsive: Desktop uses an identity-first 8/4 dashboard grid across the full Central Admin workspace; mobile stacks identity, health, lifecycle and supporting context in reading order, keeps dialogs inside 390×844, wraps long Organization/external values, bounds coverage content, and prevents page-level overflow.
out_of_scope: manual-brand-category-editing;field-level-provenance;source-management;translation-editing;product-list;site-projections;audit-history;global-tag-management;granular-brand-permissions;delete;hard-delete;soft-delete
reference_version: v6-final
---

# CA-012 — Brand Detail

## Contract

Brand Detail is the final canonical overview for a Central Brand. Its header shows the escaped canonical name, slug context, lifecycle, derived Quality state, and one primary `Edit Brand` action. Breadcrumbs are `Central Admin → Brands → {Brand name}`, with the current Brand rendered as plain text. Overview, Media and permission-aware Translations remain navigation—not dead prototype buttons.

The 1440 composition follows the original CA-012 hierarchy without copying future domains. A dominant identity/profile surface contains the authoritative global primary logo, canonical name/slug, read-only Parent Company, Country, Founded, Website, Support URL, Contact email and Primary color. Parent Company comes only from `CentralBrandOwnership.organization`; an absent relation says `No Parent Company`, and CA-012 offers no ownership mutation. URLs pass through `SafePresentationUrl`; accepted HTTP(S) values are external links with `target=_blank` and `noopener noreferrer`, while unsafe legacy values remain escaped plain text. Null profile values use an em dash. Country translations retain exact locale → base language → canonical English fallback.

Brand health and lifecycle form the compact right rail. Product portfolio, derived Category coverage, editorial Tags, external identities, actionable Quality issues and secondary record metadata then use the desktop width as a dashboard rather than a vertical sequence of equally weighted generic cards. At 390×844 the regions stack in reading order and all opaque values wrap. Usage remains a database count of Products and never loads or persists a Product collection/count on the Brand.

## Quality / Completeness

The Quality / Completeness card renders the authoritative derived `CentralBrandQualitySummary`: Complete or Needs attention, integer percentage, completed/total checks, and each unresolved issue. Every applicable check is equally weighted. The six base checks are country, website, founded year, support URL or contact email, primary color, and a usable exact global primary `brand_logo`. Every active Locale adds one translation check; inactive Locales do not affect the denominator. The exact formula and issue codes are documented in `docs/architecture/brand-quality.md`.

Each issue carries readable label/copy and an existing editor destination: canonical profile → CA-013, logo → CA-014, and selected active Locale → CA-015. The CTA is rendered only when the current actor has its existing permission. In particular, a catalog editor without `translations.manage` still sees missing/outdated translation issues but receives no CA-015 mutation link. Overview itself remains read-only: evaluation creates no Translation or MediaAssignment, writes no Brand/status/timestamp, dispatches no job, and records no audit event.

The bounded read model loads all active Locales, matching Brand translations, and the exact logo assignment/asset/variants without a query per Locale. Underlying profile, logo, or translation changes are reflected on the next request; no stored quality state or recalculation action exists. Quality does not block Activate, Archive, or Restore and contains no Site publication semantics.

The same already-bounded locale read produces a read-only translation summary: total active locales and counts for Approved, Human reviewed, Machine translated, Missing and Outdated. Absent rows count as Missing, and the completion percentage counts the three current states accepted by Brand Quality. No persisted summary or additional per-locale query is introduced. Authorized users can follow `Review translations`; mutation remains exclusively on CA-015.

## Classification

Classification groups two intentionally different concepts. Tags are explicit editorial state, displayed as neutral wrapping chips. `Manage tags` opens the shared modal with generic `x-ui.form.tag-input`: Enter/Add creates a chip, keyboard-operable `Remove {Tag}` controls remove one, hidden `tags[]` values submit, and Save is immediate explicit intent. Cancel or Escape closes without a native confirmation and restores the persisted reset snapshot, discarding unsaved chips, unfinished text, and client validation errors. Client normalization suppresses obvious duplicates and reports the 20-Tag limit; the server remains authoritative. PATCH success redirects to the same Detail `#classification` anchor with `Brand tags updated.`. Validation redirects back with old values, an immediately open dialog, and visible error; its reset snapshot remains the persisted Brand tags. Draft, Active, and Archived Brands are all manageable under `catalog.brands.manage`.

Current Category coverage is read-only and states that it is derived automatically from direct Category assignments of current Brand Products. A single grouped query includes Draft and Active Products, excludes Archived Products, preserves the referenced Category status badge, counts exact Category assignments, and sorts count descending/name/ID. It offers no add/remove/checkbox/category assignment control. Tag empty copy is `No tags have been assigned to this Brand.`; coverage empty copy is `No category coverage yet.` plus the derived explanation.

## External identities

External identities connect the canonical Brand to source-side records in configured `ImportSource` namespaces. The bounded eager-loaded list sorts by source name, source code, and opaque external ID. Each row shows the source name/code, active or inactive status, the external ID, and a safely presented `Open record` HTTP(S) link when one exists. Neither source configuration nor credentials are selected for the view or rendered. Existing inactive-source links remain visible and editable; only active sources are available for a new link.

Authorized Brand managers can add, edit, or unlink an identity. Add accepts an active Source, required external ID, and optional external record URL. Edit keeps Source read-only because Source and external ID jointly define the namespace. Remove uses the shared confirmation modal and makes clear that only the linkage is deleted. Cancel, Escape, and modal backdrop close restore persisted/default controls, clear unfinished edits, and clear client error state through the generic modal lifecycle. Server validation reopens only the relevant add/edit dialog with submitted values and visible errors; a later Cancel restores persisted state. Nested scoped binding and action ownership checks prevent cross-Brand mutation. Success returns to `#external-identities`.

With no links, the card says `No external identities are linked to this Brand.`. If active sources exist it offers Add identity; otherwise it also explains `No active import sources are available.` and does not offer source creation. Source management, automatic canonical updates, matching confidence, observation history, and field-level lineage are absent.

The aside contains Record and Lifecycle cards. Record shows Status, deterministic absolute Created/Updated UTC timestamps, and Record ID. Lifecycle exposes only valid current-state intents through explicit CSRF POST forms and `x-admin.confirmation-modal`:

- Draft: Activate Brand and Archive Brand.
- Active: Archive Brand.
- Archived: Restore Brand, with copy explaining that restore returns to Draft and activation remains separate.

Activate, Archive, and Restore delegate to `ActivateCentralBrandAction`, `ArchiveCentralBrandAction`, and `RestoreCentralBrandAction`. Successful commands redirect to the same detail route with the agreed flash message. A stale or malicious invalid transition preserves the action's `status` validation error, redirects to Detail, leaves persisted state unchanged, and presents the error inside Lifecycle.

The dedicated `catalog.brands.manage` permission protects canonical Brand reads and lifecycle commands. There is no generic status endpoint or status payload.

## States

- `draft`: Draft description, Activate and Archive confirmations.
- `active`: Active description and destructive Archive confirmation.
- `archived`: historical canonical data remains visible; Restore confirmation explicitly describes Archived → Draft.
- `status-action-error`: the Lifecycle card shows the status validation error returned by the Phase 2 action.
- `tag-empty`: explanatory copy and Manage tags remain visible.
- `coverage-empty`: explanatory derived copy appears without an assignment CTA.
- `tag-validation-error`: the editor reopens with old chip input and an associated error.
- `provenance-empty`: explanatory copy appears and Add identity is available only when an active source exists.
- `inactive-source`: the existing link remains visible, editable, and removable with an Inactive badge.
- `validation-error`: only the submitted external-identity dialog reopens with old values and associated errors; Cancel restores persisted values.
- `complete`: 100%, no unresolved issues, and explicit all-checks-complete copy.
- `needs-attention`: the derived score and concrete unresolved profile/media/translation issues are visible with authorized editor CTAs.

## Visual reference

The active desktop/mobile and archived desktop `CA-012` entries in `docs/ui/visual-references.json` use `brand-detail-v6`. Active Samsung is the deterministic populated Needs attention state and now has a real Phase 16 ownership relation to Samsung Electronics Co., Ltd.; its canonical profile is complete while its global primary logo and active-locale translations are missing. Archived Sony is the deterministic Complete state with no Parent Company, a full canonical profile, usable Shared Media logo master, and current translations for every active Locale. Samsung derives Smartphones 24, Televisions 12, and Tablets 6 from real Products while archived-only Laptops remains absent; it assigns deterministic Tags through real storage and renders actual active/inactive ImportSource identity rows. These final Phase 17 references were accepted only after side-by-side review against the immutable CA-012 prototype. Persisted repair journeys, confirmation dialogs, feedback, and full mutation sequences remain browser acceptance rather than extra baselines.

## Explicit non-goals

No source CRUD/configuration, field-level provenance, source observation history, inline media/translation/ownership mutation, Product list/filtering, Site publication/projections, audit history, granular Brand permissions, Brand deletion, or soft deletion is introduced on CA-012. Published and Synced are not lifecycle states. Hero, wordmark, symbol, dark/light, OG and localized/site media remain unsupported rather than appearing as false placeholders. There is no standalone quality workflow, persisted score, bulk quality management, or CA-011 redesign.

## Brand logo and navigation

When a global primary `brand_logo` assignment exists, CA-012 presents it prominently but contained inside the identity surface; missing or unavailable media remains honest. The Brand sub-navigation contains Overview and Media for catalog users and adds Translations only when the current user has `translations.manage`. Logo management remains on CA-014 and translation editing remains on CA-015; Overview mutates neither.
