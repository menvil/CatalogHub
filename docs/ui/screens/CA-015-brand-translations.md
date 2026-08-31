---
screen_id: CA-015
context: central-admin
purpose: Manage the localized text and row-level translation workflow for one canonical Brand.
roles: translation manager
route: /admin/central/brands/{brand}/translations (GET); /admin/central/brands/{brand}/translations/{locale-code} (GET, POST); /approve (POST); /outdated (POST)
viewports: desktop=1440x1000;mobile=390x844
fixture: brand-translations-v2
regions: central-shell;breadcrumbs;page-header;brand-tabs;locale-selector;workflow-actions;source-context;target-form;approval-metadata;recent-activity;flash-feedback
actions: select-locale;copy-from-source;save-translation;approve-translation;mark-outdated
states: no-active-locales;missing;machine-translated;human-reviewed;approved;outdated;rtl;validation-error;empty-activity;populated-activity
permissions: translations.manage
responsive: Source, Target, and workflow/activity columns stack without page-level overflow at 390px; locale cards scroll within their bounded region.
out_of_scope: machine-provider;translation-memory;glossary;persisted-field-statuses;localized-media;public-localization;site-publication;fallback;translated-slug
reference_version: v2
---

# CA-015 — Brand Translations v2

CA-015 is the manual translation workspace for one canonical Brand. The index route selects the default active Locale first, then the first active Locale by position and code. The selector queries all active Locales and their Brand rows in bulk, shows `Missing`, `Machine translated`, `Human reviewed`, `Approved`, or `Outdated`, and never creates a row on GET. With no active Locales, the screen renders an empty state. An inactive Locale—including one with a retained historical row—is absent from the selector and rejected by direct read and mutation routes.

## Source and Target semantics

The Source column follows the existing common `TranslationSourceHashService::forBrand()` contract. That hash contains normalized canonical `CentralBrand.name` and `CentralBrand.slug`; there is no persisted source-Locale entity and CA-015 does not invent one. Canonical name is therefore the only real source value with a corresponding localized target (`BrandTranslation.name`). Canonical slug is shown only to explain source-hash context because translated slug is out of scope. Tagline, descriptions, and SEO copy have no canonical Brand equivalent and are explicitly presented without fabricated source copy.

The Target column edits only `BrandTranslation`: required localized name plus optional tagline, short description, description, SEO title, and SEO description. Target controls use the selected Locale's direction. An RTL target does not change breadcrumbs, navigation, actions, or the Central Admin shell to RTL. Field cues such as required, optional, or missing are derived presentation; workflow authority remains the row-level status.

`Copy from Source` exists only for localized name. It is an explicit client-side form convenience: a non-empty different target requires overwrite confirmation, Copy performs no HTTP request, changes no status, writes no audit event, and persists nothing until Save.

## Save workflow and statuses

The authoritative shared `TranslationStatus` values remain `Missing`, `MachineTranslated`, `HumanReviewed`, `Approved`, and `Outdated`. No Brand-only status exists. `Published` and `Synced` remain future Site/projection concerns.

Save is a separate server-side mutation guarded by `translations.manage`. The FormRequest trims strings and normalizes blank optional values to `null`; Brand, Locale identity/code, source hash, actor, and approval metadata come only from route and server context. The Action locks the canonical Brand and exact Brand/Locale row in a transaction, creates or updates one row, refreshes its current source hash, and records only meaningful changes. A true no-op does not touch timestamps, invalidate cache, or emit audit noise. Posting `Approved` cannot approve a new or unapproved row.

Editing localized content or refreshing changed source context on an approved row invalidates approval through the common workflow rule: the result becomes `HumanReviewed` and both approval fields are cleared. An unchanged approved Save preserves its status and attribution.

## Explicit approval and Outdated

`Approve translation` is distinct from Save. It requires an existing `HumanReviewed` row whose stored source hash matches the currently locked canonical Brand. The Brand action delegates the transition to the shared approval Action, while the server supplies the approver and timestamp. An already Approved row is a no-op. Missing, Machine Translated, Outdated, stale-source, wrong Brand, or wrong Locale rows cannot be approved. Approval changes neither Brand lifecycle nor canonical content.

`Mark outdated` is also a separate mutation. It retains every localized field, changes the shared row status to `Outdated`, clears stale approval attribution, and leaves canonical Brand data untouched. Repeating it for an Outdated row is a no-op. Manual marking coexists with automatic hash comparison: the existing detector still marks stored rows when canonical name or slug changes and clears approval metadata. No persisted outdated-reason subsystem is introduced; the UI distinguishes an explicit Outdated row with a matching hash from a row whose stored hash differs.

## Activity, Audit, Quality, and permissions

Recent activity is a newest-first, deterministic, locale-specific query over the existing append-only Audit log, bounded to eight events and eager-loading actors. It includes meaningful translation save/create, explicit approval, and explicit Mark Outdated events. The UI renders the semantic action, actor, UTC time, status/changed field names already available, and never raw localized copy. There is no parallel translation-history table or stored content snapshot.

Save, Approve, and Mark Outdated record minimized Brand-subject audit payloads containing translation ID, Locale code, old/new status, and changed field names. Audit recording is inside each mutation transaction, so failure rolls back the row. No-op actions emit nothing.

Phase 13 remains the only Brand Quality evaluator. For every active Locale, absent/`Missing` and `Outdated` are incomplete; `MachineTranslated`, `HumanReviewed`, and `Approved` are complete. CA-012 links an affected issue directly to this Brand and Locale. Reads immediately reflect saves and status transitions; CA-015 runs no score job and persists no quality state. Inactive Locales are neither editable here nor counted by Quality, and deactivation does not delete existing rows.

All reads and mutations use the existing `translations.manage` boundary, including explicit approval because the common subsystem has no separate approval permission. A translation specialist does not need `catalog.brands.manage`; Brand Overview/Media tabs and the Brand breadcrumb remain authorization-aware.

## Deferred capabilities

CA-015 does not provide an AI or machine-translation provider, automatic translation jobs, translation memory, glossary management, persisted per-field review status, localized media, generic/bulk Brand translation management, public fallback/routing, translated slugs, Site publication/overrides/sync, or localized SEO delivery. `MachineTranslated` remains a supported common domain state without a provider workflow.
