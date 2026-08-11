# Phase 0.13 checklist — Queue scheduler and operational foundation

- [x] P00-105 — Document and verify queue runtime
- [x] P00-106 — Create standard job context middleware
- [x] P00-107 — Define retry, backoff, timeout, and uniqueness policy
- [x] P00-108 — Create idempotent demonstration job
- [x] P00-109 — Add failed-job inspection and queue health contract
- [x] P00-110 — Add scheduler heartbeat and health check
- [x] P00-111 — Complete queue scheduler operations documentation

## Completion evidence

- `job_idempotency_records` has a unique key, and the demonstration job records one completion effect for repeated execution.
- Queue and scheduler health distinguish unavailable and stale diagnostics from healthy ones.
- The scheduler registers the heartbeat once per minute.
- The operator runbook documents worker restart, scheduler execution, health semantics, and failed-job recovery.
