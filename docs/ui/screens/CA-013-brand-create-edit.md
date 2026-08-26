---
screen_id: CA-013
context: central-admin
purpose: Create or edit the canonical, language-neutral Brand profile.
roles: authorized Central Admin catalog user
route: /admin/central/brands/create (GET); /admin/central/brands (POST); /admin/central/brands/{brand}/edit (GET); /admin/central/brands/{brand} (PATCH)
viewports: desktop=1440x1000;mobile=390x844
fixture: brand-form-v3
regions: central-shell;header-breadcrumbs;page-header;profile-editor;general-information;online-presence;brand-identity-fields;profile-sidebar;status-context;logo-context;form-actions;validation-errors;flash-feedback
actions: cancel-to-list;cancel-to-detail;back-to-overview;manage-media;create-brand;save-changes
states: create-default;create-validation-error;edit-draft;edit-active;edit-archived;edit-logo;edit-logo-empty;edit-validation-error;save-progress;save-success-via-redirect-flash
permissions: catalog.brands.manage
responsive: Desktop uses the full Central Admin workspace with a readable main column and right sidebar. Mobile places real persisted context before the field cards, stacks all controls, and has no page-level horizontal overflow.
out_of_scope: status-lifecycle-control;logo-mutation;translations;product-usage;activity-log;delete;parent-company;tags;social-links;site-publication;quality-completeness
reference_version: v2
---

# CA-013 — Brand Create / Edit v2

## Contract

Create and Edit share one Brand Profile form and the established `FormRequest → CentralBrandInput → Action` write boundary. The accepted canonical fields are `name`, `slug`, `website_url`, `country_id`, `founded_year`, `support_url`, `contact_email`, and `primary_color`. Localized names, tagline, descriptions, and SEO remain on `BrandTranslation` and never appear in this form.

Create always produces Draft, ignores lifecycle/internal payload fields, redirects to Brands List, and flashes `Brand created.`. Edit supports Draft, Active, and Archived Brands, redirects back to Edit, and flashes `Brand updated.`. Lifecycle status is read-only; activation, archive, and restore remain on CA-012. A blank optional value clears it, an omitted optional value retains the locked current value, and validation failure preserves submitted values without partial mutation.

## Information architecture

The desktop workspace is a main-plus-sidebar composition rather than one vertically undifferentiated CRUD card:

- General Information: Name, Slug, searchable Country, and Founded year.
- Online Presence: Website, Support URL, and Contact email.
- Brand Identity: Primary color through the generic `x-ui.form.color-input`, with a synchronized native picker and visible `#RRGGBB` text.
- Brand Status sidebar: read-only lifecycle badge and concise lifecycle ownership guidance.
- Edit-only Brand Identity sidebar: current logo through `BrandLogoPresenter`, an honest empty state when absent, and `Manage Media` linking to CA-014. CA-013 has no upload, replace, or remove controls.

Create has no persisted Brand and therefore shows only the Draft status context. Edit also provides `Back to Overview`. Cancel returns Create to CA-011 and Edit to CA-012. The sticky action bar keeps Cancel and the primary submit action reachable without enabling the shared leave-warning dialog.

Country retains the Phase 9 searchable selector contract: active Countries are searchable by localized/English name and alpha codes, the selected inactive Country may be retained or cleared, and no `country_code` HTTP field exists.

## Responsive behavior

At 390×844 the header, status/identity context, General Information, Online Presence, Brand Identity, and actions form a single bounded column. The logo preview becomes shorter, the Country selector and color controls fit the viewport, long URLs/email values remain contained, and the action bar remains reachable. Browser coverage verifies both create and edit without horizontal overflow.

## Visual direction and references

The long-term design reference is `pictures/1. Central Admin/1.3. Brands/CA-013 — Brand Create:Edit.png`. It directs hierarchy, card grouping, compact paired fields, broad desktop proportions, sidebar treatment, and action prominence. It is not an executable baseline and must not be overwritten.

The Phase 10 executable references in `docs/ui/visual-references.json` use `brand-form-v3`: Create desktop/mobile and rich Edit desktop/mobile. The rich Edit fixture contains South Korea, 1938, official/support URLs, contact email, `#1428A0`, and a deterministic local logo. Screenshots were reviewed against the long-term reference before their checksums were approved.

## Intentional gaps

The target design's Parent Company is deliberately omitted. A legal/company owner is not equivalent to another Brand, and a free-text `parent_company` column would create duplicate, unstructured identity. Ownership requires a future organization/ownership relation.

Also deferred are descriptions and SEO (already localized), tags/classification, external identities/provenance/social links, site visibility/publication, category assignments, publish controls, richer media, translation workflow, validation summary, and quality/completeness indicators. Unsupported values are not shown as disabled placeholders or fake counters.
