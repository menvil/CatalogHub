# Job retry, timeout, and uniqueness policy

The defaults in `config/jobs.php` apply to new foundation jobs. Existing business jobs retain their reviewed policy until a task explicitly changes them.

| Class | Attempts | Backoff | Timeout | Use |
| --- | ---: | --- | ---: | --- |
| fast | 1 | none | 30 seconds | A small local operation where retry would duplicate work. |
| external | 3 | 10s, 60s | 75 seconds | A bounded external I/O operation. |
| batch | 1 | none | 75 seconds | A resumable persisted workflow that controls retry itself. |

The timeout is below Redis `retry_after` (90 seconds by default), so a worker cannot retry a still-running job. Attempts are always finite: no job may opt into unlimited retry.

`InvalidArgumentException` and `LogicException` are non-retryable by default because retrying invalid input or a programming/state invariant cannot correct it. Network and transient storage failures belong to the explicit external policy or to the owning integration policy.

Use Laravel uniqueness only for work where a duplicate dispatch is unsafe or wasteful. The standard uniqueness window is 300 seconds; an idempotency record remains the stronger completion guarantee for effects that may be replayed after that window.
