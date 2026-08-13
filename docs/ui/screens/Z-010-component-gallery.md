---
screen_id: Z-010
context: central-admin
purpose: Review deterministic foundation components and their visual states.
roles: central_admin
route: /admin/central/component-gallery
viewports: desktop=1440x1000;mobile=390x844
fixture: admin-components-v1
regions: page-header;section-navigation;actions;forms;tables;indicators;layout;feedback;overlays;higher-level-components
actions: select-section;open-dialog;retry
states: catalog;actions;forms;tables;indicators;layout;feedback;overlays;advanced
permissions: central.view
responsive: Section navigation wraps and fixtures stack without horizontal page overflow on mobile.
out_of_scope: business-resource-editing;production-public-access
reference_version: v1
---

# Z-010 — Component Gallery

The Foundation Component Gallery is the canonical visual reference for reusable
administration primitives and patterns. Its protected Central Admin route renders
the complete deterministic catalog; local/test section routes provide focused
desktop and mobile visual captures. Examples use production Blade components and
perform no persistence or destructive side effects.

The catalog groups buttons/actions, form controls, tables, status/data indicators,
layout compositions, feedback states, overlays, and the existing higher-level
localized field, unit/value, media, diff, stepper/import, and review components.
Form controls include visible, dropdown, and scrollable checkbox multi-selection
patterns plus the shared date and date-time calendar popup.
It is intentionally a compact visual reference rather than a source browser or a
second component framework.
