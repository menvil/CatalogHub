# Admin Responsive QA Checklist

Phase 2 admin layouts are shells. This checklist verifies that the Central Admin and Site Admin foundations do not collapse across target widths.

## Target Widths

- 375px mobile.
- 768px small tablet.
- 1280px desktop.
- 1440px wide desktop.

## Expected Behavior

- Mobile 375px: sidebar navigation stacks above content and scrolls horizontally when needed.
- Small tablet 768px: cards and composed panels stack predictably without horizontal page overflow.
- Desktop 1280px: sidebar is fixed-width on the left and content uses the remaining width.
- Wide desktop 1440px+: content remains constrained by the layout max width.

## Manual Smoke Pages

- `/dev/ui-kit`
- `/dev/admin-visual-smoke`

These routes are registered only in `local` and `testing` environments.

## Checks

- Body does not show page-level horizontal overflow.
- Sidebar navigation remains reachable at 375px.
- Topbar controls wrap instead of overlapping.
- Cards stack vertically on narrow widths.
- Tables or dense previews use internal horizontal scrolling.
- Modal and drawer examples remain contained on visual smoke/dev pages.
- Central Admin and Site Admin content slots remain visible.

## Evidence

Record screenshots manually for:

- `docs/qa/screenshots/admin-central-375.png`
- `docs/qa/screenshots/admin-central-768.png`
- `docs/qa/screenshots/admin-central-1280.png`
- `docs/qa/screenshots/admin-central-1440.png`
- `docs/qa/screenshots/admin-site-375.png`
- `docs/qa/screenshots/admin-site-768.png`
- `docs/qa/screenshots/admin-site-1280.png`
- `docs/qa/screenshots/admin-site-1440.png`

Screenshots are optional in Phase 2, but this checklist is required before building domain screens on top of the layouts.
