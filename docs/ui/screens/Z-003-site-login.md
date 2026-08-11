---
screen_id: Z-003
context: site-admin
purpose: Authenticate a Site Admin user without exposing authorized sites first.
roles: unauthenticated;site_admin
route: /admin/site/login
viewports: desktop=1280x900;mobile=360x800
fixture: site-admin-login-v1
regions: identity;credential-form;submit
actions: sign-in
states: default;invalid-credentials;no-membership
permissions: site.panel.access after authentication
responsive: Single-column form remains readable at 360px.
out_of_scope: registration;password-reset;pre-auth-site-list
reference_version: v1
---

# Z-003 — Site Admin login

The post-login route is authorized server-side. See `Z-003/default` in the visual manifest.
