# Design Token Contract

CatalogHub uses semantic tokens in new foundation code. Raw palette values are implementation details and must not appear in pages or components.

## Naming

- Raw palette: `--palette-*`, defined only in `resources/css/tokens/palette.css`.
- Semantic colors: `--color-foundation-*`.
- Type roles: `--text-foundation-*` and their line-height pairs.
- Geometry: `--spacing-foundation-*`, `--radius-foundation-*`, `--shadow-foundation-*`, `--width-foundation-*`, and `--height-foundation-*`.
- Responsive boundaries: `--breakpoint-foundation-*`.

Page-specific colors, raw hex values, and arbitrary geometry are forbidden in new foundation views. Choose the semantic purpose rather than a palette shade.

## Color semantics

| Token family | Purpose |
| --- | --- |
| canvas / surface / surface-muted | Application background and layered content surfaces. |
| border | Default structural separation. |
| text / text-muted | Primary copy and secondary metadata. |
| accent / accent-strong / accent-surface | Interactive emphasis and selected states. |
| focus | Keyboard focus indication. |
| success / warning / danger / info / outdated | Status text or icon with a matching `*-surface` token. |

Measured foreground/background contrast for primary text and status pairs is at least 4.5:1: text/surface 16.35, muted/surface 4.76, accent/surface 5.17, success 4.84, warning 4.51, danger 5.30, and info 5.17.

## Typography and geometry

Type roles cover display, heading, title, body, label, caption, and code. Geometry provides a deliberately small scale for compact, field, card, section, and page spacing; control, card, pill, and modal radii; three elevation levels; and structural widths.

Existing `admin-*` utilities are compatibility aliases backed by the new foundation tokens. They remain supported for established components, while new Phase 0.5 code uses `foundation-*` names. Removing aliases requires a separate migration task.

## Change process

1. Explain the semantic need; do not add a near-duplicate value for one page.
2. Add or change the raw palette only when no existing value fulfills the semantic role.
3. Update token contract tests and this document.
4. Render the deterministic gallery at every fixed viewport.
5. Review the visual difference and replace the approved checksum only for an intentional change.

Reference images are never updated automatically and visual comparison must not use random data.
