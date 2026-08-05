# Z-002 — Central Admin shell

## Purpose

The Central Admin shell is the single presentation frame for Central administration. It provides identity, permission-filtered navigation, header slots, page-header structure, flash feedback, and a neutral `CA-001` dashboard. It does not expose business metrics or inactive links.

## Structure

- `nav`: items come only from `CentralNavigationRegistry`; unavailable routes are omitted.
- `header`: Central identity, truthful unavailable search/notification states, and the authenticated user menu.
- `main`: one page heading, semantic breadcrumbs, optional actions/status, escaped flash messages, and page content.
- `CA-001`: a deterministic empty foundation state with no queries for future dashboard data.

The protected production route is `/admin/central`. The `/dev/central-shell` fixture exists only in local and testing environments and uses a deterministic in-memory Central user.

## Responsive states

| State | Viewport | Expected behavior |
| --- | --- | --- |
| Default | 1280 × 900 | Persistent expanded sidebar |
| Collapsed | 1280 × 900 | Icon rail, accessible link names, active item visible |
| Mobile | 360 × 800 | Modal drawer, backdrop, background scroll lock |
| Long header | 1280 × 900 | Heading wraps without overlapping actions or content |

Desktop collapse preference is stored under `cataloghub.central.sidebar.collapsed`. Mobile drawer state is request-local and is never persisted. Its `central-sidebar-mobile-open` body class owns scroll locking without clearing locks held by other overlays. Escape closes the drawer, focus returns to its trigger, and keyboard focus remains inside while open.

## Acceptance and reference policy

`CentralShellVisualTest` launches Chrome at the fixed viewports above, validates the four approved PNG references, and exercises collapse/open/Escape/focus-return against the real bundled JavaScript. Browser `error` and `unhandledrejection` events fail the acceptance marker.

References are never updated by tests. A changed image must be captured explicitly, inspected, and approved before its checksum is changed. The gallery reference changed intentionally in Phase 0.6 because it now renders inside the final Central shell.
