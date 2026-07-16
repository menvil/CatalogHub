# Rollback Runbook

Rollback is a controlled incident response, not an automatic reaction to every warning. Some database migrations cannot be safely reversed after new writes; code rollback may require a forward data fix or backup restore.

## Decision and Ownership

The release owner and rollback owner decide together unless availability/security policy requires immediate action. Roll back or isolate the release when one or more of these conditions are confirmed:

- health checks fail persistently after process/config remediation;
- public/admin critical paths return errors or corrupt data;
- security or authorization regression exposes protected data/actions;
- migrations fail or new code cannot read the resulting schema;
- queue/import/projection/price/snapshot failures grow uncontrollably;
- performance exceeds an agreed abort threshold and traffic cannot be reduced safely.

Record incident start, release tag, symptoms, first bad time, affected sites/data, decision-maker, and selected recovery path.

## Stabilize First

```bash
php artisan down --retry=60
```

Then, as applicable:

- stop or pause scheduler invocation;
- stop new queue consumption while preserving queued payloads;
- disable the failing price/import source through existing safe controls;
- prevent snapshot/projection/manual admin mutations;
- capture logs, failed-job IDs, database state, and health evidence before replacing them.

Do not delete queues, failed jobs, source artifacts, snapshots, or media as a first response.

## Choose a Recovery Path

### A. Code-only rollback

Use only when the prior release is compatible with the current database and stored payloads.

1. Switch the application symlink/artifact to the last known-good tag.
2. Restore that release's dependency and built asset artifacts.
3. Rebuild configuration/routes/views for the old release.
4. Restart PHP processes and queue workers so no worker runs mixed code.

```bash
php artisan config:clear
php artisan optimize
php artisan queue:restart
```

### B. Forward fix

Prefer a reviewed forward fix when new migrations/writes are not backward compatible but data remains valid. Keep traffic or mutation paths disabled until the fix passes targeted and smoke verification.

### C. Migration rollback

Run `php artisan migrate:rollback --step=<N> --force` only when all of the following are true:

- the exact migrations are identified and their `down()` methods were reviewed;
- no required post-deploy data would be dropped or truncated;
- the application release expected after rollback is schema-compatible;
- a current database backup exists and the database owner approves.

Never guess `N`, never use broad `migrate:reset`, and never describe a migration as reversible solely because a `down()` method exists.

### D. Database/media recovery

Use the provider-approved restore procedure and [backup restore dry-run](backup-restore-dry-run.md) evidence when data was corrupted or an irreversible migration must be undone.

- Select a recovery point and accept/document the write-loss window.
- Restore database and matching media/object storage into an isolated target first when time allows.
- Treat catalog JSONL snapshots as comparison/portable exports, not full database backups.
- Switch production only after schema, media integrity, projection, and smoke checks succeed.

## Rebuild Derived State

After code/data compatibility is restored:

- rebuild affected projections, search documents, sitemap URLs, and caches;
- verify media integrity and snapshot checksums when relevant;
- resume queue workers before scheduler/source sync, watching failures;
- enable one external source at a time if it contributed to the incident.

## Verify Before Restoring Traffic

1. `GET /health` returns 200 from every active application pool.
2. Database migration status matches the selected release.
3. Queue/storage/external-source health is accepted.
4. Public home/listing/product and designated Central Admin pages work.
5. Authorization regression tests or manual role checks cover the affected area.
6. Automated `php artisan test --group=smoke` passes against an isolated test/staging database.
7. Data samples around the incident time reconcile with the recovery decision.

```bash
php artisan up
```

## Close and Follow Up

- Record recovery commit/tag, database recovery point, commands, operator, validation evidence, and residual risk.
- Monitor errors, queue failures, latency, source sync, and data integrity through the agreed observation window.
- Write an incident note with root cause, customer/data impact, and prevention owners.
- Do not redeploy the failed release until its cause and compatibility assumptions are understood.

