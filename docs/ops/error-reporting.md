# Error Reporting

Phase 21 provides a provider-neutral configuration boundary. Laravel logging remains the active fallback; no external SDK is claimed to be installed until a provider is selected and reviewed.

## Configuration

| Variable | Purpose |
| --- | --- |
| `ERROR_REPORTING_ENABLED` | Master switch; defaults to `false`. |
| `ERROR_REPORTING_DRIVER` | Adapter name; `log` is the safe current fallback. |
| `ERROR_REPORTING_DSN` | Provider endpoint/credential, injected outside git. |
| `ERROR_REPORTING_ENVIRONMENT` | Stable environment label such as `staging` or `production`. |
| `ERROR_REPORTING_SAMPLE_RATE` | Provider sampling value from `0.0` through `1.0`. |

`config/error_reporting.php` fixes `send_default_pii` to `false`. A future provider adapter must preserve this default and implement explicit scrubbing before external delivery.

## Report

- Unhandled application exceptions and failed queued jobs.
- Errors from snapshot, projection, import, media, and price-source operations after attaching their safe identifiers.
- Release/tag and environment metadata when the selected provider supports them.

## Do Not Report

- Passwords, tokens, API keys, DSNs, encrypted credential blobs, session/cookie values.
- Lead email/phone/message, review email/free text, correction evidence contents.
- Raw import payloads, full request bodies, database connection strings, or storage credentials.

## Provider Enablement Checklist

1. Select and approve a provider, retention region, access roles, and data-processing terms.
2. Install its SDK in a dedicated reviewed change and map the provider-neutral variables.
3. Configure scrubbing hooks and confirm `send_default_pii=false` remains effective.
4. Inject the DSN through the deployment secret store; never commit it.
5. Send a synthetic exception containing fake sensitive markers and confirm all markers are removed.
6. Verify worker/CLI exceptions as well as HTTP exceptions.
7. Document how to disable reporting immediately and who owns triage.

Until these steps are complete, keep `ERROR_REPORTING_ENABLED=false` and treat external error reporting as a known launch limitation rather than a completed control.

