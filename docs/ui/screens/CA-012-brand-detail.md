---
screen_id: CA-012
context: central-admin
purpose: Inspect a canonical Brand, its current lifecycle state, basic metadata and usage.
roles: authorized Central Admin catalog user
route: /admin/central/brands/{brand} (GET); /admin/central/brands/{brand}/activate (POST); /admin/central/brands/{brand}/archive (POST); /admin/central/brands/{brand}/restore (POST)
viewports: desktop=1440x1000;mobile=390x844
fixture: brand-detail-v2
regions: central-shell;breadcrumbs;page-header;status-context;brand-tabs;general-information;online-presence;brand-identity;usage;record-metadata;lifecycle;confirmation-modal;flash-feedback
actions: edit-brand;activate-brand;archive-brand;restore-brand;confirm;cancel
states: draft;active;archived;status-action-error
permissions: catalog.brands.manage
responsive: Desktop uses the full Central Admin workspace with main content and a right aside; mobile stacks the detail-layout regions, keeps actions tappable, and prevents long slugs or URLs from causing page-level overflow.
out_of_scope: translation-editing;product-list;site-projections;audit-history;granular-brand-permissions;delete;hard-delete;soft-delete
reference_version: v2
---

# CA-012 — Brand Detail

## Contract

Brand Detail is the canonical read view for a Central Brand. Its header shows the escaped canonical name, textual lifecycle badge, short catalog description, and an Edit Brand link available in every lifecycle state. Breadcrumbs are `Central Admin → Brands → {Brand name}`, with the current Brand rendered as plain text.

The `x-admin.detail-layout` main column contains General Information, Online presence, Brand identity, and Usage cards. General Information renders Name, persisted Slug, Status, the application-locale Country name with alpha-2 code, and Founded as a four-digit year. Online presence renders Website, Support URL, and Contact email. Both URLs pass through `SafePresentationUrl`; accepted HTTP(S) values are external links with `target=_blank` and `noopener noreferrer`, while unsafe legacy values remain escaped plain text. Contact email is escaped plain text. Brand identity shows a swatch only for canonical `#RRGGBB` data and always shows the textual hex value. Null profile values use an em dash. Country reference translations are eagerly loaded with exact locale → base language → canonical English fallback. Usage remains a database count of Products and never loads or persists a Product collection/count on the Brand.

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

## Visual reference

The active desktop/mobile and archived desktop `CA-012` entries in `docs/ui/visual-references.json` use `brand-detail-v2`. Phase 10 expands the existing detail composition without a full-page redesign. Confirmation dialogs, feedback, and the complete lifecycle sequence are covered by browser acceptance rather than extra baselines.

## Explicit non-goals

No media or translation mutation, Product list/filtering, site projections, audit history, granular Brand permissions, deletion, hard deletion, soft deletion, or migration is introduced on CA-012.

## Brand logo and navigation

When a global primary `brand_logo` assignment exists, CA-012 presents a small contained logo in the header identity context. The Brand sub-navigation contains Overview and Media for catalog users and adds Translations only when the current user has `translations.manage`. Logo management remains on CA-014 and translation editing remains on CA-015; Overview mutates neither.
