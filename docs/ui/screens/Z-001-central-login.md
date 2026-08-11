---
screen_id: Z-001
context: central-admin
purpose: Authenticate a Central operator without revealing Site context.
roles: unauthenticated;central_admin
route: /admin/central/login
viewports: desktop=1280x900;mobile=360x800
fixture: central-login-v1
regions: identity;credential-form;submit
actions: sign-in
states: default;invalid-credentials;disabled-account
permissions: central.view after authentication
responsive: Single-column form remains readable at 360px.
out_of_scope: registration;password-reset;site-selection
reference_version: v1
---

# Z-001 — Central login

Uses a generic failure message for unknown and disabled accounts. See `Z-001/default` in the visual manifest.
