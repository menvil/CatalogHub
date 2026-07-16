# Deployment Runbook

Target: controlled staging/production deployment of a reviewed CatalogHub release tag. Replace paths and service-manager commands with the target platform's approved equivalents; never paste secrets into shell history or logs.

## Ownership and Preconditions

- Release owner: coordinates the window, checklist, and go/no-go decision.
- Deployment operator: executes commands and records output/timestamps.
- Rollback owner: remains available until post-deploy verification completes.
- [ ] Release commit/tag, change summary, migration review, known limitations, and rollback runbook are approved.
- [ ] CI passes `composer test`, `npm run build`, clean migrations, and `php artisan test --group=smoke`.
- [ ] Current database and media/storage backups exist and the recovery owner knows their locations.
- [ ] Queue depth, external-source activity, and active import/snapshot/projection jobs are within the deployment policy.

## Prepare the Release Artifact

Run in CI or a clean build workspace at the approved tag:

```bash
git fetch --tags origin
git checkout <approved-release-tag>
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
```

Package application code, `vendor/`, and `public/build/` according to the hosting platform. Do not package `.env`, local storage contents, test databases, node_modules, or credentials.

## Configure the Target

1. Start from `.env.production.example`; inject real values through the deployment secret store.
2. Confirm `APP_ENV=production`, `APP_DEBUG=false`, HTTPS URL, secure sessions, async queue, and private media/snapshot disks.
3. Ensure writable `storage/` and `bootstrap/cache/` for the application and worker identities.
4. Verify database, Redis, object storage, mail, and optional error-reporting endpoints without printing credentials.

Validate configuration before traffic is switched:

```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate:status
```

## Deploy Code and Database

Prefer an atomic release directory/symlink switch. If the release requires a maintenance window:

```bash
php artisan down --retry=60
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

Then restart/roll workers and PHP application processes using the platform service manager. Confirm every worker uses the new release before removing the previous release artifact.

If the migration fails, stop. Do not repeatedly run destructive commands; follow the rollback runbook and the migration's reviewed forward-fix/rollback decision.

## Storage and Scheduler

- Confirm the configured media, import, and private snapshot disks are mounted/reachable.
- Run the internal storage health service from an approved protected diagnostics command/surface.
- Confirm scheduler invocation (normally one `schedule:run` per minute) and queue worker supervision.
- Do not create a public storage link for private snapshot/import files.

## Resume Traffic

```bash
php artisan up
```

For an atomic deployment, switch traffic only after database/config checks and new process health succeed.

## Post-Deploy Verification

1. Call `GET /health`; require HTTP 200 and the expected environment.
2. Verify security headers and secure session-cookie attributes over HTTPS.
3. Open public home, listing, and product pages for the production site/locale.
4. Log in as the designated Central Admin and open catalog, imports, media, projections, price sources, sync, and snapshots.
5. Review queue health, failed jobs, storage health, external-source health, logs, and error reporting.
6. Confirm no unexpected migration, projection, price, import, or snapshot failures.
7. Run the automated smoke group only against an isolated test/staging database, never against the production database:

```bash
php artisan test --group=smoke
```

8. Record release tag, deploy time, migration result, process restarts, health evidence, verifier, and any accepted exception.

## Completion

- Keep the previous deploy artifact and backup references for the rollback window.
- Monitor errors, queue failures, source sync, public latency, and storage for the agreed observation period.
- Close the release only after the release owner confirms the production readiness checklist or explicitly records accepted risks.

