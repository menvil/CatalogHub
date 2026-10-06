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

## Brand module permissions

Canonical Brand list, detail, create/update, lifecycle, and logo assignment routes require `catalog.brands.manage`. Super Admin keeps wildcard access; Central Admin and Catalog Editor receive the permission. Translator, Site Admin, and Moderator do not. `catalog.products.manage` no longer authorizes Brand routes, and `catalog.brands.manage` does not authorize Product administration or the global Media Library (`media.manage`).

Brand translation read, Save, explicit Approve, and explicit Mark Outdated routes remain independently protected by `translations.manage`. The common translation subsystem has no separate approval permission, so the existing boundary also authorizes CA-015 approval. A Translator may use CA-015 and create attributed audit entries without canonical Brand mutation access. Brand Overview and Media tabs use `catalog.brands.manage`; the Translations tab uses `translations.manage`.

## Executable coverage

Phase 19.0 freezes the future Categories / Schema split in [ADR-0003](adr/0003-categories-schema-ownership.md#permission-freeze): CA-016…CA-018 use `catalog.categories.manage`, CA-019…CA-025 use `catalog.schema.manage`, and CA-026 uses `translations.manage`; all writes additionally require `central.mutation.execute`. Phase 19.1 closes the legacy Category resource blanket permission: CategoryAccess requires an active Central panel/page actor and owning module capability; all action writes additionally require Central mutation. Legacy Category create/edit/lifecycle uses categories permission, and its schema page can be reached directly with schema permission alone. Existing role grants stay unchanged; schema-only actors do not gain Category list/create/edit. See [foundation contract](category-foundation.md) and `CategoryPermissionsTest`. No new screens are delivered.

`tests/Feature/Auth/AuthorizationMatrixTest.php` covers all six roles, two independent sites, unassigned users, disabled users, query tampering, and a forbidden cross-site mutation with no database side effect. Focused policy, middleware, membership, disabled-user, and audit suites cover the underlying contracts.

Global definition and assignment actions reuse CategoryAccess: active actor, Central panel/page admission and `catalog.schema.manage`; writes additionally require `central.mutation.execute`. Category-only, Translator, Site-only, Moderator, disabled and guest actors fail closed; role grants remain unchanged. No UI-only authorization or new screen is introduced. See [the global foundation](attribute-globalization-foundation.md).

## Phase 19.3 final consumers

Facet/comparison backend actions, global options/display rules and schema export use the same schema admission; writes require Central mutation. Specs, Product updates and draft approval/publish retain Product module capability plus Central mutation and share the permanent membership row mutex. Existing temporary resources route to these server-side actions. Translation routes retain their translation capability boundary. No deployment finalizer, legacy reconciliation command or consumer-version admission gate remains. See [the convergence contract](schema-consumer-convergence.md).
