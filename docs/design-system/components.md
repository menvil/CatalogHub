# Shared administrative components

The Phase 0.9 library is presentation-only. Pages and query services prepare values, URLs, permissions, rows, and pagination state before rendering a component. Components must not query Eloquent models, infer authorization, persist uploads, or execute business mutations.

## Selection rules

- Prefer native controls and the existing Filament/Laravel primitive when it already satisfies the contract.
- Use `ui.*` for context-neutral controls and data display; use `admin.*` for administrative composition such as tables, filters, and detail layouts.
- Every form control owns an explicit, stable ID and uses the shared field wrapper for label, help, and escaped error associations.
- Table callers whitelist sort/filter keys through `TableQueryState` and pass already prepared rows. Bulk selection covers visible rows only and resets when query state changes.
- Destructive operations require the confirmation dialog and server-side authorization. Hiding an action is never an authorization decision.
- `x-admin.confirmation-modal` accepts an optional `confirmForm` ID; when present, its confirm control is a submit button associated with that explicit server form. The caller still owns the form method, CSRF token, action URL, and authorization.
- Transient feedback uses toasts; persistent or actionable failures use alerts or retry blocks. Retry is always an explicit user action.

## Gallery and visual references

The protected gallery remains at `/admin/central/component-gallery`. Local/test captures use `/dev/component-gallery?mode=components&section=...` with the fixed `admin-components-v1` fixture:

- `actions` covers button variants, icon/loading/disabled states, row and bulk actions, and progress states.
- `forms` covers fields, validation, disabled/read-only state, and form state.
- `tables` covers query controls, filters, prepared rows, selection, the generic `actions` cell type backed by `x-admin.row-actions`, and pagination. Action URLs retain the shared safe-URL and destructive-confirmation contracts.
- `indicators` covers status, translation, projection, quality, identifier, timestamp, and reference display.
- `layout` covers cards, tabs, sections, two-column detail composition, and sticky actions.
- `feedback` covers success/warning/error, empty, validation, loading, and retry states.
- `overlays` covers modal, confirmation, destructive confirmation, and drawer patterns.
- `advanced` covers existing localized-field, unit/value, attribute-value, media, diff, import/stepper, change-request, and conflict-review compositions.

The authenticated route renders all of these groups as one catalog. The inventory
below records every reusable `admin.*` component reviewed by this cleanup:

| Existing component | Gallery representation | Action |
| --- | --- | --- |
| action buttons, bulk actions, row actions | Buttons & Actions / review cards | Reused |
| table toolbar, filter bar, active filters, data table, pagination | Tables | Reused |
| status, translation, projection, quality badges | Status / Data Indicators | Reused |
| breadcrumbs, page header, card, tabs, detail layout | Header / Layout | Reused |
| empty state, flash/feedback patterns | Feedback / shell | Reused; flash region remains shell-driven |
| modal, confirmation modal, drawer | Overlays | Reused |
| localized field editor, unit/value input, attribute value editor | Higher-level components | Reused |
| media picker, diff viewer, stepper/import progress | Higher-level components | Reused |
| change request and conflict review cards | Higher-level components | Reused |
| sidebar, topbar, site context switcher | Z-002/Z-004 shell references | Kept out of the gallery because their production context is the shell itself |

Focused desktop and mobile references are stored under `tests/Visual/baselines/admin-components-*.png`; the complete authenticated catalog uses the `z-010__catalog__*` full-page references. Updating a reference requires a production build, fixed-viewport capture, manual image inspection, and an updated checked-in SHA-256. Tests compare captures but never update references.
