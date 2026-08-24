---
screen_id: CA-013
context: central-admin
purpose: Create a canonical Brand or edit canonical fields on an existing Brand.
roles: authorized Central Admin catalog user
route: /admin/central/brands/create (GET); /admin/central/brands (POST); /admin/central/brands/{brand}/edit (GET); /admin/central/brands/{brand} (PATCH)
viewports: desktop=1440x1000;mobile=390x844
fixture: brand-form-v1
regions: central-shell;header-breadcrumbs;page-header;status-context;general-form-card;form-fields;form-actions;validation-errors;flash-feedback
actions: cancel-to-list;cancel-to-detail;create-brand;save-changes
states: create-default;create-validation-error;edit-draft;edit-active;edit-archived;edit-validation-error;save-progress;save-success-via-redirect-flash
permissions: catalog.products.manage
responsive: The Central Admin workspace remains full width while the form card is locally capped at a readable max width; fields and actions stack without page-level horizontal overflow on mobile.
out_of_scope: status-lifecycle-control;media;translations;product-usage;activity-log;delete;audit
reference_version: v1
---

# CA-013 — Brand Create / Edit

## Contract

Create and Edit are two states of one Brand-specific form contract in the Central Admin Blade stack. The accepted presentation payload is limited to `name`, `slug`, `website_url`, and `country_code`. Business validation and normalization remain owned by `CreateCentralBrandAction` and `UpdateCentralBrandAction`; validation exceptions follow Laravel's normal redirect, error bag, and old-input flow.

Create accepts an optional slug. A blank slug is generated from the canonical name by the backend, the new Brand is always Draft, and success redirects to the new Edit route with `Brand created.` feedback so persisted normalized values are visible. If generation cannot produce a canonical ASCII slug, the Slug field asks the user to enter one manually.

Edit loads the Brand through route model binding. Its existing slug remains stable when the name changes unless the user explicitly edits the slug. Draft, Active, and Archived Brands may all update canonical fields, while lifecycle status is rendered only as a semantic `x-admin.status-badge`. Status is never a form input and `Brand updated.` feedback is delivered through the shared one-time flash region.

The shared form uses `x-ui.form.form-state`, `x-ui.form.input`, `x-ui.form.slug-input`, `x-admin.card`, and the standard button primitives. This supplies CSRF, PATCH method spoofing, submitting/double-submit state, dirty-form leave warning, adjacent field errors, `role=alert`, `aria-invalid`, and `aria-describedby`. Create Cancel returns to `central.brands.index`. Edit links the Brand breadcrumb to `central.brands.show`, and Edit Cancel returns to that Detail screen as the natural parent.

## State details

- `create-default`: empty canonical fields and the read-only explanation “New brands are created as Draft.”
- `create-validation-error`: submitted values take priority and errors remain adjacent to their fields.
- `edit-draft`, `edit-active`, `edit-archived`: persisted values with a text-and-color lifecycle badge; lifecycle is unchanged by save.
- `edit-validation-error`: submitted values take priority and validation-before-write leaves the whole Brand unchanged.
- `save-progress`: the shared form-state primitive marks submission and disables submit controls to prevent double submit.
- `save-success-via-redirect-flash`: Create redirects to Edit; Update redirects back to Edit; feedback appears once through the Central Admin flash region.

## Visual references

The `CA-013` Create and Edit desktop/mobile entries in `docs/ui/visual-references.json` use `brand-form-v1`. Validation and each lifecycle state are covered functionally rather than multiplying visual baselines.

## Explicit non-goals

There is no lifecycle mutation control on the form, media/logo management, translation UI, product usage, activity log, delete action, audit implementation, or Brand-specific permission expansion in CA-013.
