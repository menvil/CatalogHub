---
screen_id: CA-013
context: central-admin
purpose: Create or edit the canonical, language-neutral Brand profile and manage its current direct Parent Company.
roles: authorized Central Admin catalog user
route: /admin/central/brands/create (GET); /admin/central/brands (POST); /admin/central/brands/{brand}/edit (GET); /admin/central/brands/{brand} (PATCH); /admin/central/brands/{brand}/ownership/organizations (GET); /admin/central/brands/{brand}/ownership (POST/DELETE); /admin/central/brands/{brand}/ownership/organization (POST)
viewports: desktop=1440x1000;compact-desktop=1024x1000;tablet=768x1024;mobile=390x844
fixture: brand-form-v5
regions: central-shell;header-breadcrumbs;page-header;profile-editor;brand-information;identity-fields;company-origin;parent-company;online-presence;visual-identity-fields;profile-sidebar;status-context;logo-context;ownership-modals;validation-errors;flash-feedback
actions: cancel-to-list;cancel-to-detail;back-to-overview;manage-media;create-brand;save-changes;assign-owner;create-and-assign-organization;replace-owner;clear-owner
states: create-default;create-validation-error;edit-draft;edit-active;edit-archived;edit-logo;owner-empty;owner-populated;owner-picker;owner-create;owner-validation-error;save-progress;save-success-via-redirect-flash
permissions: catalog.brands.manage
responsive: Wide desktop uses a three-column identity row and sticky status/logo rail. Compact desktop and tablet stack the rail around a two-column form. Mobile orders header actions, status, the one-column information form, then logo/media without page-level overflow.
out_of_scope: status-lifecycle-control;logo-mutation;translations;product-usage;activity-log;delete;organization-global-crud;organization-editing;ownership-history;multiple-owners;ownership-percentages;tags;social-links;site-publication;quality-completeness
reference_version: v4
---

# CA-013 — Brand Create / Edit v4

## Scalar Brand profile

Create and Edit retain the established `FormRequest → CentralBrandInput → Action` boundary for `name`, `slug`, `website_url`, `country_id`, `founded_year`, `support_url`, `contact_email`, and `primary_color`. Create always produces Draft and does not create a temporary Brand or accept ownership. Edit works for Draft, Active, and Archived Brands. Lifecycle controls remain on CA-012, media mutations on CA-014, and localized copy on CA-015.

Create and Edit share one `Brand information` surface. Its internal Identity, Company & origin, Online presence, and Visual identity subsections use headings and dividers instead of separate outer cards. Identity uses three columns on wide desktop, two on compact desktop/tablet, and one on mobile; URL rows use two columns where space permits. Short fields are bounded instead of stretching without purpose. Optional scalar blank values clear, omitted values retain their locked value, and field-associated server validation preserves submitted values without a separate validation-summary engine. Successful Create and Edit submissions return to CA-011 with one-time feedback; invalid Edit submissions remain on CA-013.

Cancel/Back and Create/Save are page-header actions targeting the shared form. The former oversized sticky footer is removed. Create has `Create Brand`, Cancel to CA-011, and a read-only initial Draft explanation. Edit has `Save changes`, Back to Overview, and `Update canonical information for {Brand}.` Lifecycle remains read-only here and changes only from CA-012.

## Ownership / Parent Company

Edit visually embeds Parent Company inside Company & origin, beside Founded year. Its empty state matches the height of an ordinary form control, with compact Assign/Change and overflow actions; populated long Organization names may grow and wrap safely. It displays the current canonical Organization name and ID or the honest `No parent company` state. `Assign`/`Change` is the only persistent primary ownership action; Create Organization and confirmed Clear Parent Company live in a compact contextual menu. Despite this visual integration, the controls remain separate HTTP mutations and never submit through `CentralBrandInput`:

- Assign/Change searches existing Organizations through the Brand-scoped JSON endpoint. Name results use the indexed 191-character Unicode case-folded prefix, verify the full normalized value for longer queries, are ordered by normalized name then ID, limited to 20, and never preload the Organization directory into HTML. Options render as `Name — Organization #ID`; exact `#ID` lookup keeps every valid same-name Organization reachable beyond the ordinary name-result cap.
- Create new Organization validates a distinct display name, creates the canonical Organization, and assigns it to the Brand in one transaction. Same normalized names are allowed and are never silently merged.
- Clear removes only the ownership row after explicit confirmation. The Organization remains available to this or other Brands.

Assigning the already-current Organization is a true no-op. Replace retains the former Organization. All route context and Organization IDs are server-validated, and ownership cannot change canonical scalar fields or Brand lifecycle.

## Validation, authorization, and audit

All profile and ownership routes require `catalog.brands.manage`; hiding buttons is not the authorization boundary. Ownership validation reopens the relevant modal with field feedback and preserves the current relation. Cancel creates and assigns nothing.

Assign/replace and clear use domain Actions that lock the Brand and ownership context, mutate inside a database transaction, and record minimized append-only Audit snapshots on the `CentralBrand` subject. Create-and-assign wraps Organization creation in that transaction, so validation or Audit failure leaves neither a relation nor an accidental Organization. No-op emits no Audit event.

Ownership is independent of Quality, translation source hash/status/approval, lifecycle, Media, and Site/public projection. It is current direct corporate context only.

## Status, media, and responsive contract

The desktop right rail is sticky and intentionally small: Brand status shows the current Draft/Active/Archived badge with an Overview-management note, while Brand identity contains a wordmark-safe `object-contain` logo/fallback and a `Manage media` link to CA-014. CA-013 has no uploader. Breadcrumbs retain the `Central Admin / Brands / {Brand} / Edit` context used by the Brand Overview. Below the wide breakpoint, Status moves before Brand information and Logo moves after it; at 390×844 all fields, ownership names, URLs, and validation messages wrap without page-level overflow.

`brand-form-v5` enriches the deterministic Active Apple record with United States, 1976, website/support/contact, primary color, usable canonical logo, and `Apple Inc. / Organization #181100`; Create remains clean and empty. Approved references cover Create/Edit at 1440, Edit at 1024 and 768, Create/Edit at 390, representative server validation, and the server-backed Organization picker. Manual comparison used the immutable prototype for density and hierarchy, the pre-18.3 four-card/sticky-footer editor as the regression point, and the final single-form composition as the acceptance result.

The prototype's localized descriptions/tagline/SEO, manual Categories, Tags, Sites/visibility/publication controls, Preview/Publish, source identifiers, Parent Brand/group, hero/banner, and Completion Checklist are intentional divergences. Localized copy remains CA-015, Tags remain CA-012 Classification, Categories remain derived from Products, provenance remains external identities/import concern, lifecycle remains CA-012, and media mutation remains CA-014.

## Deferred

There is no global Organizations CRUD/detail page, Organization editing, legal registry data, translations, inline media editing, lifecycle mutation, hierarchy, historical/multiple/percentage/beneficial ownership, fuzzy deduplication, Site-specific owner, or public projection. CA-012 displays the authoritative relation as read-only context.
