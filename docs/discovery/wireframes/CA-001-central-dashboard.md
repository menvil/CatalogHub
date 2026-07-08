# CA-001 Central Dashboard Wireframe

Task: P00-015 - Create Central Dashboard Wireframe  
Area: UX / Wireframe  
Status: Phase 0 low-fidelity artifact

## Purpose

Show the Central Admin operational dashboard structure for catalog health,
imports, sync, translations, price sources, alerts, and quick actions.

This is not production UI, CSS, Blade, Livewire, Filament, or high-fidelity
design.

## Primary Actor

`central_admin`

## Low-Fidelity Layout

```txt
+--------------------------------------------------------------------------------+
| Top bar: Global search [products, brands, categories, imports]   Alerts  User   |
+----------------------+---------------------------------------------------------+
| Sidebar              | CA-001 Central Dashboard                                |
| - Dashboard          | Last updated: timestamp                                  |
| - Products           |                                                         |
| - Categories         | [KPI Approved Products] [KPI Drafts] [KPI Import Errors] |
| - Schemas            | [KPI Stale Sites]       [KPI Missing Translations]       |
| - Imports            |                                                         |
| - Media              | +----------------------+ +----------------------------+ |
| - Translations       | | Operational Alerts   | | Catalog Health             | |
| - Change Requests    | | - critical issues    | | - completeness             | |
| - Conflicts          | | - blocked imports    | | - schema coverage          | |
| - Price Sources      | | - sync failures      | | - duplicate risk           | |
| - Exports            | +----------------------+ +----------------------------+ |
| - Users / Roles      |                                                         |
| - Settings           | +----------------------+ +----------------------------+ |
|                      | | Recent Import        | | Site Sync Status           | |
|                      | | Activity             | | - stale sites              | |
|                      | | - batch list         | | - failed jobs              | |
|                      | | - review count       | | - rebuild queue            | |
|                      | +----------------------+ +----------------------------+ |
|                      |                                                         |
|                      | +----------------------+ +----------------------------+ |
|                      | | Price Source Monitor | | Translation Queue          | |
|                      | | - source health      | | - missing labels           | |
|                      | | - stale offers       | | - pending review           | |
|                      | +----------------------+ +----------------------------+ |
|                      |                                                         |
|                      | +----------------------+ +----------------------------+ |
|                      | | Quick Actions        | | Recent Alerts              | |
|                      | | - Start import       | | - newest first             | |
|                      | | - Review drafts      | | - severity marker          | |
|                      | | - Open requests      | |                            | |
|                      | +----------------------+ +----------------------------+ |
|                      |                                                         |
|                      | +-----------------------------------------------------+ |
|                      | | System Status: queues, storage, search, media jobs   | |
|                      | +-----------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

## Primary Actions

- Start import.
- Review normalized drafts.
- Open change requests queue.
- Inspect stale site projections.
- Retry failed sync from detail screens.
- Open price source failures.
- Open translation queue.

## States

- Empty: no imports, no alerts, no pending translations, no stale sites.
- Warning: stale site projections, missing translations, delayed price source.
- Critical: failed import, failed sync job, broken media processing, source down.
- Loading: KPI panels may load independently.
- Permission: non-authorized roles see only allowed panels or no access.

## Scope Boundaries

- Dashboard can link to Site Sync status but does not edit site-local SEO,
  homepage blocks, reviews, or leads.
- Dashboard can start Central import workflow but cannot publish directly to
  public products.
- No implementation artifacts are created in Phase 0.
