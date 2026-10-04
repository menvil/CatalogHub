---
screen_id: CA-015
context: central-admin
purpose: Translate localized Brand content with another existing translation as read-only reference.
roles: translation manager; authorized viewer without central mutation access
route: /admin/central/brands/{brand}/translations; /{locale-code}?source={source-code} (GET, POST); /approve (POST); /outdated (POST)
viewports: desktop=1440x1000;tablet=768x1024;mobile=390x844
fixture: brand-translations-v3
regions: central-shell;brand-breadcrumbs;page-header;brand-tabs;translation-direction;language-selectors;field-editor;workflow-status;approval-metadata;recent-activity;flash-feedback
actions: select-source;select-target;copy-field;copy-all;save-translation;approve-translation;mark-outdated
states: no-active-locales;missing;machine-translated;human-reviewed;approved;outdated;missing-source;outdated-source;rtl;validation-error;empty-activity;populated-activity;read-only
permissions: translations.manage; central.mutation.execute for mutations
responsive: Desktop field/source/target rows with compact right rail; tablet source/target share each field block; mobile source then target with no horizontal page overflow.
out_of_scope: machine-provider;translation-memory;glossary;persisted-field-statuses;localized-media;site-publication;translated-slug;localized-elements;locale-management
reference_version: v3
---

# CA-015 — Brand Translations, Phase 18.5

The Brand workspace uses `Brands / {Brand} / Translations`, a **Brand Translations** title, View Brand where authorized, and the existing Overview / Media / Translations tabs. Two labeled menus, **Source language → Target language**, replace the locale strip so twenty or more active languages do not add rows or horizontal scrolling. Each option shows language, code and current quality state; the selected option contains code/status without a duplicate line underneath. Native selectors use the UI kit chevron with its 12px right inset. The direction repeats above the editor. All six supported fields show target editing alongside source reference: localized name, tagline, short description, description, SEO title and SEO description. Canonical Brand name and slug appear only as compact identity metadata.

## Two different meanings of source

**Canonical source hash ≠ selected source translation shown to the translator.**

Phase 15's authoritative `TranslationSourceHashService::forBrand()` still hashes normalized canonical Brand name and slug. Stored hashes, outdated detection, approval checks, transaction locking, audit and cache eviction retain that contract. Selecting another reference locale neither changes this hash nor creates a dependency between translations.

The selected Source Locale is only a UX/reference concept: an active Locale and its existing `BrandTranslation` for this Brand. Localized content remains exclusively in `BrandTranslation`. There is no master locale, `source_locale_id`, persisted preference, ownership relationship, publication source, Site source, or cross-locale stale propagation. English is fixture content, never a domain constant.

## Source selection and reads

The target retains its locale-code route. Source is shareable through `?source=en-US`. Ordinary source candidates exclude the current target and are sorted Approved, Human reviewed, Machine translated, Outdated, Missing; ties retain the repository's default/position/code ordering. The current target is additionally available under **Switch direction**, with a URL that changes both sides rather than offering a self-source pair. All active locales remain available in both menus, including locales without a saved row. The default uses the existing `Locale.is_default` when it has a usable non-Missing translation, otherwise the first Approved, Human reviewed, Machine translated, then existing non-target row. If none exists the source is unselected with a short selection prompt.

Choosing a language already selected on the opposite side swaps Source and Target. For example, German → English becomes English → German when English is chosen as Source, and English → German becomes German → English when English is chosen as Target. Ordinary changes preserve the other side. If no source is selected, choosing the current target as Source moves Target to the first other active language; a single active locale has a disabled source menu and no self-source option. A concise hint explains swapping. Menu option URLs are built on the server and always retain distinct languages; JavaScript only follows those GET URLs. A compact disclosure supplies equivalent links when JavaScript is disabled. Explicit same-target, unknown, inactive or malformed source query values still fail server-side validation on reads and mutations. Source query survives menu navigation, successful mutations and validation return URLs. No locale preference is persisted.

The read model loads active locales and their translations in bulk, eager-loads approval actors, and queries bounded target-locale activity. Source selection reuses these collections; adding locales does not add a query per locale. GET performs no copy, create, save, hash refresh, workflow change, cache eviction or audit write. Missing target fields are empty until explicit Save creates the target row. Source is always read-only and is never mutated by target actions.

An absent source row shows `No source translation available for {language} ({code})` with a choice prompt. Empty source fields show `No source value`, without a copy button. A blank localized name in an existing source row can use the existing canonical name fallback, explicitly labeled **Canonical fallback**, without labeling it localized English content. Multiline reference text keeps paragraphs and whitespace and is HTML-escaped. Source Missing and Outdated states are visible warnings and do not impose new domain restrictions.

