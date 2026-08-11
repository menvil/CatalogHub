# Queue runtime

CatalogHub uses Laravel's built-in queue system. It does not install Horizon in the foundation phase.

| Environment | Connection | Expected behavior |
| --- | --- | --- |
| testing | `sync` | Jobs run inline and deterministically. Tests that exercise asynchronous dispatch set the `database` connection explicitly. |
| local | `redis` by default | Start a local worker through `composer dev`, or run a worker explicitly. |
| production | `redis` | Workers are supervised by the hosting platform and use the same approved release artifact as the web process. |

Failed jobs use Laravel's `database-uuids` driver and the `failed_jobs` table. `QueueHealthCheck` reports a missing queue/failed-job dependency as an error, recent failures as a warning, and a production `sync` connection as a warning; it never claims an unavailable dependency is healthy. `QueueHealthService` is the typed operational contract: `healthy` requires a recent foundation heartbeat, `failed` means a recent failed job exists, `stale` means no recent heartbeat exists, and `unavailable` means diagnostics could not be read.

## Worker operation

For local development, `composer dev` starts `php artisan queue:listen --tries=1 --timeout=0`. For a managed runtime, the supervisor command is chosen by infrastructure, but it must be equivalent to a long-running `php artisan queue:work` process against the configured connection.

After a successful deployment, run:

```bash
php artisan queue:restart
```

This asks existing workers to exit after their current job so the process manager can start workers from the new release. The deployment runbook also requires scheduler supervision via `php artisan schedule:run` once per minute.

Use `php artisan queue:failed` to inspect failed records and `php artisan queue:retry <id>` only after the cause and job idempotency have been reviewed. Do not purge failed jobs as a first response.

Queue workers never resolve HTTP sessions or request globals. The job context carries only explicit, validated correlation and site identifiers.
