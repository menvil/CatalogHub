# Admin UI Tokens

Phase 2 admin screens use Tailwind v4 theme tokens defined in `resources/css/app.css`.

## Colors

- `admin-background`: page backgrounds.
- `admin-surface`: panels, cards and table surfaces.
- `admin-surface-muted`: subtle nested surfaces.
- `admin-border`: dividers and control borders.
- `admin-text`: primary text.
- `admin-muted`: secondary text and metadata.
- `admin-primary`: primary actions and active states.
- `admin-success`: completed or healthy states.
- `admin-warning`: needs attention states.
- `admin-danger`: failed or destructive states.
- `admin-info`: neutral informational states.

Soft variants are available for primary, success, warning, danger and info status backgrounds.

## Spacing

- `admin-page`: page shell padding.
- `admin-card`: card and panel padding.
- `admin-section`: gaps between major admin sections.
- `admin-field`: gaps between form controls and compact rows.

## Radius

- `admin-card`: cards and panels.
- `admin-input`: inputs, selects and segmented controls.
- `admin-badge`: pills and badges.
- `admin-modal`: modals and large overlays.

## Shadow

- `admin-card`: persistent surfaces.
- `admin-floating`: drawers, menus and popovers.
- `admin-modal`: confirmation and blocking dialogs.

## Usage

Use token-backed Tailwind utilities in Blade and Filament-compatible views:

```blade
<section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card shadow-admin-card">
    <h2 class="text-admin-text">Import review</h2>
</section>
```

Tokens are UI primitives only. They must not introduce domain data, domain queries or production dashboard metrics.
