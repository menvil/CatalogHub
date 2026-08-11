# Error boundaries

Presentation code may throw framework HTTP exceptions where the framework owns the response. Application and domain code uses the typed exceptions in `App\Exceptions\Domain` when it needs a stable public HTTP outcome:

| Exception | HTTP status |
| --- | --- |
| `InvalidInputException` | 422 |
| `AuthenticationRequiredException` | 401 |
| `AuthorizationDeniedException` | 403 |
| `ResourceNotFoundException` | 404 |
| `ResourceConflictException` | 409 |

`ApplicationErrorResponse` is the single presentation boundary. It omits exception messages from HTML responses, returns a stable JSON message for API requests, and attaches the validated request ID to every response. Unexpected exceptions remain 500 responses and continue through the existing logging and request-ID path.

Do not translate arbitrary `RuntimeException` subclasses at this boundary. Introduce or use a typed domain exception at the action boundary when a caller needs one of these expected outcomes.