## Copy and editing

Each non-empty source field has a field-specific accessible **Copy source** action. **Copy all from Source** copies only non-empty reference fields, preserving targets whose source is empty. These actions update only local input/textarea values and counters. They never submit, create a row, change review state, change hashes, emit audit or call a provider. A differing non-empty target requires the lightweight confirmation `Replace current target values with source values?`; empty targets copy immediately. Saving remains explicit.

Counters and maxlength values follow current validators: name/tagline/SEO title 255, short description 1000, description 10000, SEO description 500. These are input length limits, not field completeness metrics. Target controls use the target locale's `dir` and `lang`; reference text independently uses the source locale's direction and language. Canonical fallback uses `dir=auto`. The shell remains LTR. Desktop and tablet place Source on the left and Target on the right in reading order. Column headings use the existing table treatment: muted 14px semibold text. Tablet shares source and target within each field; mobile stacks source then target and shows a compact language-code direction above the selectors.

## Workflow actions and statuses

The existing enum remains Missing / Machine translated / Human reviewed / Approved / Outdated. Current status is read-only in the header and Workflow status rail. Save is primary; Approve translation and Mark outdated are secondary actions in the rail. Approval requires the existing HumanReviewed row and current canonical hash; a disabled approval has concise associated help. Mark outdated retains text and clears approval attribution through the existing Action.

The former full Review state dropdown is removed. The existing contract intentionally allows MachineTranslated/HumanReviewed editing, so unapproved rows retain only these legal **Save as** choices. Missing and Outdated are never editable dropdown options, and Approved is granted only by its explicit action. Missing/outdated targets default to saving HumanReviewed. An unchanged approved Save preserves approval; changed approved content or canonical context returns to HumanReviewed and clears attribution, as before. A supported MachineTranslated state does not imply machine translation functionality.

Save still trims strings and normalizes blank optional values, resolves identity/hash/actor server-side, locks the canonical Brand and exact target row in the transaction, and audits meaningful changes. True no-ops do not touch timestamps, evict cache or emit audit. Cache eviction follows successful transaction completion. No Phase 15 Action or hash service was changed by this phase.

## Activity and authorization

The compact rail shows target locale, enum status, secondary row ID, current/changed canonical context and relevant workflow help. Approval attribution remains visible for approved rows. Recent activity stays newest-first, deterministic and bounded to eight existing audit events: creation/save, approval, marked outdated and changed field/review-state metadata. Empty activity has a compact message. No separate history or content snapshot is introduced.

The existing `translations.manage` read boundary remains; a catalog-only user still cannot open CA-015. A viewer authorized at this boundary without the existing `central.mutation.execute` capability sees read-only target/reference content, statuses and activity without Copy/Save/Approve/Outdated controls. Mutation routes enforce both capabilities. No permission name or role was added. Overview/Media/View Brand remain guarded by existing Brand permissions.

## Visual acceptance and intentional differences

Persisted `brand-translations-v3` uses the existing deterministic Zotac Brand: English Approved → German Outdated with realistic English/German content in all six fields; French has no target row. Desktop references cover outdated, missing and approved targets (including an outdated reference warning), tablet and mobile cover German editing, and the RTL reference covers English → Arabic. Full-page tablet/mobile references additionally cover all fields and the rail.

The workspace inherits the original system typography from the Central Admin layout. It has no Instrument Sans override or extra font preload. The Playwright references are captured and compared by the same immutable Linux/amd64 screenshot renderer declared in `tools/visual/environment.json`, locally and in CI. Browser version, browser dependencies and installed system fonts belong to that image; viewport, DPR, locale and timezone remain fixed by the existing test config. Tests wait for fonts to settle and assert that the workspace inherits the shell's font family. Product typography is never changed to reconcile macOS/Linux screenshots; existing diff thresholds remain unchanged. See [visual testing](../../testing/visual-tests.md) for capture and comparison commands.

Converged from the original prototype: explicit source/target direction, per-field side-by-side reference, compact locale status selection, field/all source copy, workflow status, real translation activity and professional form density. Source and Target menus replace the prototype's locale strip to support long language lists; browser coverage checks twenty active locales at desktop, tablet and mobile with unchanged selector-panel height, independent selection, swaps and no writes. Intentional differences: no speculative field-completeness donut or counts, localized hero/footer/support fields, Add Language, AI/machine translation, Translation Memory, glossary, media, publication/Site/market states or cross-locale dependency model.
