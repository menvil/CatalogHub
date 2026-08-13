---
screen_id: CA-011
context: central-admin
purpose: Browse and locate canonical Brands in Central Catalog.
roles: authorized Central Admin catalog user
route: /admin/central/brands
viewports: desktop=1440x1000;mobile=390x844
fixture: brands-list-v1
regions: central-shell;page-header;table-toolbar;brands-table;pagination
actions: search;filter-status;sort;paginate;clear-filters
states: default;empty;filtered-empty
permissions: catalog.products.manage
responsive: Desktop shows all columns in the full-width workspace; mobile prioritizes Name and Status without page-level horizontal overflow.
out_of_scope: create;edit;detail;activate;archive;restore;delete;media;translations;audit
reference_version: v1
---

# CA-011 — Brands List

## Contract

The read-only list composes the canonical Foundation components demonstrated by the Central Component Gallery: page header, table toolbar, filter bar, active filters, data table, status badge, pagination, and distinct screen states. It shows canonical Brand name, slug, lifecycle status, country, website, and a stable absolute updated date. Search covers name and slug, the status filter covers all three lifecycle values, and sorting and pagination remain database-backed. No write, row-link, or bulk action is exposed.

The existing `catalog.products.manage` permission is reused until Brand-specific permissions are introduced in Phase 8. Website links retain their stored HTTP(S) URL and open in a new tab. Status labels communicate state in text as well as color.

## Visual reference

The `CA-011` desktop and mobile entries in `docs/ui/visual-references.json` use the deterministic `brands-list-v1` fixture.
