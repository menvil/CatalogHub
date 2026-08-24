---
screen_id: CA-014
context: central-admin
purpose: Manage a Brand's one global primary logo.
roles: authorized Central Admin catalog user
route: /admin/central/brands/{brand}/media (GET); /admin/central/brands/{brand}/media/logo (POST, DELETE)
viewports: desktop=1440x1000;mobile=390x844
fixture: brand-media-v1
regions: central-shell;breadcrumbs;page-header;brand-tabs;logo-card;logo-preview;upload-form;confirmation-modal;flash-feedback
actions: upload-logo;replace-logo;remove-logo;confirm;cancel
states: no-logo;logo-ready;logo-processing;logo-variant-failed;upload-error
permissions: catalog.products.manage
responsive: Preview uses object-contain and metadata/action controls wrap without page-level horizontal overflow at 390px.
out_of_scope: svg;multiple-roles;localized-media;site-media;asset-browser;orphan-purge;media-library-redesign
reference_version: v1
---

# CA-014 — Brand Media / Logo

CA-014 Phase 6 v1 manages one global primary Brand logo. It provides no-logo, logo-ready, logo-processing/fallback and upload-error states. A Brand manager can upload or replace a JPEG, PNG or WebP logo, or detach it with confirmation. Removal retains the asset in Media Library. The responsive preview uses `object-contain` on a neutral checkerboard background and metadata wraps on mobile.

The richer design target (multiple roles, dark/light logos, wordmarks, hero/OG media, localized/site overrides, asset browser and completeness tooling) is deferred and is not this executable v1 contract.

Routes: `GET central.brands.media`, `POST central.brands.media.logo.store`, `DELETE central.brands.media.logo.destroy`.
