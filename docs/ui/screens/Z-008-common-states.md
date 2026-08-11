---
screen_id: Z-008
context: central-admin
purpose: Distinguish initial empty, filtered empty, and loading screen states.
roles: central_admin
route: /dev/component-gallery?mode=components&section=states
viewports: desktop=1280x1000;mobile=360x900
fixture: admin-components-v1
regions: state-title;message;state-action;loading-rows
actions: create;clear-filters
states: empty;filtered-empty;loading
permissions: central.view
responsive: Cards stack and preserve labelled state regions on mobile.
out_of_scope: business-specific-empty-copy;remote-loading-progress
reference_version: v1
---

# Z-008 — Common states

The fixture is deterministic and differentiates create from clear-filter actions. See `Z-008/empty-loading` in the manifest.
