---
screen_id: Z-007
context: system
purpose: Present safe administrative and public failure states with no secrets.
roles: unauthenticated;authenticated
route: /__foundation-error/500
viewports: desktop=1280x900;mobile=360x800
fixture: error-response-v1
regions: safe-identity;status-message;return-action;request-id
actions: return-to-safe-route
states: 403;404;500;503
permissions: response-context-policy
responsive: Error content remains readable and actions remain reachable at 360px.
out_of_scope: stack-traces;resource-existence-details;support-backend
reference_version: v1
---

# Z-007 — System errors

The manifest reference records the common safe-state fixture pending a dedicated error-page screenshot fixture. Production responses remain covered by feature tests and must not expose secrets.
