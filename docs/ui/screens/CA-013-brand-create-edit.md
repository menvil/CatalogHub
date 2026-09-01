---
screen_id: CA-013
context: central-admin
purpose: Create or edit the canonical, language-neutral Brand profile and manage its current direct Parent Company.
roles: authorized Central Admin catalog user
route: /admin/central/brands/create (GET); /admin/central/brands (POST); /admin/central/brands/{brand}/edit (GET); /admin/central/brands/{brand} (PATCH); /admin/central/brands/{brand}/ownership/organizations (GET); /admin/central/brands/{brand}/ownership (POST/DELETE); /admin/central/brands/{brand}/ownership/organization (POST)
viewports: desktop=1440x1000;mobile=390x844
fixture: brand-form-v4
regions: central-shell;header-breadcrumbs;page-header;profile-editor;general-information;parent-company;online-presence;brand-identity-fields;profile-sidebar;status-context;logo-context;form-actions;ownership-modals;validation-errors;flash-feedback
actions: cancel-to-list;cancel-to-detail;back-to-overview;manage-media;create-brand;save-changes;assign-owner;create-and-assign-organization;replace-owner;clear-owner
states: create-default;create-validation-error;edit-draft;edit-active;edit-archived;edit-logo;owner-empty;owner-populated;owner-picker;owner-create;owner-validation-error;save-progress;save-success-via-redirect-flash
permissions: catalog.brands.manage
responsive: Desktop keeps the profile and ownership cards in the main column with persisted context in the sidebar. Mobile stacks cards and ownership modal controls without page-level horizontal overflow.
out_of_scope: status-lifecycle-control;logo-mutation;translations;product-usage;activity-log;delete;organization-global-crud;organization-editing;ownership-history;multiple-owners;ownership-percentages;tags;social-links;site-publication;quality-completeness
reference_version: v3
---

# CA-013 — Brand Create / Edit v3

## Scalar Brand profile

Create and Edit retain the established `FormRequest → CentralBrandInput → Action` boundary for `name`, `slug`, `website_url`, `country_id`, `founded_year`, `support_url`, `contact_email`, and `primary_color`. Create always produces Draft and does not create a temporary Brand or accept ownership. Edit works for Draft, Active, and Archived Brands. Lifecycle controls remain on CA-012, media mutations on CA-014, and localized copy on CA-015.

The desktop composition remains General Information, Online Presence, and Brand Identity in the main column, with read-only lifecycle and current logo context in the sidebar. Optional scalar blank values clear, omitted values retain their locked value, and validation failure cannot partially mutate the Brand.

## Ownership / Parent Company

Edit adds a separate ownership card because Parent Company is a relation, not a scalar profile field. It displays the current canonical Organization or the honest `No Parent Company assigned` state. The controls are separate HTTP mutations and never submit through `CentralBrandInput`:

- Assign/Change searches existing Organizations through the Brand-scoped JSON endpoint. Name results use the indexed 191-character Unicode case-folded prefix, verify the full normalized value for longer queries, are ordered by normalized name then ID, limited to 20, and never preload the Organization directory into HTML. Options render as `Name — Organization #ID`; exact `#ID` lookup keeps every valid same-name Organization reachable beyond the ordinary name-result cap.
- Create new Organization validates a distinct display name, creates the canonical Organization, and assigns it to the Brand in one transaction. Same normalized names are allowed and are never silently merged.
- Clear removes only the ownership row after explicit confirmation. The Organization remains available to this or other Brands.

Assigning the already-current Organization is a true no-op. Replace retains the former Organization. All route context and Organization IDs are server-validated, and ownership cannot change canonical scalar fields or Brand lifecycle.

## Validation, authorization, and audit

All profile and ownership routes require `catalog.brands.manage`; hiding buttons is not the authorization boundary. Ownership validation reopens the relevant modal with field feedback and preserves the current relation. Cancel creates and assigns nothing.

Assign/replace and clear use domain Actions that lock the Brand and ownership context, mutate inside a database transaction, and record minimized append-only Audit snapshots on the `CentralBrand` subject. Create-and-assign wraps Organization creation in that transaction, so validation or Audit failure leaves neither a relation nor an accidental Organization. No-op emits no Audit event.

Ownership is independent of Quality, translation source hash/status/approval, lifecycle, Media, and Site/public projection. It is current direct corporate context only.

## Responsive and visual contract

At 390×844, the existing sidebar-first mobile flow and profile cards remain bounded; the ownership card stacks the long Organization name and actions, while modals fit the viewport. Browser coverage executes persisted create, reload, replace, reload, clear, Organization-retention, cancel, validation-error, lifecycle-preservation, and horizontal-overflow paths.

`brand-form-v4` supplies real `Organization → CentralBrandOwnership → CentralBrand` rows. Approved references include Create and Edit at desktop/mobile, focused populated ownership at desktop/mobile, and the server-backed picker on desktop. The long-term image under `pictures/` remains design input rather than an executable baseline.

## Deferred

There is no global Organizations CRUD/detail page, Organization editing, legal registry data, translations, media, lifecycle, hierarchy, historical/multiple/percentage/beneficial ownership, fuzzy deduplication, Site-specific owner, or public projection. Phase 17 may display the authoritative relation on CA-012 as read-only context.
