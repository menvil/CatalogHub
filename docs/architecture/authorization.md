# Foundation Authorization

Phase 0.4 uses the repository's existing config-backed authorization mechanism. `App\Enums\Permission` is the permission-name registry, `App\Enums\UserRole` contains exactly six foundation roles, and `config/cataloghub_permissions.php` is the centralized role mapping. No parallel permission package or roles table is introduced.

## Authorization layers

Panel, page, and mutation access are separate checks:

| Context | Panel | Page | Mutation |
| --- | --- | --- | --- |
| Central Admin | `central.panel.access` | `central.page.access` | `central.mutation.execute` |
| Site Admin | `site.panel.access` | `site.page.access` | `site.mutation.execute` |

`CentralPanelPolicy` and `SitePanelPolicy` own panel admission. `RequirePermission` is the enum-backed route middleware for foundation page checks. `AuthorizationService` validates typed page and mutation permissions; its mutation runner performs authorization before invoking the callback, so a forbidden mutation has no side effect.

## Foundation role matrix

| Role | Central panel | Site panel with active membership | Site panel without membership |
| --- | --- | --- | --- |
| Super Admin | Allow | Allow | Deny |
| Central Admin | Allow | Deny | Deny |
| Catalog Editor | Allow | Deny | Deny |
| Site Admin | Deny | Allow | Deny |
| Translator | Allow | Allow | Deny |
| Moderator | Deny | Allow | Deny |

Site access always requires both `site.panel.access` and an active membership in `site_user_memberships`. Membership is checked server-side against the selected site. Supplying another `site_id`, using an inactive membership, selecting an archived site, or relying only on the legacy `users.site_id` value is denied. A Central role does not imply Site membership, and Site membership does not imply Central access.

Several pre-foundation Site-owned resource classes are still registered by the Central Filament provider. Until a separately scoped class move, `SiteOwnedCentralRouteAccess` admits only their explicit route prefixes and requires both the matching Site permission and an active membership. It does not grant Central Home or unrelated Central resources.

The nullable `users.site_id` column remains only as a compatibility preference for choosing among a user's valid memberships. It is not an authorization source and cannot make an unassigned site accessible.

## Disabled users

`users.disabled_at` is the single foundation disabled-state marker. Filament login calls `User::canAccessPanel()`, which rejects disabled users. `EnsureUserIsActive` runs after the session starts and before protected panel authentication, logs out a user disabled after login, invalidates the session, and regenerates the CSRF token on the next request.

## Administrative audit

`audit_log_entries` is append-only through `AuditLogEntry` and stores actor, presentation context, optional site, action, subject, whitelisted before/after snapshots, request ID, and creation time. It has no update timestamp and exposes no activity-log UI.

Role assignment, membership changes, and user enable/disable write the mutation and audit row in one transaction. An audit write failure therefore rolls back the administrative mutation. Login and logout are framework events; their listener reports audit storage errors without blocking authentication. The recorder accepts action-specific fields only and drops password, token, and other unapproved data.

## Executable coverage

`tests/Feature/Auth/AuthorizationMatrixTest.php` covers all six roles, two independent sites, unassigned users, disabled users, query tampering, and a forbidden cross-site mutation with no database side effect. Focused policy, middleware, membership, disabled-user, and audit suites cover the underlying contracts.
