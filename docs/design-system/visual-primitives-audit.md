# Existing Visual Primitives Audit

Baseline: Phase 0.5, P00-035. This audit records the existing presentation layer before token migration; it does not authorize a legacy restyle.

## Inventory

| Primitive | Existing source | Finding | Foundation treatment |
| --- | --- | --- | --- |
| Colors | `resources/css/app.css`, admin Blade classes | Raw palette and semantic names lived in one entry point; admin bundles could not consume them consistently. | Palette values move to `tokens/palette.css`; semantic aliases move to `tokens/colors.css`. |
| Typography | Tailwind defaults and repeated `text-*` utilities | The sans stack existed, but no named type roles existed. | Display, heading, title, body, label, caption, and code roles are defined once. |
| Spacing | Admin aliases plus arbitrary utilities in legacy views | Page, card, section, and field values were useful but local to one bundle. | Preserve those values as compatibility aliases backed by foundation tokens. |
| Geometry | Repeated rounded cards, badges, shadows, modal widths | Useful patterns existed without a shared ownership boundary. | Control, card, pill, modal, elevation, and structural dimensions are named. |
| Controls | `resources/views/components/admin` | Shared admin controls already centralize markup. | Keep components shared; consume semantic tokens incrementally. |
| Status badges | `x-admin.status-badge` | Status classes were duplicated across consumers but component semantics were sound. | Keep the component and map variants to semantic status tokens. |
| Icons | Blade Heroicons / Filament Heroicon | One installed icon source already covers foundation needs. | Standardize new foundation code on the `x-ui.icon` wrapper. |
| Responsive behavior | Tailwind defaults and per-view choices | No fixed screenshot viewport contract existed. | Fix four reference widths and explicit density behavior. |

## Baseline observations

- The baseline production frontend build completed successfully.
- Existing admin components are the migration boundary; they are not mass-restyled in this phase.
- Raw values and arbitrary geometry outside new foundation sources remain legacy migration candidates.
- Central Admin, Site Admin, and Public Site keep separate entry points and layout ownership.
- The existing `/dev/ui-kit` and admin visual smoke fixture remain compatibility tools; the protected foundation gallery is the canonical Phase 0.5 reference.

## Ownership

- Raw palette: `resources/css/tokens/palette.css`.
- Semantic colors: `resources/css/tokens/colors.css`.
- Type roles: `resources/css/tokens/typography.css`.
- Spacing, radii, elevations, and dimensions: `resources/css/tokens/geometry.css`.
- Breakpoints: `resources/css/tokens/responsive.css`.
- Shared import boundary: `resources/css/foundation.css`.

Future changes must update the contract test, documentation, and approved gallery reference in the same reviewed change.
