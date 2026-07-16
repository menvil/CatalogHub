# Permissions Audit

Audit date: 2026-07-16  
Source of truth: `config/cataloghub_permissions.php`, resource guards, policies, and protected custom routes.

## Role Matrix

| Area | Super Admin | Central Admin | Catalog Editor | Site Admin | Translator | Moderator | Public guest |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Central products/brands | Manage | Manage | Manage | No | No | No | No |
| Categories | Manage | Manage | Manage | No | No | No | No |
| Category schema/units | Manage | Manage | No | No | No | No | No |
| Imports/normalization | Manage | Manage | No | No | No | No | No |
| Media | Manage | Manage | Manage | No central media | No | No | No |
| Translations | Manage | Manage | No | Manage | Manage | No | No |
| Markets/price sources | Manage | Manage | No | Site pricing configuration only | No | No | No |
| Sites/settings/content | Manage | Manage | No | Own site only | No | No | No |
| Reviews/leads | Manage | No global moderation permission | No | Own site | No | Own assigned scope | No |
| Sync/correction review | Manage | Manage/review | No | Request only | No | No | No |
| Snapshots/backups | Manage | Manage | No | No | No | No | No |
| Users/roles | Outside current admin scope | Outside current admin scope | No | No | No | No | No |

Every persisted `User` role is an internal staff role and may authenticate to the shared Filament panel shell. Resource/page permissions determine available data and actions. The application has no visitor account or viewer role; public guests cannot authenticate to admin routes.

## Findings and Fixes

- Central product, category, brand, and market resources previously relied on Filament defaults without explicit permission guards. They now require their mapped catalog/central permissions.
- Custom Central Media routes previously required authentication only. All routes in that group now require `media.manage` through named gates.
- Named gates are registered from the permission matrix, avoiding one-off route gates that drift from role configuration.
- Site Admin listing/edit access is now constrained to `users.site_id`; resolving another site returns not found.
- Snapshot history/download/generation already require `backups.manage` and download policy authorization.
- Translation routes already require `translations.manage`; reviews, leads, content, and major operational resources already apply permission and site scoping.

## Verification Rules

- Guest requests redirect to authentication and never receive admin data.
- Site-scoped roles must not enumerate or resolve another site's records.
- Catalog Editor may manage catalog/media but not markets, price sources, sync review, or backups.
- Translator may access translation surfaces only.
- Central Admin may access central catalog, integrations, sync, and backups but does not inherit a public-user context.
- Super Admin wildcard access remains explicit and should be granted sparingly.

## Remaining Risks

- The single-panel navigation model is not a separate Site Admin panel; resource guards remain security boundaries, not menu visibility alone.
- A future viewer role requires an explicit enum/config/matrix task and read-only policy tests.
- User/role management UI and organization-level tenancy are outside Phase 21.
- Infrastructure access to databases, queues, storage, logs, and deployment secrets requires a separate operational IAM review.

