# Health Checks

CatalogHub separates public liveness from internal diagnostics:

- `GET /health` is a coarse, secret-free application liveness response suitable for a load balancer or uptime monitor.
- `QueueHealthCheck` resolves the configured queue, reads recent failed-job state, and warns when production uses the synchronous driver. It never dispatches a job.
- `StorageHealthCheck` performs a small write/read/delete probe under `healthchecks/` and attempts cleanup on every path.
- `ExternalSourceHealthCheck` derives import and price-source status from local records and logs. It never calls an external API.

Internal health results use `ok`, `warning`, or `error`. They are intended for protected diagnostics/operations surfaces; exposing their detailed identifiers publicly requires a separate security review.

External-source rules use recent successful runs, explicit delayed/failed states, update frequency, and three consecutive failures. Manual sources are not marked stale merely because they have not run on a schedule.

