---
screen_id: Z-010
context: central-admin
purpose: Review deterministic foundation components and their visual states.
roles: central_admin
route: /dev/component-gallery?mode=components
viewports: desktop=1440x1200;mobile=360x900
fixture: admin-components-v1
regions: page-header;section-navigation;component-fixtures
actions: select-section;open-dialog;retry
states: forms;tables;feedback;states;actions
permissions: central.view
responsive: Section navigation scrolls horizontally and fixtures stack on mobile.
out_of_scope: business-resource-editing;production-public-access
reference_version: v1
---

# Z-010 — Component Gallery

This is a protected development/testing reference surface. See `Z-010/wide` in the manifest.
