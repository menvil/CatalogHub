# Queue and scheduler runbook

This runbook operates the queue and scheduler foundation introduced in Phase 0.13. It covers the runtime, explicit job context, retry policy, idempotency records, queue health, and scheduler heartbeat. It does not provide a Horizon installation, an admin monitor UI, or a business-job recovery procedure.

## Start and restart

Local development starts the web server, worker, logs, and Vite together:

```bash
composer dev
```

To run a worker explicitly, use the configured connection:

```bash
php artisan queue:work redis
```

In production, the process manager must run an equivalent long-lived `queue:work` process from the current release. After each successful deployment, request a graceful restart:

```bash
php artisan queue:restart
```

The process manager must then replace workers after their current jobs finish. Do not use `queue:listen` as the managed production worker command.

## Scheduler

For local development, run the scheduler worker when its tasks need to execute continuously:

```bash
php artisan schedule:work
```

Production runs this command every minute through its platform scheduler:

```bash
php artisan schedule:run
```

The schedule includes `operations:record-scheduler-heartbeat` once per minute. It can be invoked manually for diagnosis:

```bash
php artisan operations:record-scheduler-heartbeat
php artisan schedule:list
```

The command upserts one `scheduler` record in `operational_heartbeats`; re-running it is safe.

## Inspection and health meanings

Inspect failed Laravel jobs with:

```bash
php artisan queue:failed
```

Queue health uses the configured connection, the `failed_jobs` table, and recent `foundation-heartbeat:*` idempotency records:

| Status | Meaning | Operator response |
| --- | --- | --- |
| `healthy` | Queue diagnostics are readable and a recent foundation heartbeat exists. | Continue normal observation. |
| `failed` | At least one failed job is less than 24 hours old. | Inspect the failed payload and exception before retrying. |
| `stale` | No foundation heartbeat, or no scheduler heartbeat, arrived inside its configured threshold. | Verify worker/scheduler processes, connection settings, and deployment state. |
| `unavailable` | Queue or heartbeat diagnostics could not be read. | Treat this as an operational incident; it is not healthy. |

The thresholds are `operations.queue_heartbeat_stale_after` and `operations.scheduler_heartbeat_stale_after` (both 300 seconds by default). Inspect the typed services without changing state when Tinker is available:

```bash
php artisan tinker --execute="dump(app(\App\Services\Operations\QueueHealthService::class)->inspect());"
php artisan tinker --execute="dump(app(\App\Services\Operations\SchedulerHealthService::class)->inspect());"
```

## Failed-job recovery

1. Run `php artisan queue:failed` and identify the actual cause. Do not retry an unknown failure.
2. Check the job's retry policy in `docs/operations/job-policy.md`. Invalid input and invariant failures are non-retryable by default.
3. Confirm the job's idempotency key or durable business state makes a replay safe.
4. Retry only the reviewed failed job:

   ```bash
   php artisan queue:retry <id>
   ```

5. Re-inspect `queue:failed` and health. Escalate if failures recur.

Do not use `queue:flush` as first-line recovery. Do not assume a job was not executed merely because the worker failed after starting it; the `job_idempotency_records` unique key is the completion guarantee demonstrated by `RecordFoundationHeartbeatJob`.

## Context and policy boundaries

Jobs carry only an explicit validated correlation ID and optional site ID through `ApplyJobContext`. Workers must not resolve HTTP requests, session state, or the current site implicitly. The middleware clears log context in `finally`, including after a failed job, so one worker process cannot leak context into the next job.

All retry limits are finite. Job-specific timeout values must remain below the queue connection's `retry_after`; the standard policy is documented in `docs/operations/job-policy.md`.
