# Shared administrative components

The Phase 0.9 library is presentation-only. Pages and query services prepare values, URLs, permissions, rows, and pagination state before rendering a component. Components must not query Eloquent models, infer authorization, persist uploads, or execute business mutations.

## Selection rules

- Prefer native controls and the existing Filament/Laravel primitive when it already satisfies the contract.
- Use `ui.*` for context-neutral controls and data display; use `admin.*` for administrative composition such as tables, filters, and detail layouts.
- Every form control owns an explicit, stable ID and uses the shared field wrapper for label, help, and escaped error associations.
- Table callers whitelist sort/filter keys through `TableQueryState` and pass already prepared rows. Bulk selection covers visible rows only and resets when query state changes.
- Destructive operations require the confirmation dialog and server-side authorization. Hiding an action is never an authorization decision.
- Transient feedback uses toasts; persistent or actionable failures use alerts or retry blocks. Retry is always an explicit user action.

## Gallery and visual references

The protected gallery remains at `/admin/central/component-gallery`. Local/test captures use `/dev/component-gallery?mode=components&section=...` with the fixed `admin-components-v1` fixture:

- `forms` covers buttons, fields, form state, cards, tabs, and detail composition.
- `tables` covers query controls, filters, prepared rows, selection, row actions, and pagination.
- `feedback` covers status/reference display, alerts, retry, and contained modal states.

Desktop and mobile references are stored under `tests/Visual/baselines/admin-components-*.png`. Updating a reference requires a production build, fixed-viewport capture, manual image inspection, and an updated checked-in SHA-256. Tests compare captures but never update references.
