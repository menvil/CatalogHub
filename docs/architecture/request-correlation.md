# Request and audit correlation

`AssignRequestId` resolves one request ID at the start of every HTTP request. A syntactically valid `X-Request-ID` is preserved for upstream correlation; invalid or oversized values are replaced by a generated UUID.

The middleware places the value in the response header and in Laravel's shared log context for the lifetime of the request. `AuditRecorder` resolves the same request-scoped value when it writes an audit row. Shared log context is flushed after the request, so long-running workers cannot leak one request's ID into another.

Jobs do not infer an HTTP request ID. A job that needs correlation must receive an explicitly validated correlation ID in its payload and add it to its own log context.
