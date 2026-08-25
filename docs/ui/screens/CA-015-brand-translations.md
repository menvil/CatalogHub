---
screen_id: CA-015
context: central-admin
purpose: Manage localized text content for one canonical Brand.
roles: translation manager
route: /admin/central/brands/{brand}/translations (GET); /admin/central/brands/{brand}/translations/{locale-code} (GET, POST)
viewports: desktop=1440x1000;mobile=390x844
fixture: brand-translations-v1
regions: central-shell;breadcrumbs;page-header;brand-tabs;locale-selector;locale-status;translation-form;canonical-context;approval-metadata;flash-feedback
actions: select-locale;save-translation
states: no-active-locales;missing;human-reviewed;approved;outdated
permissions: translations.manage
responsive: Locale choices scroll horizontally when needed; form fields and metadata stack without page-level overflow at 390px.
out_of_scope: source-target-editor;copy-from-source;approval-redesign;history;field-statuses;machine-provider;public-localization;fallback;localized-media;translated-slug
reference_version: v1
---

# CA-015 — Brand Translations v1

CA-015 manages the text rows in `brand_translations` for one canonical Brand. The index route chooses the default active locale first, then the first active locale by position and code. With no active locale it renders a stable empty state and creates no locale. The locale selector contains active locales only, displays each locale's row status, and changes the route to the selected locale code. Direct inactive-locale routes return not found and cannot create a translation.

Access requires the existing `translations.manage` permission. A translation specialist can arrive from Missing or Outdated Translations without Brand catalog mutation access. Overview and Media tabs and the Brand breadcrumb link appear only when `catalog.brands.manage` is also allowed. Conversely, Brand Overview and Media expose the Translations tab only to users allowed to manage translations.

The form stores Localized name (required), Tagline, Short description, Description, SEO title, SEO description, and the existing row-level `TranslationStatus`. A missing row prefills Localized name with the canonical Brand name in the UI only; nothing is persisted before Save. Blank optional strings normalize to `null`. Brand, locale, source hash, and approval actor/time come only from server context. A new or unapproved row cannot post `Approved`; the common `AllowedTranslationStatuses` policy is authoritative. Normal Save stays on the same Brand and locale, flashes `Translation saved.`, and has no leave-warning or confirmation dialog.

The selected-locale summary distinguishes a missing row from stored statuses. An approved row shows safe approver name/email and approved time; raw foreign keys are not presented. Outdated copy is deliberately neutral because the common subsystem supports explicit marking as well as source-hash detection. Translated controls use the selected locale's `ltr` or `rtl` direction without changing the Central Admin shell direction.

The canonical context card exposes only read-only Brand name and slug. Slug, lifecycle status, website, country, and media remain canonical data and are never translation form fields.

## v1 versus future v2

This executable v1 is the common translation core plus locale selection, status, metadata, and save. The richer design reference remains a future target: side-by-side Source/Target editing, Copy from Source, explicit approval redesign, Mark Outdated controls, translation history/activity, field-level review/completeness, AI or machine-translation providers, translation memory/glossary, localized media, and public localized Brand delivery are deferred.
