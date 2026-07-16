# Observability Baseline

This baseline defines what operators must be able to trace for a controlled production launch. Database audit records remain the primary workflow history; application logs provide cross-cutting runtime and failure context.

## Required Context

Use stable identifiers rather than serialized models. Include only identifiers relevant to an operation:

- `job_id`, `batch_id`, or workflow log ID;
- `source_id`, `site_id`, `market_id`, and `product_id` where applicable;
- operation/status and processed/failed counts;
- duration in milliseconds for bounded work;
- exception class and a scrubbed message where the database audit model requires it.

Never log passwords, API tokens, encrypted credentials, session IDs, complete request bodies, evidence attachments, lead contact details, or raw import payloads.

## Critical Workflow Inventory

| Workflow | Current durable signal | Minimum launch check |
| --- | --- | --- |
| Imports and normalization | `import_batches`, artifacts, raw records, normalization errors | Batch status, source/batch IDs, counts, error reason, worker failure visibility. |
| Media variants | `media_variants.status`, queue failed jobs | Asset/variant IDs and failures can be correlated; missing originals appear in integrity reports. |
| Projection builds | `projection_jobs` and `projection_logs` | Site/product/category/locale, start/completion/failure, counts and error class. |
| Price source sync | `price_source_sync_logs` and source status | Source/log IDs, stage counters, last success/failure and repeated failures. |
| Snapshot generation | `catalog_snapshots`, file metadata, structured start/completion/failure logs | Snapshot UUID, actor ID, section/file counts, duration and error class. |
| Corrections and conflicts | change requests, product versions, `sync_logs` | Actor, product/site, transition/resolution, version and rebuild result. |
| Leads and reviews | lead/review records and notification failures | Record/site/product identifiers only; do not log contact fields or free text. |

## Log Levels

- `info`: explicit lifecycle start/completion for long-running or operationally important work.
- `warning`: recoverable partial failure, stale/delayed source, skipped item, or degraded dependency.
- `error`: failed operation that requires operator review or retry/rollback decision.
- `debug`: local diagnostics only; production defaults to `LOG_LEVEL=warning` unless a reviewed logging plan captures needed `info` events.

Because the production template currently uses `LOG_LEVEL=warning`, deployments that need lifecycle `info` events must route an approved channel/level combination and account for retention volume.

## Correlation and Retention

1. Search durable workflow records first, then correlate application/worker logs by IDs.
2. Keep application and worker clocks synchronized and timestamps in UTC.
3. Retain security and operational logs according to the deployment policy; the repository does not claim a universal retention period.
4. Alert on application boot failure, rising failed jobs, repeated external-source failures, projection failure bursts, and failed snapshots.
5. Verify log destinations and redaction with synthetic data before launch.

## Known Gaps

- There is no distributed tracing or global request correlation ID in Phase 21.
- Queue age/worker heartbeat needs infrastructure metrics in addition to the application queue health service.
- Import and media workflows rely primarily on durable records and failed jobs; richer duration metrics are future work.
- Public health remains intentionally coarse; internal health details are not yet exposed by a dedicated protected diagnostics screen.

