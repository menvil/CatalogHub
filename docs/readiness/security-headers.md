# Security Headers

CatalogHub adds the following baseline headers to application responses:

- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`

The middleware is registered globally so error and health responses receive the same baseline when they pass through Laravel's HTTP pipeline.

A Content Security Policy is intentionally deferred until every Filament, public asset, font, and external widget origin can be inventoried and tested. Deployments may add a report-only CSP at the proxy layer, but must not enable an untested blocking policy.

