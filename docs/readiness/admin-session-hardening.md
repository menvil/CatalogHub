# Admin Session Hardening

CatalogHub's Central and Site Admin panels use Laravel's `web` session. Production deployments must apply the following controls to that shared session:

| Control | Production value | Reason |
| --- | --- | --- |
| Transport | `SESSION_SECURE_COOKIE=true` | Never send the session cookie over plain HTTP. |
| Script access | `SESSION_HTTP_ONLY=true` | Prevent browser JavaScript from reading the cookie. |
| Cross-site behavior | `SESSION_SAME_SITE=lax` | Protect state-changing requests while preserving normal top-level navigation. |
| Idle lifetime | `SESSION_LIFETIME=60` | Bound unattended admin sessions without forcing an impractical short timeout. |
| Close behavior | `SESSION_EXPIRE_ON_CLOSE=false` | Keep the documented idle timeout as the predictable rule. |
| Serialization | JSON (fixed in `config/session.php`) | Do not deserialize arbitrary PHP objects from session storage. |
| Partitioning | `SESSION_PARTITIONED_COOKIE=false` | CatalogHub does not embed admin sessions cross-site. |

`SESSION_ENCRYPT=false` is an explicit decision for server-side database/Redis sessions: the cookie contains only the signed session identifier, secrets must not be placed in session state, and storage access remains restricted. A deployment may enable encryption after validating session size and key-rotation behavior.

## Operational Checks

1. Terminate TLS at a trusted proxy and configure forwarded headers correctly.
2. Inspect the login response and confirm `Secure`, `HttpOnly`, and `SameSite=Lax` on the session cookie.
3. Confirm inactive admin sessions expire after the configured period.
4. Rotate `APP_KEY` only with the documented previous-key and session-invalidation plan.
5. Invalidate active sessions after credential compromise or material permission changes.

These controls do not replace authorization checks on every admin operation.

