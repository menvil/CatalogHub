# Central Admin Information Architecture

Task: P00-002 - Map Central Admin Information Architecture  
Area: UX / IA  
Status: Phase 0 discovery artifact

## Purpose

Central Admin is the operational area for canonical catalog truth. It manages
products, category schemas, imports, translations, media, corrections, conflicts,
price source configuration, exports, and platform users.

This document is an information architecture map only. It does not define
database tables, Laravel routes, Filament resources, Livewire components, or
production UI.

## Primary Actors

- `central_admin`
- `catalog_editor`
- `data_manager`
- `import_operator`
- `translator`
- `media_manager`
- `price_manager`
- `super_admin`

## Ownership Boundaries

Central Admin may manage:

- canonical product identity;
- brands and categories;
- attribute schemas and units;
- normalized values and enum options;
- canonical media assets and assignments;
- global translations;
- import review;
- accepted corrections;
- central price source configuration;
- sync and conflict visibility.

Central Admin is not the place for:

- local homepage block editing;
- site theme selection;
- local SEO overrides;
- lead processing;
- review moderation for a single site;
- public runtime rendering.

## Sitemap

| Section | Purpose | Main screens | Primary actions | Important states |
| --- | --- | --- | --- | --- |
| Dashboard | Monitor catalog operations and queues. | CA-001 Central Dashboard | Open queue, inspect alert, start import, review sync status. | Healthy, warning, critical, empty queues, stale projections. |
| Products | Maintain canonical product records. | Product list, CA-003 Product Detail, versions, conflicts. | Create draft, edit identity, edit specs, review quality, inspect versions. | Draft, approved, deprecated, conflict, missing data. |
| Brands | Maintain canonical brand identity. | Brand list, brand detail, aliases. | Create brand, merge duplicate, manage aliases, localize brand text. | Active, duplicate candidate, deprecated. |
| Categories | Manage category tree and availability for schema work. | Category list, category detail, hierarchy view. | Create category, reorder, assign parent, mark eligible for sites. | Draft, active, hidden, schema incomplete. |
| Attribute Schemas | Define category attributes and comparison behavior. | CA-019 Category Schema Builder, schema versions. | Add section, add attribute, define enum, configure facets, approve schema. | Draft, validating, approved, incompatible change. |
| Units | Maintain canonical units and labels. | Units list, unit detail, conversion rules. | Add unit, map source unit, localize unit label, mark deprecated. | Active, unmapped, deprecated, conversion warning. |
| Imports | Run raw to normalized import review pipeline. | CA-036 Import Wizard, CA-038 Normalized Draft Review, CA-039 Mapping Rules Editor. | Create batch, upload/select source, map fields, review drafts, approve/reject. | Running, needs mapping, review required, failed, completed. |
| Media Library | Manage assets, variants, and assignments. | CA-044 Media Library, asset detail, assignment view. | Upload asset, generate variant, assign media, flag quality issue. | Processing, ready, missing variant, broken source. |
| Translations | Manage global translated labels and content. | CA-052 Translation Dashboard, translation queue. | Translate label, approve suggestion, flag missing translation. | Missing, pending review, approved, stale after schema change. |
| Change Requests | Review Site to Central correction proposals. | CA-060 Change Requests Queue, request detail. | Triage, request evidence, approve, reject, apply to central. | Draft, submitted, under review, approved, rejected, applied. |
| Conflicts | Resolve conflicts from imports, corrections, and sync. | Conflict list, conflict detail. | Compare values, choose canonical value, escalate, defer. | Open, blocked, resolved, recurring. |
| Price Sources | Configure external/manual offer sources. | CA-066 Price Sources List, source detail, offer quality. | Add source, test connection, review feed status, disable source. | Healthy, delayed, failing, disabled, stale offers. |
| Snapshots / Exports | Create audit snapshots and central exports. | Snapshot list, export detail. | Create snapshot, export catalog, inspect diff, download artifact. | Queued, ready, expired, failed. |
| Users / Roles | Manage platform-level admin users and role assignment. | Users list, role assignment, access audit. | Invite user, assign role, disable user, review access. | Active, invited, disabled, access conflict. |
| Settings | Configure Central Admin operational settings. | General settings, sync settings, import settings. | Update defaults, configure thresholds, manage feature flags. | Valid, invalid, inherited, requires restart or deployment note. |

## Navigation Model

Primary navigation:

- Dashboard
- Catalog
- Schema
- Imports
- Media
- Translations
- Corrections
- Sync / Conflicts
- Price Sources
- Exports
- Administration

Secondary navigation should remain contextual to the selected section. For
example, Product Detail may expose tabs for specs, media, translations, versions,
change requests, and site publication status.

## Cross-Section Workflows

- Imports feed Products, Media, Mapping Rules, Duplicates, and Review Queues.
- Attribute Schema changes affect Products, Imports, Facets, Comparison layouts,
  Translations, and Site projections.
- Accepted Change Requests update Products or Translations, increment versions,
  and trigger Central to Site sync.
- Price Source status affects offer documents and public price blocks, but does
  not introduce checkout or order management.

## Future Phase Dependencies

- Phase 1: technical foundation and admin authentication base.
- Phase 2: admin design system / UI kit.
- Phase 3: Central Catalog Core.
- Phase 4: Category Schema UX and backend.
- Phase 9: Import System and Import UX.
- Phase 10: Sites, Markets, Portal Admin UX.
- Phase 13: Public Demo Site projections.

## Phase 0 Boundaries

Included:

- Central Admin sections;
- section purposes;
- key screens;
- primary actions;
- important states;
- ownership boundaries.

Excluded:

- routes;
- controllers;
- migrations;
- models;
- Filament resources;
- Livewire components;
- Blade templates;
- production UI.
