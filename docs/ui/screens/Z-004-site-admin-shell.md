# Z-004 — Site Admin shell

## Purpose

The Site Admin shell is the site-aware presentation frame for authorized site administration. It keeps the selected site visible, exposes only authorized site choices and implemented navigation destinations, and provides the shared page-header and feedback primitives without introducing business modules or fake sync data.

## Structure

- `nav`: items come only from `SiteAdminNavigationRegistry`; unavailable future modules are omitted.
- `site selector`: lists active memberships only and generates explicit server-authorized `site_id` URLs.
- `context header`: renders the resolved site, primary host, market, locale, currency, and status from the immutable request context without re-querying.
- `main`: uses the shared page-header contract and the `SA-001` truthful empty dashboard.
- `sync status`: reports only `Not configured` or `Unknown`; it does not query future sync storage.

The protected production route is `/admin/site?site_id={authorized-site}`. The `/dev/site-admin-shell` fixture exists only in local and testing environments and uses deterministic in-memory models.

## Responsive states

| State | Viewport | Expected behavior |
| --- | --- | --- |
| Default | 1280 × 900 | Expanded sidebar and multi-site selector |
| Mobile | 360 × 800 | Accessible drawer with the current site still identifiable |
| One site | 1280 × 900 | Non-switching selector state |
| Multiple sites | 1280 × 900 | Explicit links for both authorized sites |

Desktop collapse preference is stored under `cataloghub.site.sidebar.collapsed`. Mobile state is not persisted. The drawer owns only the `site-sidebar-scroll-locked` body class, closes after a site selection, traps keyboard focus while open, closes on Escape, and returns focus to its trigger.

## Acceptance and reference policy

`SiteAdminShellVisualTest` launches Chrome at the fixed viewports above, validates approved PNG references, and exercises collapse, drawer, site-switch close, Escape, focus return, ARIA state, and runtime errors against the bundled JavaScript.

References are never updated by tests. A changed image must be captured explicitly, inspected, and approved before its checksum is added or changed.
