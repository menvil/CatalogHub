---
screen_id: CA-011
context: central-admin
purpose: Browse and locate canonical Brands in Central Catalog.
roles: authorized Central Admin catalog user
route: /admin/central/brands
viewports: desktop=1440x1000;mobile=390x844
fixture: brands-list-v1
regions: central-shell;header-breadcrumbs;page-heading;compact-filters;brands-table;row-actions;pagination
actions: add-brand;view-detail;edit-brand;search;filter-status;sort;paginate;clear-filters
states: default;empty;filtered-empty
permissions: catalog.brands.manage
responsive: Desktop keeps Search and Status compact and aligned above the four-column table; mobile stacks the heading action and filters and contains table overflow inside the surface.
out_of_scope: activate;archive;restore;delete;media;translations;audit
reference_version: v1
---

# CA-011 — Brands List

## Contract

The list uses the full-width Central Admin shell with breadcrumbs in the shell header, a compact Search/Status filter row, and a focused four-column table. Each row shows the canonical Brand name with its slug, lifecycle status, relative updated time, and explicit same-tab View and Edit actions. View opens `central.brands.show`; Edit remains available. Search covers name and slug, the status filter covers all three lifecycle values, and sorting and pagination remain database-backed. Add Brand is available in the page heading and in the database-empty state. A filtered-empty state instead offers Clear filters. No whole-row link, lifecycle mutation, delete, or bulk action is exposed.

Access requires the dedicated `catalog.brands.manage` permission. Product management does not authorize Brand management. Status labels communicate state in text as well as color.

## Visual reference

The `CA-011` desktop and mobile entries in `docs/ui/visual-references.json` use the deterministic `brands-list-v1` fixture.
