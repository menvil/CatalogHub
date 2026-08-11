---
screen_id: Z-009
context: central-admin
purpose: Present truthful pending, success, and failure action outcomes.
roles: central_admin
route: /dev/component-gallery?mode=components&section=actions
viewports: desktop=1280x1000;mobile=360x900
fixture: admin-components-v1
regions: progress-status;message;timestamp;next-action
actions: start;retry;dismiss
states: idle;pending;success;failure
permissions: central.view
responsive: State cards stack without fabricating percentage progress.
out_of_scope: job-queue-progress;automatic-retry;fake-percentages
reference_version: v1
---

# Z-009 — Action progress

Pending disables duplicate start. Terminal states provide explicit next actions. See `Z-009/action-progress` in the manifest.
