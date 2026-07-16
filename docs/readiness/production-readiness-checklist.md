# Production Readiness Checklist

Use this checklist before every controlled production or public demo launch. Check an item only after verifying it in the target environment, and record exceptions in the release notes.

Configuration references: [environment audit](environment-audit.md) and [production environment template](../../.env.production.example).
Session controls: [admin session hardening](admin-session-hardening.md).
Health check behavior: [health checks](health-checks.md).
Recovery validation: [backup restore dry-run](../runbooks/backup-restore-dry-run.md).
Operational signals: [observability baseline](../ops/observability-baseline.md).
Error delivery and redaction: [error reporting](../ops/error-reporting.md).
Public performance limits: [public page performance budget](../performance/public-page-budget.md).
Database query coverage: [database index audit](../performance/database-index-audit.md).
Authorization boundaries: [permissions audit](../security/permissions-audit.md).
Release procedure: [deployment runbook](../runbooks/deployment.md).

## Environment

- [ ] `APP_ENV=production` and `APP_DEBUG=false` are effective.
- [ ] `APP_URL`, database, cache, queue, session, mail, and storage settings match the target environment.
- [ ] Production secrets are stored outside the repository and differ from local/test values.
- [ ] Configuration cache builds successfully with the production environment.

## Security

- [ ] Security headers are present on public and admin web responses.
- [ ] Public forms and high-frequency endpoints return `429` after their configured limits.
- [ ] Session cookies are secure, HTTP-only, and use the approved same-site policy.
- [ ] Guest and site-scoped roles cannot access Central Admin operations.
- [ ] Snapshot downloads require authorization and cannot accept arbitrary paths.

## Database

- [ ] A current database backup exists and its retention location is known.
- [ ] `php artisan migrate --pretend` has been reviewed for the release.
- [ ] Release migrations have a documented rollback or forward-fix strategy.
- [ ] Critical query indexes match the database index audit.

## Queues and Scheduled Work

- [ ] Production uses the intended asynchronous queue connection.
- [ ] Queue workers are running with the deployed code and have been restarted.
- [ ] Failed-job count and oldest pending-job age are within the accepted range.
- [ ] Scheduler execution is configured and its latest run is visible.

## Storage and Media

- [ ] Private snapshot and media disks are reachable from the application.
- [ ] Storage read/write/delete health check succeeds.
- [ ] Media integrity check has no unexplained missing originals or variants.
- [ ] Generated snapshot files are not publicly addressable without authorization.

## Imports and External Sources

- [ ] Import smoke test passes without external network access.
- [ ] Enabled import and price sources have a recent successful run or an accepted exception.
- [ ] Production credentials are configured only for sources intended to run.
- [ ] A failing source can be disabled without blocking unrelated catalog operations.

## Projections and Public Site

- [ ] Projection smoke test creates a usable site product projection.
- [ ] Public home, listing, and product smoke tests pass.
- [ ] Public pages read projection/search data and stay within the agreed performance budget.
- [ ] Required image variants, locales, canonical URLs, and sitemap endpoints are available.

## Admin

- [ ] Admin login and key Central Admin pages pass navigation smoke tests.
- [ ] Central Admin, Site Admin, editor, translator, moderator, and viewer permissions match the audited role matrix.
- [ ] Destructive or high-impact actions require the expected authorization and confirmation.

## Sync, Corrections, and Price Sources

- [ ] Correction create, approve, reject, and apply flows pass their tests.
- [ ] Applying a correction increments the canonical version and schedules projection rebuilds.
- [ ] Open sync conflicts and stale products have been reviewed.
- [ ] Price-source health status has no unexplained repeated failures.

## Snapshots and Restore Readiness

- [ ] A recent completed catalog snapshot exists on private storage.
- [ ] Snapshot and media checksums verify successfully.
- [ ] Backup restore dry-run checklist has been completed for the target release window.
- [ ] Restore limitations and manual steps are understood; no snapshot is treated as a full database backup.

## Observability and Errors

- [ ] Application, queue, storage, and external-source health checks report an accepted state.
- [ ] Error reporting is enabled with the approved data-scrubbing policy, or its absence is recorded.
- [ ] Logs include identifiers needed to trace imports, projections, sync, prices, and snapshots.
- [ ] An owner knows where to inspect errors and operational logs during the launch.

## Deployment and Rollback

- [ ] Deployment runbook has been reviewed by the release owner.
- [ ] Rollback decision-maker and communication channel are named for the release.
- [ ] Code rollback, maintenance mode, queue suspension, and data recovery steps are available.
- [ ] Full regression checklist, build, migrations, and smoke suite pass at the release commit.
- [ ] Known limitations and accepted risks are listed in the release notes.
