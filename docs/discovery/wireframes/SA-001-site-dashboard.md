# SA-001 Site Dashboard Wireframe

Task: P00-019 - Create Site Dashboard Wireframe  
Area: UX / Wireframe  
Status: Phase 0 low-fidelity artifact

## Purpose

Show the Site Admin dashboard for a local portal as a managed projection
storefront.

This is not production UI, theme implementation, Livewire, Filament, Blade,
projection code, or public frontend.

## Primary Actor

`site_admin`

## Low-Fidelity Layout

```txt
+--------------------------------------------------------------------------------+
| Site: Monitors Demo         Domain: monitors.example.test                        |
| Market: BG                  Locale: bg/en                 Sync: stale warnings  |
| Actions: Rebuild projections | Open products | Open leads | Open reviews         |
+----------------------+---------------------------------------------------------+
| Sidebar              | SA-001 Site Dashboard                                   |
| - Dashboard          |                                                         |
| - Products           | [Visible products] [Hidden products] [Stale products]   |
| - Overrides          | [Without offers]   [Without translations] [Pending rev.] |
| - Sync               | [New leads]        [Failed price sources]               |
| - Price Sources      |                                                         |
| - Reviews            | +----------------------+ +----------------------------+ |
| - Leads              | | Sync Status          | | Price Source Status        | |
| - Content            | | - stale products     | | - source health            | |
| - Homepage Blocks    | | - failed jobs        | | - stale offers             | |
| - Theme              | | - last rebuild       | | - failed sources           | |
| - Settings           | +----------------------+ +----------------------------+ |
|                      |                                                         |
|                      | +----------------------+ +----------------------------+ |
|                      | | Product Readiness    | | Reviews / Leads            | |
|                      | | - no offers          | | - pending reviews          | |
|                      | | - no translations    | | - new leads                | |
|                      | | - hidden/excluded    | | - spam flags               | |
|                      | +----------------------+ +----------------------------+ |
|                      |                                                         |
|                      | +-----------------------------------------------------+ |
|                      | | Warnings                                             | |
|                      | | - stale projections after Central product update     | |
|                      | | - failed price source: Merchant feed                 | |
|                      | | - products missing bg translations                   | |
|                      | +-----------------------------------------------------+ |
|                      |                                                         |
|                      | +-----------------------------------------------------+ |
|                      | | Quick Actions                                        | |
|                      | | Rebuild stale | Review products | Moderate reviews   | |
|                      | | Process leads | Edit homepage blocks | Check theme     | |
|                      | +-----------------------------------------------------+ |
+----------------------+---------------------------------------------------------+
```

## Primary Actions

- Rebuild stale projections.
- Review products without offers.
- Review products without translations.
- Moderate reviews.
- Process leads.
- Open price source failures.
- Edit homepage blocks.
- Check theme compatibility.

## Required Metrics

- site identity / domain / market / locale;
- visible products;
- hidden products;
- stale products;
- products without offers;
- products without translations;
- pending reviews;
- new leads;
- sync status;
- price source status.

## States

- Healthy: projections current, price sources healthy, queues manageable.
- Warning: stale projections, missing translations, products without offers.
- Critical: failed projection rebuild, failed price source, lead processing error.
- Empty: no products enabled, no reviews, no leads, no configured price sources.

## Scope Boundaries

- Dashboard does not edit canonical product specs, brand, category, or schema.
- Canonical issues are submitted as correction requests.
- Public site changes occur after projection rebuild.
- No production UI or implementation is created in Phase 0.
